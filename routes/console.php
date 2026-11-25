<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('tqa:clave', function () {
    $clave = $this->secret('Elige tu clave personal para TQA (8 caracteres como mínimo)');
    if (! is_string($clave) || strlen($clave) < 8 || $clave !== $this->secret('Repite la clave')) {
        $this->error('La clave debe tener al menos 8 caracteres y coincidir en ambos campos.');
        return 1;
    }
    $path = storage_path('app/private/operador.hash');
    File::ensureDirectoryExists(dirname($path));
    File::replace($path, password_hash($clave, PASSWORD_DEFAULT));
    $this->info('Clave del operador guardada.');
    return 0;
})->purpose('Establece o cambia la clave local de acceso a TQA');
