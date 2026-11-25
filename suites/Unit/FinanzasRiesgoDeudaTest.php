<?php

namespace Tests\Unit;

use App\Services\FinanzasService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FinanzasRiesgoDeudaTest extends TestCase
{
    #[Test]
    public function clasifica_el_riesgo_por_las_bandas_de_monto_definidas(): void
    {
        $this->assertSame('sin_deuda', FinanzasService::nivelRiesgoDeuda(0));
        $this->assertSame('bajo', FinanzasService::nivelRiesgoDeuda(49.99));
        $this->assertSame('medio', FinanzasService::nivelRiesgoDeuda(50));
        $this->assertSame('medio', FinanzasService::nivelRiesgoDeuda(99.99));
        $this->assertSame('alto', FinanzasService::nivelRiesgoDeuda(100));
    }

    #[Test]
    public function los_agravantes_no_sacan_el_puntaje_de_la_banda_del_monto(): void
    {
        $this->assertLessThan(15, FinanzasService::ajustarPuntajeRiesgoDeuda(40, 100));
        $this->assertGreaterThanOrEqual(20, FinanzasService::ajustarPuntajeRiesgoDeuda(50, 0));
        $this->assertLessThan(55, FinanzasService::ajustarPuntajeRiesgoDeuda(99.99, 100));
        $this->assertGreaterThanOrEqual(55, FinanzasService::ajustarPuntajeRiesgoDeuda(100, 0));
    }

    #[Test]
    public function genera_una_explicacion_y_accion_para_cada_deuda(): void
    {
        $resumen = FinanzasService::resumenRiesgoDeuda(30, 2, 2);

        $this->assertSame('bajo', $resumen['nivel']);
        $this->assertNotEmpty($resumen['criterio']);
        $this->assertNotEmpty($resumen['observacion']);
        $this->assertNotEmpty($resumen['accion']);
        $this->assertNotEmpty($resumen['factores']);
    }
}
