@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4 py-4">
        <!-- En-tête de la page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                {{-- @dd($salaire) --}}
                <a href="{{ route('admin.payrolls.index', ['mois' => $salaire->mois, 'annee' => $salaire->annee]) }}"
                    class="btn btn-outline-secondary btn-sm mb-2">
                    <i class="bi bi-arrow-left me-1"></i> Retour au tableau de paie
                </a>
                <h2 class="h4 fw-bold text-dark mb-0">Bulletin détaillé & traçabilité analytique</h2>
                <p class="text-muted small">Période : <span
                        class="fw-bold text-dark">{{ ucfirst(\Carbon\Carbon::create(null, $salaire->mois, 1)->locale('fr')->monthName) }}
                        {{ $salaire->annee }}</span> | Réf : <span
                        class="font-monospace text-primary">{{ $salaire->reference }}</span></p>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-dark btn-sm shadow-sm">
                    <i class="bi bi-printer me-1"></i> Imprimer le bulletin
                </button>
            </div>
        </div>

        <!-- Section 1 : Infos Agent & Traçabilité de Validation -->
        <div class="row g-4 mb-4">
            <!-- Carte Agent -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted fw-bold small mb-3">Informations de l'agent</h6>
                        <div class="d-flex align-items-center">
                            <div class="bg-success-subtle text-success rounded-circle p-3 fw-bold me-3 fs-5">
                                {{ substr($salaire->agent->prenom ?? 'A', 0, 1) }}{{ substr($salaire->agent->nom ?? 'G', 0, 1) }}
                            </div>
                            <div>
                                <h5 class="mb-1 fw-bold">{{ $salaire->agent->nom ?? '---' }}
                                    {{ $salaire->agent->prenom ?? '' }}</h5>
                                <span class="badge bg-secondary font-monospace">Code :
                                    {{ $salaire->agent->code_agent ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte Traçabilité -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted fw-bold small mb-3">Traçabilité & sécurité financière</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Statut global :</span>
                                <span
                                    class="badge {{ $salaire->statut === 'Validé' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $salaire->statut }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Généré le :</span>
                                <strong
                                    class="text-dark">{{ $salaire->created_at ? $salaire->created_at->format('d/m/Y à H:i') : '---' }}</strong>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Validé par :</span>
                                <strong
                                    class="text-dark">{{ $salaire->validator->name ?? 'En attente de validation' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Date de validation :</span>
                                <strong
                                    class="text-dark">{{ $salaire->validated_at ? \Carbon\Carbon::parse($salaire->validated_at)->format('d/m/Y à H:i') : '---' }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        {{-- @dump($salaire) --}}

        <!-- Récapitulatif Financier Global -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title h6 fw-bold mb-0">Décomposition analytique du salaire net</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7">
                            <tr>
                                <th>Composante</th>
                                <th>Base / Source</th>
                                <th>Règle / Taux</th>
                                <th class="text-end">Montant (FCFA)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Salaire de Base -->
                            <tr>
                                <td class="fw-bold">Salaire de Base</td>
                                <td>Grille salariale active</td>
                                <td>Tranche par seuil de commissions</td>
                                <td class="text-end font-monospace">
                                    {{ number_format($salaire->salaire_base ?? 0, 0, ',', ' ') }}
                                </td>
                            </tr>

                            <!-- Commissions sur Collectes (Travail) -->
                            <tr>
                                <td class="fw-bold">Commissions sur Collectes (Travail)</td>
                                <td>Volume mensuel global</td>
                                <td>Barème fixe</td>
                                <td class="text-end font-monospace">
                                    {{ number_format($salaire->commission_travail ?? 0, 0, ',', ' ') }}
                                </td>
                            </tr>

                            <!-- Commissions sur Carnets -->
                            <tr>
                                <td class="fw-bold">Commissions sur Carnets</td>
                                <td>Portefeuille de carnets actifs</td>
                                <td>{{ $salaire->taux_carnet }} % réglementaire</td>
                                <td class="text-end font-monospace">
                                    {{ number_format($salaire->commission_carnet ?? 0, 0, ',', ' ') }}
                                </td>
                            </tr>

                            <!-- Commissions de Cycles -->
                            <tr>
                                <td class="fw-bold">Commissions de Cycles</td>
                                <td>Clôtures de cycles</td>
                                <td>Palier de cycle</td>
                                <td class="text-end font-monospace">
                                    {{ number_format($salaire->commission_cycle ?? 0, 0, ',', ' ') }}
                                </td>
                            </tr>

                            <!-- Bonus Exceptionnels -->
                            <tr>
                                <td class="fw-bold">Bonus Exceptionnels</td>
                                <td colspan="2">
                                    {{-- <span class="text-muted fst-italic">{{ $salaire->motif_bonus ?? 'Aucun motif renseigné' }}</span> --}}
                                </td>
                                <td class="text-end font-monospace">
                                    {{ number_format($salaire->bonus ?? 0, 0, ',', ' ') }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="fw-bold text-uppercase text-end">Salaire Net à Verser :</td>
                                <td class="text-end text-success fs-5 fw-bold font-monospace">
                                    {{ number_format($salaire->montant_net ?? 0, 0, ',', ' ') }} FCFA
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sections Détaillées : Carnets, Cycles et Audit -->
        <div class="row g-4">
            <!-- A. Journal Détaillé des Carnets -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title h6 fw-bold mb-0 text-primary">
                            <i class="bi bi-journal-text me-2"></i> A. Journal détaillé des carnets (Commissions carnets -
                            Taux {{ $salaire->taux_carnet }}%)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light fs-7">
                                    <tr>
                                        <th>Numéro du Carnet</th>
                                        <th>Nom / Propriétaire</th>
                                        {{-- <th>Montant Total Cotisé (Mois)</th> --}}
                                        <th>Commission Générée</th>
                                        <th>Date d'assignation</th>
                                        <th>Statut du Carnet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($carnets ?? [] as $carnet)
                                        <tr>
                                            <td class="font-monospace fw-bold">{{ $carnet->numero }}</td>
                                            <td>{{ $carnet->client->nom ?? '---' }} {{ $carnet->client->prenom ?? '' }}
                                            </td>
                                            {{-- <td class="font-monospace">
                                                {{ number_format($carnet->montant_cotise_mois, 0, ',', ' ') }} FCFA</td> --}}
                                            <td class="font-monospace text-success fw-bold">
                                                {{ number_format($carnet->commission_generee, 0, ',', ' ') }} FCFA</td>

                                            <!-- Affichage de la date d'assignation -->
                                            <td class="font-monospace">
                                                {{ $carnet->assigned_at ? \Carbon\Carbon::parse($carnet->assigned_at)->format('d/m/Y') : '---' }}
                                            </td>

                                            <td>
                                                <span
                                                    class="badge {{ $carnet->statut === 'actif' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ ucfirst($carnet->statut) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Aucun carnet actif ou
                                                mouvementé pour cette période.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- B. Historique des Cycles de Tontine -->
            @php
                // var_dump($cycles);
            @endphp
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title h6 fw-bold mb-0 text-success">
                            <i class="bi bi-arrow-repeat me-2"></i> B. Historique des cycles de tontine (Commissions de
                            cycles)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light fs-7">
                                    <tr>
                                        <th>N° Carnet</th>
                                        <th>Client</th>
                                        <th>Date de début</th>
                                        <th>Date de clôture prévue</th>
                                        <th>Date de clôture réelle</th>
                                        <th>Pointages</th>
                                        <th>Montant global géré</th>
                                        <th>Part de commission agent</th>
                                        <th>Validé par</th> <!-- Nouvelle colonne -->
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cycles ?? [] as $cycle)
                                        <tr>
                                            <td class="font-monospace fw-bold">{{ $cycle->carnet->numero ?? '---' }}</td>
                                            <td>{{ $cycle->carnet->client->nom ?? '---' }}
                                                {{ $cycle->carnet->client->prenom ?? '' }}</td>
                                            <td>{{ $cycle->date_debut ? \Carbon\Carbon::parse($cycle->date_debut)->format('d/m/Y') : '---' }}
                                            </td>
                                            <td>{{ $cycle->date_fin_prevue ? \Carbon\Carbon::parse($cycle->date_fin_prevue)->format('d/m/Y') : '---' }}
                                            </td>
                                            <td>{{ $cycle->date_cloture_reelle ? \Carbon\Carbon::parse($cycle->date_cloture_reelle)->format('d/m/Y') : 'En cours' }}
                                            </td>
                                            <td class="text-end">{{ $cycle->nombre_pointages }}</td>
                                            <td class="font-monospace text-end">
                                                {{ number_format($cycle->mise * $cycle->nombre_pointages ?? 0, 0, ',', ' ') }}
                                                FCFA
                                            </td>
                                            <td class="font-monospace text-success fw-bold text-end">
                                                {{ number_format($cycle->commission_genere ?? 0, 0, ',', ' ') }} FCFA
                                            </td>

                                            <!-- Colonne Validation (Admin + Date) -->
                                            <td>
                                                @if ($cycle->validated_at)
                                                    <span class="fw-semibold text-dark">{{ $cycle->validateur_nom }}</span>
                                                    <br><small
                                                        class="text-muted">{{ \Carbon\Carbon::parse($cycle->validated_at)->format('d/m/Y H:i') }}</small>
                                                @else
                                                    <span class="badge bg-warning text-dark">En attente</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-3">Aucun cycle trouvé pour
                                                cette période.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title h6 fw-bold mb-0 text-success">
                            <i class="bi bi-star-fill me-2"></i> C. Bonus et gratifications du mois
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light fs-7">
                                    <tr>
                                        <th>Date d'attribution</th>
                                        <th>Motif</th>
                                        <th>Montant</th>
                                        <th>Créé par</th>
                                        <th>Validé par</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bonusManuels ?? [] as $bonus)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($bonus->date_attribution)->format('d/m/Y') }}</td>
                                            <td>{{ $bonus->motif ?? '---' }}</td>
                                            <td class="font-monospace text-success fw-bold">
                                                {{ number_format($bonus->montant, 0, ',', ' ') }} FCFA</td>

                                            <!-- Créateur (admin_id) -->
                                            <td>
                                                {{ optional($bonus->admin)->name ?? '---' }}
                                            </td>

                                            <!-- Validateur (validated_by) -->
                                            <td>
                                                @if ($bonus->validated_by)
                                                    <span
                                                        class="fw-semibold text-dark">{{ optional($bonus->validator)->name }}</span>
                                                    <br><small
                                                        class="text-muted">{{ \Carbon\Carbon::parse($bonus->validated_at)->format('d/m/Y H:i') }}</small>
                                                @else
                                                    <span class="badge bg-warning text-dark">En attente</span>
                                                @endif
                                            </td>

                                            <td>
                                                <span
                                                    class="badge {{ $bonus->statut === 'valide' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ ucfirst($bonus->statut) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">Aucun bonus manuel pour
                                                cette période.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <!-- C. Historique et Audit de Validation (Prêt pour laravel-auditing) -->
            {{-- <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title h6 fw-bold mb-0 text-dark">
                            <i class="bi bi-shield-check me-2"></i> C. Historique et Audit des Modifications
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light fs-7">
                                    <tr>
                                        <th>Date / Heure</th>
                                        <th>Auteur de l'action</th>
                                        <th>Événement</th>
                                        <th>Détails des modifications (Audit Log)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (method_exists($salaire, 'audits') && $salaire->audits->count() > 0)
                                        @foreach ($salaire->audits as $audit)
                                            <tr>
                                                <td>{{ $audit->created_at->format('d/m/Y à H:i') }}</td>
                                                <td class="fw-bold">{{ optional($audit->user)->name ?? 'Système' }}</td>
                                                <td><span
                                                        class="badge bg-info text-dark">{{ ucfirst($audit->event) }}</span>
                                                </td>
                                                <td class="small text-muted">
                                                    Modifications : {{ implode(', ', array_keys($audit->getModified())) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                <span class="fst-italic">Journal d'audit inactif ou aucune modification
                                                    enregistrée (module laravel-auditing prêt à être branché).</span>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> --}}
        </div>
    </div>
@endsection
