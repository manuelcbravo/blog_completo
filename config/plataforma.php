<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contraseña inicial del super administrador
    |--------------------------------------------------------------------------
    |
    | La usa el DatabaseSeeder al crear el usuario principal. En producción
    | es obligatoria; en desarrollo local cae a un valor por defecto débil.
    |
    */

    'admin_seed_password' => env('ADMIN_SEED_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Cuenta de demostración
    |--------------------------------------------------------------------------
    |
    | Las mismas variables que alimentan el bloque de acceso de la página de
    | proyectos, para que la credencial que se enseña en la vitrina y la que
    | siembra el seeder no puedan separarse.
    |
    | Si `DEMO_ACCESO_CLAVE` está vacía, el seeder no crea la cuenta. Es el
    | mismo interruptor que apaga el bloque en la página pública.
    |
    */

    'demo' => [
        'email' => env('DEMO_ACCESO_USUARIO'),
        'password' => env('DEMO_ACCESO_CLAVE'),
        'nombre' => env('DEMO_ACCESO_NOMBRE', 'Cuenta de demostración'),
    ],

];
