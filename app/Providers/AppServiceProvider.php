<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserSession;
use App\Policies\AdminPolicy;
use App\Policies\UserPolicy;
use App\Policies\UserSessionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(UserSession::class, UserSessionPolicy::class);
    }
}
