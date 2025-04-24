<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate; // Add this import
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // User permissions
       // In AuthServiceProvider.php
Gate::define('create-users', function ($user) {
    return $user->role_id == 1; // ONLY role_id=1 can create users (changed from != to ==)
});

Gate::define('update-users', function ($user) {
    return $user->role_id == 1; // Only role_id=1 can update users
});

Gate::define('delete-users', function ($user) {
    return $user->role_id == 1; // Only role_id=1 can delete users
});
    }
}