<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * Handle an incoming request.
     *
     * Blocks access to main features if user profile is not completed.
     * Only allows access to profile completion and user info endpoints.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_profile_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete your profile first',
                'error_code' => 'PROFILE_INCOMPLETE',
            ], 403);
        }

        return $next($request);
    }
}
