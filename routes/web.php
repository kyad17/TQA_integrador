<?php

use App\Http\Controllers\CentroTqaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/acceso', 'acceso')->name('login');
Route::post('/acceso', function (Request $request) {
    $datos = $request->validate(['clave' => ['required', 'string', 'max:512']]);
    $hash = config('tqa.clave');
    if (! $hash || ! password_verify($datos['clave'], $hash)) {
        return back()->withErrors(['clave' => 'No se pudo iniciar la sesiÃ³n. Verifica tu clave de TQA.']);
    }
    $request->session()->regenerate();
    $request->session()->put('operador', hash('sha256', $hash));

    return redirect()->route('tqa.index');
})->middleware('throttle:8,1');

Route::middleware('operador')->group(function () {
    Route::get('/', [CentroTqaController::class, 'index'])->name('tqa.index');
    Route::post('/ejecutar', [CentroTqaController::class, 'ejecutar']);
    Route::get('/reportes/{ejecucion}/{formato}', [CentroTqaController::class, 'descargar'])
        ->whereUuid('ejecucion')->whereIn('formato', ['pdf', 'html']);
    Route::post('/salir', function (Request $request) {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return $request->header('X-Inertia') ? \Inertia\Inertia::location(route('login')) : redirect()->route('login');
    });
});
