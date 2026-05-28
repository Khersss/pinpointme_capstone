<?php
require __DIR__ . "/vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$loaded = $dotenv->load();
var_export($loaded);
echo PHP_EOL;
echo 'GEMINI=' . (getenv('GEMINI_API_KEY') ?: '<null>') . PHP_EOL;
echo 'APP_NAME=' . (getenv('APP_NAME') ?: '<null>') . PHP_EOL;
?>
