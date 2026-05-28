<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userId = 1;

$start = microtime(true);
$query = App\Models\Conversation::whereHas('participants', function ($q) use ($userId) {
    $q->where('user_id', $userId);
})
->with([
    'participants.user:id,first_name,last_name,email,profile_picture',
    'rescueRequest:id,conversation_id,rescue_code,status,urgency_level,user_id,assigned_rescuer',
    'rescueRequest.requester:id,first_name,last_name,email,profile_picture',
    'rescueRequest.rescuer:id,first_name,last_name,email,profile_picture'
])
->orderBy('updated_at', 'desc');

echo 'build=' . round((microtime(true) - $start) * 1000, 2) . "ms\n";

$start = microtime(true);
$rows = $query->get();
echo 'get=' . round((microtime(true) - $start) * 1000, 2) . "ms\n";

echo 'count=' . $rows->count() . "\n";

$start = microtime(true);
$rows->transform(function ($conversation) use ($userId) {
    $participant = $conversation->participants->firstWhere('user_id', $userId);
    $conversation->unread_count = $participant?->unread_count ?? 0;
    return $conversation;
});
echo 'transform=' . round((microtime(true) - $start) * 1000, 2) . "ms\n";

$start = microtime(true);
$json = $rows->toJson();
echo 'json=' . round((microtime(true) - $start) * 1000, 2) . "ms\n";
echo 'size=' . strlen($json) . "\n";
?>
