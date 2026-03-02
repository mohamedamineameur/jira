<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyLoginOtpRequest;
use App\Models\User;
use App\Models\UserSession;
use App\Services\EmailService;
use App\Services\SessionTokenService;
use App\Services\UserSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends Controller
{
    public function __construct(
        private readonly UserSessionService $userSessionService,
        private readonly SessionTokenService $sessionTokenService,
        private readonly EmailService $emailService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $ip = (string) ($request->ip() ?? 'unknown');
        $throttleKey = "login:ip:{$ip}";

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $retryAfterSeconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => 'Too many login attempts. Try again later.',
                'retry_after_seconds' => $retryAfterSeconds,
            ], 429);
        }

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $request->validated()['email'])
            ->first();

        if (! $user || ! Hash::check($request->validated()['password'], $user->password_hash)) {
            RateLimiter::hit($throttleKey, 300);

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (! $user->is_active || $user->is_deleted) {
            return response()->json([
                'message' => 'Account is disabled.',
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        $otpCode = (string) random_int(100000, 999999);
        $otpExpiresAt = now()->addMinutes(10);

        if (is_string($user->otp_hash)) {
            RateLimiter::clear($this->otpThrottleKey($user));
        }

        $user->otp_hash = Hash::make($otpCode);
        $user->otp_expires_at = $otpExpiresAt;
        $user->save();

        $this->emailService->sendThemed(
            $user->email,
            'Your Agilify login code',
            [
                'heroTitle' => 'Your login OTP code',
                'heroText' => 'Use this 6-digit code to complete your login.',
                'otpCode' => $otpCode,
                'otpCopyText' => 'Copy code',
                'otpCopyHint' => 'On mobile, press and hold the code to copy it.',
                'cards' => [
                    [
                        'title' => 'Expiration',
                        'text' => 'This code expires in 10 minutes.',
                    ],
                    [
                        'title' => 'Security',
                        'text' => 'Never share this code with anyone.',
                    ],
                ],
                'footerText' => 'Agilify - Login Security',
            ]
        );

        return response()->json([
            'message' => 'OTP sent successfully.',
            'data' => [
                'email' => $user->email,
                'otp_expires_at' => $otpExpiresAt->toIso8601String(),
            ],
        ]);
    }

    public function verifyOtp(VerifyLoginOtpRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::query()
            ->where('email', $request->validated()['email'])
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid OTP or email.',
            ], 401);
        }

        $otp = $request->validated()['otp'];

        if (! is_string($user->otp_hash) || $user->otp_expires_at === null) {
            return response()->json([
                'message' => 'Invalid OTP or email.',
            ], 401);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            RateLimiter::clear($this->otpThrottleKey($user));
            $this->invalidateOtp($user);

            return response()->json([
                'message' => 'Invalid OTP or email.',
            ], 401);
        }

        $otpThrottleKey = $this->otpThrottleKey($user);
        $otpDecaySeconds = 600;

        if (! Hash::check($otp, $user->otp_hash)) {
            RateLimiter::hit($otpThrottleKey, $otpDecaySeconds);

            if (RateLimiter::attempts($otpThrottleKey) >= 3) {
                RateLimiter::clear($otpThrottleKey);
                $this->invalidateOtp($user);

                return response()->json([
                    'message' => 'OTP has been invalidated. Request a new code.',
                ], 401);
            }

            return response()->json([
                'message' => 'Invalid OTP or email.',
            ], 401);
        }

        RateLimiter::clear($otpThrottleKey);

        $sessionSecret = bin2hex(random_bytes(32));
        $session = $this->userSessionService->create([
            'user_id' => $user->id,
            'token' => $sessionSecret,
            'ip' => $request->ip(),
            'agent' => $request->userAgent(),
        ]);

        $this->invalidateOtp($user);

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

    private function otpThrottleKey(User $user): string
    {
        $hash = is_string($user->otp_hash) ? $user->otp_hash : 'none';

        return "login_otp:{$user->id}:{$hash}";
    }

    private function invalidateOtp(User $user): void
    {
        $user->otp_hash = null;
        $user->otp_expires_at = null;
        $user->save();
    }
}
