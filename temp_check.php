<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "config_key:" . (config('services.gemini.key') ?? '<null>') . PHP_EOL;
echo "env_get:" . (getenv('GEMINI_API_KEY') ?: '<null>') . PHP_EOL;
echo "env_file_exists:" . (file_exists(__DIR__ . '/.env') ? 'yes' : 'no') . PHP_EOL;
?>
