<?php

namespace Tests\Unit;

use App\Services\FinanzasService;
use App\Services\ML\NormalizadorCaracteristicas;
use PHPUnit\Framework\TestCase;

class RendimientoLotesTest extends TestCase
{
    public function test_clasifica_diez_mil_deudas_en_menos_de_dos_segundos(): void
    {
        $inicio = hrtime(true);
        $altas = 0;
        for ($i = 0; $i < 10000; $i++) {
            if (FinanzasService::nivelRiesgoDeuda($i % 200) === 'alto') {
                $altas++;
            }
        }
        $segundos = (hrtime(true) - $inicio) / 1e9;
        $this->assertSame(5000, $altas);
        $this->assertLessThan(2.0, $segundos);
    }

    public function test_normaliza_diez_mil_velocidades_en_menos_de_dos_segundos(): void
    {
        $normalizador = new NormalizadorCaracteristicas();
        $normalizador->entrenar([['velocidad' => 0], ['velocidad' => 99]]);
        $suma = 0;
        $inicio = hrtime(true);
        for ($i = 0; $i < 10000; $i++) {
            $suma += $normalizador->normalizar(['velocidad' => $i % 100])['velocidad'];
        }
        $segundos = (hrtime(true) - $inicio) / 1e9;
        $this->assertEqualsWithDelta(5000, $suma, 0.0001);
        $this->assertLessThan(2.0, $segundos);
    }
}
