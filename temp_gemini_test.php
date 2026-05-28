<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$key = (new ReflectionClass(App\Http\Controllers\OpenAIController::class))->getMethod('apiKey');
$key->setAccessible(true);
$controller = app(App\Http\Controllers\OpenAIController::class);
$apiKey = $key->invoke($controller);
$response = Illuminate\Support\Facades\Http::timeout(30)->acceptJson()->asJson()->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=' . urlencode($apiKey), [
    'contents' => [[ 'role' => 'user', 'parts' => [[ 'text' => 'Say OK only.' ]] ]],
    'generationConfig' => [ 'temperature' => 0, 'maxOutputTokens' => 20 ],
]);
echo 'status=' . $response->status() . PHP_EOL;
echo $response->body() . PHP_EOL;
?>
