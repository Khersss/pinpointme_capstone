<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminNotificationController extends Controller
{
    /**
     * Store a new admin notification (called by frontend after user actions).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'    => 'required|string|max:100',
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'data'    => 'nullable|array',
        ]);

        $notification = AdminNotification::create($validated);

        return response()->json([
            'success' => true,
            'notification' => $notification,
        ], 201);
    }

    /**
     * List admin notifications (latest first, with optional filters).
     */
    public function index(Request $request): JsonResponse
    {
        $query = AdminNotification::query()->latest();

        // Optional: filter by unread only
        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        // Optional: filter by type
        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        $notifications = $query->paginate($request->input('per_page', 50));

        return response()->json($notifications);
    }

    /**
     * Get count of unread notifications.
     */
    public function unreadCount(): JsonResponse
    {
        $count = AdminNotification::unread()->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(AdminNotification $adminNotification): JsonResponse
    {
        $adminNotification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark ALL notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        AdminNotification::unread()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
