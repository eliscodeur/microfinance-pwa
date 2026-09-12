<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Client;
use App\Models\ClientAgentHistory;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        
        $agentIds = Agent::pluck('id')->toArray();

        if (empty($agentIds)) {
            $this->command->error("Aucun agent trouvé ! Exécutez d'abord AgentSeeder.");
            return;
        }

        $count = 50; 

        for ($i = 0; $i < $count; $i++) {
            
            DB::transaction(function () use ($faker, $agentIds) {
                $genre = $faker->randomElement(['Masculin', 'Féminin']);
                $agentId = $faker->randomElement($agentIds);

                // 1. Création du client
                $client = Client::create([
                    'nom'                 => $faker->lastName,
                    'prenom'              => ($genre == 'Masculin') ? $faker->firstNameMale : $faker->firstNameFemale,
                    'date_naissance'      => $faker->date('Y-m-d', '2005-01-01'),
                    'lieu_naissance'      => $faker->city,
                    'genre'               => $genre,
                    'statut_matrimonial'  => $faker->randomElement(['Célibataire', 'Marié(e)', 'Veuf(ve)', 'Divorcé(e)']),
                    'nationalite'         => 'Togolaise',
                    'profession'          => $faker->jobTitle,
                    'telephone'           => '2289' . rand(0, 3) . rand(100000, 999999),
                    'adresse'             => $faker->address,
                    'reference_nom'       => $faker->name,
                    'reference_telephone' => '228' . rand(90, 99) . rand(100000, 999999),
                ]);

                // 2. Création de l'historique avec génération d'ULID explicite
                ClientAgentHistory::create([
                    'ulid'        => strtolower((string) Str::ulid()),
                    'client_id'   => $client->id,
                    'agent_id'    => $agentId,
                    'assigned_at' => now(),
                ]);
            });
        }

        $this->command->info("{$count} clients et leurs historiques d'affectation ont été créés.");
    }
}