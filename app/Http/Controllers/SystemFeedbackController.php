<?php

namespace App\Http\Controllers;

use App\Models\SystemFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SystemFeedbackController extends Controller
{
    /**
     * Store a new system feedback (bug report / improvement suggestion).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'category' => 'required|in:bug,improvement',
            'area' => 'nullable|string|max:100',
            'description' => 'required|string|min:10|max:3000',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,mp4,mov,pdf',
        ]);

        try {
            $attachmentPath = null;

            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('system-feedbacks', 'public');
            }

            $feedback = SystemFeedback::create([
                'user_id' => $validated['user_id'],
                'category' => $validated['category'],
                'area' => $validated['area'] ?? null,
                'description' => $validated['description'],
                'attachment_path' => $attachmentPath,
                'status' => 'open',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback! We\'ll review it soon.',
                'data' => $feedback,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to store system feedback: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback. Please try again.',
            ], 500);
        }
    }

    /**
     * List all system feedbacks (admin).
     */
    public function index(Request $request)
    {
        $query = SystemFeedback::with('user');

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Filter by area
        if ($request->has('area') && $request->area) {
            $query->where('area', $request->area);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Date range
        if ($request->has('from') && $request->from) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->has('to') && $request->to) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $feedbacks = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $feedbacks,
        ]);
    }

    /**
     * Get stats for system feedbacks (admin).
     */
    public function stats()
    {
        $total = SystemFeedback::count();
        $bugs = SystemFeedback::where('category', 'bug')->count();
        $improvements = SystemFeedback::where('category', 'improvement')->count();
        $open = SystemFeedback::where('status', 'open')->count();
        $inReview = SystemFeedback::where('status', 'in_review')->count();
        $resolved = SystemFeedback::where('status', 'resolved')->count();
        $closed = SystemFeedback::where('status', 'closed')->count();

        // Top reported areas
        $areaDistribution = SystemFeedback::selectRaw('area, COUNT(*) as count')
            ->whereNotNull('area')
            ->groupBy('area')
            ->orderByDesc('count')
            ->pluck('count', 'area')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'bugs' => $bugs,
                'improvements' => $improvements,
                'open' => $open,
                'in_review' => $inReview,
                'resolved' => $resolved,
                'closed' => $closed,
                'area_distribution' => $areaDistribution,
            ],
        ]);
    }

    /**
     * Update status / admin notes (admin).
     */
    public function update(Request $request, SystemFeedback $systemFeedback)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:open,in_review,resolved,closed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        if (isset($validated['status'])) {
            $systemFeedback->status = $validated['status'];
        }
        if (array_key_exists('admin_notes', $validated)) {
            $systemFeedback->admin_notes = $validated['admin_notes'];
        }

        $systemFeedback->save();

        return response()->json([
            'success' => true,
            'message' => 'Feedback updated.',
            'data' => $systemFeedback->fresh('user'),
        ]);
    }

    /**
     * Get a user's own system feedbacks.
     */
    public function userFeedbacks(Request $request, $userId)
    {
        $feedbacks = SystemFeedback::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $feedbacks,
        ]);
    }
}
