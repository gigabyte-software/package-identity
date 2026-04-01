<?php

namespace Gigabyte\Identity\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken && $token->last_used_at instanceof Carbon) {
            $maxDays = (int) config('identity.token_inactivity_days', 30);

            if ($token->last_used_at->lt(now()->subDays($maxDays))) {
                $token->delete();

                return response()->json(['message' => 'Token expired due to inactivity.'], 401);
            }
        }

        return $next($request);
    }
}
