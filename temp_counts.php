<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo 'conversations=' . App\Models\Conversation::count() . PHP_EOL;
echo 'participants=' . App\Models\ConversationParticipant::count() . PHP_EOL;
echo 'messages=' . App\Models\Message::count() . PHP_EOL;
?>
