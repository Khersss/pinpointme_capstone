<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$controller = app(App\Http\Controllers\OpenAIController::class);
$ref = new ReflectionClass($controller);
$apiKeyMethod = $ref->getMethod('apiKey');
$apiKeyMethod->setAccessible(true);
$apiKey = $apiKeyMethod->invoke($controller);
$response = Illuminate\Support\Facades\Http::timeout(30)->acceptJson()->asJson()->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=' . urlencode($apiKey), [
    'contents' => [[ 'role' => 'user', 'parts' => [[ 'text' => 'Say OK only.' ]] ]],
    'generationConfig' => [ 'temperature' => 0, 'maxOutputTokens' => 20 ],
]);
echo 'status=' . $response->status() . PHP_EOL;
echo $response->body() . PHP_EOL;
?>
