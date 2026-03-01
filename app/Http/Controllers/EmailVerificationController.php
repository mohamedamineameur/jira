<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, User $user): View
    {
        if ($user->email_verified) {
            return view('email-verification-result', [
                'status' => 'expired',
                'title' => 'Verification link expired',
                'message' => 'This verification link has expired because this email is already verified.',
            ]);
        }

        if (! $request->hasValidSignature()) {
            return view('email-verification-result', [
                'status' => 'expired',
                'title' => 'Verification link expired',
                'message' => 'This verification link is no longer valid. Please request a new one.',
            ]);
        }

        $token = $request->query('token');
        if (! is_string($token) || $token === '') {
            return view('email-verification-result', [
                'status' => 'error',
                'title' => 'Verification error',
                'message' => 'The verification token is missing or invalid.',
            ]);
        }

        if (! is_string($user->token_hash) || ! Hash::check($token, $user->token_hash)) {
            return view('email-verification-result', [
                'status' => 'error',
                'title' => 'Verification error',
                'message' => 'The verification token is invalid.',
            ]);
        }

        if (
            $user->email_verification_expires_at !== null
            && now()->greaterThan($user->email_verification_expires_at)
        ) {
            return view('email-verification-result', [
                'status' => 'expired',
                'title' => 'Verification link expired',
                'message' => 'This verification link has expired. Please request a new one.',
            ]);
        }

        $user->email_verified = true;
        $user->token_hash = null;
        $user->email_verification_expires_at = null;
        $user->save();

        return view('email-verification-result', [
            'status' => 'success',
            'title' => 'Email verified',
            'message' => 'Your email has been successfully verified.',
        ]);
    }
}
