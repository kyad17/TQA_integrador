<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Role;
use Tests\TestCase;

class AccesoTransporteTest extends TestCase
{
    private function entrarComo(string $rol): void
    {
        $cuenta = new Cuenta(['username' => 'demo-tqa', 'tipo' => $rol]);
        $cuenta->setRelation('rol', new Role(['nombre' => $rol]));
        $cuenta->setRelation('usuario', null);
        $this->actingAs($cuenta);
    }

    public function test_invitado_necesita_iniciar_sesion(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_afiliado_regresa_a_su_panel(): void
    {
        $this->entrarComo('afiliado');
        $this->get('/admin')->assertRedirect(route('dashboard'));
    }

    public function test_login_conduce_a_la_portada(): void
    {
        $this->get('/login')->assertRedirect(route('home'));
    }

    public function test_login_exige_usuario_y_clave(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['login', 'password']);
    }
}
