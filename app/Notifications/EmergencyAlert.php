<?php

namespace App\Notifications;

use App\Models\RescueRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class EmergencyAlert extends Notification
{
    use Queueable;

    public function __construct(
        protected User $user,
        protected RescueRequest $report
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['vonage'];
    }

    public function toVonage($notifiable): VonageMessage
    {
        $fullName = trim(($this->user->first_name ?? '') . ' ' . ($this->user->last_name ?? ''));
        $displayName = $fullName !== '' ? $fullName : ($this->user->username ?? 'A user');
        $location = $this->buildLocation();
        $incidentType = trim((string) ($this->report->mobility_status ?? ''));
        $pleaseSpecify = trim((string) ($this->report->injuries ?? ''));
        $urgency = trim((string) ($this->report->urgency_level ?? '')) ?: 'Unknown';
        $additionalInfo = trim((string) ($this->report->additional_info ?? ''));
        $reportedAt = $this->report->created_at?->format('M d, Y h:i A') ?? '';

        $content = "EMERGENCY REPORT\n" .
            "Name: {$displayName}\n" .
            "Time: {$reportedAt}\n" .
            "Location: {$location}\n" .
            "Incident Type: {$incidentType}\n" .
            "Please Specify: {$pleaseSpecify}\n" .
            "Urgency Level: {$urgency}" .
            ($additionalInfo !== '' ? "\nAdditional Info: {$additionalInfo}" : '');

        return (new VonageMessage)->content($content);
    }

    private function buildLocation(): string
    {
        $building = $this->report->building?->name ?? '';
        $floorLabel = $this->formatFloorLabel($this->report->floor?->floor_name ?? '');
        $room = $this->report->room?->room_name ?? '';

        $parts = array_filter([$building, $floorLabel, $room], function ($value) {
            return trim((string) $value) !== '';
        });

        return implode(', ', $parts);
    }

    private function formatFloorLabel(string $floorName): string
    {
        $floorName = trim($floorName);
        if ($floorName === '') {
            return '';
        }

        if (preg_match('/^floor\s*(\d+)\s*-\s*(.+)$/i', $floorName, $matches)) {
            $floorNumber = $matches[1];
            $namePart = trim($matches[2]);

            return $namePart !== ''
                ? sprintf('Floor %s (%s)', $floorNumber, $namePart)
                : sprintf('Floor %s', $floorNumber);
        }

        return $floorName;
    }
}
