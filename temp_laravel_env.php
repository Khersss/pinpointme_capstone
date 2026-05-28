<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo 'env_helper:' . (env('GEMINI_API_KEY') ?: '<null>') . PHP_EOL;
echo 'config_services:' . var_export(config('services.gemini'), true) . PHP_EOL;
?>
