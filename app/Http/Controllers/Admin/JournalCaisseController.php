<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Models\CashSession;
use App\Models\MouvementCaisse;
use App\Models\User;
use App\Services\CashClosingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JournalCaisseController extends Controller
{
    protected $closingService;

    public function __construct(CashClosingService $closingService)
    {
        $this->closingService = $closingService;
    }

    /**
     * Journal de caisse personnel (pour l'agent connecté)
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'periode'        => 'nullable|string|max:50',
            'type_operation' => 'nullable|string|max:100',
            'sens'           => 'nullable|in:entree,sortie',
            'mode_paiement'  => 'nullable|string|max:50',
        ]);

        $query          = MouvementCaisse::with(['user', 'cashSession'])->where('user_id', auth()->id());
        $periodeLibelle = "Aujourd'hui (" . Carbon::today()->format('d/m/Y') . ")";

        if (! empty($validated['periode'])) {
            $dates = preg_split('/\s+au\s+/i', trim($validated['periode']));

            if (count($dates) < 1 || count($dates) > 2 || in_array('', $dates, true)) {
                throw ValidationException::withMessages([
                    'periode' => 'Veuillez saisir une date ou une plage de dates valide.',
                ]);
            }

            $dateDebut = $this->parseDate(trim($dates[0]));
            $dateFin   = isset($dates[1]) ? $this->parseDate(trim($dates[1])) : $dateDebut->copy();

            if ($dateDebut->gt($dateFin)) {
                throw ValidationException::withMessages([
                    'periode' => 'La date de début doit précéder la date de fin.',
                ]);
            }

            $query->whereBetween('date_mouvement', [
                $dateDebut->startOfDay(),
                $dateFin->copy()->endOfDay(),
            ]);
            $periodeLibelle = count($dates) === 2
                ? 'Du ' . $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y')
                : 'Le ' . $dateDebut->format('d/m/Y');
        } else {
            $query->whereDate('date_mouvement', Carbon::today()->toDateString());
        }

        foreach (['type_operation', 'sens', 'mode_paiement'] as $filter) {
            if (! empty($validated[$filter])) {
                $query->where($filter, $validated[$filter]);
            }
        }

        $totalEntrees   = (clone $query)->where('sens', 'entree')->sum('montant');
        $totalSorties   = (clone $query)->where('sens', 'sortie')->sum('montant');
        $soldeTheorique = $totalEntrees - $totalSorties; // Corrigé ici

        $mouvements = $query->latest('date_mouvement')->paginate(15)->appends(request()->query());

        $operations    = MouvementCaisse::query()->distinct()->orderBy('type_operation')->pluck('type_operation');
        $modesPaiement = MouvementCaisse::query()->distinct()->orderBy('mode_paiement')->pluck('mode_paiement');
        $clotures      = CashClosing::with(['user', 'session'])
            ->where('user_id', auth()->id())
            ->latest('date_cloture')
            ->limit(10)
            ->get();

        $session = CashSession::where('user_id', auth()->id())
            ->where('statut', 'ouverte')
            ->first();
        $sessionSummary = $session ? $this->closingService->calculateSummary($session) : null;

        return view('admin.caisse.journal', compact(
            'mouvements',
            'totalEntrees',
            'totalSorties',
            'soldeTheorique',
            'periodeLibelle',
            'operations',
            'modesPaiement',
            'clotures',
            'session',
            'sessionSummary'
        ));
    }

    /**
     * Journal d'audit global (pour l'administrateur / comptable / auditeur)
     */
    public function audit(Request $request)
    {
        $validated = $request->validate([
            'periode'        => 'nullable|string|max:50',
            'type_operation' => 'nullable|string|max:100',
            'sens'           => 'nullable|in:entree,sortie',
            'mode_paiement'  => 'nullable|string|max:50',
            'user_id'        => 'nullable|exists:users,id', // Filtre optionnel par admin/superadmin
        ]);

        // On filtre les mouvements uniquement pour les types 'admin' ou 'superadmin'
        $query = MouvementCaisse::with(['user', 'cashSession'])->whereHas('user', function ($q) {
            $q->whereIn('type', ['admin', 'superadmin']);
        });

        $periodeLibelle = "Aujourd'hui (" . Carbon::today()->format('d/m/Y') . ")";

        // Si l'auditeur sélectionne un utilisateur spécifique dans le filtre
        if (! empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (! empty($validated['periode'])) {
            $dates = preg_split('/\s+au\s+/i', trim($validated['periode']));

            if (count($dates) < 1 || count($dates) > 2 || in_array('', $dates, true)) {
                throw ValidationException::withMessages([
                    'periode' => 'Veuillez saisir une date ou une plage de dates valide.',
                ]);
            }

            $dateDebut = $this->parseDate(trim($dates[0]));
            $dateFin   = isset($dates[1]) ? $this->parseDate(trim($dates[1])) : $dateDebut->copy();

            if ($dateDebut->gt($dateFin)) {
                throw ValidationException::withMessages([
                    'periode' => 'La date de début doit précéder la date de fin.',
                ]);
            }

            $query->whereBetween('date_mouvement', [
                $dateDebut->startOfDay(),
                $dateFin->copy()->endOfDay(),
            ]);
            $periodeLibelle = count($dates) === 2
                ? 'Du ' . $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y')
                : 'Le ' . $dateDebut->format('d/m/Y');
        } else {
            $query->whereDate('date_mouvement', Carbon::today()->toDateString());
        }

        foreach (['type_operation', 'sens', 'mode_paiement'] as $filter) {
            if (! empty($validated[$filter])) {
                $query->where($filter, $validated[$filter]);
            }
        }

        $totalEntrees   = (clone $query)->where('sens', 'entree')->sum('montant');
        $totalSorties   = (clone $query)->where('sens', 'sortie')->sum('montant');
        $soldeTheorique = $totalEntrees - $totalSorties; // Correction du calcul

        $mouvements = $query->latest('date_mouvement')->paginate(15)->appends(request()->query());

        $operations    = MouvementCaisse::query()->distinct()->orderBy('type_operation')->pluck('type_operation');
        $modesPaiement = MouvementCaisse::query()->distinct()->orderBy('mode_paiement')->pluck('mode_paiement');

        // Liste uniquement des administrateurs et superadmins pour le menu déroulant d'audit
        $users = User::whereIn('type', ['admin', 'superadmin'])->orderBy('name')->get();

        $clotures = CashClosing::with(['user', 'session'])
            ->latest('date_cloture')
            ->limit(10)
            ->get();

        return view('admin.caisse.audit', compact(
            'mouvements',
            'totalEntrees',
            'totalSorties',
            'soldeTheorique',
            'periodeLibelle',
            'operations',
            'modesPaiement',
            'users',
            'clotures'
        ));
    }

    private function parseDate(string $value): Carbon
    {
        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
            } catch (\InvalidArgumentException $exception) {
                continue;
            }

            if ($date && $date->format($format) === $value) {
                return $date;
            }
        }

        throw ValidationException::withMessages([
            'periode' => 'Veuillez saisir une date au format jour/mois/année.',
        ]);
    }
}
