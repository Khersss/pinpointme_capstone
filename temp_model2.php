<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo 'env=' . (env('GEMINI_MODEL') ?: '<null>') . PHP_EOL;
echo 'config=' . config('services.gemini.model') . PHP_EOL;
?>
