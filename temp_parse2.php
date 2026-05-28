<?php
require __DIR__ . '/vendor/autoload.php';
$content = file_get_contents(__DIR__ . '/.env');
$vars = Dotenv\Dotenv::parse($content);
echo 'count=' . count($vars) . PHP_EOL;
echo 'has_gemini=' . (array_key_exists('GEMINI_API_KEY', $vars) ? 'yes' : 'no') . PHP_EOL;
print_r(array_slice(array_keys($vars), -10));
?>
