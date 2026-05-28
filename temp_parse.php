<?php
require __DIR__ . '/vendor/autoload.php';
$vars = Dotenv\Dotenv::parse(file_get_contents(__DIR__ . '/.env'));
echo ($vars['GEMINI_API_KEY'] ?? '<null>') . PHP_EOL;
?>
