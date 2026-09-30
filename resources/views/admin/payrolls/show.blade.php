@extends('admin.layouts.app')

@section('content')
    <div class="container py-4">

        <!-- En-tête de page (Actions - Masqué à l'impression) -->
        <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
            <div>
                <a href="{{ route('admin.payrolls.index', ['mois' => $salaire->mois, 'annee' => $salaire->annee]) }}"
                    class="text-decoration-none text-muted small fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i> Retour à la gestion des salaires
                </a>
                <h2 class="h4 text-dark fw-bold mb-0 mt-1">Consultation du Bulletin</h2>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-primary btn-sm px-3 shadow-sm">
                    <i class="bi bi-printer me-1"></i> Imprimer / PDF
                </button>
            </div>
        </div>

        <!-- CARTE PRINCIPALE DU BULLETIN OFFICIEL -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white invoice-card">

            <!-- En-tête du Bulletin (Branding & Réf) -->
            <div class="card-header bg-white border-bottom p-4 p-md-5 pb-4">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-primary text-white rounded-3 p-2 me-3 d-flex align-items-center justify-content-center"
                                style="width: 42px; height: 42px;">
                                <i class="bi bi-shield-check fs-5"></i>
                            </div>
                            <div>
                                <h4 class="fw-bold text-dark mb-0">BULLETIN DE PAIE</h4>
                                <span class="text-muted small">Édition Officielle & Verrouillée</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill font-monospace small">
                            Réf :
                            {{ $salaire->reference ?? 'PAY-' . str_pad($salaire->mois, 2, '0', STR_PAD_LEFT) . '-' . $salaire->id }}
                        </span>
                        <div class="text-muted small mt-2">
                            Période de paie : <strong class="text-dark">{{ str_pad($salaire->mois, 2, '0', STR_PAD_LEFT) }}
                                / {{ $salaire->annee }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">

                <!-- Blocs d'Informations (Agent & Validation) -->
                <div class="row g-4 mb-5 pb-4 border-bottom">
                    <!-- Infos Agent -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100 border-0">
                            <span class="text-uppercase text-muted fw-bold x-small tracking-wider d-block mb-2">Informations
                                Salarié</span>
                            <h5 class="fw-bold text-dark mb-1">{{ $salaire->agent->nom ?? '---' }}</h5>
                            <p class="text-muted small mb-1">Code Agent : <span
                                    class="text-dark fw-medium">{{ $salaire->agent->code_agent ?? 'N/A' }}</span></p>
                            <p class="text-muted small mb-0">Poste : <span
                                    class="text-dark fw-medium">{{ $salaire->agent->poste ?? 'Agent de terrain' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Infos Traçabilité & Validation -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100 border-0">
                            <span class="text-uppercase text-muted fw-bold x-small tracking-wider d-block mb-2">Sécurité &
                                Traçabilité</span>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Statut :</span>
                                <span class="badge bg-secondary bg-opacity-15 px-2 py-1">Validé & Clôturé</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Validé par :</span>
                                <span
                                    class="text-dark fw-medium small">{{ optional($salaire->validator)->name ?? 'Administration' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Date de validation :</span>
                                <span
                                    class="text-dark fw-medium small">{{ $salaire->validated_at ? \Carbon\Carbon::parse($salaire->validated_at)->format('d/m/Y à H:i') : '---' }}</span>
                            </div>
                            @if ($salaire->depense)
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Pièce de dépense :</span>
                                    <span
                                        class="text-primary fw-semibold small">{{ $salaire->depense->reference_piece ?? 'Liée' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tableau Financier -->
                <div class="table-responsive mb-4">
                    <table class="table align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-muted border-bottom">
                            <tr>
                                <th class="py-3 ps-3">Élément de Rémunération</th>
                                <th class="py-3 text-end pe-3">Montant (FCFA)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-3 py-3">
                                    <span class="fw-semibold text-dark">Salaire de Base</span>
                                </td>
                                <td class="text-end pe-3 fw-medium">
                                    {{ number_format($salaire->salaire_base, 0, ',', ' ') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-3">
                                    <span class="text-dark">Commission sur Travail</span>
                                </td>
                                <td class="text-end pe-3 fw-medium">
                                    {{ number_format($salaire->commission_travail, 0, ',', ' ') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-3">
                                    <span class="text-dark">Commission Carnets (Tontine)</span>
                                </td>
                                <td class="text-end pe-3 fw-medium">
                                    {{ number_format($salaire->commission_carnet, 0, ',', ' ') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-3">
                                    <span class="text-dark">Commission Cycles</span>
                                </td>
                                <td class="text-end pe-3 fw-medium">
                                    {{ number_format($salaire->commission_cycle, 0, ',', ' ') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-3">
                                    <span class="text-dark">Primes & Bonus de performance</span>
                                </td>
                                <td class="text-end pe-3 fw-medium">
                                    {{ number_format($salaire->bonus, 0, ',', ' ') }}
                                </td>
                            </tr>

                            <!-- Ligne Total Brut -->
                            <tr class="bg-light fw-bold">
                                <td class="ps-3 py-3 text-uppercase text-secondary fs-7">Total Brut Global</td>
                                <td class="text-end pe-3 text-dark">
                                    {{ number_format($salaire->salaire_base + $salaire->commission_travail + $salaire->commission_carnet + $salaire->commission_cycle + $salaire->bonus, 0, ',', ' ') }}
                                </td>
                            </tr>

                            <!-- Déductions & Avances sur salaire -->
                            @php
                                $totalAvances = $salaire->total_avances ?? 0;
                            @endphp

                            @if ($totalAvances > 0)
                                <tr class="table-danger bg-opacity-10">
                                    <td class="ps-3 py-3 text-danger">
                                        <i class="bi bi-dash-circle me-1"></i> Déductions (Avances sur salaire)
                                    </td>
                                    <td class="text-end pe-3 text-danger fw-semibold">
                                        - {{ number_format($totalAvances, 0, ',', ' ') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>

                        <!-- Pied du tableau : Net à payer -->
                        <tfoot>
                            <tr class="border-top border-2">
                                <td class="ps-3 py-4 bg-dark text-white rounded-start-3">
                                    <span class="fs-6 fw-bold text-uppercase tracking-wider">Net à Payer</span>
                                </td>
                                <td class="text-end pe-3 py-4 bg-dark text-white rounded-end-3">
                                    <span class="fs-4 fw-black font-monospace text-warning">
                                        {{ number_format($salaire->montant_net, 0, ',', ' ') }} FCFA
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Mentions légales / Pied de page du bulletin -->
                <div class="text-center pt-4 border-top text-muted x-small">
                    <p class="mb-1">Ce document est un état de paie officiel généré et sécurisé par le système de gestion
                        interne.</p>
                    <p class="mb-0">Archivé électroniquement — Aucune signature manuscrite requise conformément aux
                        protocoles de validation interne.</p>
                </div>

            </div>
        </div>
    </div>

    <!-- Styles spécifiques pour l'impression et les détails visuels -->
    @push('styles')
        <style>
            .x-small {
                font-size: 0.75rem;
            }

            .tracking-wider {
                letter-spacing: 0.05em;
            }

            @media print {

                /* Masquer la navigation, le footer et les boutons d'action */
                body {
                    background-color: #fff !important;
                    color: #000 !important;
                    -webkit-print-color-adjust: exact;
                }

                .navbar,
                .sidebar,
                footer,
                .d-print-none {
                    display: none !important;
                }

                .container {
                    max-width: 100% !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }

                .card {
                    border: none !important;
                    box-shadow: none !important;
                }

                .card-body {
                    padding: 1rem !important;
                }

                .bg-light {
                    background-color: #f8f9fa !important;
                    border: 1px solid #dee2e6 !important;
                }

                .bg-dark {
                    background-color: #212529 !important;
                    color: #fff !important;
                }
            }
        </style>
    @endpush
@endsection
