# TQA Integrador

Centro de pruebas independiente para el Sindicato de Transporte La Paz. Abre **Iniciar TQA.cmd** en esta carpeta. La primera vez, elige y confirma una clave de operador. Visita **http://127.0.0.1:8010** y usa esa clave. Cierra el servidor con Ctrl+C. Para cambiar la clave, detén el servidor y ejecuta `php artisan tqa:clave`.

## Ejecutar pruebas

Elige un tipo y una suite. El centro marca inicialmente todos sus casos. Puedes desmarcar casos, revisar lo que comprueban y ejecutar la selección. Al finalizar, despliega el detalle de cada caso o descarga el PDF y el HTML. El informe identifica las comprobaciones aprobadas, fallidas, omitidas y sin resultado.

## Conexión con transporte

Configura en el `.env` de **esta carpeta** la ruta absoluta `TQA_TARGET_PATH` al proyecto de transporte. Se necesita su código y su instalación de Composer con dependencias de desarrollo. El portal abre por sí solo. Al ejecutar, crea un proceso PHPUnit en la carpeta de transporte y utiliza su configuración de pruebas, con SQLite en memoria. Para estas suites no hace falta iniciar el servidor web del sindicato.

Las pruebas propias de acceso, finanzas, GPS y rendimiento están en `suites/`. La regresión también reutiliza las pruebas de dominio de `tests/Unit` del proyecto evaluado. `config/tqa_casos.php` identifica cada método y describe su comprobación. Los informes se guardan en `storage/app/private/tqa-reportes/<id>/`, incluidos los archivos migrados de ejecuciones anteriores.

## Correo

Con `MAIL_MAILER=log` el envío se registra localmente. Para entregar ambos adjuntos en un buzón configura `MAIL_MAILER=smtp`, servidor, puerto, usuario, contraseña y remitente en el `.env` de TQA. Reinicia el portal después de cambiar la configuración.

## Instalación en otro equipo

Las versiones fijadas en composer.lock requieren PHP 8.4 o posterior, las extensiones de Laravel y PHPUnit, Composer y Node.js con npm para compilar los recursos. Ejecuta en la carpeta de TQA:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm ci
npm run build
```

Ajusta la ruta del proyecto evaluado en `.env` y abre **Iniciar TQA.cmd**. Para comprobar el propio portal, usa `php vendor/phpunit/phpunit/phpunit`. Sus pruebas verifican selección, acceso, descargas e incidencias; las pruebas que ejecutan casos reales requieren el objetivo configurado.
