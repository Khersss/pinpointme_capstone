<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     * Block inactive users from accessing any part of the application.
     * Forces logout and redirects to login with an error message.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status === 'inactive') {
            // Allow access to logout route so the user can be properly logged out
            $allowedPaths = ['/logout', '/api/auth/logout'];
            if (in_array('/' . ltrim($request->path(), '/'), $allowedPaths)) {
                return $next($request);
            }

            // For API requests (mobile app), return 403 JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                // Revoke all tokens to force mobile app logout
                $user->tokens()->delete();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Your account has been deactivated. Please contact the administrator.',
                    'account_inactive' => true,
                ], 403);
            }

            // For web requests, logout and redirect to login page
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }

        return $next($request);
    }
}
