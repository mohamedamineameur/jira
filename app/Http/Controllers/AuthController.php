<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\UserSession;
use App\Services\SessionTokenService;
use App\Services\UserSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends Controller
{
    public function __construct(
        private readonly UserSessionService $userSessionService,
        private readonly SessionTokenService $sessionTokenService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::query()
            ->where('email', $request->validated()['email'])
            ->first();

        if (! $user || ! Hash::check($request->validated()['password'], $user->password_hash)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (! $user->is_active || $user->is_deleted) {
            return response()->json([
                'message' => 'Account is disabled.',
            ], 403);
        }

        $sessionSecret = bin2hex(random_bytes(32));
        $session = $this->userSessionService->create([
            'user_id' => $user->id,
            'token' => $sessionSecret,
            'ip' => $request->ip(),
            'agent' => $request->userAgent(),
        ]);

        $cookieToken = $this->sessionTokenService->createToken($session->id, $sessionSecret);

        return response()->json([
            'message' => 'Logged in successfully.',
            'data' => [
                'user' => $user,
                'session_id' => $session->id,
            ],
        ])->cookie($this->sessionCookie($cookieToken));
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var UserSession|null $session */
        $session = $request->attributes->get('current_session');
        if (! $session && $request->user()) {
            $session = UserSession::query()
                ->where('user_id', $request->user()->id)
                ->whereNull('deleted_at')
                ->latest('created_at')
                ->first();
        }

        if ($session instanceof UserSession) {
            $this->userSessionService->revoke($session);
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ])->withoutCookie('session_token');
    }

    private function sessionCookie(string $cookieToken): Cookie
    {
        return cookie(
            'session_token',
            $cookieToken,
            60 * 24 * 7,
            '/',
            null,
            false,
            true,
            false,
            'strict',
        );
    }
}
