<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryAdvance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalaryAdvanceController extends Controller
{
    /**
     * Enregistrer une nouvelle avance sur salaire avec une transaction DB (via AJAX).
     */
    public function store(Request $request, int $agentId)
    {
        $request->validate([
            'montant_total'   => 'required|numeric|min:0',
            'nombre_tranches' => 'required|integer|min:1',
            'motif'           => 'nullable|string|max:255',
        ]);

        try {
            $avance = DB::transaction(function () use ($request, $agentId) {
                $montantTotal   = $request->montant_total;
                $nombreTranches = $request->nombre_tranches;
                $montantMensuel = $montantTotal / $nombreTranches;

                return SalaryAdvance::create([
                    'advance_uid'     => Str::uuid(),
                    'agent_id'        => $agentId,
                    'montant_total'   => $montantTotal,
                    'montant_mensuel' => $montantMensuel,
                    'nombre_tranches' => $nombreTranches,
                    'tranches_payees' => 0,
                    'montant_restant' => $montantTotal,
                    'date_demande'    => now(),
                    'statut'          => 'en_attente',
                    'motif'           => $request->motif,
                    'created_by'      => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Avance enregistrée avec succès.',
                'avance'  => $avance,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une avance avec une transaction DB (via AJAX).
     */
    public function destroy(string $id)
    {
        try {
            DB::transaction(function () use ($id) {
                // On cherche l'avance via son champ personnalisé 'advance_uid'
                $avance = SalaryAdvance::where('advance_uid', $id)->firstOrFail();
                $avance->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Avance supprimée avec succès.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        // Si vous utilisez un champ personnalisé (ex: advance_uid) :
        $avance = SalaryAdvance::where('advance_uid', $id)->firstOrFail();

        // OU si vous utilisez l'ID standard de Laravel :
        // $avance = Avance::findOrFail($id);

        return response()->json([
            'success' => true,
            'avance'  => $avance,
        ]);
    }

    public function update(Request $request, string $uid)
    {
        return DB::transaction(function () use ($request, $uid) {
            // Recherche par advance_uid et non par id classique
            $avance = SalaryAdvance::where('advance_uid', $uid)->firstOrFail();

            $avance->update([
                'montant_total'   => $request->montant_total,
                'nombre_tranches' => $request->nombre_tranches,
                'motif'           => $request->motif,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mise à jour effectuée avec succès.',
            ]);
        });
    }
    public function validateAdvance($uid)
    {
        // On cherche directement par l'UID
        $avance = SalaryAdvance::where('advance_uid', $uid)->firstOrFail();

        // Vérifier si l'avance est bien en attente
        if ($avance->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Cette avance a déjà été traitée.',
            ], 422);
        }

        // Mise à jour du statut et de l'approbateur
        $avance->update([
            'statut'      => 'valide',
            'approved_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'L\'avance sur salaire a été validée avec succès !',
        ]);
    }

    public function index(Request $request)
    {
        $query = SalaryAdvance::with('agent', 'creator', 'approver');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $avances = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.payrolls.avance', compact('avances'));
    }
    public function validerAvance($id)
    {
        $avance = SalaryAdvance::where('advance_uid', $id)->firstOrFail();

        $avance->update([
            'statut'      => 'en_cours',
            'approved_by' => auth()->id(), // Optionnel selon votre base de données
        ]);

        return back()->with('success', "L'avance sur salaire a été validée avec succès.");
    }

    public function rejeterAvance($id)
    {
        $avance = SalaryAdvance::where('advance_uid', $id)->firstOrFail();

        $avance->update([
            'statut'      => 'rejetee',
            'approved_by' => auth()->id(), // Optionnel selon votre base de données
        ]);

        return back()->with('success', "L'avance sur salaire a été rejetée.");
    }

}
