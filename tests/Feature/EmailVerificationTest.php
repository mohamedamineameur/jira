<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_link_marks_user_as_verified_and_shows_success_page(): void
    {
        $user = User::factory()->create([
            'email_verified' => false,
            'token_hash' => Hash::make('valid-token'),
            'email_verification_expires_at' => now()->addHour(),
        ]);

        $url = URL::temporarySignedRoute('email.verify', now()->addMinutes(30), [
            'user' => $user->id,
            'token' => 'valid-token',
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSeeText('Email verified')
            ->assertSeeText('Your email has been successfully verified.');

        $user->refresh();
        $this->assertTrue($user->email_verified);
        $this->assertNull($user->token_hash);
        $this->assertNull($user->email_verification_expires_at);
    }

    public function test_verification_link_with_invalid_token_shows_error_page(): void
    {
        $user = User::factory()->create([
            'email_verified' => false,
            'token_hash' => Hash::make('expected-token'),
            'email_verification_expires_at' => now()->addHour(),
        ]);

        $url = URL::temporarySignedRoute('email.verify', now()->addMinutes(30), [
            'user' => $user->id,
            'token' => 'wrong-token',
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSeeText('Verification error')
            ->assertSeeText('The verification token is invalid.');
    }

    public function test_already_verified_user_sees_expired_message(): void
    {
        $user = User::factory()->create([
            'email_verified' => true,
            'token_hash' => Hash::make('any-token'),
            'email_verification_expires_at' => now()->addHour(),
        ]);

        $url = URL::temporarySignedRoute('email.verify', now()->addMinutes(30), [
            'user' => $user->id,
            'token' => 'any-token',
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSeeText('Verification link expired')
            ->assertSeeText('This verification link has expired because this email is already verified.');
    }
}
