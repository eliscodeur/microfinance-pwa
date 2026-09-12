<?php
namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Carnet;
use App\Models\CarnetAgentHistory;
use App\Models\ClientCarnetNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CarnetSeeder extends Seeder
{
    public function run(): void
    {
        // On récupère uniquement les numéros disponibles de type 'tontine'
        $availableNumbers = ClientCarnetNumber::where('statut', 'disponible')
            ->where('type_carnet', 'tontine')
            ->get();

        $agents = Agent::all();

        if ($availableNumbers->isEmpty() || $agents->isEmpty()) {
            return;
        }

        foreach ($availableNumbers as $carnetNumber) {
            $randomAgent = $agents->random();

            $carnet = Carnet::create([
                'ulid'                => strtolower((string) Str::ulid()),
                'client_id'           => $carnetNumber->client_id,
                'type'                => 'tontine',
                'category_tontine_id' => 1,    // ID de la catégorie de tontine par défaut
                'parent_id'           => null, // Une tontine n'a pas de parent
                'agent_id'            => $randomAgent->id,
                'numero'              => $carnetNumber->numero,
                'statut'              => 'actif',
                'date_debut'          => now()->toDateString(),
                'created_by'          => 1,
            ]);

            CarnetAgentHistory::create([
                'ulid'        => strtolower((string) Str::ulid()),
                'carnet_id'   => $carnet->id,
                'agent_id'    => $randomAgent->id,
                'assigned_at' => now(),
            ]);

            $carnetNumber->update([
                'statut'  => 'utilise',
                'used_at' => now(),
            ]);
        }
    }
}
