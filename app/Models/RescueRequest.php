<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescueRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'rescue_code',
        'assigned_rescuer',
        'user_id',
        'status',
        'building_id',
        'floor_id',
        'room_id',
        'conversation_id',
        'description',
        'original_description',
        'mobility_status',
        'injuries',
        'original_injuries',
        'urgency_level',
        'additional_info',
        'original_additional_info',
        'is_translated',
        'media_attachments',
        'completion_photo',
        'completion_notes',
        'firstName',
        'lastName',
        'force_alert',
        'force_alert_at',
        'cancellation_reason',
        'cancelled_at',
        'safe_approval_requested',
        'safe_approval_requested_at',
        'safe_approval_status',
        'safe_approval_responded_at',
        'safe_approval_reason',
        'cancel_approval_requested',
        'cancel_approval_requested_at',
        'cancel_approval_status',
        'cancel_approval_responded_at',
        'cancel_approval_reason',
        'cancel_proof_details',
        'cancel_proof_photo',
        'cancel_in_progress_at',
        'cancel_attempt_count',
        'last_cancel_attempt_at',
        'marking_safe_in_progress_at',
        'safe_proof_photo',
        'safe_proof_reason',
        'self_marked_safe_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'media_attachments' => 'array',
        'force_alert' => 'boolean',
        'force_alert_at' => 'datetime',
        'is_translated' => 'boolean',
        'cancelled_at' => 'datetime',
        'safe_approval_requested' => 'boolean',
        'safe_approval_requested_at' => 'datetime',
        'safe_approval_responded_at' => 'datetime',
        'cancel_approval_requested' => 'boolean',
        'cancel_approval_requested_at' => 'datetime',
        'cancel_approval_responded_at' => 'datetime',
        'cancel_in_progress_at' => 'datetime',
        'last_cancel_attempt_at' => 'datetime',
        'marking_safe_in_progress_at' => 'datetime',
        'self_marked_safe_at' => 'datetime',
    ];

    /**
     * Get the building for this rescue request.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Get the floor for this rescue request.
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * Get the room for this rescue request.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the user who requested rescue.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the assigned rescuer.
     */
    public function rescuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_rescuer');
    }

    /**
     * Get the conversation for this rescue request.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the full location string.
     *
     * @return string
     */
    public function getFullLocation(): string
    {
        return trim(sprintf(
            '%s - %s - %s',
            $this->building?->name ?? '',
            $this->floor?->floor_name ?? '',
            $this->room?->room_name ?? ''
        ));
    }
}
