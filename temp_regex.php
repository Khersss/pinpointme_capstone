<?php
$envPath = __DIR__ . '/.env';
$contents = file_get_contents($envPath);
preg_match('/^' . preg_quote('GEMINI_API_KEY', '/') . '\s*=\s*(.*)$/m', $contents, $matches);
$value = trim($matches[1] ?? '');
if (preg_match('/^(["\'])(.*)\1$/', $value, $quoted)) {
    $value = stripcslashes($quoted[2]);
}
echo $value . PHP_EOL;
?>
