<?php
require __DIR__ . "/vendor/autoload.php";
Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
echo '_ENV=' . (array_key_exists('GEMINI_API_KEY', $_ENV) ? $_ENV['GEMINI_API_KEY'] : '<missing>') . PHP_EOL;
echo '_SERVER=' . (array_key_exists('GEMINI_API_KEY', $_SERVER) ? $_SERVER['GEMINI_API_KEY'] : '<missing>') . PHP_EOL;
echo 'getenv=' . (getenv('GEMINI_API_KEY') ?: '<missing>') . PHP_EOL;
?>
