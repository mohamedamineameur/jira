<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use App\Services\SessionTokenService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    public function __construct(private readonly SessionTokenService $sessionTokenService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        $rawCookieToken = $request->cookie('session_token');
        if (! is_string($rawCookieToken) || $rawCookieToken === '') {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $payload = $this->sessionTokenService->parseToken($rawCookieToken);
        if ($payload === null) {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $session = UserSession::query()
            ->with('user')
            ->where('id', $payload['sid'])
            ->whereNull('deleted_at')
            ->first();

        if (! $session || ! Hash::check($payload['sec'], $session->token_hash)) {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = $session->user;
        if (! $user || $user->is_deleted || ! $user->is_active) {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $session->last_used_at = now();
        $session->save();

        $request->attributes->set('current_session', $session);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
