<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCarnetNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'client_id',
        'type_carnet',
        'numero',
        'statut',
        'created_by',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Génère un lot de numéros de carnets pour un client donné selon le type
     */
    public static function generateForClient(Client $client, string $typeCarnet, int $quantite = 1, ?int $createdBy = null): array
    {
        $generatedNumbers = [];

        for ($i = 0; $i < $quantite; $i++) {
            $numero = self::generateUniqueNumber($client, $typeCarnet);

            $carnetNumber = self::create([
                'client_id'   => $client->id,
                'type_carnet' => $typeCarnet,
                'numero'      => $numero,
                'statut'      => 'disponible',
                'created_by'  => auth()->id(),
                'ulid'        => strtolower(\Illuminate\Support\Str::ulid()),
            ]);

            $generatedNumbers[] = $carnetNumber;
        }

        return $generatedNumbers;
    }

    /**
     * Calcule le format du numéro selon le type de carnet
     */
    protected static function generateUniqueNumber(Client $client, string $typeCarnet): string
    {
        $count  = self::count() + Carnet::count() + 1;
        $idPart = 1000 + $count;

        // 1. Initialisation en amont pour éviter les avertissements de l'IDE
        $initialeNom    = strtoupper(mb_substr($client->nom ?? 'C', 0, 1));
        $initialePrenom = strtoupper(mb_substr($client->prenom ?? 'X', 0, 1));

        // 2. Boucle do-while propre
        do {
            if ($typeCarnet === 'tontine') {
                $numero = (string) $idPart;
            } else {
                $numero = "{$initialeNom}{$idPart}{$initialePrenom}";
            }

            $exists = self::where('numero', $numero)->exists() || Carnet::where('numero', $numero)->exists();

            if ($exists) {
                $idPart++;
            }
        } while ($exists);

        return $numero;
    }

    /**
     * Enregistre manuellement un numéro physique pour le client
     */
    public static function createManualNumber(Client $client, string $typeCarnet, string $numero, ?int $createdBy = null): self
    {
        $numeroClean = trim($numero);

        // Vérification d'unicité globale (réserve + carnets existants)
        $existsInReserve = self::where('numero', $numeroClean)->exists();
        $existsInCarnets = Carnet::where('numero', $numeroClean)->exists();

        if ($existsInReserve || $existsInCarnets) {
            throw new \Exception("Le numéro {$numeroClean} est déjà utilisé dans le système.");
        }

        return self::create([
            'client_id'   => $client->id,
            'type_carnet' => $typeCarnet,
            'numero'      => $numeroClean,
            'statut'      => 'disponible',
            'created_by'  => auth()->id(),
            'ulid'        => strtolower(\Illuminate\Support\Str::ulid()),
        ]);
    }
}
