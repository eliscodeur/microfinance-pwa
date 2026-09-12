<?php
namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientCarnetNumber;
use Illuminate\Database\Seeder;

class ClientCarnetNumberSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer quelques clients existants dans la base
        $clients = Client::all();

        if ($clients->isEmpty()) {
            return; // Sort si aucun client n'existe
        }

        foreach ($clients as $client) {
            // Générer 2 numéros de carnets de type 'tontine' pour chaque client
            ClientCarnetNumber::generateForClient(
                client: $client,
                typeCarnet: 'tontine',
                quantite: 2,
                createdBy: 1// ID de l'utilisateur qui crée ces numéros (ex: admin)
            );

            // Générer 1 numéro de carnet d'un autre type (ex: 'epargne')
            ClientCarnetNumber::generateForClient(
                client: $client,
                typeCarnet: 'compte',
                quantite: 1,
                createdBy: 1// ID de l'utilisateur qui crée ces numéros (ex: admin)
            );
        }
    }
}
