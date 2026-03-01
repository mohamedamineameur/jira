<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(string $to, string $subject, string $htmlView, array $data = [], ?string $textView = null): void
    {
        $views = $textView === null ? $htmlView : [
            'html' => $htmlView,
            'text' => $textView,
        ];

        try {
            Mail::send($views, $data, static function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
                $message->from((string) config('mail.from.address'), 'Agilify');
            });

            Log::info('Email sent successfully.', [
                'to' => $to,
                'subject' => $subject,
                'html_view' => $htmlView,
                'text_view' => $textView,
            ]);
        } catch (Throwable $exception) {
            Log::error('Email sending failed.', [
                'to' => $to,
                'subject' => $subject,
                'html_view' => $htmlView,
                'text_view' => $textView,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendThemed(string $to, string $subject, array $data = []): void
    {
        $defaults = [
            'navItems' => [],
            'heroTitle' => $subject,
            'heroText' => null,
            'otpCode' => null,
            'otpCopyText' => 'Copy code',
            'otpCopyHint' => 'Tip: press and hold the code to copy it.',
            'buttonText' => null,
            'buttonUrl' => null,
            'cards' => [],
            'footerText' => 'Agilify',
        ];

        $this->send(
            $to,
            $subject,
            'emails.generic',
            array_merge($defaults, $data),
            'emails.generic-text'
        );
    }
}
