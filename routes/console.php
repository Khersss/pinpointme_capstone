<?php

use App\Models\Building;
use App\Models\Floor;
use App\Models\RescueRequest;
use App\Models\Room;
use App\Models\User;
use App\Notifications\EmergencyAlert;
use App\Support\VonagePhone;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('rescue:test-report
    {--user-id= : User ID to use as requester}
    {--building=GD : Building name}
    {--floor-name=Floor 1 - Ground : Floor name (include number)}
    {--room-name=Room 101 : Room name}
    {--status=pending : Rescue status}', function () {
    $userId = $this->option('user-id');
    $status = $this->option('status') ?? 'pending';

    $requester = $userId
        ? User::find($userId)
        : User::whereNotNull('emergency_contact_phone')->first();

    if (!$requester) {
        $this->error('No user found with an emergency contact phone. Provide --user-id.');
        return 1;
    }

    $normalizedPhone = VonagePhone::normalizeToE164($requester->emergency_contact_phone);
    if (!$normalizedPhone) {
        $this->error('Requester emergency contact phone is not a valid PH mobile number.');
        return 1;
    }

    $buildingName = $this->option('building') ?? 'GD';
    $floorName = $this->option('floor-name') ?? 'Floor 1 - Ground';
    $roomName = $this->option('room-name') ?? 'Room 101';

    $building = Building::firstOrCreate(['name' => $buildingName]);
    $floor = Floor::firstOrCreate([
        'building_id' => $building->id,
        'floor_name' => $floorName,
    ]);
    $room = Room::firstOrCreate([
        'floor_id' => $floor->id,
        'room_name' => $roomName,
    ]);

    do {
        $rescueCode = strtoupper(Str::random(8));
    } while (RescueRequest::where('rescue_code', $rescueCode)->exists());

    $fullName = trim(($requester->first_name ?? '') . ' ' . ($requester->last_name ?? ''));
    $displayName = $fullName !== '' ? $fullName : ($requester->username ?? 'Unknown');

    $rescueRequest = RescueRequest::create([
        'rescue_code' => $rescueCode,
        'user_id' => $requester->id,
        'status' => $status,
        'building_id' => $building->id,
        'floor_id' => $floor->id,
        'room_id' => $room->id,
        'description' => "Test report for {$displayName}.",
        'firstName' => $requester->first_name,
        'lastName' => $requester->last_name,
    ]);

    Notification::route('vonage', $normalizedPhone)
        ->notify(new EmergencyAlert($requester, $rescueRequest));

    $this->info('Test rescue report created and SMS triggered.');
    $this->line('Rescue Code: ' . $rescueRequest->rescue_code);
    $this->line('Location: ' . $rescueRequest->getFullLocation());
    $this->line('Status: ' . $rescueRequest->status);
    $this->line('Recipient: ' . $normalizedPhone);
})->purpose('Create a test rescue request and send an emergency SMS');
