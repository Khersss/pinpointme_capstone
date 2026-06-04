<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use App\Models\RescueRequest;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Admin Dashboard with analytics
     */
    public function dashboard(Request $request)
    {
        $timeFilter = $request->get('time_filter', 'week');
        $startDate = $this->getStartDate($timeFilter);

        // Get rescue request statistics
        $rescueRequests = RescueRequest::where('created_at', '>=', $startDate)->get();
        
        $statusCounts = [
            'total' => $rescueRequests->count(),
            'pending' => $rescueRequests->where('status', 'pending')->count(),
            'in_progress' => $rescueRequests->whereIn('status', ['accepted', 'in_progress', 'en_route'])->count(),
            'completed' => $rescueRequests->whereIn('status', ['completed', 'rescued', 'safe'])->count(),
            'cancelled' => $rescueRequests->where('status', 'cancelled')->count(),
        ];

        // Rescues by building
        $rescuesByBuilding = RescueRequest::where('created_at', '>=', $startDate)
            ->selectRaw('building_id, COUNT(*) as count')
            ->groupBy('building_id')
            ->with('building')
            ->get()
            ->map(fn($item) => [
                'name' => $item->building?->name ?? 'Unknown',
                'count' => $item->count
            ]);

        // Rescuer statistics - count based on actual assignments
        $rescuers = User::where('role', 'rescuer')->get();
        
        // Count rescuers with active assignments (assigned, in_progress, en_route)
        $activeRescueCount = RescueRequest::whereIn('status', ['assigned', 'in_progress', 'en_route'])
            ->whereNotNull('assigned_rescuer')
            ->distinct('assigned_rescuer')
            ->count('assigned_rescuer');
        
        // Available rescuers are those with 'available' status OR no active assignments
        $availableCount = $rescuers->filter(function($rescuer) {
            // Check if rescuer has any active rescue assignments
            $hasActiveRescue = RescueRequest::where('assigned_rescuer', $rescuer->id)
                ->whereIn('status', ['assigned', 'in_progress', 'en_route'])
                ->exists();
            
            // Available if status is 'available' AND no active rescues
            return $rescuer->status === 'available' && !$hasActiveRescue;
        })->count();
        
        $rescuerStats = [
            'total' => $rescuers->count(),
            'available' => $availableCount,
            'on_rescue' => $activeRescueCount,
            'off_duty' => $rescuers->where('status', 'off_duty')->count(),
            'unavailable' => $rescuers->where('status', 'unavailable')->count(),
        ];

        // Recent alerts (recent rescue requests)
        $recentAlerts = RescueRequest::with(['building', 'floor', 'room', 'requester'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'rescue_code' => $r->rescue_code,
                'status' => $r->status,
                'urgency_level' => $r->urgency_level,
                'location' => $r->building?->name . ' - ' . $r->floor?->floor_name . ' - ' . $r->room?->room_name,
                'requester_name' => $r->firstName ? "{$r->firstName} {$r->lastName}" : ($r->requester ? "{$r->requester->first_name} {$r->requester->last_name}" : 'Anonymous'),
                'created_at' => $r->created_at->toISOString(),
                'cancellation_reason' => $r->cancellation_reason,
                'cancelled_at' => $r->cancelled_at?->toISOString(),
            ]);

        // User statistics
        $dashboardRoles = ['student', 'faculty', 'staff', 'rescuer'];
        $userStats = [
            'total' => User::whereIn('role', $dashboardRoles)->count(),
            'by_role' => [
                'student' => User::where('role', 'student')->count(),
                'faculty' => User::where('role', 'faculty')->count(),
                'staff' => User::where('role', 'staff')->count(),
                'rescuer' => User::where('role', 'rescuer')->count(),
            ]
        ];

        $dashboardUsers = User::whereIn('role', $dashboardRoles)
            ->orderBy('role')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'role', 'profile_picture', 'updated_at']);

        $usersForStats = User::where('role', '!=', 'admin')
            ->get(['id', 'gender', 'date_of_birth']);

        $normalizeLabel = function ($value, string $fallback) {
            $normalized = is_string($value) ? trim($value) : $value;
            return filled($normalized) ? 
                ucwords(strtolower((string) $normalized)) : $fallback;
        };

        $genderStats = $usersForStats
            ->groupBy(fn($user) => $normalizeLabel($user->gender, 'Unspecified'))
            ->map(fn($group, $label) => [
                'label' => $label,
                'count' => $group->count(),
            ])
            ->values();

        $ageGroupCounts = [
            'Under 18' => 0,
            '18-24' => 0,
            '25-34' => 0,
            '35-44' => 0,
            '45-54' => 0,
            '55+' => 0,
            'Unknown' => 0,
        ];

        foreach ($usersForStats as $user) {
            if (empty($user->date_of_birth)) {
                $ageGroupCounts['Unknown']++;
                continue;
            }

            try {
                $age = Carbon::parse($user->date_of_birth)->age;
            } catch (\Exception $e) {
                $ageGroupCounts['Unknown']++;
                continue;
            }

            if ($age < 18) {
                $ageGroupCounts['Under 18']++;
            } elseif ($age < 25) {
                $ageGroupCounts['18-24']++;
            } elseif ($age < 35) {
                $ageGroupCounts['25-34']++;
            } elseif ($age < 45) {
                $ageGroupCounts['35-44']++;
            } elseif ($age < 55) {
                $ageGroupCounts['45-54']++;
            } else {
                $ageGroupCounts['55+']++;
            }
        }

        $ageGroupStats = collect($ageGroupCounts)
            ->map(fn($count, $label) => [
                'label' => $label,
                'count' => $count,
            ])
            ->values();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => compact('statusCounts', 'rescuesByBuilding', 'rescuerStats', 'recentAlerts', 'userStats', 'dashboardUsers', 'genderStats', 'ageGroupStats')
            ]);
        }

        return Inertia::render('Admin/Dashboard', compact('statusCounts', 'rescuesByBuilding', 'rescuerStats', 'recentAlerts', 'userStats', 'dashboardUsers', 'genderStats', 'ageGroupStats'));
    }

    /**
     * Admin Profile page
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        }

        return Inertia::render('Admin/Profile');
    }

    /**
     * Users management page
     */
    public function users(Request $request)
    {
        $query = User::where('role', '!=', 'admin')
            ->where('role', '!=', 'rescuer');

        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 50));

        $stats = [
            'total' => User::where('role', '!=', 'admin')->where('role', '!=', 'rescuer')->count(),
            'by_role' => [
                'student' => User::where('role', 'student')->count(),
                'faculty' => User::where('role', 'faculty')->count(),
                'staff' => User::where('role', 'staff')->count(),
            ]
        ];

        // Get recent audit trail for users
        $auditTrail = AuditTrail::where('entity_type', 'user')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $users,
                'stats' => $stats,
                'audit_trail' => $auditTrail
            ]);
        }

        return Inertia::render('Admin/Users', compact('users', 'stats', 'auditTrail'));
    }

    /**
     * Rescuers management page
     */
    public function rescuers(Request $request)
    {
        $query = User::where('role', 'rescuer');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $rescuers = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 50));

        // Count rescuers with active rescue assignments
        $activeRescueCount = RescueRequest::whereIn('status', ['assigned', 'in_progress', 'en_route'])
            ->whereNotNull('assigned_rescuer')
            ->distinct('assigned_rescuer')
            ->count('assigned_rescuer');
        
        $allRescuers = User::where('role', 'rescuer')->get();
        
        // Available rescuers have 'available' status AND no active assignments
        $availableCount = $allRescuers->filter(function($rescuer) {
            $hasActiveRescue = RescueRequest::where('assigned_rescuer', $rescuer->id)
                ->whereIn('status', ['assigned', 'in_progress', 'en_route'])
                ->exists();
            return $rescuer->status === 'available' && !$hasActiveRescue;
        })->count();
        
        $counts = [
            'total' => $allRescuers->count(),
            'available' => $availableCount,
            'on_rescue' => $activeRescueCount,
            'off_duty' => $allRescuers->where('status', 'off_duty')->count(),
            'unavailable' => $allRescuers->where('status', 'unavailable')->count(),
            'pending' => $allRescuers->where('status', 'pending')->count(),
        ];

        // Get recent audit trail for rescuers
        $auditTrail = AuditTrail::where('entity_type', 'rescuer')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $rescuers,
                'counts' => $counts,
                'audit_trail' => $auditTrail
            ]);
        }

        return Inertia::render('Admin/Rescuers', compact('rescuers', 'counts', 'auditTrail'));
    }

    /**
     * Buildings management page
     */
    public function buildings(Request $request)
    {
        $buildings = Building::with(['floors.rooms'])->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $buildings
            ]);
        }

        return Inertia::render('Admin/Buildings', compact('buildings'));
    }

    /**
     * Reports page
     */
    public function reports(Request $request)
    {
        $timeFilter = $request->get('time_filter', 'day');
        $statusFilter = $request->get('status_filter', 'all');
        $startDate = $this->getStartDate($timeFilter);

        $query = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])
            ->where('created_at', '>=', $startDate);

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'need_help') {
                $query->where('status', 'pending');
            } elseif ($statusFilter === 'in_progress') {
                $query->whereIn('status', ['accepted', 'in_progress', 'en_route']);
            } elseif ($statusFilter === 'rescued') {
                $query->whereIn('status', ['rescued', 'completed', 'safe']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        $rescueRequests = $query->orderBy('created_at', 'desc')->get();

        // Process for reports
        $reportData = $rescueRequests->map(fn($r) => [
            'id' => $r->id,
            'rescue_code' => $r->rescue_code,
            'name' => $r->firstName ? "{$r->firstName} {$r->lastName}" : ($r->requester ? "{$r->requester->first_name} {$r->requester->last_name}" : 'Anonymous'),
            'time' => $r->created_at->format('h:i A'),
            'date' => $r->created_at->format('M d, Y'),
            'location' => $r->building?->name . ' - ' . $r->floor?->floor_name . ' - ' . $r->room?->room_name,
            'building' => $r->building?->name,
            'status' => $r->status,
            'urgency_level' => $r->urgency_level,
            'rescuer_name' => $r->rescuer ? "{$r->rescuer->first_name} {$r->rescuer->last_name}" : null,
            'completion_photo' => $r->completion_photo,
            'completion_notes' => $r->completion_notes,
            'cancellation_reason' => $r->cancellation_reason,
            'cancelled_at' => $r->cancelled_at?->toISOString(),
        ]);

        // Also fetch false report logs from AuditTrail
        $falseReports = \App\Models\AuditTrail::where('action', 'false_report')
            ->where('created_at', '>=', $startDate)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($a) => [
                'id' => 'fr_' . $a->id,
                'rescue_code' => null,
                'name' => 'False Report',
                'time' => $a->created_at->format('h:i A'),
                'date' => $a->created_at->format('M d, Y'),
                'location' => null,
                'building' => null,
                'status' => 'false_report',
                'urgency_level' => null,
                'rescuer_name' => $a->user ? "{$a->user->first_name} {$a->user->last_name}" : null,
                'completion_photo' => null,
                'completion_notes' => null,
                'cancellation_reason' => $a->description,
                'cancelled_at' => $a->created_at->toISOString(),
            ]);

        $counts = [
            'total' => $rescueRequests->count(),
            'pending' => $rescueRequests->where('status', 'pending')->count(),
            'in_progress' => $rescueRequests->whereIn('status', ['accepted', 'in_progress', 'en_route'])->count(),
            'completed' => $rescueRequests->whereIn('status', ['completed', 'rescued', 'safe'])->count(),
            'cancelled' => $rescueRequests->where('status', 'cancelled')->count(),
            'false_reports' => $falseReports->count(),
        ];

        // Merge false reports into report data when showing all or filtering for false reports
        if ($statusFilter === 'all' || $statusFilter === 'false_report') {
            $reportData = $reportData->concat($falseReports)->sortByDesc('date')->values();
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $reportData,
                'counts' => $counts
            ]);
        }

        return Inertia::render('Admin/Reports', compact('reportData', 'counts'));
    }

    /**
     * Self-Safe Reports - Get all rescue requests that were self-marked as safe with proof
     */
    public function selfSafeReports(Request $request)
    {
        $timeFilter = $request->get('time_filter', 'day');
        $startDate = $this->getStartDate($timeFilter);

        $selfSafeRequests = RescueRequest::with(['building', 'floor', 'room', 'requester'])
            ->whereNotNull('self_marked_safe_at')
            ->where('created_at', '>=', $startDate)
            ->orderBy('self_marked_safe_at', 'desc')
            ->get();

        $data = $selfSafeRequests->map(fn($r) => [
            'id' => $r->id,
            'rescue_code' => $r->rescue_code,
            'user_name' => $r->firstName ? "{$r->firstName} {$r->lastName}" : 
                          ($r->requester ? "{$r->requester->first_name} {$r->requester->last_name}" : 'Anonymous'),
            'location' => $r->building?->name . ' - ' . $r->floor?->floor_name . ' - ' . $r->room?->room_name,
            'safe_proof_photo' => $r->safe_proof_photo,
            'safe_proof_reason' => $r->safe_proof_reason,
            'self_marked_safe_at' => $r->self_marked_safe_at?->toISOString(),
            'created_at' => $r->created_at->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Store a new user
     */
    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑ\s\-\.\,\']+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑ\s\-\.\,\']+$/'],
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:student,faculty,staff',
            'phone' => 'nullable|string|regex:/^09[0-9]{9}$/|size:11',
            'student_id' => 'nullable|string|size:9|regex:/^[0-9]{9}$/',
            'faculty_id' => 'nullable|string|size:9|regex:/^[0-9]{9}$/',
            'staff_id' => 'nullable|string|size:9|regex:/^[0-9]{9}$/',
            'id_number' => 'nullable|string|size:9|regex:/^[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Get the ID number based on role
        $idNumber = $request->student_id ?? $request->faculty_id ?? $request->staff_id ?? $request->id_number;
        
        // Validate ID uniqueness across all ID fields (student_id, faculty_id, staff_id, rescuer_id)
        if ($idNumber) {
            $existingUser = User::where(function($query) use ($idNumber) {
                $query->where('student_id', $idNumber)
                      ->orWhere('faculty_id', $idNumber)
                      ->orWhere('staff_id', $idNumber)
                      ->orWhere('rescuer_id', $idNumber);
            })->first();
            
            if ($existingUser) {
                $idFieldName = $request->role === 'student' ? 'student_id' : 
                              ($request->role === 'faculty' ? 'faculty_id' : 'staff_id');
                return response()->json([
                    'success' => false,
                    'errors' => [$idFieldName => ['This ID is already taken or recorded by another user (student/staff/rescuer).']]
                ], 422);
            }
        }

        // Validate SDCA email domain (only in production)
        if (!AuthController::isValidSdcaEmail($request->email)) {
            return response()->json([
                'success' => false, 
                'errors' => ['email' => ['Only SDCA email addresses (@sdca.edu.ph) are allowed.']]
            ], 422);
        }

        // Generate a random temporary password
        $tempPassword = 'sdca' . rand(1000, 9999);
        
        // In local environment, create user as active for easier testing
        $isLocal = app()->environment('local', 'development');

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role' => $request->role,
            'phone' => $request->phone,
            'student_id' => $request->role === 'student' ? $idNumber : null,
            'faculty_id' => $request->role === 'faculty' ? $idNumber : null,
            'staff_id' => $request->role === 'staff' ? $idNumber : null,
            'password' => Hash::make($tempPassword),
            'status' => $isLocal ? 'active' : 'pending',
            'otp_verified' => $isLocal,
            'force_password_change' => true, // User must change password on first login
        ]);

        // Send OTP email if requested
        if ($request->send_otp) {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->otp_code = $otp;
            $user->otp_expires_at = now()->addMinutes(30); // 30 minutes for initial setup
            $user->save();

            // Send welcome email with OTP
            try {
                \Mail::send([], [], function ($message) use ($user, $otp, $tempPassword) {
                    $message->to($user->email)
                        ->subject('Welcome to PinPointMe - Verify Your Account')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <div style='background: linear-gradient(135deg, #1976D2, #0D47A1); padding: 30px; text-align: center;'>
                                    <h1 style='color: white; margin: 0;'>Welcome to PinPointMe</h1>
                                </div>
                                <div style='padding: 30px; background: #f5f5f5;'>
                                    <h2 style='color: #333;'>Hello {$user->first_name}!</h2>
                                    <p style='color: #666; font-size: 16px;'>Your account has been created. Please verify your email to activate your account.</p>
                                    
                                    <div style='background: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>
                                        <p style='color: #888; margin-bottom: 10px;'>Your verification code is:</p>
                                        <h1 style='color: #1976D2; letter-spacing: 8px; font-size: 36px; margin: 10px 0;'>{$otp}</h1>
                                        <p style='color: #888; font-size: 12px;'>This code expires in 30 minutes</p>
                                    </div>
                                    
                                    <div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                        <p style='color: #e65100; margin: 0;'><strong>Your temporary password:</strong> {$tempPassword}</p>
                                        <p style='color: #666; font-size: 12px; margin: 5px 0 0;'>Please change this after logging in.</p>
                                    </div>
                                    
                                    <p style='color: #888; font-size: 14px;'>If you didn't request this, please ignore this email.</p>
                                </div>
                                <div style='background: #333; padding: 20px; text-align: center;'>
                                    <p style='color: #888; margin: 0; font-size: 12px;'>PinPointMe Emergency Response System</p>
                                </div>
                            </div>
                        ");
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send welcome email: ' . $e->getMessage());
            }
        }

        // Record audit trail
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'description' => "Created user: {$user->first_name} {$user->last_name} (pending verification)",
        ]);

        // In local environment, include temp password in response for testing
        $responseData = ['success' => true, 'data' => $user, 'message' => 'User created successfully.'];
        if (app()->environment('local', 'development')) {
            $responseData['temp_password'] = $tempPassword;
            $responseData['message'] = "User created. Temp password: {$tempPassword}";
        }
        
        return response()->json($responseData);
    }

    /**
     * Update a user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-ZñÑ\s\-\.\,\']+$/'],
            'last_name' => ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-ZñÑ\s\-\.\,\']+$/'],
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:student,faculty,staff,rescuer',
            'status' => 'sometimes|string|in:available,on_rescue,off_duty,unavailable,pending,active,inactive',
            'phone' => 'nullable|string|regex:/^09[0-9]{9}$/|size:11',
            'phone_number' => 'nullable|string|regex:/^09[0-9]{9}$/|size:11',
            'contact_number' => 'nullable|string|regex:/^09[0-9]{9}$/|size:11',
            'student_id' => 'nullable|string|size:9|regex:/^[0-9]{9}$/',
            'faculty_id' => 'nullable|string|size:9|regex:/^[0-9]{9}$/',
            'staff_id' => 'nullable|string|size:9|regex:/^[0-9]{9}$/',
            'rescuer_id' => 'nullable|string|size:9|regex:/^[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Block setting status to on_rescue manually (auto-set only)
        if ($request->status === 'on_rescue' && $user->status !== 'on_rescue') {
            return response()->json([
                'success' => false,
                'errors' => ['status' => ['Cannot manually set status to "On Rescue". This status is set automatically when user accepts a rescue.']]
            ], 422);
        }

        // Handle phone field from different sources (phone, phone_number, contact_number)
        $phoneValue = $request->phone ?? $request->phone_number ?? $request->contact_number ?? $user->phone;
        
        $updateData = $request->only(['first_name', 'last_name', 'email', 'role', 'status']);
        
        // Handle ID fields based on role - clean to only 9 digits and validate uniqueness
        $idFields = ['student_id', 'faculty_id', 'staff_id', 'rescuer_id'];
        foreach ($idFields as $idField) {
            if ($request->has($idField) && $request->$idField) {
                $cleanedId = preg_replace('/\D/', '', $request->$idField);
                $cleanedId = substr($cleanedId, 0, 9);
                
                // Validate cross-table uniqueness (excluding current user)
                $existingUser = User::where('id', '!=', $id)
                    ->where(function($query) use ($cleanedId) {
                        $query->where('student_id', $cleanedId)
                              ->orWhere('faculty_id', $cleanedId)
                              ->orWhere('staff_id', $cleanedId)
                              ->orWhere('rescuer_id', $cleanedId);
                    })->first();
                
                if ($existingUser) {
                    return response()->json([
                        'success' => false,
                        'errors' => [$idField => ['This ID is already taken or recorded by another user (student/staff/rescuer).']]
                    ], 422);
                }
                
                $updateData[$idField] = $cleanedId;
            }
        }
        
        if ($phoneValue !== null) {
            $updateData['phone'] = $phoneValue;
        }
        
        // Track what fields actually changed for audit trail
        $changedFields = [];
        $oldValues = [];
        $newValues = [];
        
        foreach ($updateData as $field => $value) {
            if ($user->$field != $value) {
                $changedFields[] = $field;
                $oldValues[$field] = $user->$field;
                $newValues[$field] = $value;
            }
        }
        
        $user->update($updateData);

        // Build detailed description based on what changed
        $description = $this->buildUpdateDescription($user, $changedFields);

        // Record audit trail
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'entity_type' => $user->role === 'rescuer' ? 'rescuer' : 'user',
            'entity_id' => $user->id,
            'description' => $description,
            'old_values' => !empty($oldValues) ? $oldValues : null,
            'new_values' => !empty($newValues) ? $newValues : null,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['success' => true, 'data' => $user]);
    }

    /**
     * Build a detailed description for user update audit trail
     */
    private function buildUpdateDescription($user, array $changedFields): string
    {
        $name = "{$user->first_name} {$user->last_name}";
        
        if (empty($changedFields)) {
            return "No changes made to {$name}";
        }

        // Group fields into categories for readable descriptions
        $contactFields = ['phone', 'email'];
        $personalFields = ['first_name', 'last_name'];
        $idFields = ['student_id', 'faculty_id', 'staff_id', 'rescuer_id'];
        
        $categories = [];
        
        if (!empty(array_intersect($changedFields, $personalFields))) {
            $categories[] = 'name';
        }
        if (!empty(array_intersect($changedFields, $contactFields))) {
            $categories[] = 'contact info';
        }
        if (in_array('status', $changedFields)) {
            $categories[] = 'status';
        }
        if (in_array('role', $changedFields)) {
            $categories[] = 'role';
        }
        if (!empty(array_intersect($changedFields, $idFields))) {
            $categories[] = 'ID number';
        }
        
        if (empty($categories)) {
            $categories = $changedFields; // fallback to raw field names
        }
        
        return "Updated " . implode(', ', $categories) . " for {$name}";
    }

    /**
     * Delete a user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $name = "{$user->first_name} {$user->last_name}";
        $entityType = $user->role === 'rescuer' ? 'rescuer' : 'user';

        $user->delete();

        // Record audit trail
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'entity_type' => $entityType,
            'entity_id' => $id,
            'description' => "Deleted user: {$name}",
        ]);

        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    }

    /**
     * Store a new rescuer
     */
    public function storeRescuer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Zñ\u00d1\s\-\.\,\']+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Zñ\u00d1\s\-\.\,\']+$/'],
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|regex:/^09[0-9]{9}$/|size:11',
            'rescuer_id' => 'required|string|size:9|regex:/^[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate rescuer_id uniqueness across all ID fields (student_id, faculty_id, staff_id, rescuer_id)
        $rescuerId = $request->rescuer_id;
        $existingUser = User::where(function($query) use ($rescuerId) {
            $query->where('student_id', $rescuerId)
                  ->orWhere('faculty_id', $rescuerId)
                  ->orWhere('staff_id', $rescuerId)
                  ->orWhere('rescuer_id', $rescuerId);
        })->first();
        
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'errors' => ['rescuer_id' => ['This ID is already taken or recorded by another user (student/staff/rescuer).']]
            ], 422);
        }

        // Validate SDCA email domain (only in production)
        if (!AuthController::isValidSdcaEmail($request->email)) {
            return response()->json([
                'success' => false, 
                'errors' => ['email' => ['Only SDCA email addresses (@sdca.edu.ph) are allowed.']]
            ], 422);
        }

        // Generate a random temporary password
        $tempPassword = 'sdca' . rand(1000, 9999);
        
        // In local environment, create rescuer as available for easier testing
        $isLocal = app()->environment('local', 'development');

        $rescuer = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role' => 'rescuer',
            'phone' => $request->phone,
            'rescuer_id' => $rescuerId,
            'password' => Hash::make($tempPassword),
            'status' => $isLocal ? 'available' : 'pending',
            'otp_verified' => $isLocal,
            'force_password_change' => true, // Rescuer must change password on first login
        ]);

        // Send OTP email if requested
        if ($request->send_otp) {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $rescuer->otp_code = $otp;
            $rescuer->otp_expires_at = now()->addMinutes(30); // 30 minutes for initial setup
            $rescuer->save();

            // Send welcome email with OTP
            try {
                \Mail::send([], [], function ($message) use ($rescuer, $otp, $tempPassword) {
                    $message->to($rescuer->email)
                        ->subject('Welcome to PinPointMe - Rescuer Account Verification')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <div style='background: linear-gradient(135deg, #1976D2, #0D47A1); padding: 30px; text-align: center;'>
                                    <h1 style='color: white; margin: 0;'>Welcome to PinPointMe</h1>
                                    <p style='color: rgba(255,255,255,0.8); margin: 5px 0 0;'>Emergency Rescue Team</p>
                                </div>
                                <div style='padding: 30px; background: #f5f5f5;'>
                                    <h2 style='color: #333;'>Hello {$rescuer->first_name}!</h2>
                                    <p style='color: #666; font-size: 16px;'>You have been registered as a <strong>Rescuer</strong> in the PinPointMe Emergency Response System.</p>
                                    <p style='color: #666; font-size: 16px;'>Please verify your email to activate your account.</p>
                                    
                                    <div style='background: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>
                                        <p style='color: #888; margin-bottom: 10px;'>Your verification code is:</p>
                                        <h1 style='color: #1976D2; letter-spacing: 8px; font-size: 36px; margin: 10px 0;'>{$otp}</h1>
                                        <p style='color: #888; font-size: 12px;'>This code expires in 30 minutes</p>
                                    </div>
                                    
                                    <div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                        <p style='color: #e65100; margin: 0;'><strong>Your temporary password:</strong> {$tempPassword}</p>
                                        <p style='color: #666; font-size: 12px; margin: 5px 0 0;'>You will be required to change this after logging in.</p>
                                    </div>
                                    
                                    <div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                        <p style='color: #1565c0; margin: 0;'><strong>As a rescuer, you will:</strong></p>
                                        <ul style='color: #666; margin: 10px 0 0; padding-left: 20px;'>
                                            <li>Receive emergency rescue requests</li>
                                            <li>Respond to users in need of assistance</li>
                                            <li>Coordinate with the emergency response team</li>
                                        </ul>
                                    </div>
                                    
                                    <p style='color: #888; font-size: 14px;'>If you didn't expect this email, please contact the administrator.</p>
                                </div>
                                <div style='padding: 20px; background: #e0e0e0; text-align: center;'>
                                    <p style='color: #666; margin: 0; font-size: 12px;'>&copy; " . date('Y') . " PinPointMe - SDCA Emergency Rescue System</p>
                                </div>
                            </div>
                        ");
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send rescuer welcome email: ' . $e->getMessage());
                // Still create the rescuer but note the email failure
            }
        }

        // Record audit trail
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'entity_type' => 'rescuer',
            'entity_id' => $rescuer->id,
            'description' => "Created rescuer: {$rescuer->first_name} {$rescuer->last_name}",
        ]);

        return response()->json(['success' => true, 'data' => $rescuer]);
    }

    /**
     * Get pending rescuer applications (for admin notifications)
     */
    public function pendingRescuers(Request $request)
    {
        $pendingRescuers = User::where('role', 'rescuer')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($rescuer) {
                $tags = json_decode($rescuer->tags, true) ?? [];
                return [
                    'id' => $rescuer->id,
                    'first_name' => $rescuer->first_name,
                    'last_name' => $rescuer->last_name,
                    'email' => $rescuer->email,
                    'phone' => $rescuer->phone,
                    'organization' => $tags['organization'] ?? null,
                    'otp_verified' => (bool) $rescuer->otp_verified,
                    'email_verified_at' => $rescuer->email_verified_at,
                    'created_at' => $rescuer->created_at->toISOString(),
                    'is_external' => !str_ends_with($rescuer->email, '@sdca.edu.ph'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pendingRescuers,
            'count' => $pendingRescuers->count(),
        ]);
    }

    /**
     * Approve a pending rescuer application
     */
    public function approveRescuer(Request $request, $id)
    {
        $rescuer = User::where('id', $id)
            ->where('role', 'rescuer')
            ->where('status', 'pending')
            ->firstOrFail();

        $rescuer->update([
            'status' => 'available',
            'is_able_to_login' => true,
        ]);

        // Record audit trail
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'entity_type' => 'rescuer',
            'entity_id' => $rescuer->id,
            'description' => "Approved rescuer application: {$rescuer->first_name} {$rescuer->last_name}",
        ]);

        // Send approval notification email
        try {
            $firstName = $rescuer->first_name;
            $email = $rescuer->email;
            \Mail::send([], [], function ($message) use ($email, $firstName) {
                $message->to($email)
                    ->subject('PinPointMe - Rescuer Application Approved!')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%); padding: 30px; text-align: center;'>
                                <h1 style='color: white; margin: 0; font-size: 28px;'>Application Approved!</h1>
                                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;'>Welcome to the PinPointMe Rescue Team</p>
                            </div>
                            <div style='padding: 30px; background-color: #f8f9fa;'>
                                <h2 style='color: #333; margin-bottom: 20px;'>Congratulations, {$firstName}!</h2>
                                <p style='color: #666; font-size: 16px; line-height: 1.6;'>Your rescuer account has been reviewed and <strong style='color: #2E7D32;'>approved</strong> by an administrator.</p>
                                <p style='color: #666; font-size: 16px; line-height: 1.6;'>You now have full access to the PinPointMe rescue system. You can:</p>
                                <ul style='color: #666; font-size: 15px; line-height: 2;'>
                                    <li>Receive and respond to rescue requests</li>
                                    <li>View rescue locations on the map</li>
                                    <li>Communicate with users in need</li>
                                    <li>Access the full rescuer dashboard</li>
                                </ul>
                                <div style='background: #e8f5e9; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2E7D32;'>
                                    <p style='color: #2E7D32; margin: 0; font-size: 14px;'>
                                        <strong>You can now log in</strong> with your email and the temporary password sent during registration.
                                    </p>
                                </div>
                            </div>
                            <div style='background: #333; padding: 20px; text-align: center;'>
                                <p style='color: #ccc; margin: 0; font-size: 14px;'>PinPointMe Emergency Rescue System</p>
                            </div>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send rescuer approval email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => "Responder {$rescuer->first_name} {$rescuer->last_name} has been approved.",
            'data' => $rescuer,
        ]);
    }

    /**
     * Decline a pending rescuer application
     */
    public function declineRescuer(Request $request, $id)
    {
        $rescuer = User::where('id', $id)
            ->where('role', 'rescuer')
            ->where('status', 'pending')
            ->firstOrFail();

        $reason = $request->input('reason', 'Your application did not meet our requirements at this time.');

        // Record audit trail before deletion
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'entity_type' => 'rescuer',
            'entity_id' => $rescuer->id,
            'description' => "Declined rescuer application: {$rescuer->first_name} {$rescuer->last_name}. Reason: {$reason}",
        ]);

        // Send decline notification email
        try {
            $firstName = $rescuer->first_name;
            $email = $rescuer->email;
            \Mail::send([], [], function ($message) use ($email, $firstName, $reason) {
                $message->to($email)
                    ->subject('PinPointMe - Rescuer Application Update')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background: linear-gradient(135deg, #E65100 0%, #BF360C 100%); padding: 30px; text-align: center;'>
                                <h1 style='color: white; margin: 0; font-size: 28px;'>Application Update</h1>
                                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;'>PinPointMe Rescue Team</p>
                            </div>
                            <div style='padding: 30px; background-color: #f8f9fa;'>
                                <h2 style='color: #333; margin-bottom: 20px;'>Hi {$firstName},</h2>
                                <p style='color: #666; font-size: 16px; line-height: 1.6;'>Thank you for your interest in joining the PinPointMe rescue team. After review, we were unable to approve your application at this time.</p>
                                <div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #E65100;'>
                                    <p style='color: #E65100; margin: 0; font-size: 14px;'>
                                        <strong>Reason:</strong> {$reason}
                                    </p>
                                </div>
                                <p style='color: #666; font-size: 16px; line-height: 1.6;'>If you believe this was in error or have additional information to provide, please contact the administrator.</p>
                            </div>
                            <div style='background: #333; padding: 20px; text-align: center;'>
                                <p style='color: #ccc; margin: 0; font-size: 14px;'>PinPointMe Emergency Rescue System</p>
                            </div>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send rescuer decline email: ' . $e->getMessage());
        }

        // Mark as declined instead of deleting — rescuer will see the decline reason on login
        $rescuer->update([
            'status' => 'declined',
            'tags' => json_encode(array_merge(
                json_decode($rescuer->tags ?? '{}', true) ?: [],
                ['decline_reason' => $reason, 'declined_at' => now()->toDateTimeString()]
            )),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Rescuer application has been declined and the applicant has been notified.",
        ]);
    }

    /**
     * Helper to get start date based on time filter
     */
    private function getStartDate($timeFilter)
    {
        return match($timeFilter) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfWeek(),
        };
    }

    /**
     * Feedback & Ratings page
     */
    public function feedbacks(Request $request)
    {
        return Inertia::render('Admin/Feedbacks');
    }

    /**
     * False Alarm Reports page
     */
    public function falseReports(Request $request)
    {
        $timeFilter = $request->get('time_filter', 'all');
        
        $query = AuditTrail::where('action', 'false_report')->orderBy('created_at', 'desc');
        
        if ($timeFilter !== 'all') {
            $startDate = $this->getStartDate($timeFilter);
            $query->where('created_at', '>=', $startDate);
        }

        $reports = $query->get()->map(function ($audit) {
            return [
                'id' => $audit->id,
                'description' => $audit->description,
                'details' => $audit->details,
                'initiator' => $audit->initiator,
                'ip_address' => $audit->ip_address,
                'created_at' => $audit->created_at->toISOString(),
                'user' => $audit->user ? [
                    'id' => $audit->user->id,
                    'first_name' => $audit->user->first_name,
                    'last_name' => $audit->user->last_name,
                ] : null,
            ];
        });

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $reports,
            ]);
        }

        return Inertia::render('Admin/FalseReports', [
            'falseReports' => $reports,
        ]);
    }
}
