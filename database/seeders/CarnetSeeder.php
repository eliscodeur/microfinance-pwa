<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Carnet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarnetSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::all();

        if ($clients->isEmpty()) {
            $this->command->error("Aucun client trouvé !");
            return;
        }

        foreach ($clients as $client) {
            DB::transaction(function () use ($client) {
                
                // --- 1. CRÉATION DES TONTINES (Les Parents potentiels) ---
                $tontinesIds = [];
                $nbTontines = rand(0, 3);
                
                for ($i = 0; $i < $nbTontines; $i++) {
                    $tontine = Carnet::create([
                        'client_id'           => $client->id,
                        'type'                => 'tontine',
                        'date_debut'          => now()->subDays(rand(1, 15)),
                        'statut'              => 'actif',
                        'category_tontine_id' => rand(1, 2),
                        'parent_id'           => null,
                    ]);
                    $tontinesIds[] = $tontine->id;
                }

                // --- 2. CRÉATION DES COMPTES (Les Enfants qui peuvent être liés) ---
                $nbComptes = rand(1, 2);
                for ($j = 0; $j < $nbComptes; $j++) {
                    
                    // Logique de rattachement : 40% de chance d'être lié à l'une des tontines du client
                    $parentId = null;
                    if (!empty($tontinesIds) && rand(1, 2) <= 1) { // 50% de chance de rattacher à une tontine existante
                        $parentId = $tontinesIds[array_rand($tontinesIds)];
                    }

                    Carnet::create([
                        'client_id'  => $client->id,
                        'type'       => 'compte',
                        'date_debut' => now()->subMonths(rand(1, 6)),
                        'statut'     => 'actif',
                        'parent_id'  => $parentId, // Le compte est lié à la tontine ou reste indépendant
                    ]);
                }
            });
        }

        $this->command->info('Seed terminé : Carnets de comptes rattachés (ou non) aux tontines.');
    }
}