<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProfileCompletion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (in_array($user->role, ['admin', 'rescuer'], true)) {
            return $next($request);
        }

        if (!\Schema::hasColumn('users', 'must_update_profile')) {
            return $next($request);
        }

        $allowedPaths = [
            'user/profile',
            'user/profile/*',
            'api/user',
            'api/users/*',
            'logout',
            'change-password',
            'terms-acceptance',
            'storage/*',
            'build/*',
            'images/*',
            'firebase-messaging-sw.js',
            'service-worker.js',
            'robots.txt',
            'hot',
        ];

        foreach ($allowedPaths as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        $requiredFields = [
            'first_name',
            'last_name',
            'phone_number',
            'emergency_contact_name',
            'emergency_contact_phone',
            'allergies',
            'gender',
            'date_of_birth',
        ];

        $isIncomplete = $user->must_update_profile;

        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                $isIncomplete = true;
                break;
            }
        }

        if (!$isIncomplete && empty($user->id_number)) {
            $isIncomplete = true;
        }

        if ($isIncomplete) {
            return redirect('/user/profile');
        }

        return $next($request);
    }
}
