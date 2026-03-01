<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordApiRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwordResetService) {}

    public function requestLink(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->sendResetLink($request->validated()['email']);

        return response()->json([
            'message' => 'If this email exists, a password reset link has been sent.',
        ]);
    }

    public function resetApi(ResetPasswordApiRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $isReset = $this->passwordResetService->resetPasswordByEmail(
            $validated['email'],
            $validated['token'],
            $validated['password']
        );

        if (! $isReset) {
            return response()->json([
                'message' => 'Invalid or expired password reset token.',
            ], 422);
        }

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ]);
    }

    public function show(Request $request, User $user): View
    {
        if (! $request->hasValidSignature()) {
            return view('password-reset', [
                'status' => 'expired',
                'title' => 'Reset link expired',
                'message' => 'This password reset link is no longer valid. Please request a new one.',
            ]);
        }

        $token = $request->query('token');

        if (! is_string($token) || $token === '') {
            return view('password-reset', [
                'status' => 'error',
                'title' => 'Reset error',
                'message' => 'The reset token is missing or invalid.',
            ]);
        }

        if (! $this->passwordResetService->tokenIsValid($user, $token)) {
            return view('password-reset', [
                'status' => 'expired',
                'title' => 'Reset link expired',
                'message' => 'This password reset token is invalid or expired. Please request a new one.',
            ]);
        }

        return view('password-reset', [
            'status' => 'form',
            'title' => 'Reset your password',
            'message' => 'Enter your new password below.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function reset(ResetPasswordRequest $request, User $user): View
    {
        $validated = $request->validated();
        $token = $validated['token'];

        if (! is_string($token) || ! $this->passwordResetService->resetPassword($user, $token, $validated['password'])) {
            return view('password-reset', [
                'status' => 'expired',
                'title' => 'Reset link expired',
                'message' => 'This password reset token is invalid or expired. Please request a new one.',
            ]);
        }

        return view('password-reset', [
            'status' => 'success',
            'title' => 'Password updated',
            'message' => 'Your password has been reset successfully. You can now log in.',
        ]);
    }
}
