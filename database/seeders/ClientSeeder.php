<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Client;
use App\Models\ClientAgentHistory; // Import de ton modèle d'historique
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        
        $agentIds = Agent::pluck('id')->toArray();

        if (empty($agentIds)) {
            $this->command->error("Aucun agent trouvé !");
            return;
        }

        // On va créer les clients un par un ou par petits groupes pour l'historique
        for ($i = 0; $i < 200; $i++) {
            
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
                    'agent_id'            => $agentId,
                ]);

                // 2. Création automatique de l'historique (comme dans ton controller)
                ClientAgentHistory::create([
                    'client_id'   => $client->id,
                    'agent_id'    => $agentId,
                    'assigned_at' => now(),
                ]);
            });
        }

        $this->command->info('200 clients et leurs historiques de recrutement ont été créés.');
    }
}