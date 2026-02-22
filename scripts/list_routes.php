<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$routes = app('router')->getRoutes();
foreach ($routes as $r) {
    if (str_contains($r->getName(), 'kas-bank')) {
        echo $r->getName() . PHP_EOL;
    }
}
