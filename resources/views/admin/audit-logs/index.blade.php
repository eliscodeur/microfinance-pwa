@extends('admin.layouts.app')

@section('content')
    @php
        $eventLabels = [
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            'restored' => 'Restauration',
            'cash_expense_rejected' => 'Dépense refusée',
        ];
    @endphp

    <div class="container-fluid px-0">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="rounded-circle bg-primary-subtle text-primary p-2"><i class="bi bi-shield-lock fs-5"></i></span>
                    <h1 class="h3 mb-0 fw-bold text-dark">Journal d’audit</h1>
                </div>
                <p class="text-muted mb-0">Traçabilité des opérations et changements effectués dans l’application.</p>
            </div>
            @can('Exporter les audits')
                <a class="btn btn-outline-primary px-3"
                    href="{{ route('admin.audit-logs.export', request()->query()) }}">
                    <i class="bi bi-download me-2"></i>Exporter le résultat filtré
                </a>
            @endcan
        </div>

        <div class="alert alert-light border d-flex gap-3 align-items-start mb-4" role="note">
            <i class="bi bi-info-circle text-primary fs-5"></i>
            <div class="small text-muted">
                Les consultations et exports de ce journal sont eux-mêmes tracés. Les champs d’authentification,
                secrets et PIN sont masqués, y compris dans les anciennes traces.
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-uppercase text-muted fw-semibold">Événements filtrés</div>
                            <div class="fs-3 fw-bold text-dark">{{ number_format($total, 0, ',', ' ') }}</div>
                        </div>
                        <span class="rounded-3 bg-primary-subtle text-primary p-3"><i class="bi bi-list-check fs-4"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-uppercase text-muted fw-semibold">Entités concernées</div>
                            <div class="fs-3 fw-bold text-dark">{{ number_format($modelCount, 0, ',', ' ') }}</div>
                        </div>
                        <span class="rounded-3 bg-info-subtle text-info p-3"><i class="bi bi-collection fs-4"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-uppercase text-muted fw-semibold">Auteurs identifiés</div>
                            <div class="fs-3 fw-bold text-dark">{{ number_format($actorCount, 0, ',', ' ') }}</div>
                        </div>
                        <span class="rounded-3 bg-success-subtle text-success p-3"><i class="bi bi-person-check fs-4"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h2 class="h6 fw-bold mb-0"><i class="bi bi-funnel me-2 text-primary"></i>Critères de recherche</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-3 align-items-end">
                    <div class="col-12 col-sm-6 col-xl-2">
                        <label for="date_from" class="form-label small fw-semibold">Du</label>
                        <input type="date" id="date_from" name="date_from" class="form-control"
                            value="{{ $filters['date_from'] }}">
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <label for="date_to" class="form-label small fw-semibold">Au</label>
                        <input type="date" id="date_to" name="date_to" class="form-control"
                            value="{{ $filters['date_to'] }}">
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <label for="user_id" class="form-label small fw-semibold">Auteur</label>
                        <select id="user_id" name="user_id" class="form-select">
                            <option value="">Tous les auteurs</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>
                                    {{ $user->name }} — {{ $user->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <label for="event" class="form-label small fw-semibold">Action</label>
                        <select id="event" name="event" class="form-select">
                            <option value="">Toutes les actions</option>
                            @foreach ($events as $event)
                                <option value="{{ $event }}" @selected($filters['event'] === $event)>
                                    {{ $eventLabels[$event] ?? ucfirst($event) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <label for="auditable_type" class="form-label small fw-semibold">Type d’opération</label>
                        <select id="auditable_type" name="auditable_type" class="form-select">
                            <option value="">Toutes les entités</option>
                            @foreach ($models as $model)
                                <option value="{{ $model }}" @selected($filters['auditable_type'] === $model)>
                                    {{ $modelLabels[$model] ?? class_basename($model) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <label for="reference" class="form-label small fw-semibold">ID ou référence métier</label>
                        <input type="text" id="reference" name="reference" class="form-control"
                            value="{{ $filters['reference'] }}" placeholder="ID, ULID, numéro, code…">
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-light border">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-search me-2"></i>Appliquer les filtres
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 py-3">
                <div>
                    <h2 class="h6 fw-bold mb-1">Registre des événements</h2>
                    <div class="small text-muted">Période du {{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }}
                        au {{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}</div>
                </div>
                <span class="badge rounded-pill text-bg-light border">{{ number_format($total, 0, ',', ' ') }} résultat(s)</span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date / heure</th>
                                <th>Auteur</th>
                                <th>Action</th>
                                <th>Objet concerné</th>
                                <th>Adresse IP</th>
                                <th class="text-end pe-4">Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($audits as $audit)
                                @php
                                    $badgeClass = match ($audit->event) {
                                        'created' => 'text-bg-success',
                                        'updated' => 'text-bg-primary',
                                        'deleted' => 'text-bg-danger',
                                        'restored' => 'text-bg-warning',
                                        'cash_expense_rejected' => 'text-bg-danger',
                                        default => 'text-bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-4 text-nowrap">
                                        <div class="fw-semibold">{{ optional($audit->created_at)->format('d/m/Y') }}</div>
                                        <div class="small text-muted">{{ optional($audit->created_at)->format('H:i:s') }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ optional($audit->user)->name ?? ($audit->user_id ? 'Compte supprimé' : 'Système') }}
                                        </div>
                                        <div class="small text-muted">{{ optional($audit->user)->email ?? 'Auteur non disponible' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $eventLabels[$audit->event] ?? ucfirst($audit->event) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $audit->auditable_label }}</div>
                                        @if ($audit->auditable_reference)
                                            <div class="small text-primary text-break">{{ $audit->auditable_reference }}</div>
                                        @endif
                                        <div class="small text-muted text-break">ID interne : {{ $audit->auditable_id }}</div>
                                    </td>
                                    <td class="small text-muted text-nowrap">{{ $audit->ip_address ?: '—' }}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#audit-detail-{{ $audit->id }}"
                                            aria-expanded="false" aria-controls="audit-detail-{{ $audit->id }}">
                                            <i class="bi bi-eye me-1"></i>Voir
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse bg-light" id="audit-detail-{{ $audit->id }}">
                                    <td colspan="6" class="p-3 p-lg-4">
                                        <div class="row g-3">
                                            <div class="col-12 col-lg-6">
                                                <div class="small text-uppercase fw-bold text-muted mb-2">Avant</div>
                                                <pre class="audit-values mb-0">{{ json_encode($audit->safe_old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre>
                                            </div>
                                            <div class="col-12 col-lg-6">
                                                <div class="small text-uppercase fw-bold text-muted mb-2">Après</div>
                                                <pre class="audit-values mb-0">{{ json_encode($audit->safe_new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex flex-wrap gap-3 small text-muted">
                                                    <span><i class="bi bi-globe2 me-1"></i>{{ $audit->url ?: 'URL non disponible' }}</span>
                                                    <span><i class="bi bi-window me-1"></i>{{ $audit->user_agent ?: 'Navigateur non disponible' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-inbox display-6 text-muted"></i>
                                        <p class="fw-semibold mt-3 mb-1">Aucun événement pour ces critères</p>
                                        <p class="small text-muted mb-0">Modifiez la période ou retirez certains filtres.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($audits->hasPages())
                <div class="card-footer bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <small class="text-muted">
                        Affichage de {{ $audits->firstItem() }} à {{ $audits->lastItem() }} sur {{ $audits->total() }}
                    </small>
                    {{ $audits->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white py-3">
                <h2 class="h6 fw-bold mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Accès récents au journal</h2>
                <p class="small text-muted mb-0">Consultations et exports effectués par les utilisateurs autorisés.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date / heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Critères utilisés</th>
                            <th>Résultats</th>
                            <th class="pe-4">Adresse IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAccesses as $access)
                            <tr>
                                <td class="ps-4 text-nowrap">{{ optional($access->created_at)->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    <div>{{ optional($access->user)->name ?? $access->actor_name }}</div>
                                    <div class="small text-muted">{{ optional($access->user)->email ?? $access->actor_email }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $access->action === 'export' ? 'text-bg-info' : 'text-bg-light border text-dark' }}">
                                        {{ $access->action === 'export' ? 'Export' : 'Consultation' }}
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    {{ $access->filters['date_from'] ?? '—' }} au {{ $access->filters['date_to'] ?? '—' }}
                                    @if (!empty($access->filters['event']))
                                        · {{ $eventLabels[$access->filters['event']] ?? $access->filters['event'] }}
                                    @endif
                                    @if (isset($access->filters['reference']) || isset($access->filters['auditable_id']))
                                        · Réf. {{ $access->filters['reference'] ?? $access->filters['auditable_id'] }}
                                    @endif
                                </td>
                                <td>{{ number_format($access->result_count, 0, ',', ' ') }}</td>
                                <td class="pe-4 small text-muted">{{ $access->ip_address ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Aucun accès enregistré.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .audit-values {
            max-height: 260px;
            overflow: auto;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: .5rem;
            background: #fff;
            color: #344054;
            font-size: .8rem;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
    </style>
@endsection
