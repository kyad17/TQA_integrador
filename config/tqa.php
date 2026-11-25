<?php

$archivo = storage_path('app/private/operador.hash');

return [
    'objetivo' => env('TQA_TARGET_PATH'),
    'clave' => is_file($archivo) ? trim(file_get_contents($archivo)) : null,
];
