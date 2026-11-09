<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bonus;
use App\Models\Collecte;
use App\Models\Cycle;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\MouvementCaisse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CollecteController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cycle_id'        => 'required|integer|exists:cycles,id',
            'pointage'        => 'required|integer|min:1',
            'idempotency_key' => 'required|uuid',
            'payeur_type'     => 'required|in:client,tiers',
            'payeur_nom'      => 'nullable|required_if:payeur_type,tiers|string|max:150',
            'payeur_telephone'=> 'nullable|string|max:30',
            'payeur_lien'     => 'nullable|required_if:payeur_type,tiers|string|max:80',
        ]);

        $existingCollecte = Collecte::where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existingCollecte) {
            if ((int) $existingCollecte->user_id !== (int) auth()->id()) {
                abort(409, 'Cette demande a déjà été utilisée. Actualisez la page avant de réessayer.');
            }

            return redirect()->route('admin.collectes.receipt', $existingCollecte);
        }

        try {
            $collecte = DB::transaction(function () use ($validated) {
                $cycle = Cycle::whereKey($validated['cycle_id'])
                    ->where('statut', 'en_cours')
                    ->lockForUpdate()
                    ->first();

                if (! $cycle) {
                    throw new \DomainException('Aucun cycle actif trouvé pour cette collecte.');
                }

                $carnet = $cycle->carnet()->lockForUpdate()->first();
                $client = Client::whereKey($cycle->client_id)->first();
                $session = CashSession::where('user_id', auth()->id())
                    ->where('statut', 'ouverte')
                    ->latest('date_ouverture')
                    ->lockForUpdate()
                    ->first();

                if (! $carnet || $carnet->type !== 'tontine' || $carnet->statut !== 'actif' || ! $client || ! $client->is_active) {
                    throw new \DomainException('La collecte est impossible : le carnet ou le compte client n’est pas actif.');
                }
                if (! $session) {
                    throw new \DomainException('Votre session de caisse est fermée. Ouvrez une caisse avant d’effectuer une collecte.');
                }

                $totalPointagesActuels = (int) $cycle->collectes()->sum('pointage');
                $nouveauTotal = $totalPointagesActuels + (int) $validated['pointage'];
                $objectifMax = (int) ($cycle->nombre_jours_objectif ?? 31);
                if ($nouveauTotal > $objectifMax) {
                    $restants = max(0, $objectifMax - $totalPointagesActuels);
                    throw new \DomainException("Impossible d’enregistrer cette collecte. Il ne reste que {$restants} pointage(s) pour ce cycle.");
                }

                $miseJournaliere = (float) $cycle->montant_journalier;
                if ($miseJournaliere <= 0 || floor($miseJournaliere) !== $miseJournaliere) {
                    throw new \DomainException('La mise journalière du cycle doit être un montant positif en FCFA entier.');
                }
                $montant = (int) $miseJournaliere * (int) $validated['pointage'];
                if ($montant > 9999999999999) {
                    throw new \DomainException('Le montant de la collecte dépasse la limite autorisée.');
                }

                $payeurEstClient = $validated['payeur_type'] === 'client';
                $collecte = Collecte::create([
                    'collecte_uid'     => (string) Str::uuid(),
                    'cycle_id'         => $cycle->id,
                    'client_id'        => $client->id,
                    'agent_id'         => $cycle->agent_id,
                    'user_id'          => auth()->id(),
                    'pointage'         => $validated['pointage'],
                    'montant'          => $montant,
                    'date_saisie'      => now(),
                    'cycle_uid'        => $cycle->cycle_uid,
                    'idempotency_key'  => $validated['idempotency_key'],
                    'cash_session_id'  => $session->id,
                    'payeur_type'      => $validated['payeur_type'],
                    'payeur_nom'       => $payeurEstClient
                        ? trim($client->nom . ' ' . $client->prenom)
                        : trim($validated['payeur_nom']),
                    'payeur_telephone' => $payeurEstClient
                        ? $client->telephone
                        : ($validated['payeur_telephone'] ?? null),
                    'payeur_lien'      => $payeurEstClient
                        ? 'Titulaire du carnet'
                        : $validated['payeur_lien'],
                ]);

                $reference = 'COL-' . now()->format('Ymd') . '-' . str_pad((string) $collecte->id, 8, '0', STR_PAD_LEFT);
                $collecte->update(['reference_operation' => $reference]);

                MouvementCaisse::create([
                    'ulid'            => strtolower((string) Str::ulid()),
                    'date_mouvement'  => $collecte->date_saisie,
                    'type_operation'  => 'collecte',
                    'sens'            => 'entree',
                    'montant'         => $montant,
                    'mode_paiement'   => 'especes',
                    'reference'       => $reference,
                    'libelle'         => 'Collecte tontine carnet #' . $carnet->numero . ' - Client ' . $client->nom . ' ' . $client->prenom,
                    'source_type'     => Collecte::class,
                    'source_id'       => $collecte->id,
                    'user_id'         => auth()->id(),
                    'cash_session_id' => $session->id,
                ]);

                if (! Bonus::where('cycle_id', $cycle->id)->exists()) {
                    $montantCommission = $cycle->calculerCommission();
                    $cycle->update(['commission_genere' => true]);
                    Bonus::create([
                        'agent_id'         => $cycle->agent_id,
                        'cycle_id'         => $cycle->id,
                        'montant'          => $montantCommission,
                        'statut'           => 'en_attente',
                        'motif'            => 'Commission collecte — Cycle',
                        'date_attribution' => now(),
                    ]);
                }

                if ($nouveauTotal === $objectifMax) {
                    $cycle->update([
                        'statut'              => 'termine',
                        'completed_at'        => now(),
                        'date_cloture_reelle' => now(),
                    ]);
                }

                return $collecte;
            });

            return redirect()->route('admin.collectes.receipt', $collecte);
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            $existingCollecte = Collecte::where('idempotency_key', $validated['idempotency_key'])->first();
            if ($existingCollecte && (int) $existingCollecte->user_id === (int) auth()->id()) {
                return redirect()->route('admin.collectes.receipt', $existingCollecte);
            }

            Log::error('Échec de l’enregistrement d’une collecte tontine administrative.', [
                'user_id' => auth()->id(),
                'cycle_id' => $validated['cycle_id'],
                'exception' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'La collecte n’a pas pu être enregistrée. Vérifiez la connexion et réessayez.');
        } catch (\Throwable $e) {
            Log::error('Échec de l’enregistrement d’une collecte tontine administrative.', [
                'user_id' => auth()->id(),
                'cycle_id' => $validated['cycle_id'],
                'exception' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Une erreur technique a empêché l’enregistrement de la collecte.');
        }
    }

    public function receipt(Collecte $collecte)
    {
        $collecte->load(['cycle.carnet', 'client', 'agent', 'user']);

        return view('admin.carnets.collecte-recu', compact('collecte'));
    }
}
