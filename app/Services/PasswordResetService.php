<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Password;

class PasswordResetService
{
    public function __construct(private readonly EmailService $emailService) {}

    public function sendResetLink(string $email): void
    {
        $user = User::query()
            ->where('email', $email)
            ->where('is_deleted', false)
            ->first();

        if (! $user instanceof User) {
            return;
        }

        $token = Password::broker()->createToken($user);
        $expiresInMinutes = (int) config('auth.passwords.users.expire', 60);
        $appUrl = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');
        $resetUrl = $appUrl.'/#/reset-password?email='.urlencode($user->email).'&token='.urlencode($token);

        $this->emailService->sendThemed(
            $user->email,
            'Reset your password',
            [
                'heroTitle' => 'Reset your password',
                'heroText' => 'We received a request to reset your password. Click the button below to continue.',
                'buttonText' => 'Reset Password',
                'buttonUrl' => $resetUrl,
                'cards' => [
                    [
                        'title' => 'Security',
                        'text' => 'If you did not request this, you can ignore this email.',
                    ],
                    [
                        'title' => 'Expiration',
                        'text' => "This reset link expires in {$expiresInMinutes} minutes.",
                    ],
                ],
                'footerText' => 'Agilify - Password Reset',
            ]
        );
    }

    public function tokenIsValid(User $user, string $token): bool
    {
        return Password::broker()->tokenExists($user, $token);
    }

    public function resetPassword(User $user, string $token, string $newPassword): bool
    {
        if (! $this->tokenIsValid($user, $token)) {
            return false;
        }

        $user->password_hash = $newPassword;
        $user->save();

        Password::broker()->deleteToken($user);

        return true;
    }

    public function resetPasswordByEmail(string $email, string $token, string $newPassword): bool
    {
        $status = Password::broker()->reset(
            [
                'email' => $email,
                'token' => $token,
                'password' => $newPassword,
            ],
            static function (User $user, string $password): void {
                $user->password_hash = $password;
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET;
    }
}
