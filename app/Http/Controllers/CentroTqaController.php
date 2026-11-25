<?php

namespace App\Http\Controllers;

use App\Services\Tqa\InformePdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CentroTqaController
{
    private const SUITES = [
        'smoke' => ['tipo' => 'Funcional', 'nombre' => 'Smoke de administración', 'descripcion' => 'Acceso al panel de transporte, permisos por rol e inicio de sesión.', 'archivos' => ['Feature/AccesoTransporteTest']],
        'finanzas' => ['tipo' => 'Funcional', 'nombre' => 'Finanzas y deudas', 'descripcion' => 'Bandas de riesgo, puntaje y recomendaciones de deuda.', 'archivos' => ['Unit/FinanzasRiesgoDeudaTest']],
        'integracion' => ['tipo' => 'Integración', 'nombre' => 'Telemetría GPS', 'descripcion' => 'Formato de las posiciones, compatibilidad y canal de difusión.', 'archivos' => ['Unit/GpsPositionsUpdatedTest']],
        'motor' => ['tipo' => 'Integración', 'nombre' => 'Motor inteligente', 'descripcion' => 'Análisis de vehículos, resumen del panel y tratamiento de recorridos.', 'archivos' => ['Unit/MotorInteligenteServiceTest']],
        'estabilidad' => ['tipo' => 'No funcional', 'nombre' => 'Robustez de datos', 'descripcion' => 'Datos vacíos, valores atípicos, rangos constantes y restauración del normalizador.', 'archivos' => ['Unit/NormalizadorCaracteristicasTest']],
        'rendimiento' => ['tipo' => 'No funcional', 'nombre' => 'Rendimiento por lotes', 'descripcion' => 'Tiempo de procesamiento de 10.000 registros sintéticos de deuda y telemetría.', 'archivos' => ['Unit/RendimientoLotesTest']],
        'regresion' => ['tipo' => 'Regresión', 'nombre' => 'Regresión del sistema', 'descripcion' => 'Cobertura conjunta de administración, GPS, finanzas y modelos de análisis.', 'archivos' => ['Feature/AccesoTransporteTest', 'Unit/FinanzasRiesgoDeudaTest', 'Unit/GpsPositionsUpdatedTest', 'Unit/MotorInteligenteServiceTest', 'Unit/NormalizadorCaracteristicasTest', 'Unit/MlpClasificadorTest', 'Unit/EntrenadorModeloPerfilTest']],
    ];

    public function index(Request $request)
    {

        return \Inertia\Inertia::render('CentroTqa', [
            'suites' => collect(self::SUITES)->map(fn ($suite, $id) => [
                'id' => $id, 'tipo' => $suite['tipo'], 'nombre' => $suite['nombre'], 'descripcion' => $suite['descripcion'],
                'casos' => array_values($this->catalogo($suite)),
            ])->values(),
            'ultima' => $request->session()->get('tqa_ultima_ejecucion'),
        ]);
    }

    public function ejecutar(Request $request): RedirectResponse
    {

        $datos = $request->validate([
            'correo' => ['required', 'email', 'max:255'],
            'suite' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(self::SUITES))],
        ]);

        $suite = self::SUITES[$datos['suite']];

        $catalogo = $this->catalogo($suite);
        $seleccion = $request->validate([
            'casos' => ['sometimes', 'required', 'array', 'list', 'min:1', 'max:'.count($catalogo)],
            'casos.*' => ['bail', 'required', 'string', 'distinct:strict', \Illuminate\Validation\Rule::in(array_keys($catalogo))],
        ], [
            'casos.required' => 'Marca al menos una prueba.',
            'casos.*.in' => 'Uno de los casos seleccionados no pertenece a esta suite.',
        ]);
        $marcados = array_intersect_key($catalogo, array_flip($seleccion['casos'] ?? array_keys($catalogo)));
        $ejecucion = $this->ejecutarSuite($suite, $datos['correo'], $marcados);
        session(['tqa_ultima_ejecucion' => $ejecucion]);

        $mensaje = $ejecucion['estado'] === 'finalizada'
            ? 'La ejecución TQA finalizó. Se generaron los reportes.'
            : 'La ejecución TQA finalizó con incidencias. Revisa el detalle y los reportes.';

        if ($ejecucion['correo_estado'] === 'fallido') {
            $mensaje .= ' No se pudo enviar el correo configurado.';
        }

        return redirect()->route('tqa.index')->with('success', $mensaje);
    }

    public function descargar(string $ejecucion, string $formato)
    {
        abort_unless(preg_match('/^[a-z0-9-]+$/', $ejecucion) === 1, 404);
        abort_unless(in_array($formato, ['pdf', 'html'], true), 404);

        $path = "tqa-reportes/{$ejecucion}/reporte.{$formato}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download(
            $path,
            "reporte-tqa-{$ejecucion}.{$formato}",
            ['Content-Type' => $formato === 'pdf' ? 'application/pdf' : 'text/html; charset=UTF-8'],
        );
    }

    private function catalogo(array $suite): array
    {
        $casos = [];
        foreach ($suite['archivos'] as $archivo) {
            $clase = 'Tests\\'.str_replace('/', '\\', $archivo);
            foreach (config('tqa_casos')[$archivo] as $metodo => $descripcion) {
                $id = $clase.'::'.$metodo;
                $casos[$id] = ['id' => $id, 'descripcion' => $descripcion];
            }
        }

        return $casos;
    }

    private function ejecutarSuite(array $suite, string $correo, array $marcados): array
    {
        $id = (string) Str::uuid();
        $inicio = now('America/La_Paz');
        $directorio = "tqa-reportes/{$id}";
        $junitPath = Storage::disk('local')->path("{$directorio}/resultado.xml");
        Storage::disk('local')->makeDirectory($directorio);

        $incidencia = null;
        try {
            $objetivo = realpath((string) config('tqa.objetivo'));
            if (! config('tqa.objetivo') || ! $objetivo || ! is_file($objetivo.'/phpunit.xml') || ! is_file($objetivo.'/vendor/phpunit/phpunit/phpunit')) {
                throw new \RuntimeException('Revisa TQA_TARGET_PATH y las dependencias de desarrollo del sistema de transporte.');
            }
            $php = (new \Symfony\Component\Process\PhpExecutableFinder())->find(false);
            $archivos = [];
            foreach ($suite['archivos'] as $archivo) {
                $local = base_path("suites/{$archivo}.php");
                $ruta = is_file($local) ? $local : "{$objetivo}/tests/{$archivo}.php";
                $clase = 'Tests\\'.str_replace('/', '\\', $archivo).'::';
                if (array_filter(array_keys($marcados), fn ($id) => str_starts_with($id, $clase))) {
                    $archivos[] = $ruta;
                }
            }
            $patrones = array_map(fn ($id) => preg_quote($id, '~'), array_keys($marcados));
            $filtro = '~^(?:'.implode('|', $patrones).')$~';
            $resultado = Process::path($objetivo)
                ->env(['APP_BASE_PATH' => $objetivo, 'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
                    'APP_CONFIG_CACHE' => dirname($junitPath).'/config-testing.php', 'DB_URL' => '', 'QUEUE_CONNECTION' => 'sync',
                    'MAIL_HOST' => false, 'MAIL_USERNAME' => false, 'MAIL_PASSWORD' => false, 'MAIL_URL' => false,
                    'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'MAIL_MAILER' => 'array', 'BROADCAST_CONNECTION' => 'null'])
                ->timeout(120)
                ->run([$php ?: 'php', $objetivo.'/vendor/phpunit/phpunit/phpunit', '--configuration', $objetivo.'/phpunit.xml', '--do-not-cache-result', '--filter', $filtro, "--log-junit={$junitPath}", ...$archivos]);

            $casos = $this->casosDesdeJunit($junitPath, $marcados);
            $infraestructura = array_diff(array_keys($marcados), array_column($casos, 'clave')) !== []
                || (! $resultado->successful() && ! in_array('FAIL', array_column($casos, 'estado'), true));
            if ($infraestructura) {
                $incidencia = Str::limit(trim($resultado->errorOutput()."\n".$resultado->output()), 1800);
            }
        } catch (\Throwable $error) {
            $incidencia = $error->getMessage();
            report($error);
            $casos = [];
            $infraestructura = true;
        }

        $fin = now('America/La_Paz');
        $resumen = $this->resumen($casos);
        $estado = $resumen['fail'] > 0 || $infraestructura ? 'fallida' : 'finalizada';
        $ejecucion = [
            'id' => $id,
            'tipo' => $suite['tipo'],
            'suite' => $suite['nombre'],
            'descripcion_suite' => $suite['descripcion'],
            'seleccionados' => count($marcados),
            'sin_resultado' => array_values(array_diff_key($marcados, array_flip(array_column($casos, 'clave')))),
            'estado' => $estado,
            'error_proceso' => $infraestructura ? ('PHPUnit no pudo completar la suite. '.($incidencia ?: 'Revisa el entorno de pruebas.')) : null,
            'inicio' => $inicio->format('d/m/Y H:i:s'),
            'fin' => $fin->format('d/m/Y H:i:s'),
            'duracion' => $inicio->diffInSeconds($fin).' s',
            'correo' => $correo,
            'resumen' => $resumen,
            'casos' => $casos,
            'correo_estado' => 'pendiente',
            'reportes' => [
                'pdf' => "/reportes/{$id}/pdf",
                'html' => "/reportes/{$id}/html",
            ],
        ];

        $pdfPath = Storage::disk('local')->path("{$directorio}/reporte.pdf");
        $htmlPath = Storage::disk('local')->path("{$directorio}/reporte.html");
        Storage::disk('local')->put("{$directorio}/reporte.pdf", (new InformePdf())->generar($ejecucion));
        Storage::disk('local')->put("{$directorio}/reporte.html", view('tqa.resumen', ['informe' => $ejecucion])->render());

        try {
            Mail::raw(
                "Adjuntamos los resultados de la ejecución TQA {$suite['nombre']} ({$inicio->format('d/m/Y H:i')}).",
                function ($mensaje) use ($correo, $suite, $inicio, $pdfPath, $htmlPath): void {
                    $mensaje->to($correo)
                        ->subject("Reporte de automatización - {$suite['nombre']} - {$inicio->format('d/m/Y H:i')}")
                        ->attach($pdfPath, ['as' => 'informe-detallado-tqa.pdf', 'mime' => 'application/pdf'])
                        ->attach($htmlPath, ['as' => 'resumen-tqa.html', 'mime' => 'text/html']);
                },
            );
            $ejecucion['correo_estado'] = in_array(config('mail.default'), ['log', 'array'], true) ? 'registrado' : 'enviado';
        } catch (\Throwable) {
            $ejecucion['correo_estado'] = 'fallido';
        }

        return $ejecucion;
    }

    private function casosDesdeJunit(string $path, array $marcados): array
    {
        if (! is_file($path)) {
            return [];
        }

        try {
            $xml = simplexml_load_file($path);
            $nodos = $xml ? $xml->xpath('//testcase') : false;
            if (! $nodos) {
                return [];
            }

            return array_map(function (\SimpleXMLElement $caso, int $indice) use ($marcados): array {
                $clase = (string) ($caso['class'] ?? str_replace('.', '\\', (string) $caso['classname']));
                $clave = $clase.'::'.(string) $caso['name'];
                $fallo = $caso->failure[0] ?? $caso->error[0] ?? null;
                $omitido = isset($caso->skipped);
                $estado = $fallo !== null ? 'FAIL' : ($omitido ? 'SKIPPED' : 'PASS');
                $detalle = match ($estado) {
                    'FAIL' => trim((string) $fallo) ?: (string) ($fallo['message'] ?? $fallo['type'] ?? 'PHPUnit registró un fallo sin mensaje.'),
                    'SKIPPED' => trim((string) $caso->skipped) ?: 'PHPUnit omitió este caso.',
                    default => 'Sin observaciones',
                };

                return [
                    'id' => sprintf('TC-%03d', $indice + 1),
                    'clave' => $clave,
                    'comprobacion' => $marcados[$clave]['descripcion'] ?? null,
                    'aserciones' => (int) $caso['assertions'],
                    'error' => $estado === 'FAIL' ? Str::limit(str_replace(base_path().DIRECTORY_SEPARATOR, '', $detalle), 4000) : null,
                    'nombre' => (string) ($caso['classname'] ?? 'Prueba').' :: '.(string) ($caso['name'] ?? 'sin nombre'),
                    'estado' => $estado,
                    'duracion' => number_format((float) ($caso['time'] ?? 0), 2).' s',
                    'observacion' => Str::limit($detalle ?: 'Sin observaciones', 240),
                ];
            }, $nodos, array_keys($nodos));
        } catch (\Throwable) {
            return [];
        }
    }

    private function resumen(array $casos): array
    {
        $totales = ['total' => count($casos), 'pass' => 0, 'fail' => 0, 'skipped' => 0];
        foreach ($casos as $caso) {
            match ($caso['estado']) {
                'PASS' => $totales['pass']++,
                'FAIL' => $totales['fail']++,
                default => $totales['skipped']++,
            };
        }
        $totales['exito'] = $totales['total'] > 0 ? round(($totales['pass'] / $totales['total']) * 100) : 0;

        return $totales;
    }

}
