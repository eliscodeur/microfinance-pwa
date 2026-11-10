<?php

namespace App\Providers;

use App\Support\PermissionCatalog;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{  
   
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            $superAdminEmail = config('role_permissions.super_admin_email');
            if ($superAdminEmail && strcasecmp((string) $user->email, $superAdminEmail) === 0) {
                return true;
            }

            $permissions = $user->role ? $user->role->permissions : [];
            if (is_array($permissions)) {
                return in_array($ability, PermissionCatalog::expand($permissions), true);
            }
        });
    }
}
