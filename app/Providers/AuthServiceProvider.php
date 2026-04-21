<?php

namespace App\Providers;
use App\Models\User;

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

        // On crée une Gate dynamique pour chaque permission
        Gate::before(function ($user, $ability) {
            if ($user->email === 'admin@gmail.com') {
                return true; 
            }
            // Si l'utilisateur a un rôle et que ce rôle contient la permission demandée
            if ($user->role && is_array($user->role->permissions)) {
                return in_array($ability, $user->role->permissions);
            }
        });
    }
}
