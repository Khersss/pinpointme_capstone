<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTermsAcceptance
{
    /**
     * Current terms version - update this when terms change significantly
     */
    const CURRENT_TERMS_VERSION = '1.0';

    /**
     * Routes that are allowed even without terms acceptance
     */
    protected $allowedRoutes = [
        'terms-acceptance',
        'logout',
        'change-password',
    ];

    /**
     * Path patterns that are allowed even without terms acceptance
     */
    protected $allowedPaths = [
        '/terms-acceptance',
        '/logout',
        '/change-password',
        '/api/accept-terms',
        '/api/auth/',
    ];

    /**
     * Handle an incoming request.
     * Redirect users who haven't accepted the terms.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // If user is logged in and hasn't accepted terms (or accepted an outdated version)
        if ($user && !$this->hasAcceptedCurrentTerms($user)) {
            // Allow access to permitted routes
            if ($this->isAllowedRoute($request)) {
                return $next($request);
            }

            // For API requests, return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terms and Conditions acceptance required.',
                    'requires_terms_acceptance' => true,
                    'redirect' => '/terms-acceptance'
                ], 403);
            }

            // Redirect to terms acceptance page
            return redirect()->route('terms-acceptance');
        }
        
        return $next($request);
    }

    /**
     * Check if user has accepted the current version of terms
     */
    protected function hasAcceptedCurrentTerms($user): bool
    {
        // If no acceptance timestamp, terms not accepted
        if (!$user->terms_accepted_at) {
            return false;
        }

        // If we have version tracking, check the version
        if ($user->terms_accepted_version) {
            return version_compare($user->terms_accepted_version, self::CURRENT_TERMS_VERSION, '>=');
        }

        // If we only have timestamp (legacy), consider it accepted
        return true;
    }

    /**
     * Check if current route/path is allowed without terms acceptance
     */
    protected function isAllowedRoute(Request $request): bool
    {
        // Check named routes
        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, $this->allowedRoutes)) {
            return true;
        }

        // Check path patterns
        $path = '/' . ltrim($request->path(), '/');
        foreach ($this->allowedPaths as $allowedPath) {
            if (str_starts_with($path, $allowedPath)) {
                return true;
            }
        }

        return false;
    }
}
