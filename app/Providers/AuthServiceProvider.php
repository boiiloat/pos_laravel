<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
            // 'App\Models\Model' => 'App\Models\ModelPolicy',
        Table::class => TablePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // User permissions
        Gate::define('create-users', function ($user) {
            return $user->role_id == 1; // Admin only
        });

        Gate::define('update-users', function ($user) {
            return $user->role_id == 1; // Admin only
        });

        Gate::define('delete-users', function ($user) {
            return $user->role_id == 1; // Admin only
        });

        // Category permissions (action-based)
        Gate::define('create-categories', function ($user) {
            return $user->role_id == 1 || $user->role_id == 2; // Admin and Cashier
        });

        Gate::define('update-categories', function ($user) {
            return $user->role_id == 1 || $user->role_id == 2; // Admin and Cashier
        });

        Gate::define('delete-categories', function ($user) {
            return $user->role_id == 1; // Admin only
        });

        // Category permissions (model-based - for use with Gate::denies('update', $category))
        Gate::define('update', function ($user, Category $category) {
            return $user->role_id == 1 || $user->role_id == 2; // Admin and Cashier
        });

        Gate::define('delete', function ($user, Category $category) {
            return $user->role_id == 1; // Admin only
        });

        Gate::define('create', function ($user, $modelClass) {
            if ($modelClass === Category::class || $modelClass === 'App\Models\Category') {
                return $user->role_id == 1 || $user->role_id == 2; // Admin and Cashier
            }
            return false;
        });

        // Role permissions (if needed)
        Gate::define('view-roles', function ($user) {
            return $user->role_id == 1; // Admin only
        });

        Gate::define('create-roles', function ($user) {
            return $user->role_id == 1; // Admin only
        });

        Gate::define('update-roles', function ($user) {
            return $user->role_id == 1; // Admin only
        });

        Gate::define('delete-roles', function ($user) {
            return $user->role_id == 1; // Admin only
        });



        // Product permissions
        // Add this inside the boot() method

        Gate::define('patch-products', function ($user) {
            return $user->role_id == 1 || $user->role_id == 2; // Same as update-products
        });
        Gate::define('create-products', function ($user) {
            return $user->role_id == 1 || $user->role_id == 2; // Admin and Cashier
        });

        Gate::define('update-products', function ($user) {
            return $user->role_id == 1 || $user->role_id == 2; // Admin and Cashier
        });

        Gate::define('delete-products', function ($user) {
            return $user->role_id == 1; // Admin only
        });


        Gate::define('manage-tables', function ($user) {
            return $user->role_id === 1; // Only admin can manage tables
        });


        Gate::define('manage-tables', function ($user) {
            return $user->role_id === 1; // Only admin can manage tables
        });





    }
}