<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Acceso · TQA Integrador</title>
<style>
*{box-sizing:border-box}body{min-height:100dvh;margin:0;display:grid;place-items:center;background:#10253a;color:#1b3248;font:1rem/1.5 system-ui}main{margin:1rem;background:#fbfcfd;width:min(26rem,92vw);padding:clamp(1.6rem,5vw,2.8rem);border-radius:1.2rem}h1{margin:.25rem 0 1.5rem;font-size:2rem}label,input,button{display:block;width:100%}input,button{padding:.8rem;margin:.4rem 0 1rem;font:inherit;border-radius:.5rem}input{border:1px solid #74889a}button{border:0;background:#164c65;color:#fff;cursor:pointer;font-weight:650}small{color:#216477;letter-spacing:.09em}p[role=alert]{color:#982637}
</style></head><body><main><small>TQA INTEGRADOR</small><h1>Acceso a pruebas</h1>
@if(!config('tqa.clave'))<p>Para crear la clave del operador, abre <strong>Iniciar TQA.cmd</strong> en la carpeta de TQA.</p>@endif
<form method="post" action="{{ route('login') }}">@csrf<label for="clave">Clave de TQA</label><input id="clave" name="clave" type="password" autocomplete="current-password" required autofocus>
@error('clave')<p role="alert">{{ $message }}</p>@enderror<button>Ingresar</button></form></main></body></html>
