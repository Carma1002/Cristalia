<?php
/**
 * Ejemplo de configuración. El instalador (install.php) crea app/config.php automáticamente.
 * Si necesita crearlo a mano, copie este archivo como app/config.php y complete los datos.
 */
return [
    'db' => [
        'host' => 'sql000.infinityfree.com',   // Host MySQL que muestra InfinityFree
        'port' => 3306,
        'name' => 'if0_00000000_cristalia',    // Nombre de la base de datos
        'user' => 'if0_00000000',              // Usuario MySQL
        'pass' => 'su_contraseña',             // Contraseña de la cuenta de hosting
    ],
    'timezone' => 'America/Caracas',
    'debug'    => false,                       // true sólo para depurar (muestra errores)
];
