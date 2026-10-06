<?php

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = config('database.connections.' . config('database.default'));
foreach ([
    'DB_HOST' => $database['host'] ?? 'localhost',
    'DB_PORT' => $database['port'] ?? 3306,
    'DB_USER' => $database['username'] ?? 'root',
    'DB_PASS' => $database['password'] ?? '',
    'DB_NAME' => $database['database'] ?? '',
] as $name => $value) {
    putenv($name . '=' . $value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

require dirname(__DIR__) . '/legacy/panel_flow.php';
