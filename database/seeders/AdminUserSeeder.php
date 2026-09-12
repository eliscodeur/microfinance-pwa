<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Récupère dynamiquement toutes les permissions
        $allPermissions = config('role_permissions.labels', []);

        // 2. Crée ou met à jour le rôle Administrateur
        $adminRole = Role::updateOrCreate(
            ['nom' => 'Administrateur Principal'],
            ['permissions' => $allPermissions]
        );

        // 3. Crée ou met à jour l'utilisateur administrateur
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'      => 'Administrateur Principal',
                'username'  => 'superadmin',
                'type'      => 'admin',
                'password'  => Hash::make('123456'), 
                'role_id'   => $adminRole->id,
                'is_active' => true,
            ]
        );
    }
}