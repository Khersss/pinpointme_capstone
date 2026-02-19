<?php

namespace App\Http\Controllers;

use App\Models\RescueFeedback;
use App\Models\RescueRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RescueFeedbackController extends Controller
{
    /**
     * Store feedback for a completed rescue.
     */
    public function store(Request $request, RescueRequest $rescueRequest)
    {
        $validated = $request->validate([
            'overall_rating' => 'required|integer|min:1|max:5',
            'response_time_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'professionalism_rating' => 'nullable|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
            'would_recommend' => 'boolean',
            'liked_most' => 'nullable|string|max:255',
            'feeling_safe_now' => 'nullable|boolean',
            'feedback_tags' => 'nullable|array',
            'feedback_tags.*' => 'string|max:100',
        ]);

        // Check if feedback already exists for this rescue by this user
        $existing = RescueFeedback::where('rescue_request_id', $rescueRequest->id)
            ->where('user_id', $rescueRequest->user_id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback already submitted for this rescue.',
            ], 409);
        }

        try {
            $feedback = RescueFeedback::create([
                'rescue_request_id' => $rescueRequest->id,
                'user_id' => $rescueRequest->user_id,
                'rescuer_id' => $rescueRequest->assigned_rescuer,
                'overall_rating' => $validated['overall_rating'],
                'response_time_rating' => $validated['response_time_rating'] ?? null,
                'communication_rating' => $validated['communication_rating'] ?? null,
                'professionalism_rating' => $validated['professionalism_rating'] ?? null,
                'comments' => $validated['comments'] ?? null,
                'would_recommend' => $validated['would_recommend'] ?? true,
                'liked_most' => $validated['liked_most'] ?? null,
                'feeling_safe_now' => $validated['feeling_safe_now'] ?? null,
                'feedback_tags' => $validated['feedback_tags'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback!',
                'data' => $feedback,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to store feedback: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback. Please try again.',
            ], 500);
        }
    }

    /**
     * Check if feedback exists for a rescue request.
     */
    public function check(RescueRequest $rescueRequest)
    {
        $exists = RescueFeedback::where('rescue_request_id', $rescueRequest->id)
            ->where('user_id', $rescueRequest->user_id)
            ->exists();

        return response()->json([
            'success' => true,
            'has_feedback' => $exists,
        ]);
    }

    /**
     * Get feedback for a specific rescue request.
     */
    public function show(RescueRequest $rescueRequest)
    {
        $feedback = RescueFeedback::where('rescue_request_id', $rescueRequest->id)
            ->with(['user', 'rescuer'])
            ->first();

        if (!$feedback) {
            return response()->json([
                'success' => false,
                'message' => 'No feedback found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ]);
    }

    /**
     * Get all feedbacks (Admin dashboard / reports).
     */
    public function index(Request $request)
    {
        $query = RescueFeedback::with(['rescueRequest', 'user', 'rescuer']);

        // Filter by rescuer if provided
        if ($request->has('rescuer_id')) {
            $query->where('rescuer_id', $request->rescuer_id);
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $feedbacks = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $feedbacks,
        ]);
    }

    /**
     * Get feedback statistics (for admin reports).
     */
    public function stats(Request $request)
    {
        $query = RescueFeedback::query();

        if ($request->has('rescuer_id')) {
            $query->where('rescuer_id', $request->rescuer_id);
        }

        $stats = [
            'total_feedbacks' => $query->count(),
            'average_overall' => round($query->avg('overall_rating'), 1),
            'average_response_time' => round($query->avg('response_time_rating'), 1),
            'average_communication' => round($query->avg('communication_rating'), 1),
            'average_professionalism' => round($query->avg('professionalism_rating'), 1),
            'would_recommend_percent' => $query->count() > 0
                ? round(($query->where('would_recommend', true)->count() / $query->count()) * 100)
                : 0,
            'feeling_safe_percent' => $query->count() > 0
                ? round((RescueFeedback::where('feeling_safe_now', true)->count() / max($query->count(), 1)) * 100)
                : 0,
            'rating_distribution' => [
                5 => RescueFeedback::where('overall_rating', 5)->count(),
                4 => RescueFeedback::where('overall_rating', 4)->count(),
                3 => RescueFeedback::where('overall_rating', 3)->count(),
                2 => RescueFeedback::where('overall_rating', 2)->count(),
                1 => RescueFeedback::where('overall_rating', 1)->count(),
            ],
            'tag_distribution' => $this->getTagDistribution(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get tag distribution from feedback_tags JSON column.
     */
    private function getTagDistribution(): array
    {
        $feedbacks = RescueFeedback::whereNotNull('feedback_tags')->pluck('feedback_tags');
        $tagCounts = [];

        foreach ($feedbacks as $tags) {
            $tagsArray = is_string($tags) ? json_decode($tags, true) : $tags;
            if (!is_array($tagsArray)) continue;

            foreach ($tagsArray as $tag) {
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
        }

        arsort($tagCounts);
        return $tagCounts;
    }
}
