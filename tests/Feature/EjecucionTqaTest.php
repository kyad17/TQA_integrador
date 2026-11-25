<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EjecucionTqaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['mail.default' => 'array']);
        config(['tqa.clave' => 'hash-de-prueba']);
        $this->withSession(['operador' => hash('sha256', 'hash-de-prueba')]);
    }

    public function test_centro_y_descargas_requieren_sesion_propia(): void
    {
        $this->flushSession();
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/reportes/00000000-0000-4000-8000-000000000001/pdf')->assertRedirect(route('login'));
        $this->post('/ejecutar')->assertRedirect(route('login'));
    }

    public function test_el_operador_ingresa_consulta_el_catalogo_y_cierra_sesion(): void
    {
        $this->flushSession();
        config(['tqa.clave' => password_hash('clave-temporal', PASSWORD_DEFAULT)]);
        $this->get('/acceso')->assertOk()->assertSee('Acceso a pruebas');
        $this->post('/acceso', ['clave' => 'incorrecta'])->assertSessionHasErrors('clave');
        $this->post('/acceso', ['clave' => 'clave-temporal'])->assertRedirect(route('tqa.index'));
        $this->get('/')->assertOk()->assertSee('CentroTqa', false);
        $this->get('/', ['X-Inertia' => 'true', 'X-Inertia-Version' => app(\Inertia\Middleware::class)->version(request())])->assertOk()
            ->assertJsonPath('component', 'CentroTqa')->assertJsonCount(7, 'props.suites');
        $this->post('/salir')->assertRedirect(route('login'));
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_ejecuta_smoke_y_devuelve_los_cuatro_casos_al_centro(): void
    {
        $this->post('/ejecutar', [
            'suite' => 'smoke',
            'correo' => 'qa@example.test',
        ])->assertRedirect(route('tqa.index'));

        $ultima = session('tqa_ultima_ejecucion');
        $this->assertSame('finalizada', $ultima['estado']);
        $this->assertSame(4, $ultima['resumen']['pass']);
        $this->assertCount(4, $ultima['casos']);
        $html = Storage::disk('local')->get("tqa-reportes/{$ultima['id']}/reporte.html");
        $this->assertStringContainsString('4 de 4 casos seleccionados', $html);
        $this->assertStringContainsString($ultima['casos'][0]['comprobacion'], $html);
        $this->assertStringContainsString($ultima['casos'][0]['clave'], $html);
        foreach (['pdf', 'html'] as $formato) {
            $this->get($ultima['reportes'][$formato])->assertDownload();
        }
    }

    public function test_ejecuta_solo_el_metodo_marcado_aunque_otro_tenga_el_mismo_nombre(): void
    {
        $clave = 'Tests\Unit\MlpClasificadorTest::lanza_excepcion_con_dataset_vacio';
        $this->post('/ejecutar', [
            'suite' => 'regresion', 'correo' => 'qa@example.test', 'casos' => [$clave],
        ])->assertRedirect(route('tqa.index'));
        $ultima = session('tqa_ultima_ejecucion');
        $this->assertSame('finalizada', $ultima['estado']);
        $this->assertSame([$clave], array_column($ultima['casos'], 'clave'));
        $this->assertNotEmpty($ultima['casos'][0]['comprobacion']);
        $this->assertGreaterThan(0, $ultima['casos'][0]['aserciones']);
    }

    public function test_rechaza_seleccion_vacia_o_ajena_a_la_suite(): void
    {
        Process::fake();
        foreach ([[], ['Tests\Unit\MlpClasificadorTest::lanza_excepcion_con_dataset_vacio']] as $casos) {
            $this->post('/ejecutar', [
                'suite' => 'smoke', 'correo' => 'qa@example.test', 'casos' => $casos,
            ])->assertSessionHasErrors($casos === [] ? 'casos' : 'casos.0');
        }
        Process::assertNothingRan();
    }

    public function test_muestra_el_mensaje_de_un_error_del_reporte_junit(): void
    {
        $clase = 'Tests\Feature\AccesoTransporteTest';
        $metodo = 'test_invitado_necesita_iniciar_sesion';
        Process::fake(function ($proceso) use ($clase, $metodo) {
            $argumento = collect($proceso->command)->first(fn ($arg) => str_starts_with($arg, '--log-junit='));
            file_put_contents(substr($argumento, strlen('--log-junit=')), "<testsuite><testcase class=\"$clase\" name=\"$metodo\"><error message=\"Fallo controlado del caso\"/></testcase></testsuite>");

            return Process::result(exitCode: 2);
        });
        $this->post('/ejecutar', [
            'suite' => 'smoke', 'correo' => 'qa@example.test', 'casos' => [$clase.'::'.$metodo],
        ])->assertRedirect(route('tqa.index'));
        $caso = session('tqa_ultima_ejecucion.casos.0');
        $this->assertSame('FAIL', $caso['estado']);
        $this->assertSame('Fallo controlado del caso', $caso['error']);
        $id = session('tqa_ultima_ejecucion.id');
        $this->assertStringContainsString('Fallo controlado del caso', Storage::disk('local')->get("tqa-reportes/$id/reporte.html"));
    }

    public function test_informe_identifica_los_casos_sin_resultado_si_falta_el_xml(): void
    {
        Process::fake(fn () => Process::result(exitCode: 2));
        $this->post('/ejecutar', [
            'suite' => 'smoke', 'correo' => 'qa@example.test',
        ])->assertRedirect(route('tqa.index'));
        $ultima = session('tqa_ultima_ejecucion');
        $this->assertCount(4, $ultima['sin_resultado']);
        $html = Storage::disk('local')->get("tqa-reportes/{$ultima['id']}/reporte.html");
        $this->assertStringContainsString('0 de 4 casos seleccionados', $html);
        $this->assertStringContainsString('Incidencia de ejecución', $html);
        $this->assertStringContainsString($ultima['sin_resultado'][0]['descripcion'], $html);
    }
}
