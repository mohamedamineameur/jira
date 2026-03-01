<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserSession;
use App\Policies\AdminPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\UserPolicy;
use App\Policies\UserSessionPolicy;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Admin::class, AdminPolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(UserSession::class, UserSessionPolicy::class);

        Event::listen(MessageSending::class, function (MessageSending $event): void {
            $message = $event->message;

            if (! $message instanceof Email) {
                Log::channel('mail')->info('Mailer sending (non-email message).');

                return;
            }

            Log::channel('mail')->info('Mailer sending.', [
                'to' => $this->formatAddresses($message->getTo()),
                'cc' => $this->formatAddresses($message->getCc()),
                'bcc' => $this->formatAddresses($message->getBcc()),
                'subject' => $message->getSubject(),
            ]);
        });

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $message = $event->sent->getOriginalMessage();

            if (! $message instanceof Email) {
                Log::channel('mail')->info('Mailer sent (non-email message).');

                return;
            }

            Log::channel('mail')->info('Mailer sent.', [
                'to' => $this->formatAddresses($message->getTo()),
                'cc' => $this->formatAddresses($message->getCc()),
                'bcc' => $this->formatAddresses($message->getBcc()),
                'subject' => $message->getSubject(),
                'message_id' => $message->getHeaders()->get('Message-ID')?->getBodyAsString(),
            ]);
        });
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, string>
     */
    private function formatAddresses(array $addresses): array
    {
        return array_map(
            static fn (Address $address): string => $address->toString(),
            $addresses
        );
    }
}
