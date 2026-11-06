<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AuditLogAccess;
use App\Models\Carnet;
use App\Models\CashClosing;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\ClientCarnetNumber;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Depense;
use App\Models\EmployeAdministratif;
use App\Models\MouvementStockCarnets;
use App\Models\Paiement;
use App\Models\PaymentTransaction;
use App\Models\SalaryAdvance;
use App\Models\User;
use App\Support\AuditValueSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

class AuditLogController extends Controller
{
    public function index(Request $request, AuditValueSanitizer $sanitizer)
    {
        $filters = $this->validatedFilters($request);
        $query   = $this->filteredAudits($filters);

        $total      = (clone $query)->count();
        $modelCount = (clone $query)->distinct('auditable_type')->count('auditable_type');
        $actorCount = (clone $query)->whereNotNull('user_id')->distinct('user_id')->count('user_id');

        $audits = (clone $query)
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $this->attachAuditableReferences($audits->getCollection());

        $audits->getCollection()->transform(function (Audit $audit) use ($sanitizer) {
            $audit->safe_old_values = $sanitizer->sanitize((array) $audit->old_values);
            $audit->safe_new_values = $sanitizer->sanitize((array) $audit->new_values);
            $audit->auditable_label = $this->modelLabel($audit->auditable_type);

            return $audit;
        });

        $models = Audit::query()
            ->select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type');

        $this->recordAccess($request, 'view', $filters, $total);

        return view('admin.audit-logs.index', [
            'audits'         => $audits,
            'filters'        => $filters,
            'total'          => $total,
            'modelCount'     => $modelCount,
            'actorCount'     => $actorCount,
            'events'         => Audit::query()->select('event')->distinct()->orderBy('event')->pluck('event'),
            'models'         => $models,
            'modelLabels'    => $models->mapWithKeys(function (string $model) {
                return [$model => $this->modelLabel($model)];
            }),
            'users'          => User::query()->whereNotNull('role_id')->orderBy('name')->get(['id', 'name', 'email']),
            'recentAccesses' => AuditLogAccess::query()
                ->with('user')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function export(Request $request, AuditValueSanitizer $sanitizer)
    {
        $filters = $this->validatedFilters($request);
        $query   = $this->filteredAudits($filters);
        $total   = (clone $query)->count();

        $this->recordAccess($request, 'export', $filters, $total);

        return response()->streamDownload(function () use ($query, $sanitizer) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Date et heure',
                'Auteur',
                'Événement',
                'Type d’entité',
                'Référence métier',
                'ID interne',
                'Anciennes valeurs',
                'Nouvelles valeurs',
                'URL',
                'Adresse IP',
                'Navigateur',
            ], ';', '"', '');

            (clone $query)
                ->with('user')
                ->orderBy('created_at')
                ->orderBy('id')
                ->chunk(500, function ($audits) use ($output, $sanitizer) {
                    $this->attachAuditableReferences($audits);

                    foreach ($audits as $audit) {
                        fputcsv($output, [
                            $audit->created_at,
                            $this->csvSafe(optional($audit->user)->name ?? 'Système'),
                            $this->csvSafe($audit->event),
                            $this->csvSafe($this->modelLabel($audit->auditable_type)),
                            $this->csvSafe($audit->auditable_reference),
                            $this->csvSafe((string) $audit->auditable_id),
                            $this->csvSafe(json_encode($sanitizer->sanitize((array) $audit->old_values), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)),
                            $this->csvSafe(json_encode($sanitizer->sanitize((array) $audit->new_values), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)),
                            $this->csvSafe($audit->url),
                            $this->csvSafe($audit->ip_address),
                            $this->csvSafe($audit->user_agent),
                        ], ';', '"', '');
                    }
                });

            fclose($output);
        }, 'journal-audit-' . now()->format('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        $dateToRules = ['nullable', 'date'];
        if ($request->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        $validated = $request->validate([
            'date_from'      => ['nullable', 'date'],
            'date_to'        => $dateToRules,
            'user_id'        => ['nullable', 'integer', 'exists:users,id'],
            'event'          => ['nullable', 'string', 'max:100'],
            'auditable_type' => ['nullable', 'string', 'max:191'],
            'reference'      => ['nullable', 'string', 'max:100'],
            'auditable_id'   => ['nullable', 'string', 'max:100'],
        ]);

        return [
            'date_from'      => $validated['date_from'] ?? now()->subDays(30)->toDateString(),
            'date_to'        => $validated['date_to'] ?? now()->toDateString(),
            'user_id'        => $validated['user_id'] ?? null,
            'event'          => $validated['event'] ?? null,
            'auditable_type' => $validated['auditable_type'] ?? null,
            'reference'      => $validated['reference'] ?? $validated['auditable_id'] ?? null,
        ];
    }

    private function filteredAudits(array $filters): Builder
    {
        return Audit::query()
            ->whereDate('created_at', '>=', $filters['date_from'])
            ->whereDate('created_at', '<=', $filters['date_to'])
            ->when($filters['user_id'], function (Builder $query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($filters['event'], function (Builder $query, $event) {
                $query->where('event', $event);
            })
            ->when($filters['auditable_type'], function (Builder $query, $type) {
                $query->where('auditable_type', $type);
            })
            ->when($filters['reference'], function (Builder $query, string $reference) use ($filters) {
                $this->whereAuditReferenceMatches($query, $reference, $filters['auditable_type']);
            });
    }

    private function whereAuditReferenceMatches(Builder $query, string $reference, ?string $auditableType): void
    {
        $query->where(function (Builder $query) use ($reference, $auditableType) {
            $query->where('auditable_id', $reference);

            foreach ($this->referenceFields() as $model => $fields) {
                if ($auditableType && $auditableType !== $model) {
                    continue;
                }

                $modelClass = $model;
                $ids = $modelClass::query()
                    ->where(function (Builder $query) use ($fields, $reference) {
                        foreach ($fields as $field) {
                            $query->orWhere($field, $reference)
                                ->orWhere($field, strtolower($reference));
                        }
                    })
                    ->pluck((new $modelClass())->getKeyName());

                if ($ids->isNotEmpty()) {
                    $query->orWhere(function (Builder $query) use ($model, $ids) {
                        $query->where('auditable_type', $model)
                            ->whereIn('auditable_id', $ids);
                    });
                }
            }
        });
    }

    private function attachAuditableReferences(Collection $audits): void
    {
        foreach ($audits->groupBy('auditable_type') as $model => $modelAudits) {
            $fields = $this->referenceFields()[$model] ?? [];
            $modelClass = $model;

            if (! $fields || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                foreach ($modelAudits as $audit) {
                    $audit->auditable_reference = $this->referenceFromAuditValues($audit, $fields);
                }

                continue;
            }

            $modelInstance = new $modelClass();
            $keyName = $modelInstance->getKeyName();
            $records = $modelClass::query()
                ->whereKey($modelAudits->pluck('auditable_id')->unique())
                ->get(array_unique(array_merge([$keyName], $fields)))
                ->keyBy($keyName);

            foreach ($modelAudits as $audit) {
                $record = $records->get($audit->auditable_id);
                $audit->auditable_reference = $this->formatAuditableReference(
                    $model,
                    $record ? $record->getAttributes() : $this->referenceFromAuditValues($audit, $fields)
                );
            }
        }
    }

    private function referenceFromAuditValues(Audit $audit, array $fields): array
    {
        foreach ([$audit->new_values, $audit->old_values] as $values) {
            $values = (array) $values;
            if (array_intersect($fields, array_keys($values))) {
                return $values;
            }
        }

        return [];
    }

    private function formatAuditableReference(string $model, array $attributes): ?string
    {
        $values = [];

        if ($model === Client::class) {
            $name = trim(($attributes['nom'] ?? '') . ' ' . ($attributes['prenom'] ?? ''));
            if ($name !== '') {
                $values[] = $name;
            }
        }

        $labels = [
            Agent::class => ['code_agent' => 'Code agent', 'ulid' => 'ULID'],
            Carnet::class => ['numero' => 'N° carnet', 'ulid' => 'ULID'],
            CashClosing::class => ['ulid' => 'ULID'],
            CashSession::class => ['ulid' => 'ULID'],
            Client::class => ['ulid' => 'ULID'],
            ClientCarnetNumber::class => ['numero' => 'N° carnet', 'ulid' => 'ULID'],
            Credit::class => ['credit_uid' => 'N° crédit'],
            CreditPayment::class => ['echeance' => 'Échéance', 'credit_id' => 'Crédit'],
            Depense::class => ['reference_piece' => 'Pièce', 'ulid' => 'ULID'],
            EmployeAdministratif::class => ['ulid' => 'ULID'],
            MouvementStockCarnets::class => ['ulid' => 'ULID'],
            Paiement::class => ['reference' => 'Référence', 'ulid' => 'ULID'],
            PaymentTransaction::class => ['transaction_uid' => 'Transaction', 'reference_externe' => 'Référence externe'],
            SalaryAdvance::class => ['advance_uid' => 'ULID'],
            User::class => ['username' => 'Identifiant', 'ulid' => 'ULID'],
        ][$model] ?? [];

        foreach ($labels as $field => $label) {
            $value = $attributes[$field] ?? null;
            if ($value !== null && $value !== '') {
                $values[] = $label . ' : ' . $value;
            }
        }

        return $values ? implode(' · ', $values) : null;
    }

    private function referenceFields(): array
    {
        return [
            Agent::class => ['code_agent', 'ulid'],
            Carnet::class => ['numero', 'ulid'],
            CashClosing::class => ['ulid'],
            CashSession::class => ['ulid'],
            Client::class => ['nom', 'prenom', 'ulid'],
            ClientCarnetNumber::class => ['numero', 'ulid'],
            Credit::class => ['credit_uid'],
            CreditPayment::class => ['echeance', 'credit_id'],
            Depense::class => ['reference_piece', 'ulid'],
            EmployeAdministratif::class => ['ulid'],
            MouvementStockCarnets::class => ['ulid'],
            Paiement::class => ['reference', 'ulid'],
            PaymentTransaction::class => ['transaction_uid', 'reference_externe'],
            SalaryAdvance::class => ['advance_uid'],
            User::class => ['username', 'ulid'],
        ];
    }

    private function recordAccess(Request $request, string $action, array $filters, int $resultCount): void
    {
        \App\Models\AuditLogAccess::create([
            'user_id'      => $request->user()->id,
            'actor_name'   => $request->user()->name,
            'actor_email'  => $request->user()->email,
            'action'       => $action,
            'filters'      => $filters,
            'result_count' => $resultCount,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);
    }

    private function csvSafe(?string $value): string
    {
        if ($value !== '' && preg_match('/^[\s]*[=+\-@]/u', $value)) {
            return "'" . $value;
        }

        return $value ?? '';
    }

    private function modelLabel(string $model): string
    {
        return [
            'Admin' => 'Administrateur',
            'Agent' => 'Agent terrain',
            'Bonus' => 'Commission / bonus',
            'Carnet' => 'Carnet d’épargne',
            'CashClosing' => 'Clôture de caisse',
            'CashSession' => 'Session de caisse',
            'Client' => 'Client',
            'ClientCarnetNumber' => 'Numéro de carnet client',
            'Credit' => 'Crédit',
            'CreditPayment' => 'Échéance de crédit',
            'Depense' => 'Dépense',
            'Depot' => 'Dépôt',
            'EmployeAdministratif' => 'Employé administratif',
            'MouvementStockCarnets' => 'Mouvement de stock de carnets',
            'Paiement' => 'Paiement de commission',
            'PaymentTransaction' => 'Transaction de remboursement',
            'Retrait' => 'Retrait',
            'Role' => 'Rôle et droits',
            'SalaryAdvance' => 'Avance sur salaire',
            'User' => 'Utilisateur',
        ][class_basename($model)] ?? class_basename($model);
    }
}
