<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class EmailVerificationService
{
    public function __construct(private readonly EmailService $emailService) {}

    public function sendVerificationEmail(User $user): void
    {
        if ($user->email_verified) {
            return;
        }

        $expiresInMinutes = (int) env('EMAIL_VERIFICATION_EXPIRE_MINUTES', 60);
        $expiresAt = now()->addMinutes($expiresInMinutes);
        $plainToken = Str::random(64);

        $user->token_hash = Hash::make($plainToken);
        $user->email_verification_expires_at = $expiresAt;
        $user->save();

        $verificationUrl = URL::temporarySignedRoute(
            'email.verify',
            $expiresAt,
            [
                'user' => $user->id,
                'token' => $plainToken,
            ]
        );

        $this->emailService->sendThemed(
            $user->email,
            'Verify your email address',
            [
                'heroTitle' => 'Verify your email address',
                'heroText' => 'Please confirm your account by clicking the button below.',
                'buttonText' => 'Verify Email',
                'buttonUrl' => $verificationUrl,
                'cards' => [
                    [
                        'title' => 'Security',
                        'text' => 'This link is unique and can be used only for your account.',
                    ],
                    [
                        'title' => 'Expiration',
                        'text' => "The verification link expires in {$expiresInMinutes} minutes.",
                    ],
                ],
                'footerText' => 'Agilify - Email Verification',
            ]
        );
    }
}
