<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name'      => 'Administrateur Principal',
            'email'     => 'admin@gmail.com',
            'username'  => 'superadmin',
            'type'      => 'admin', // Selon ta colonne 'type'
            'password'  => Hash::make('123456'), 
            'role_id'   => null, // À remplir si tu utilises aussi les IDs de rôles
            'is_active' => true,
        ]);
    }
}