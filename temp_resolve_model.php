<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$controller = app(App\Http\Controllers\OpenAIController::class);
$ref = new ReflectionClass($controller);
$method = $ref->getMethod('geminiModel');
$method->setAccessible(true);
echo 'resolved=' . $method->invoke($controller) . PHP_EOL;
?>
