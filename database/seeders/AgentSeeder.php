<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Agent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR'); // On utilise le local français pour des noms réalistes

        for ($i = 1; $i <= 10; $i++) {
            
            DB::transaction(function () use ($faker) {
                
                // 1. On génère le code NEC via la méthode du modèle
                $code = Agent::generateNecCode();
                $name = $faker->name();

                // 2. Création de l'Utilisateur (SANS can_sync)
                $user = User::create([
                    'name'     => $name,
                    'email'    => $faker->unique()->safeEmail(),
                    'username' => $code,
                    'password' => Hash::make('password'),
                    'type'     => 'agent',
                    'is_active'=> 1, // Assure-toi que cette colonne existe bien dans 'users'
                ]);

                // 3. Création du profil AGENT (AVEC can_sync)
                Agent::create([
                    'user_id'    => $user->id,
                    'code_agent' => $code,
                    'nom'        => $name,
                    'telephone'  => $faker->phoneNumber(),
                    'actif'      => 1,
                    'can_sync'   => true, // On le met ici si c'est une propriété de l'agent
                    'portefeuille_virtuel' => rand(0, 10000),
                ]);
                
            });
        }

        $this->command->info('Succès : 10 agents créés pour NANA ECO CONSULTING.');
    }
}