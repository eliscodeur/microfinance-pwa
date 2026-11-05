<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryGrid;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SalaryGridController extends Controller
{
    public function index()
    {
        $grilles = SalaryGrid::orderByDesc('date_debut')
            ->orderBy('seuil_min')
            ->get();

        return view('admin.payrolls.salary-grids', compact('grilles'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedGrid($request);
        $this->ensureNoOverlap($data);

        SalaryGrid::create($data);

        return redirect()->route('admin.salary-grids.index')
            ->with('success', 'La tranche salariale a été ajoutée.');
    }

    public function update(Request $request, SalaryGrid $salary_grid)
    {
        if ($salary_grid->date_debut->startOfDay()->lte(now()->startOfDay())) {
            return back()->with('error', 'Une tranche déjà entrée en vigueur est conservée pour l’historique. Créez une nouvelle tranche avec sa date d’effet.');
        }

        $data = $this->validatedGrid($request);
        $this->ensureNoOverlap($data, $salary_grid->id);

        $salary_grid->update($data);

        return redirect()->route('admin.salary-grids.index')
            ->with('success', 'La tranche salariale a été mise à jour.');
    }

    public function close(Request $request, SalaryGrid $salary_grid)
    {
        $data = $request->validate([
            'date_fin' => ['required', 'date', 'after_or_equal:today'],
        ]);

        if (
            ! $salary_grid->est_actif
            || $salary_grid->date_debut->isFuture()
            || ($salary_grid->date_fin && $salary_grid->date_fin->endOfDay()->lt(now()))
        ) {
            return back()->with('error', 'Seule une tranche active déjà entrée en vigueur peut être clôturée.');
        }

        $salary_grid->update(['date_fin' => $data['date_fin']]);

        return redirect()->route('admin.salary-grids.index')
            ->with('success', 'La période de cette tranche a été clôturée sans modifier son historique.');
    }

    private function validatedGrid(Request $request): array
    {
        $data = $request->validate([
            'seuil_min'          => ['nullable', 'numeric', 'min:0'],
            'seuil_max'          => ['required', 'numeric', 'min:0'],
            'salaire_base'       => ['required', 'numeric', 'min:0'],
            'commission_travail' => ['required', 'numeric', 'min:0'],
            'taux_carnet'        => ['required', 'numeric', 'min:0', 'max:100'],
            'date_debut'         => ['required', 'date'],
            'date_fin'           => ['nullable', 'date', 'after_or_equal:date_debut'],
            'est_actif'          => ['required', 'boolean'],
        ]);

        if ($data['seuil_min'] !== null && (float) $data['seuil_min'] > (float) $data['seuil_max']) {
            throw ValidationException::withMessages([
                'seuil_min' => 'Le seuil minimum doit être inférieur ou égal au seuil maximum.',
            ]);
        }

        $data['est_actif'] = (bool) $data['est_actif'];

        return $data;
    }

    private function ensureNoOverlap(array $data, ?int $exceptId = null): void
    {
        if (! $data['est_actif']) {
            return;
        }

        $start = Carbon::parse($data['date_debut'])->startOfDay();
        $end = isset($data['date_fin']) ? Carbon::parse($data['date_fin'])->endOfDay() : null;

        $grilles = SalaryGrid::query()
            ->where('est_actif', true)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('date_debut', '<=', $end ?? '9999-12-31')
            ->where(function ($query) use ($start) {
                $query->whereNull('date_fin')->orWhere('date_fin', '>=', $start->toDateString());
            })
            ->get(['seuil_min', 'seuil_max']);

        $newMin = $data['seuil_min'] === null ? null : (float) $data['seuil_min'];
        $newMax = (float) $data['seuil_max'];

        foreach ($grilles as $grille) {
            $existingMin = $grille->seuil_min === null ? null : (float) $grille->seuil_min;
            $existingMax = (float) $grille->seuil_max;

            $rangesOverlap = ($newMin === null || $existingMax >= $newMin)
                && ($existingMin === null || $newMax >= $existingMin);

            if ($rangesOverlap) {
                throw ValidationException::withMessages([
                    'seuil_min' => 'Cette tranche chevauche une autre tranche active sur une période d’application commune.',
                ]);
            }
        }
    }
}
