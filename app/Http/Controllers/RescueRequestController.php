<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use App\Models\RescueRequest;
use App\Services\PushNotificationService;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RescueRequestController extends Controller
{
    /**
     * Display rescue requests
     *
     * @return void
     */
    public function index()
    {
        // Handle API requests - return all rescue requests with relationships
        if (request()->expectsJson() || request()->is('api/*')) {
            $rescueRequests = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $rescueRequests
            ]);
        }

        $isAdmin = Auth::check() && Auth::user()->isAdmin;

        if (!$isAdmin) {
            return Inertia::render('Error', [
                'code' => 404,
                'message' => 'This page unauthorized to access'
            ]);
        }

        return Inertia::render('System/RescueRequests', [
            'can' => []
        ]);
    }

    /**
     * Store Rescue Request
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'assigned_rescuer',
            'user_id',
            'status',
            'building_id',
            'floor_id',
            'room_id',
            'description',
            'mobility_status',
            'injuries',
            'urgency_level',
            'additional_info',
            'firstName',
            'lastName'
        ]);

        $validator = Validator::make($data, [
            'user_id' => 'nullable|exists:users,id',
            'assigned_rescuer' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'building_id' => 'nullable|exists:buildings,id',
            'floor_id' => 'nullable|exists:floors,id',
            'room_id' => 'nullable|exists:rooms,id',
            'description' => 'nullable|string',
            'mobility_status' => 'nullable|string',
            'injuries' => 'nullable|string',
            'urgency_level' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'firstName' => 'nullable|string',
            'lastName' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        // Check if user must complete profile first
        if (isset($data['user_id'])) {
            $user = \App\Models\User::find($data['user_id']);
            if ($user && $this->userMustUpdateProfile($user)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'must_update_profile' => true,
                        'message' => 'You must complete your profile information before you can submit emergency reports. Please update your personal information, emergency contact, and medical details.'
                    ], 400);
                }
                
                return redirect()->back()->with('error', 'You must complete your profile information before you can submit emergency reports.');
            }
        }

        $data['rescue_code'] = $this->generateUniqueRescueCode();
        $data['status'] = $data['status'] ?? 'pending';

        // Handle media file uploads
        if ($request->hasFile('media_files')) {
            $mediaAttachments = [];
            $files = $request->file('media_files');
            
            foreach ($files as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('rescue_media', $filename, 'public');
                
                $mediaAttachments[] = [
                    'path' => $path,
                    'url' => '/storage/' . $path,
                    'type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
                    'mime_type' => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize()
                ];
            }
            
            $data['media_attachments'] = $mediaAttachments;
        }

        // Translate text fields to English for rescuers
        $data = $this->translateTextFields($data);

        $rescueRequest = RescueRequest::create($data);

        // Send push notification to all rescuers
        $this->notifyRescuers($rescueRequest);

        // Handle API requests differently
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Rescue request created successfully',
                'rescueCode' => $rescueRequest->rescue_code,
                'requestId' => $rescueRequest->id,
                'data' => $rescueRequest
            ], 201);
        }

        return redirect()->back()->with('success', 'Rescue request created successfully');
    }

    /**
     * Send push notification to all rescuers about a new rescue request
     *
     * @param RescueRequest $rescueRequest
     * @return void
     */
    protected function notifyRescuers(RescueRequest $rescueRequest): void
    {
        try {
            $pushService = new PushNotificationService();

            // Build the notification payload
            $location = [];
            if ($rescueRequest->building) {
                $location[] = $rescueRequest->building->name ?? 'Building';
            }
            if ($rescueRequest->floor) {
                $location[] = $rescueRequest->floor->name ?? 'Floor';
            }
            if ($rescueRequest->room) {
                $location[] = $rescueRequest->room->name ?? 'Room';
            }
            $locationStr = !empty($location) ? implode(' - ', $location) : 'Unknown Location';

            $urgencyLevel = $rescueRequest->urgency_level ?? 'normal';
            $urgencyPrefix = $urgencyLevel === 'critical' ? '🚨 CRITICAL: ' :
                           ($urgencyLevel === 'high' ? '⚠️ URGENT: ' : '');

            $payload = [
                'title' => $urgencyPrefix . 'New Rescue Request',
                'body' => "Location: {$locationStr}\nCode: {$rescueRequest->rescue_code}",
                'icon' => '/images/logos/pinpointme.png',
                'badge' => '/images/logos/pinpointme.png',
                'tag' => 'rescue-' . $rescueRequest->rescue_code,
                'type' => 'rescue_request',
                'requireInteraction' => true,
                'data' => [
                    'type' => 'rescue_request',
                    'rescue_code' => $rescueRequest->rescue_code,
                    'request_id' => $rescueRequest->id,
                    'urgency_level' => $urgencyLevel,
                    'url' => '/rescuer/dashboard?rescue=' . $rescueRequest->rescue_code,
                ],
                'actions' => [
                    ['action' => 'view', 'title' => 'View Request'],
                    ['action' => 'dismiss', 'title' => 'Dismiss'],
                ],
            ];

            // Send to all rescuers
            $result = $pushService->sendToRole('rescuer', $payload);
            
            \Illuminate\Support\Facades\Log::info('Push notifications sent to rescuers', [
                'rescue_code' => $rescueRequest->rescue_code,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            // Log the error but don't fail the rescue request creation
            \Illuminate\Support\Facades\Log::error('Failed to send push notifications to rescuers', [
                'rescue_code' => $rescueRequest->rescue_code,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified rescue request.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $rescueRequest = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])->findOrFail($id);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $rescueRequest
            ]);
        }

        return Inertia::render('RescueRequest/Show', [
            'rescueRequest' => $rescueRequest
        ]);
    }

    /**
     * Display rescue request by code.
     *
     * @param string $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByCode($code)
    {
        try {
            $rescueRequest = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])
                ->where('rescue_code', $code)
                ->first();

            if (!$rescueRequest) {
                if (request()->expectsJson() || request()->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rescue request not found'
                    ], 404);
                }
                abort(404);
            }

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'data' => $rescueRequest
                ]);
            }

            return Inertia::render('RescueRequest/Show', [
                'rescueRequest' => $rescueRequest
            ]);
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error retrieving rescue request: ' . $e->getMessage()
                ], 500);
            }
            abort(500);
        }
    }

    /**
     * Get user history.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function userHistory($userId)
    {
        $rescueRequests = RescueRequest::with(['building', 'floor', 'room', 'rescuer'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rescueRequests
        ]);
    }

    /**
     * Get user's active (open) rescue request.
     * Active statuses: pending, accepted, in_progress, en_route
     * Once marked as 'rescued', 'safe', or 'completed', user can request again
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function userActiveRescue($userId)
    {
        // Only these statuses are truly "active" - user cannot create new request
        // 'rescued' means waiting for user to confirm safe, but they can still create new request
        // 'safe' and 'completed' mean rescue is done
        $activeStatuses = ['pending', 'accepted', 'assigned', 'in_progress', 'en_route'];
        
        $activeRequest = RescueRequest::with(['building', 'floor', 'room', 'rescuer'])
            ->where('user_id', $userId)
            ->whereIn('status', $activeStatuses)
            ->orderByDesc('created_at')
            ->first();

        if ($activeRequest) {
            return response()->json([
                'success' => true,
                'has_active' => true,
                'data' => $activeRequest
            ]);
        }

        return response()->json([
            'success' => true,
            'has_active' => false,
            'data' => null
        ]);
    }

    /**
     * Get location details
     *
     * @param int $buildingId
     * @param int $floorId
     * @param int $roomId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocationDetails($buildingId, $floorId, $roomId)
    {
        try {
            $building = Building::find($buildingId);
            $floor = Floor::find($floorId);
            $room = Room::find($roomId);

            return response()->json([
                'success' => true,
                'data' => [
                    'building' => $building,
                    'floor' => $floor,
                    'room' => $room,
                    'location_string' => trim(sprintf(
                        '%s - %s - %s',
                        $building?->name ?? '',
                        $floor?->floor_name ?? '',
                        $room?->room_name ?? ''
                    ))
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving location details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update rescue request
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        $data = $request->only([
            'assigned_rescuer',
            'status',
            'building_id',
            'floor_id',
            'room_id',
            'description',
            'mobility_status',
            'injuries',
            'urgency_level',
            'additional_info',
            'cancellation_reason'
        ]);

        $validator = Validator::make($data, [
            'assigned_rescuer' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:pending,assigned,in_progress,rescued,safe,completed,cancelled',
            'building_id' => 'nullable|exists:buildings,id',
            'floor_id' => 'nullable|exists:floors,id',
            'room_id' => 'nullable|exists:rooms,id',
            'description' => 'nullable|string',
            'mobility_status' => 'nullable|string',
            'injuries' => 'nullable|string',
            'urgency_level' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'cancellation_reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            // Handle API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            // Use database transaction with row-level locking for race condition protection
            return DB::transaction(function () use ($request, $id, $data) {
            $rescueRequest = RescueRequest::lockForUpdate()->findOrFail($id);
            
            // ── Block accept if user is in the middle of cancelling ──
            if (isset($data['assigned_rescuer']) && isset($data['status']) && in_array($data['status'], ['assigned', 'in_progress'])) {
                if ($rescueRequest->cancel_in_progress_at) {
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This rescue request is currently undergoing cancellation by the user. Please wait for them to finish.',
                            'cancel_in_progress' => true,
                        ], 409);
                    }
                    return redirect()->back()->with('error', 'User is currently processing a cancellation.');
                }
                
                // ── Block accept if user is in the middle of marking themselves safe ──
                if ($rescueRequest->marking_safe_in_progress_at) {
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This rescue request is currently undergoing self-safe marking by the user. Please wait for them to finish.',
                            'marking_safe_in_progress' => true,
                        ], 409);
                    }
                    return redirect()->back()->with('error', 'User is currently marking themselves as safe.');
                }
            }

            // ── Race condition guard: if a rescuer is trying to accept a request
            //    that has already been accepted by another rescuer, reject it. ──
            if (isset($data['assigned_rescuer']) && isset($data['status']) && $data['status'] === 'assigned') {
                if ($rescueRequest->status !== 'pending') {
                    $existingRescuer = $rescueRequest->rescuer;
                    $rescuerName = $existingRescuer 
                        ? trim(($existingRescuer->first_name ?? '') . ' ' . ($existingRescuer->last_name ?? ''))
                        : 'another rescuer';
                    
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'success' => false,
                            'message' => "This rescue request has already been accepted by {$rescuerName}.",
                            'already_accepted' => true,
                            'current_status' => $rescueRequest->status,
                        ], 409); // 409 Conflict
                    }
                    return redirect()->back()->with('error', 'This request has already been accepted.');
                }
            }
            
            // Check if rescuer is available (not off_duty or unavailable)
            if (isset($data['assigned_rescuer']) && $data['assigned_rescuer'] !== $rescueRequest->assigned_rescuer) {
                $rescuer = \App\Models\User::find($data['assigned_rescuer']);
                if ($rescuer && $rescuer->role === 'rescuer') {
                    // Check if rescuer can accept new requests
                    if (in_array($rescuer->status, ['off_duty', 'unavailable'])) {
                        if ($request->expectsJson() || $request->is('api/*')) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Rescuer is currently ' . str_replace('_', ' ', $rescuer->status) . ' and cannot accept rescue requests.'
                            ], 422);
                        }
                        return redirect()->back()->with('error', 'Rescuer is not available.');
                    }
                    
                    // Check if rescuer already has an active rescue
                    $hasActiveRescue = RescueRequest::where('assigned_rescuer', $rescuer->id)
                        ->whereIn('status', ['assigned', 'in_progress', 'en_route'])
                        ->where('id', '!=', $id)
                        ->exists();
                    
                    if ($hasActiveRescue) {
                        if ($request->expectsJson() || $request->is('api/*')) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Rescuer already has an active rescue assignment.'
                            ], 422);
                        }
                        return redirect()->back()->with('error', 'Rescuer already has an active rescue.');
                    }
                }
            }
            
            // Update timestamp for status changes
            if (isset($data['status']) && $data['status'] !== $rescueRequest->status) {
                $data['updated_at'] = now();
                
                // ── Clear force_alert when request is no longer pending ──
                // This ensures ALL rescuers stop receiving the alarm on next poll
                if ($data['status'] !== 'pending' && $rescueRequest->force_alert) {
                    $data['force_alert'] = false;
                }
            }
            
            $rescueRequest->update($data);
            
            // Update rescuer's status when rescue request status changes
            if (isset($data['assigned_rescuer'])) {
                $rescuer = \App\Models\User::find($data['assigned_rescuer']);
                if ($rescuer && $rescuer->role === 'rescuer') {
                    // Set rescuer status to 'on_rescue' when assigned
                    $rescuer->update(['status' => 'on_rescue']);
                }
            } elseif (isset($data['status']) && $rescueRequest->assigned_rescuer) {
                $rescuer = \App\Models\User::find($rescueRequest->assigned_rescuer);
                if ($rescuer && $rescuer->role === 'rescuer') {
                    // If rescue is completed, rescued, safe, or cancelled, set rescuer back to available
                    if (in_array($data['status'], ['completed', 'rescued', 'safe', 'cancelled'])) {
                        $rescuer->update(['status' => 'available']);
                    }
                    // If rescue is assigned or in_progress, ensure rescuer is on_rescue
                    elseif (in_array($data['status'], ['assigned', 'in_progress'])) {
                        $rescuer->update(['status' => 'on_rescue']);
                    }
                }
            }
            
            // Load relationships for API response
            $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer']);

            // Handle API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Rescue request updated successfully',
                    'data' => $rescueRequest
                ]);
            }

            return redirect()->back()->with('success', 'Rescue request updated successfully');
            }); // end DB::transaction
        } catch (\Exception $e) {
            // Handle API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update rescue request: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to update rescue request');
        }
    }

    /**
     * Get rescue requests for a specific rescuer
     *
     * @param int $rescuerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function rescuerFeed($rescuerId)
    {
        $rescueRequests = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])
            ->where(function($query) use ($rescuerId) {
                $query->where('assigned_rescuer', $rescuerId)
                      ->orWhere('status', 'pending'); // Show all pending requests
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $rescueRequests
        ]);
    }

    /**
     * Delete rescue request
     *
     * @param int $id
     * @return void
     */
    public function destroy($id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);
        $rescueRequest->delete();

        return redirect()->back()->with('success', 'Rescue request deleted successfully');
    }

    /**
     * Update status by rescue code
     *
     * @param Request $request
     * @param string $code
     * @return void
     */
    public function updateStatus(Request $request, $code)
    {
        $data = $request->only('status', 'assigned_rescuer');

        $validator = Validator::make($data, [
            'status' => 'required|string|in:pending,assigned,in_progress,rescued,safe,completed,cancelled',
            'assigned_rescuer' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $rescueRequest = RescueRequest::where('rescue_code', $code)->firstOrFail();
        $rescueRequest->update($data);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
            ]);
        }

        return redirect()->back()->with('success', 'Rescue request status updated successfully');
    }

    /**
     * Mark rescue request as safe
     *
     * @param int $id
     * @return void
     */
    public function markSafe(Request $request, $id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);

        // Check if rescue is already completed, cancelled, or safe
        if (in_array($rescueRequest->status, ['safe', 'cancelled', 'completed', 'rescued'])) {
            return response()->json([
                'success' => false,
                'message' => 'This rescue request has already been completed or cancelled.',
            ], 422);
        }

        // If rescuer is assigned and rescue is in_progress, require rescuer approval
        if ($rescueRequest->assigned_rescuer && in_array($rescueRequest->status, ['assigned', 'in_progress'])) {
            // Check if there's already a pending approval request
            if ($rescueRequest->safe_approval_requested && $rescueRequest->safe_approval_status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'A safe approval request is already pending. Please wait for the rescuer to respond.',
                    'approval_pending' => true,
                ], 422);
            }

            // Validate required proof fields for rescuer approval case
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
                'proof_photo' => 'required|string', // Base64 encoded image
            ], [
                'reason.required' => 'Please provide a reason for marking yourself as safe.',
                'proof_photo.required' => 'A proof photo is required to verify your safety.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            // Handle proof photo upload
            $proofPhotoPath = null;
            if ($request->has('proof_photo')) {
                $proofPhotoPath = $this->saveBase64Image($request->input('proof_photo'), 'safe-proofs');
            }

            // Request approval from rescuer with proof
            $rescueRequest->update([
                'safe_approval_requested' => true,
                'safe_approval_requested_at' => now(),
                'safe_approval_status' => 'pending',
                'safe_approval_responded_at' => null,
                'safe_approval_reason' => $request->input('reason'),
                'safe_proof_photo' => $proofPhotoPath,
                'safe_proof_reason' => $request->input('reason'),
                'marking_safe_in_progress_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Safe approval request sent to rescuer. Please wait for their response.',
                'approval_requested' => true,
                'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
            ]);
        }

        // No rescuer assigned or status is pending - validate before allowing direct mark as safe
        
        // 1. Status validation: Only allow for pending/open statuses
        if (!in_array($rescueRequest->status, ['pending', 'open'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot mark as safe at this stage. Current status: ' . ucfirst($rescueRequest->status),
            ], 422);
        }

        // 2. Validate request input for self-marking as safe - REQUIRED photo and reason
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
            'proof_photo' => 'required|string', // Base64 encoded image
        ], [
            'reason.required' => 'Please provide a reason for marking yourself as safe.',
            'proof_photo.required' => 'A proof photo is required to verify your safety.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Handle proof photo upload
        $proofPhotoPath = null;
        if ($request->has('proof_photo')) {
            $proofPhotoPath = $this->saveBase64Image($request->input('proof_photo'), 'safe-proofs');
        }

        // 3. Critical urgency level requires admin approval before marking safe
        $urgencyLevel = strtolower($rescueRequest->urgency_level ?? '');
        if ($urgencyLevel === 'critical') {
            // Check if there's already a pending approval request
            if ($rescueRequest->safe_approval_requested && $rescueRequest->safe_approval_status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'A safe approval request is already pending admin review.',
                    'approval_pending' => true,
                ], 422);
            }

            $reason = $request->input('reason', 'User marked themselves as safe');

            // Save proof and request admin approval
            $rescueRequest->update([
                'safe_approval_requested' => true,
                'safe_approval_requested_at' => now(),
                'safe_approval_status' => 'pending',
                'safe_approval_responded_at' => null,
                'safe_approval_reason' => $reason,
                'safe_proof_photo' => $proofPhotoPath,
                'safe_proof_reason' => $reason,
                'marking_safe_in_progress_at' => null,
            ]);

            // Create admin notification for critical safe approval request
            $requesterName = $rescueRequest->requester 
                ? "{$rescueRequest->requester->first_name} {$rescueRequest->requester->last_name}" 
                : 'Unknown User';
            $locationParts = [];
            if ($rescueRequest->building) $locationParts[] = $rescueRequest->building->name;
            if ($rescueRequest->floor) $locationParts[] = $rescueRequest->floor->name;
            if ($rescueRequest->room) $locationParts[] = $rescueRequest->room->name;
            $location = !empty($locationParts) ? implode(' - ', $locationParts) : 'Unknown Location';

            \App\Models\AdminNotification::create([
                'type' => 'critical_safe_approval_request',
                'title' => 'Critical Patient Requesting Safe Mark',
                'message' => "User {$requesterName} with CRITICAL urgency level is requesting to mark themselves as safe. Admin approval required.",
                'data' => [
                    'rescue_id' => $rescueRequest->id,
                    'rescue_code' => $rescueRequest->rescue_code,
                    'user_name' => $requesterName,
                    'urgency_level' => $rescueRequest->urgency_level,
                    'reason' => $reason,
                    'proof_photo' => $proofPhotoPath,
                    'location' => $location,
                    'requested_at' => now()->toISOString(),
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your safe request has been sent to the administrator for review. Since your urgency level is critical, an admin must verify your safety.',
                'approval_requested' => true,
                'admin_approval' => true,
                'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
            ]);
        }

        // 4. Non-critical: Proceed to mark as safe directly

        // Log the self-safe action for audit purposes
        $reason = $request->input('reason', 'User marked themselves as safe without rescuer assistance');
        \App\Models\AuditTrail::create([
            'user_id' => $rescueRequest->user_id,
            'action' => 'self_mark_safe',
            'description' => sprintf(
                'User self-marked request #%d as safe. Reason: %s.',
                $rescueRequest->id,
                $reason
            ),
            'ip_address' => $request->ip(),
        ]);

        // Now allow direct mark as safe with proof
        $rescueRequest->update([
            'status' => 'safe',
            'safe_approval_requested' => false,
            'safe_approval_status' => null,
            'completion_notes' => $reason,
            'safe_proof_photo' => $proofPhotoPath,
            'safe_proof_reason' => $reason,
            'self_marked_safe_at' => now(),
            'marking_safe_in_progress_at' => null,
        ]);

        // If a rescuer was assigned, set them back to available
        if ($rescueRequest->assigned_rescuer) {
            $rescuer = \App\Models\User::find($rescueRequest->assigned_rescuer);
            if ($rescuer) {
                $rescuer->update(['status' => 'active']);
            }
        }

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Marked as safe successfully',
                'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
            ]);
        }

        return redirect()->back()->with('success', 'Rescue request marked as safe');
    }

    /**
     * Approve safe request from user - Rescuer allows the user to mark themselves safe
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveSafeRequest(Request $request, $id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);

        // Validate that this request has a pending safe approval
        if (!$rescueRequest->safe_approval_requested || $rescueRequest->safe_approval_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'No pending safe approval request found.',
            ], 422);
        }

        // Update the rescue request to safe
        $rescueRequest->update([
            'status' => 'safe',
            'safe_approval_status' => 'approved',
            'safe_approval_responded_at' => now(),
            'safe_approval_reason' => $request->input('reason', 'Approved'),
            'self_marked_safe_at' => now(),
        ]);

        // Set rescuer back to available and get rescuer details
        $rescuer = null;
        if ($rescueRequest->assigned_rescuer) {
            $rescuer = \App\Models\User::find($rescueRequest->assigned_rescuer);
            if ($rescuer) {
                $rescuer->update(['status' => 'available']);
            }
        }

        // Load the requester relationship to avoid undefined property error
        $rescueRequest->load('requester');

        // Create admin notification for rescuer-approved safe confirmation
        \App\Models\AdminNotification::create([
            'type' => 'rescue_safe_rescuer_approved',
            'title' => 'User Marked Safe (Rescuer Approved)',
            'message' => "User {$rescueRequest->requester->first_name} {$rescueRequest->requester->last_name} has been approved as safe by their rescuer.",
            'data' => [
                'rescue_id' => $rescueRequest->id,
                'rescue_code' => $rescueRequest->rescue_code,
                'user_name' => "{$rescueRequest->requester->first_name} {$rescueRequest->requester->last_name}",
                'rescuer_name' => $rescuer ? "{$rescuer->first_name} {$rescuer->last_name}" : "Unknown Rescuer",
                'approval_reason' => $request->input('reason', 'Approved by rescuer'),
                'building' => $rescueRequest->building ? $rescueRequest->building->name : null,
                'floor' => $rescueRequest->floor ? $rescueRequest->floor->name : null,
                'room' => $rescueRequest->room ? $rescueRequest->room->name : null,
                'location' => $rescueRequest->building ? 
                    ($rescueRequest->building->name . 
                    ($rescueRequest->floor ? ' - ' . $rescueRequest->floor->name : '') . 
                    ($rescueRequest->room ? ' - ' . $rescueRequest->room->name : '')) : 
                    'Unknown Location'
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Safe request approved. The user has been marked as safe.',
            'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
        ]);
    }

    /**
     * Deny safe request from user - Rescuer believes user still needs assistance
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function denySafeRequest(Request $request, $id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);

        // Validate that this request has a pending safe approval
        if (!$rescueRequest->safe_approval_requested || $rescueRequest->safe_approval_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'No pending safe approval request found.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Update the rescue request denial
        $rescueRequest->update([
            'safe_approval_status' => 'denied',
            'safe_approval_responded_at' => now(),
            'safe_approval_reason' => $request->input('reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Safe request denied. The rescue operation will continue.',
            'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
        ]);
    }

    /**
     * Cancel safe approval request - User wants to withdraw their safe request
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSafeApproval($id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);

        // Validate that this request has a pending safe approval
        if (!$rescueRequest->safe_approval_requested || $rescueRequest->safe_approval_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'No pending safe approval request found.',
            ], 422);
        }

        // Reset the safe approval fields
        $rescueRequest->update([
            'safe_approval_requested' => false,
            'safe_approval_status' => null,
            'safe_approval_requested_at' => null,
            'safe_approval_responded_at' => null,
            'safe_approval_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Safe approval request cancelled.',
            'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
        ]);
    }

    /**
     * Cancel a rescue request with enhanced validation and approval flow
     *
     * Flow 1: Rescuer assigned → Send cancel approval request to rescuer (requires chat confirmation)
     * Flow 2: No rescuer assigned → Immediate cancellation with reason + proof (no time delays)
     *
     * Safety nets are handled on the frontend via multi-step confirmation + type-to-confirm.
     * No time-based delays — this is an emergency app, cancellation must always be possible.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function cancelRequest(Request $request, $id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);

        // Only allow cancellation if still in active states
        if (!in_array($rescueRequest->status, ['pending', 'open', 'assigned', 'accepted', 'in_progress'])) {
            return response()->json([
                'success' => false,
                'message' => 'This rescue request can no longer be cancelled because it is already completed or cancelled.',
            ], 422);
        }

        // Block cancellation for HIGH and CRITICAL urgency levels
        if (in_array(strtolower($rescueRequest->urgency_level ?? ''), ['high', 'critical'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rescue requests with ' . strtoupper($rescueRequest->urgency_level) . ' urgency level cannot be cancelled. A rescuer must verify your safety in person.',
                'urgency_blocked' => true,
                'urgency_level' => $rescueRequest->urgency_level,
            ], 403);
        }

        // Block if there's already a pending cancel approval
        if ($rescueRequest->cancel_approval_requested && $rescueRequest->cancel_approval_status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'A cancellation request is already pending rescuer approval.',
                'cancel_pending' => true,
            ], 422);
        }

        // Validate input
        $rules = [
            'cancellation_reason' => 'required|string|max:500',
            'cancel_proof_details' => 'nullable|string|max:1000',
            'cancel_proof_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        // ────────────────────────────────────────────────────────
        // FLOW 1: Rescuer is assigned → require rescuer approval
        // ────────────────────────────────────────────────────────
        if ($rescueRequest->assigned_rescuer) {

            // Handle proof photo upload
            $proofPhotoPath = null;
            if ($request->hasFile('cancel_proof_photo')) {
                $file = $request->file('cancel_proof_photo');
                $filename = 'cancel_proof_' . $rescueRequest->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $proofPhotoPath = $file->storeAs('cancel_proofs', $filename, 'public');
            }

            // Send cancel approval request to rescuer
            $rescueRequest->update([
                'cancel_approval_requested' => true,
                'cancel_approval_requested_at' => now(),
                'cancel_approval_status' => 'pending',
                'cancel_approval_reason' => $request->cancellation_reason,
                'cancel_proof_details' => $request->cancel_proof_details,
                'cancel_proof_photo' => $proofPhotoPath,
                'cancel_attempt_count' => $rescueRequest->cancel_attempt_count + 1,
                'last_cancel_attempt_at' => now(),
            ]);

            // Log the cancel approval request  
            \App\Models\AuditTrail::create([
                'user_id' => $rescueRequest->user_id,
                'action' => 'cancel_approval_requested',
                'description' => sprintf(
                    'User requested cancellation approval for rescue #%d (Code: %s). Reason: %s. Attempt #%d.',
                    $rescueRequest->id,
                    $rescueRequest->rescue_code ?? 'N/A',
                    $request->cancellation_reason,
                    $rescueRequest->cancel_attempt_count + 1
                ),
                'ip_address' => $request->ip(),
            ]);

            // Create admin notification for cancellation request
            \App\Models\AdminNotification::create([
                'type' => 'rescue_cancelled',
                'title' => 'Rescue Cancellation Request',
                'message' => sprintf(
                    'Rescue request %s — cancellation requested by %s.',
                    $rescueRequest->rescue_code ?? '#' . $rescueRequest->id,
                    trim(($rescueRequest->firstName ?? '') . ' ' . ($rescueRequest->lastName ?? '')) ?: 'Unknown User'
                ),
                'data' => [
                    'rescue_id' => $rescueRequest->id,
                    'rescue_code' => $rescueRequest->rescue_code,
                    'user_name' => trim(($rescueRequest->firstName ?? '') . ' ' . ($rescueRequest->lastName ?? '')) ?: 'Unknown User',
                    'cancellation_reason' => $request->cancellation_reason,
                    'cancelled_at' => now()->toISOString(),
                    'status' => 'pending_approval',
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your cancellation request has been sent to the rescuer for approval. Please use chat to confirm you no longer need help.',
                'cancel_approval_requested' => true,
                'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
            ]);
        }

        // ────────────────────────────────────────────────────────
        // FLOW 2: No rescuer assigned → immediate self-cancellation
        // No time delays — safety nets are on the frontend (multi-step + type-to-confirm).
        // ────────────────────────────────────────────────────────

        // Require proof details (thorough explanation) when no rescuer
        if (!$request->cancel_proof_details || strlen(trim($request->cancel_proof_details)) < 15) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide proof of safety or a detailed explanation (at least 15 characters) for cancelling without rescuer verification.',
                'proof_required' => true,
            ], 422);
        }

        // Handle proof photo upload for no-rescuer case
        $proofPhotoPath = null;
        if ($request->hasFile('cancel_proof_photo')) {
            $file = $request->file('cancel_proof_photo');
            $filename = 'cancel_proof_' . $rescueRequest->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $proofPhotoPath = $file->storeAs('cancel_proofs', $filename, 'public');
        }

        // Proceed with cancellation
        $rescueRequest->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
            'cancel_proof_details' => $request->cancel_proof_details,
            'cancel_proof_photo' => $proofPhotoPath,
            'cancelled_at' => now(),
            'cancel_attempt_count' => $rescueRequest->cancel_attempt_count + 1,
            'last_cancel_attempt_at' => now(),
        ]);

        // Audit trail for self-cancellation without rescuer
        \App\Models\AuditTrail::create([
            'user_id' => $rescueRequest->user_id,
            'action' => 'self_cancel_no_rescuer',
            'description' => sprintf(
                'User self-cancelled rescue request #%d (Code: %s) without rescuer assigned. Reason: %s. Proof: %s.',
                $rescueRequest->id,
                $rescueRequest->rescue_code ?? 'N/A',
                $request->cancellation_reason,
                $request->cancel_proof_details
            ),
            'ip_address' => $request->ip(),
        ]);

        // Create admin notification for direct cancellation
        \App\Models\AdminNotification::create([
            'type' => 'rescue_cancelled',
            'title' => 'Rescue Request Cancelled',
            'message' => sprintf(
                'Rescue request %s has been cancelled by %s (no rescuer assigned).',
                $rescueRequest->rescue_code ?? '#' . $rescueRequest->id,
                trim(($rescueRequest->firstName ?? '') . ' ' . ($rescueRequest->lastName ?? '')) ?: 'the user'
            ),
            'data' => [
                'rescue_id' => $rescueRequest->id,
                'rescue_code' => $rescueRequest->rescue_code,
                'user_name' => trim(($rescueRequest->firstName ?? '') . ' ' . ($rescueRequest->lastName ?? '')) ?: 'Unknown User',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_at' => now()->toISOString(),
                'status' => 'cancelled',
            ],
        ]);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Rescue request cancelled successfully.',
                'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
            ]);
        }

        return redirect()->back()->with('success', 'Rescue request cancelled');
    }

    /**
     * Approve a cancel request — rescuer confirms user no longer needs help
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveCancelRequest(Request $request, $id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);

        // Validate that there's a pending cancel approval
        if (!$rescueRequest->cancel_approval_requested || $rescueRequest->cancel_approval_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'No pending cancellation request found.',
            ], 422);
        }

        // Update the cancel approval and cancel the request
        $rescueRequest->update([
            'status' => 'cancelled',
            'cancel_approval_status' => 'approved',
            'cancel_approval_responded_at' => now(),
            'cancellation_reason' => $rescueRequest->cancel_approval_reason,
            'cancelled_at' => now(),
        ]);

        // Free the rescuer
        if ($rescueRequest->assigned_rescuer) {
            $rescuer = \App\Models\User::find($rescueRequest->assigned_rescuer);
            if ($rescuer) {
                $rescuer->update(['status' => 'active']);
            }
        }

        // Audit trail
        \App\Models\AuditTrail::create([
            'user_id' => $rescueRequest->assigned_rescuer,
            'action' => 'cancel_approved',
            'description' => sprintf(
                'Rescuer approved cancellation for rescue #%d (Code: %s). User reason: %s.',
                $rescueRequest->id,
                $rescueRequest->rescue_code ?? 'N/A',
                $rescueRequest->cancel_approval_reason
            ),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cancellation approved. The rescue request has been cancelled.',
            'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
        ]);
    }

    /**
     * Deny a cancel request — rescuer determines user still needs help
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function denyCancelRequest(Request $request, $id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);

        // Validate that there's a pending cancel approval
        if (!$rescueRequest->cancel_approval_requested || $rescueRequest->cancel_approval_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'No pending cancellation request found.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'deny_reason' => 'required|string|min:5|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Deny the cancel request
        $rescueRequest->update([
            'cancel_approval_status' => 'denied',
            'cancel_approval_responded_at' => now(),
            'cancel_approval_reason' => $request->deny_reason,
        ]);

        // Audit trail
        \App\Models\AuditTrail::create([
            'user_id' => $rescueRequest->assigned_rescuer,
            'action' => 'cancel_denied',
            'description' => sprintf(
                'Rescuer denied cancellation for rescue #%d (Code: %s). Deny reason: %s.',
                $rescueRequest->id,
                $rescueRequest->rescue_code ?? 'N/A',
                $request->deny_reason
            ),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cancellation request denied.',
            'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
        ]);
    }

    /**
     * Withdraw a cancel approval request — user takes back their cancel request
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function withdrawCancelRequest($id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);

        // Validate that this request has a pending cancel approval
        if (!$rescueRequest->cancel_approval_requested || $rescueRequest->cancel_approval_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'No pending cancel approval request found.',
            ], 422);
        }

        // Reset the cancel approval fields
        $rescueRequest->update([
            'cancel_approval_requested' => false,
            'cancel_approval_status' => null,
            'cancel_approval_requested_at' => null,
            'cancel_approval_responded_at' => null,
            'cancel_approval_reason' => null,
            'cancel_proof_details' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cancel request withdrawn.',
            'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
        ]);
    }

    /**
     * Report a false/joke rescue request — deletes the request and logs it for admin
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportFalseRequest(Request $request, $id)
    {
        $rescueRequest = RescueRequest::with(['building', 'floor', 'room', 'requester'])->findOrFail($id);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'cancellation_reason' => 'required|string|max:500',
            'reported_by' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // If a rescuer was assigned, set them back to available
        if ($rescueRequest->assigned_rescuer) {
            $rescuer = \App\Models\User::find($rescueRequest->assigned_rescuer);
            if ($rescuer) {
                $rescuer->update(['status' => 'available']);
            }
        }

        // Log the false report in audit trail for admin visibility
        \App\Models\AuditTrail::create([
            'user_id' => $request->reported_by ?? auth()->id(),
            'action' => 'false_report',
            'description' => sprintf(
                'False/joke rescue request reported and deleted. Request #%d (Code: %s) by %s %s at %s. Reason: %s',
                $rescueRequest->id,
                $rescueRequest->rescue_code ?? 'N/A',
                $rescueRequest->firstName ?? $rescueRequest->requester?->first_name ?? 'Unknown',
                $rescueRequest->lastName ?? $rescueRequest->requester?->last_name ?? '',
                $rescueRequest->building?->name . ' > ' . $rescueRequest->floor?->floor_name . ' > ' . $rescueRequest->room?->room_name,
                $request->cancellation_reason
            ),
            'ip_address' => $request->ip(),
        ]);

        // Create admin notification for false alarm report
        $reporterUser = $request->reported_by ? \App\Models\User::find($request->reported_by) : auth()->user();
        $reporterName = $reporterUser ? "{$reporterUser->first_name} {$reporterUser->last_name}" : 'Unknown Rescuer';
        $requesterName = ($rescueRequest->requester ? "{$rescueRequest->requester->first_name} {$rescueRequest->requester->last_name}" : 'Unknown User');
        $locationStr = trim(implode(' > ', array_filter([
            $rescueRequest->building?->name,
            $rescueRequest->floor?->floor_name,
            $rescueRequest->room?->room_name,
        ])));

        \App\Models\AdminNotification::create([
            'type' => 'false_alarm_report',
            'title' => 'False Alarm Report',
            'message' => "Rescuer {$reporterName} reported a false alarm for request #{$rescueRequest->id} (Code: {$rescueRequest->rescue_code}) by {$requesterName}.",
            'data' => [
                'rescue_id' => $rescueRequest->id,
                'rescue_code' => $rescueRequest->rescue_code,
                'requester_name' => $requesterName,
                'reporter_name' => $reporterName,
                'reporter_id' => $request->reported_by ?? auth()->id(),
                'location' => $locationStr ?: 'Unknown Location',
                'reason' => $request->cancellation_reason,
                'reported_at' => now()->toISOString(),
            ],
        ]);

        // Delete the false rescue request
        $rescueRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'False report deleted and admin has been notified.',
        ]);
    }

    /**
     * Complete a rescue request — marks as safe, stores completion photo and notes.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeRescue(Request $request, $id)
    {
        $rescueRequest = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])->findOrFail($id);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'completion_notes' => 'nullable|string|max:1000',
            'completion_photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ], [
            'completion_photo.required' => 'A completion photo is required as proof that the patient is okay.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $updateData = [
                'status' => 'safe',
                'completion_notes' => $request->completion_notes,
            ];

            // Handle completion photo upload
            if ($request->hasFile('completion_photo')) {
                $file = $request->file('completion_photo');
                $filename = 'completion_' . $rescueRequest->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('rescue_completions', $filename, 'public');
                $updateData['completion_photo'] = '/storage/' . $path;
            }

            $rescueRequest->update($updateData);

            // Set rescuer back to available
            if ($rescueRequest->assigned_rescuer) {
                $rescuer = \App\Models\User::find($rescueRequest->assigned_rescuer);
                if ($rescuer && $rescuer->role === 'rescuer') {
                    $rescuer->update(['status' => 'available']);
                }
            }

            // Log completion in audit trail for admin visibility
            \App\Models\AuditTrail::create([
                'user_id' => $rescueRequest->assigned_rescuer ?? auth()->id(),
                'action' => 'rescue_completed',
                'description' => sprintf(
                    'Rescue completed. Request #%d (Code: %s) for %s %s at %s. Notes: %s. Photo: %s',
                    $rescueRequest->id,
                    $rescueRequest->rescue_code ?? 'N/A',
                    $rescueRequest->firstName ?? $rescueRequest->requester?->first_name ?? 'Unknown',
                    $rescueRequest->lastName ?? $rescueRequest->requester?->last_name ?? '',
                    $rescueRequest->building?->name . ' > ' . $rescueRequest->floor?->floor_name . ' > ' . $rescueRequest->room?->room_name,
                    $request->completion_notes ?? 'None',
                    isset($updateData['completion_photo']) ? 'Uploaded' : 'None'
                ),
                'ip_address' => $request->ip(),
            ]);

            // Reload relationships
            $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer']);

            return response()->json([
                'success' => true,
                'message' => 'Rescue completed successfully.',
                'data' => $rescueRequest,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete rescue: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set cancel-in-progress status when user starts cancel flow
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setCancelInProgress($id)
    {
        try {
            $rescueRequest = RescueRequest::findOrFail($id);

            // Access to the rescue request ID is authorization enough
            // (only the requester knows their rescue code)

            // Only set if request is still active
            if (!in_array($rescueRequest->status, ['pending', 'assigned', 'in_progress'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot start cancel process for completed requests',
                ], 400);
            }

            $rescueRequest->update([
                'cancel_in_progress_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cancel process started - rescuer notified',
                'data' => $rescueRequest->only(['id', 'cancel_in_progress_at'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start cancel process: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear cancel-in-progress status when user completes or abandons cancel flow
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCancelInProgress($id)
    {
        try {
            $rescueRequest = RescueRequest::findOrFail($id);

            // Access to the rescue request ID is authorization enough
            // (only the requester knows their rescue code)

            $rescueRequest->update([
                'cancel_in_progress_at' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cancel process cleared',
                'data' => $rescueRequest->only(['id', 'cancel_in_progress_at'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cancel process: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set marking-safe-in-progress flag when user starts the mark safe slide
     * This notifies rescuers that user is in the process of marking themselves safe
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setMarkingSafeInProgress($id)
    {
        try {
            $rescueRequest = RescueRequest::findOrFail($id);

            // Access to the rescue request ID is authorization enough
            // (only the requester knows their rescue code)

            // Only set if request is still active
            if (!in_array($rescueRequest->status, ['pending', 'assigned', 'in_progress'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot start safe process for completed requests',
                ], 400);
            }

            $rescueRequest->update([
                'marking_safe_in_progress_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Safe marking process started - rescuer notified',
                'data' => $rescueRequest->only(['id', 'marking_safe_in_progress_at'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start safe marking process: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear marking-safe-in-progress status when user completes or abandons safe flow
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearMarkingSafeInProgress($id)
    {
        try {
            $rescueRequest = RescueRequest::findOrFail($id);

            // Access to the rescue request ID is authorization enough
            // (only the requester knows their rescue code)

            $rescueRequest->update([
                'marking_safe_in_progress_at' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Safe marking process cleared',
                'data' => $rescueRequest->only(['id', 'marking_safe_in_progress_at'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear safe marking process: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate unique rescue code
     *
     * @return string
     */
    private function generateUniqueRescueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (RescueRequest::where('rescue_code', $code)->exists());

        return $code;
    }

    /**
     * Check if user must update profile before creating rescue request
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function userMustUpdateProfile($user): bool
    {
        // Check if essential profile fields are missing
        $requiredFields = [
            'first_name',
            'last_name',
            'phone',
            'emergency_contact_name',
            'emergency_contact_phone',
            'allergies'
        ];

        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                return true;
            }
        }

        // Also check id_number via accessor (student_id or faculty_id)
        if (empty($user->id_number)) {
            return true;
        }

        // All fields are filled — if the flag is still set, clear it now
        if (\Schema::hasColumn('users', 'must_update_profile') && $user->must_update_profile) {
            $user->update(['must_update_profile' => false]);
        }

        return false;
    }

    /**
     * Translate text fields to English for rescuers.
     * Stores originals in original_* columns and overwrites main columns with English.
     */
    private function translateTextFields(array $data): array
    {
        try {
            $translationService = app(TranslationService::class);
            $fieldsToCheck = ['description', 'additional_info', 'injuries'];

            $hasNonEnglish = false;

            foreach ($fieldsToCheck as $field) {
                if (!empty($data[$field]) && is_string($data[$field])) {
                    if (!$translationService->isLikelyEnglish($data[$field])) {
                        $hasNonEnglish = true;
                        break;
                    }
                }
            }

            // Just flag whether the text is non-English; don't auto-translate
            $data['is_translated'] = $hasNonEnglish;
        } catch (\Exception $e) {
            Log::error('Language detection failed during rescue request creation', [
                'error' => $e->getMessage(),
            ]);
        }

        return $data;
    }

    /**
     * Translate a rescue request's text fields on-demand.
     *
     * @param  \App\Models\RescueRequest  $rescueRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function translateRequest(RescueRequest $rescueRequest)
    {
        try {
            $translationService = app(TranslationService::class);
            $fieldsToTranslate = [
                'description'     => 'original_description',
                'additional_info' => 'original_additional_info',
                'injuries'        => 'original_injuries',
            ];

            $translations = [];

            foreach ($fieldsToTranslate as $field => $originalField) {
                if (!empty($rescueRequest->$field) && is_string($rescueRequest->$field)) {
                    if (!$translationService->isLikelyEnglish($rescueRequest->$field)) {
                        // Preserve original and translate
                        $original = $rescueRequest->$field;
                        $translated = $translationService->translateToEnglish($original);
                        
                        $rescueRequest->$originalField = $original;
                        $rescueRequest->$field = $translated;
                        $translations[$field] = $translated;
                    }
                }
            }

            $rescueRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Translation completed',
                'translations' => $translations,
                'data' => $rescueRequest->fresh(['building', 'floor', 'room', 'rescuer', 'requester']),
            ]);
        } catch (\Exception $e) {
            Log::error('On-demand translation failed', [
                'rescue_request_id' => $rescueRequest->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Translation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Mark a pending rescue request for force-alert.
     * This sets force_alert = true so that the rescuer dashboard plays an unstoppable ringtone.
     *
     * @param  \App\Models\RescueRequest  $rescueRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceAlert(RescueRequest $rescueRequest)
    {
        // Only allow force-alert on pending requests
        if (!in_array($rescueRequest->status, ['pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Force alert can only be triggered for pending requests.'
            ], 422);
        }

        $rescueRequest->update([
            'force_alert' => true,
            'force_alert_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Force alert activated. Available rescuers will receive an unstoppable notification.',
            'data' => $rescueRequest->fresh(['building', 'floor', 'room'])
        ]);
    }

    /**
     * Admin: Get pending rescue requests that have been waiting longer than
     * their urgency-based threshold with no rescuer accepting.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendingTooLong()
    {
        $requests = RescueRequest::with(['building', 'floor', 'room', 'requester'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($request) {
                $urgency = strtolower($request->urgency_level ?? 'medium');
                $thresholds = [
                    'critical' => 10,
                    'high'     => 30,
                    'medium'   => 120,
                    'low'      => 300,
                ];
                $requiredSeconds = $thresholds[$urgency] ?? 120;
                return now()->diffInSeconds($request->created_at) >= $requiredSeconds;
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $requests,
            'count' => $requests->count()
        ]);
    }

    /**
     * Get all rescuer user IDs for FCM notifications
     * Returns only active rescuers with valid status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRescuerIds()
    {
        try {
            $rescuerIds = \App\Models\User::where('role', 'rescuer')
                ->whereIn('status', ['active', 'available'])
                ->pluck('id')
                ->toArray();

            return response()->json([
                'success' => true,
                'rescuer_ids' => $rescuerIds,
                'count' => count($rescuerIds)
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to get rescuer IDs', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rescuer IDs',
                'rescuer_ids' => []
            ], 500);
        }
    }

    /**
     * Save a base64 encoded image to storage
     *
     * @param string $base64String The base64 encoded image string
     * @param string $folder The folder to save the image in
     * @return string|null The path to the saved image
     */
    private function saveBase64Image($base64String, $folder = 'uploads')
    {
        try {
            // Remove data URL prefix if present
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
                $base64String = substr($base64String, strpos($base64String, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif, webp

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    return null;
                }
            } else {
                $type = 'png'; // Default to PNG if no type detected
            }

            $imageData = base64_decode($base64String);

            if ($imageData === false) {
                return null;
            }

            // Generate unique filename
            $filename = uniqid('safe_proof_') . '_' . time() . '.' . $type;
            $path = $folder . '/' . $filename;

            // Store the file
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $imageData);

            return $path;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to save base64 image', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
