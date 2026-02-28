<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:create-admin {email?} {--name=} {--password=}', function (): int {
    $email = $this->argument('email') ?? $this->ask('Email de l\'admin');
    if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Email invalide.');

        return 1;
    }

    $name = $this->option('name');
    if (! is_string($name) || trim($name) === '') {
        $name = $this->ask('Nom de l\'admin', 'Admin');
    }

    $password = $this->option('password');
    $passwordFromOption = is_string($password) && trim($password) !== '';
    $passwordGenerated = false;
    if (! $passwordFromOption) {
        $password = Str::random(20);
        $passwordGenerated = true;
    }

    $user = User::query()->where('email', strtolower($email))->first();

    if (! $user) {
        $user = User::query()->create([
            'name' => $name,
            'email' => strtolower($email),
            'password_hash' => $password,
            'is_active' => true,
            'email_verified' => true,
            'is_deleted' => false,
            'deleted_at' => null,
        ]);
        $this->info('Utilisateur créé.');
    } else {
        $user->name = $name;
        $user->is_active = true;
        $user->is_deleted = false;
        $user->deleted_at = null;
        $user->save();

        if ($passwordFromOption || $this->confirm('Mettre à jour le mot de passe ?', false)) {
            $user->password_hash = $password;
            $user->save();
            $this->info('Mot de passe mis à jour.');
            $passwordGenerated = ! $passwordFromOption;
        } else {
            $passwordGenerated = false;
        }
    }

    $admin = Admin::query()->firstOrNew([
        'user_id' => $user->id,
    ]);
    $admin->is_active = true;
    $admin->save();

    $this->info("Admin prêt: {$user->email}");
    $this->line("User ID: {$user->id}");
    $this->line("Admin ID: {$admin->id}");

    if ($passwordGenerated) {
        $this->warn("Mot de passe: {$password}");
    }

    return 0;
})->purpose('Créer ou activer un admin');
