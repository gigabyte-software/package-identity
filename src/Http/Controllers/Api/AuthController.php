<?php

namespace Gigabyte\Identity\Http\Controllers\Api;

use Gigabyte\Identity\Http\Requests\LoginRequest;
use Gigabyte\Identity\Http\Requests\VerifyCodeRequest;
use Gigabyte\Identity\Services\IdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private readonly IdentityService $identity,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        Log::info('API login attempted', ['email' => $request->email]);

        $user = $this->identity->resolveUser($request->email);

        if ($user) {
            $this->identity->sendLoginCode($user);
        }

        $this->identity->clearAttempts($request->email);

        return response()->json([
            'message' => 'If an account exists, a login code has been sent.',
        ]);
    }

    public function verify(VerifyCodeRequest $request): JsonResponse
    {
        $email = $request->email;

        if ($this->identity->hasExceededAttempts($email)) {
            $this->identity->deleteCodesForEmail($email);
            $this->identity->clearAttempts($email);

            return response()->json([
                'message' => 'Too many failed attempts. Please request a new code.',
            ], 422);
        }

        $user = $this->identity->verifyCode($email, $request->code);

        if (! $user) {
            $this->identity->recordFailedAttempt($email);

            return response()->json([
                'message' => 'The provided code is invalid.',
            ], 422);
        }

        $this->identity->clearAttempts($email);

        Log::info('API login successful via code', ['email' => $user->email]);

        $tokenName = config('identity.token_name', 'api-token');
        $accessToken = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $accessToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function demoLogin(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = $this->identity->demoResolveUser($request->email);

        if (! $user) {
            return response()->json([
                'message' => 'Demo login is not available.',
            ], 403);
        }

        Log::info('API login successful via demo', ['email' => $user->email]);

        $tokenName = config('identity.token_name', 'api-token');
        $accessToken = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $accessToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
