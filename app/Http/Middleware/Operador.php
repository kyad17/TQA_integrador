<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Operador
{
    public function handle(Request $request, Closure $next)
    {
        $hash = config('tqa.clave');
        if (! is_string($hash) || $hash === '' || ! hash_equals(hash('sha256', $hash), (string) $request->session()->get('operador'))) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
