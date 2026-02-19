<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescueFeedback extends Model
{
    use HasFactory;

    protected $table = 'rescue_feedbacks';

    protected $fillable = [
        'rescue_request_id',
        'user_id',
        'rescuer_id',
        'overall_rating',
        'response_time_rating',
        'communication_rating',
        'professionalism_rating',
        'comments',
        'would_recommend',
        'liked_most',
        'feeling_safe_now',
        'feedback_tags',
    ];

    protected $casts = [
        'overall_rating' => 'integer',
        'response_time_rating' => 'integer',
        'communication_rating' => 'integer',
        'professionalism_rating' => 'integer',
        'would_recommend' => 'boolean',
        'feeling_safe_now' => 'boolean',
        'feedback_tags' => 'array',
    ];

    /**
     * Get the rescue request this feedback belongs to.
     */
    public function rescueRequest(): BelongsTo
    {
        return $this->belongsTo(RescueRequest::class);
    }

    /**
     * Get the user who submitted the feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the rescuer who was rated.
     */
    public function rescuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rescuer_id');
    }
}
