<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $informe['suite'] }} · Resultados TQA</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #edf2f5; color: #193043; font: 15px/1.5 system-ui, sans-serif; }
        main { max-width: 1050px; margin: 28px auto; padding: 0 16px; }
        header, section { border: 1px solid #cddae1; border-radius: 12px; padding: 22px; margin-bottom: 14px; background: white; }
        header { background: #153348; color: white; }
        header p { margin: 4px 0; }
        h1, h2 { line-height: 1.2; margin: 8px 0; }
        h2 { font-size: 20px; }
        .datos, .indicadores { display: grid; grid-template-columns: repeat(auto-fit,minmax(140px,1fr)); gap: 9px; }
        dt, small { color: #466073; font-size: 13px; }
        dd { margin: 0; font-weight: 600; overflow-wrap: anywhere; }
        .indicadores div { padding: 10px 14px; border-radius: 6px; background: #edf4f7; }
        .indicadores dd { font-size: 22px; }
        .estado { display: inline-block; padding: 2px 7px; border-radius: 4px; font-weight: 700; font-size: 13px; }
        .PASS { color: #125638; background: #dff1e6; }
        .FAIL, .alerta { color: #832a31; background: #fde4e4; }
        .SKIPPED { color: #705215; background: #faf1d5; }
        .alerta { padding: 10px; }
        .tabla { overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 10px; text-align: left; vertical-align: top; border-bottom: 1px solid #cfdee5; }
        th { background: #edf4f7; }
        code, pre { overflow-wrap: anywhere; white-space: pre-wrap; font-size: 12px; }
        details { margin-top: 5px; }
        summary { cursor: pointer; color: #235d78; }
        footer { overflow-wrap: anywhere; color: #466073; font-size: 12px; }
        @media print { body, main { background: white; margin: 0; } section { border: 0; } tr { break-inside: avoid; } }
    </style>
</head>
<body><main>
    <header>
        <p>Centro TQA · Sindicato de Transporte La Paz</p>
        <h1>{{ $informe['suite'] }}</h1>
        <p>{{ $informe['tipo'] }} · {{ $informe['descripcion_suite'] }}</p>
    </header>
    <section>
        <h2>{{ $informe['estado'] === 'finalizada' ? 'Ejecución finalizada' : 'Ejecución con incidencias' }}</h2>
        <p>{{ $informe['resumen']['total'] }} de {{ $informe['seleccionados'] }} casos seleccionados tienen resultado registrado en PHPUnit.</p>
        <dl class="indicadores">
            @foreach (['pass' => 'Aprobados', 'fail' => 'Fallidos', 'skipped' => 'Omitidos'] as $clave => $etiqueta)
                <div><dt>{{ $etiqueta }}</dt><dd>{{ $informe['resumen'][$clave] }}</dd></div>
            @endforeach
            <div><dt>Aprobación*</dt><dd>{{ $informe['resumen']['total'] ? $informe['resumen']['exito'].'%' : 'Sin datos' }}</dd></div>
            <div><dt>Aserciones</dt><dd>{{ array_sum(array_column($informe['casos'], 'aserciones')) }}</dd></div>
        </dl>
        <small>* Sobre los casos con resultado registrado.</small>
        <dl class="datos">
            <div><dt>Inicio (La Paz)</dt><dd>{{ $informe['inicio'] }}</dd></div>
            <div><dt>Fin (La Paz)</dt><dd>{{ $informe['fin'] }}</dd></div>
            <div><dt>Duración</dt><dd>{{ $informe['duracion'] }}</dd></div>
            <div><dt>Destinatario</dt><dd>{{ $informe['correo'] }}</dd></div>
        </dl>
        @if ($informe['error_proceso'])
            <p class="alerta"><strong>Incidencia de ejecución.</strong> {{ $informe['error_proceso'] }}</p>
        @endif
        @if ($informe['sin_resultado'])
            <h3>Casos seleccionados sin resultado</h3>
            <ul>@foreach ($informe['sin_resultado'] as $pendiente)<li>{{ $pendiente['descripcion'] }}</li>@endforeach</ul>
        @endif
    </section>
    @if ($informe['casos'])
        <section>
            <h2>Resultados por caso</h2>
            <div class="tabla"><table>
                <thead><tr><th>ID</th><th>Comprobación y evidencia</th><th>Estado</th><th>Duración</th><th>Aserciones</th></tr></thead>
                <tbody>
                    @foreach ($informe['casos'] as $caso)
                        <tr>
                            <td>{{ $caso['id'] }}</td>
                            <td>
                                {{ $caso['comprobacion'] ?? $caso['nombre'] }}
                                @if ($caso['estado'] === 'FAIL')
                                    <p><strong>Error registrado por PHPUnit</strong></p>
                                    <pre>{{ $caso['error'] ?: $caso['observacion'] }}</pre>
                                @elseif ($caso['estado'] === 'SKIPPED')
                                    <p>Motivo de omisión: {{ $caso['observacion'] }}</p>
                                @endif
                                <details><summary>Ver método de prueba</summary><code>{{ $caso['clave'] }}</code></details>
                            </td>
                            <td><span class="estado {{ $caso['estado'] }}">{{ $caso['estado'] }}</span></td>
                            <td>{{ $caso['duracion'] }}</td><td>{{ $caso['aserciones'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    @endif
    <footer>Entorno de pruebas: testing · Fuente: PHPUnit (JUnit XML) · Ejecución {{ $informe['id'] }}</footer>
</main></body>
</html>
