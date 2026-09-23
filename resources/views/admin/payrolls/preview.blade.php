@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4 py-4">
        <!-- En-tête de la page -->
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <a href="{{ route('admin.payrolls.index', ['mois' => $salaire->mois, 'annee' => $salaire->annee]) }}"
                    class="btn btn-outline-secondary btn-sm mb-2">
                    <i class="bi bi-arrow-left me-1"></i> Retour au tableau de paie
                </a>
                <h2 class="h4 fw-bold text-dark mb-0">Bulletin détaillé & traçabilité analytique</h2>
                <p class="text-muted small">
                    @php
                        $moisFr = [
                            1 => 'janvier',
                            2 => 'février',
                            3 => 'mars',
                            4 => 'avril',
                            5 => 'mai',
                            6 => 'juin',
                            7 => 'juillet',
                            8 => 'août',
                            9 => 'septembre',
                            10 => 'octobre',
                            11 => 'novembre',
                            12 => 'décembre',
                        ];
                        $nomMois = $moisFr[$salaire->mois] ?? '---';
                    @endphp
                    Période : <span class="fw-bold text-dark">
                        {{ ucfirst($nomMois) }} {{ $salaire->annee }}

                </p>
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

                            <div>
                                <h5 class="mb-1 fw-bold">
                                    {{ $salaire->agent->nom ?? '---' }} {{ $salaire->agent->prenom ?? '' }}
                                </h5>
                                <span class="badge bg-secondary font-monospace">Code :
                                    {{ $salaire->agent->code_agent }}</span>
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
                                    class="badge {{ ($salaire->statut ?? '') === 'Valide' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $salaire->statut ?? 'En attente' }}

                                </span>

                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Généré le :</span>
                                <strong class="text-dark">
                                    {{ optional($salaire->created_at)->format('d/m/Y à H:i') ?? '---' }}
                                </strong>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Validé par :</span>
                                <strong
                                    class="text-dark">{{ $salaire->validator->name ?? 'En attente de validation' }}</strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Date de validation :</span>
                                <strong class="text-dark">
                                    {{ $salaire->validated_at ? \Carbon\Carbon::parse($salaire->validated_at)->format('d/m/Y à H:i') : '---' }}
                                </strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

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
                                <td>{{ $salaire->taux_carnet ?? 0 }} % réglementaire</td>
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
                                <td colspan="2">Primes validées sur la période</td>
                                <td class="text-end font-monospace">
                                    {{ number_format($salaire->bonus ?? 0, 0, ',', ' ') }}
                                </td>
                            </tr>

                            <!-- Avances sur Salaire (Déduction) -->
                            <tr class="table-danger-subtle">
                                <td class="fw-bold text-danger">Avances sur Salaire</td>
                                <td colspan="2">Prélèvements validés sur la période</td>
                                <td class="text-end font-monospace text-danger">
                                    - {{ number_format($salaire->total_avances ?? 0, 0, ',', ' ') }}
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

        <!-- Sections Détaillées : Carnets, Cycles, Bonus et Avances -->
        <div class="row g-4">
            <!-- A. Journal Détaillé des Carnets -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title h6 fw-bold mb-0 text-primary">
                            <i class="bi bi-journal-text me-2"></i> A. Journal détaillé des carnets (Taux
                            {{ $salaire->taux_carnet ?? 0 }}%)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light fs-7">
                                    <tr>
                                        <th>Numéro du Carnet</th>
                                        <th>Nom / Propriétaire</th>
                                        <th>Commission Générée</th>
                                        <th>Date d'assignation</th>
                                        <th>Statut du Carnet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($carnets ?? [] as $carnet)
                                        <tr>
                                            <td class="font-monospace fw-bold">{{ $carnet->numero ?? '---' }}</td>
                                            <td>{{ $carnet->client->nom ?? '---' }} {{ $carnet->client->prenom ?? '' }}
                                            </td>
                                            <td class="font-monospace text-success fw-bold">
                                                {{ number_format($carnet->commission_generee ?? 0, 0, ',', ' ') }} FCFA
                                            </td>
                                            <td class="font-monospace">
                                                {{ $carnet->assigned_at ? \Carbon\Carbon::parse($carnet->assigned_at)->format('d/m/Y') : '---' }}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge {{ ($carnet->statut ?? '') === 'actif' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ ucfirst($carnet->statut ?? 'inconnu') }}
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
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title h6 fw-bold mb-0 text-success">
                            <i class="bi bi-arrow-repeat me-2"></i> B. Historique des cycles de tontine
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
                                        <th>Clôture prévue</th>
                                        <th>Clôture réelle</th>
                                        <th class="text-end">Pointages</th>
                                        <th class="text-end">Montant global</th>
                                        <th class="text-end">Commission agent</th>
                                        <th>Validation</th>
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
                                            <td class="text-end">{{ $cycle->nombre_pointages ?? 0 }}</td>
                                            <td class="font-monospace text-end">
                                                {{ number_format(($cycle->mise ?? 0) * ($cycle->nombre_pointages ?? 0), 0, ',', ' ') }}
                                                FCFA
                                            </td>
                                            <td class="font-monospace text-success fw-bold text-end">
                                                {{ number_format($cycle->commission_genere ?? 0, 0, ',', ' ') }} FCFA
                                            </td>
                                            <td>
                                                @if (!empty($cycle->validated_at))
                                                    <span
                                                        class="fw-semibold text-dark">{{ $cycle->validateur_nom ?? 'Admin' }}</span>
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

            <!-- C. Bonus et gratifications du mois -->
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
                                            <td>{{ $bonus->date_attribution ? \Carbon\Carbon::parse($bonus->date_attribution)->format('d/m/Y') : '---' }}
                                            </td>
                                            <td>{{ $bonus->motif ?? '---' }}</td>
                                            <td class="font-monospace text-success fw-bold">
                                                {{ number_format($bonus->montant ?? 0, 0, ',', ' ') }} FCFA
                                            </td>
                                            <td>{{ optional($bonus->admin)->name ?? '---' }}</td>
                                            <td>
                                                @if (!empty($bonus->validated_by))
                                                    <span
                                                        class="fw-semibold text-dark">{{ optional($bonus->validator)->name ?? '---' }}</span>
                                                    <br><small
                                                        class="text-muted">{{ \Carbon\Carbon::parse($bonus->validated_at)->format('d/m/Y H:i') }}</small>
                                                @else
                                                    <span class="badge bg-warning text-dark">En attente</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="badge {{ ($bonus->statut ?? '') === 'valide' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ ucfirst($bonus->statut ?? 'en attente') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">Aucun bonus manuel pour
                                                cette période.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- D. Historique des Avances sur Salaire -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title h6 fw-bold mb-0 text-danger">
                            <i class="bi bi-wallet2 me-2"></i> D. Historique des avances sur salaire déduites
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light fs-7">
                                    <tr>
                                        <th>Date de demande / création</th>
                                        <th>Motif</th>
                                        <th>Montant initial / Déduit</th>
                                        <th>Statut</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($avancesList ?? [] as $avance)
                                        @php
                                            // On calcule le montant en multipliant le nombre de tranches sélectionnées/actives par le montant mensuel
                                            $tranchesActivesCount = collect($avance->tranches_formatees ?? [])
                                                ->where('selectionne', true)
                                                ->count();

                                            $montantDeduuitMois =
                                                $tranchesActivesCount * ($avance->montant_mensuel ?? 0);
                                        @endphp
                                        <tr>
                                            <td>{{ optional($avance->created_at)->format('d/m/Y') ?? '---' }}</td>
                                            <td>{{ $avance->motif ?? 'Avance sur salaire' }}</td>
                                            <td class="font-monospace text-danger fw-bold">
                                                - {{ number_format($montantDeduuitMois, 0, ',', ' ') }} FCFA
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    {{ ucfirst($avance->statut ?? 'en attente') }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <!-- Bouton pour ouvrir le modal spécifique à cette avance -->
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalAvance-{{ $avance->id }}">
                                                    <i class="bi bi-list-check me-1"></i> Gérer les tranches
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Aucune avance sur
                                                salaire enregistrée pour cette période.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @foreach ($avancesList ?? [] as $avance)
        <div class="modal fade" id="modalAvance-{{ $avance->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.payrolls.avances.updateTranches', $avance->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- On transmet également le mois et l'année pour que le contrôleur sache quel bulletin mettre à jour --}}
                        <input type="hidden" name="mois" value="{{ $salaire->mois ?? request('mois') }}">
                        <input type="hidden" name="annee" value="{{ $salaire->annee ?? request('annee') }}">
                        <input type="hidden" name="agent_id" value="{{ $avance->agent_id }}">

                        <div class="modal-header bg-light">
                            <h5 class="modal-title h6 fw-bold">
                                <i class="bi bi-wallet2 text-danger me-2"></i> Gestion des tranches -
                                {{ $avance->motif ?? 'Avance sur salaire' }}
                                <span
                                    class="text-muted fs-7">({{ number_format($avance->montant_total ?? 0, 0, ',', ' ') }}
                                    FCFA)</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Fermer"></button>
                        </div>

                        <div class="modal-body">
                            <p class="text-muted small">Cochez ou décochez les tranches à déduire pour ce bulletin de paie.
                                Les tranches des mois passés sont verrouillées.</p>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light fs-7">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">Sélection</th>
                                            <th>Tranche / Mois</th>
                                            <th>Montant</th>
                                            <th>État</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($avance->tranches_formatees ?? [] as $tranche)
                                            <tr class="{{ $tranche['deja_paye'] ? 'table-light' : '' }}">
                                                <td class="text-center">
                                                    @if ($tranche['deja_paye'])
                                                        {{-- Champ caché indispensable car un input disabled n'est pas envoyé par le formulaire --}}
                                                        <input type="hidden" name="tranches_ids[]"
                                                            value="{{ $tranche['numero'] }}">
                                                        <input type="checkbox" class="form-check-input" checked disabled>
                                                    @else
                                                        <input type="checkbox" name="tranches_ids[]"
                                                            value="{{ $tranche['numero'] }}" class="form-check-input"
                                                            {{ $tranche['selectionne'] ? 'checked' : '' }}>
                                                    @endif
                                                </td>
                                                <td class="fw-bold">
                                                    Tranche {{ $tranche['numero'] }} / {{ $avance->nombre_tranches }}
                                                    <span
                                                        class="text-muted fw-normal">({{ ucfirst($tranche['mois']) }})</span>
                                                </td>
                                                <td class="font-monospace text-danger">
                                                    {{ number_format($tranche['montant'], 0, ',', ' ') }} FCFA
                                                </td>
                                                <td>
                                                    @if ($tranche['deja_paye'])
                                                        <span class="badge bg-success">Déjà réglé (Verrouillé)</span>
                                                    @elseif ($tranche['selectionne'])
                                                        <span class="badge bg-primary">Sélectionné pour ce mois</span>
                                                    @else
                                                        <span class="badge bg-secondary text-light">Non sélectionné</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm"
                                data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-danger btn-sm">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
