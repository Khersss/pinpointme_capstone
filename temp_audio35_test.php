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
$wav = tempnam(sys_get_temp_dir(), 'wav');
$sampleRate = 8000;
$numSamples = $sampleRate;
$dataSize = $numSamples * 2;
$header = '';
$header .= 'RIFF';
$header .= pack('V', 36 + $dataSize);
$header .= 'WAVEfmt ';
$header .= pack('V', 16);
$header .= pack('v', 1);
$header .= pack('v', 1);
$header .= pack('V', $sampleRate);
$header .= pack('V', $sampleRate * 2);
$header .= pack('v', 2);
$header .= pack('v', 16);
$header .= 'data';
$header .= pack('V', $dataSize);
$audio = str_repeat("\0\0", $numSamples);
file_put_contents($wav, $header . $audio);
$payload = [
  'contents' => [[
    'role' => 'user',
    'parts' => [
      ['text' => 'Transcribe the audio verbatim. Return only the transcript text.'],
      ['inlineData' => ['mimeType' => 'audio/wav', 'data' => base64_encode(file_get_contents($wav))]],
    ],
  ]],
  'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 128],
];
$response = Illuminate\Support\Facades\Http::timeout(45)->acceptJson()->asJson()->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=' . urlencode($apiKey), $payload);
echo 'status=' . $response->status() . PHP_EOL;
echo $response->body() . PHP_EOL;
@unlink($wav);
?>
