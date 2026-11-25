<?php

namespace Tests\Unit;

use App\Events\GpsPositionsUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GpsPositionsUpdatedTest extends TestCase
{
    #[Test]
    public function normaliza_el_formato_del_simulador_y_emite_por_canal_privado(): void
    {
        $event = new GpsPositionsUpdated([
            [
                'vehiculo_id' => 15,
                'vehicle_id' => 'VEH-0015',
                'device_code' => 'GPS-TLP-0015',
                'placa' => '1234-ABC',
                'route_code' => '209',
                'route_name' => 'Ruta 209',
                'lat' => -16.5001,
                'lng' => -68.1301,
                'speed' => 25,
                'heading' => 90,
                'status' => 'moving',
                'progress' => 20,
                'ts' => '2026-06-22T12:00:00-04:00',
            ],
        ], '2026-06-22T12:00:01-04:00');

        $channels = $event->broadcastOn();
        $payload = $event->broadcastWith();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-gps.positions', $channels[0]->name);
        $this->assertSame('positions.updated', $event->broadcastAs());
        $this->assertSame(1, $payload['count']);
        $this->assertSame(15, $payload['positions'][0]['vehiculo_id']);
        $this->assertSame(-16.5001, $payload['positions'][0]['lat']);
        $this->assertSame(-68.1301, $payload['positions'][0]['lng']);
        $this->assertSame('2026-06-22T12:00:00-04:00', $payload['positions'][0]['ts']);
    }

    #[Test]
    public function acepta_aliases_antiguos_de_coordenadas_y_velocidad(): void
    {
        $event = new GpsPositionsUpdated([
            [
                'placa' => '5678-XYZ',
                'latitud' => -16.51,
                'longitud' => -68.14,
                'velocidad_kmh' => 18,
                'rumbo_grados' => 45,
                'timestamp' => '2026-06-22T12:05:00-04:00',
            ],
        ]);

        $position = $event->broadcastWith()['positions'][0];

        $this->assertSame(-16.51, $position['lat']);
        $this->assertSame(-68.14, $position['lng']);
        $this->assertSame(18.0, $position['speed']);
        $this->assertSame(45.0, $position['heading']);
    }
}
