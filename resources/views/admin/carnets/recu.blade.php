@extends('admin.layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 receipt-actions">
            <a href="{{ route('admin.carnets.show', $retrait->carnet) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour au carnet
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimer le reçu
            </button>
        </div>

        <article class="receipt mx-auto bg-white border rounded shadow-sm p-4 p-md-5">
            <header class="text-center border-bottom pb-3 mb-4">
                <h1 class="h3 fw-bold mb-1">{{ config('app.name') }}</h1>
                <div class="text-muted text-uppercase small">Reçu de retrait</div>
                <div class="mt-3 fw-bold">Référence : {{ $retrait->reference_operation ?? '—' }}</div>
            </header>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="text-muted small">Client</div>
                    <strong>{{ $retrait->client->nom }} {{ $retrait->client->prenom }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Téléphone</div>
                    <strong>{{ $retrait->client->telephone ?: '—' }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Carnet</div>
                    <strong>#{{ $retrait->carnet->numero }} ({{ ucfirst($retrait->carnet->type) }})</strong>
                </div>
                @if ($retrait->cycle_id)
                    <div class="col-sm-6">
                        <div class="text-muted small">Cycle</div>
                        <strong>#{{ $retrait->cycle_id }}</strong>
                    </div>
                @endif
                <div class="col-sm-6">
                    <div class="text-muted small">Date de l'opération</div>
                    <strong>{{ $retrait->date_retrait->format('d/m/Y à H:i') }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Agent de caisse</div>
                    <strong>{{ $retrait->admin->name ?? '—' }}</strong>
                </div>
            </div>

            <table class="table table-bordered align-middle">
                <tbody>
                    <tr>
                        <th>Montant total débité du carnet</th>
                        <td class="text-end">{{ number_format((float) $retrait->montant_total, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <th>Solde du carnet avant retrait</th>
                        <td class="text-end">{{ number_format((float) $retrait->solde_carnet_avant, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <th>Commission</th>
                        <td class="text-end">{{ number_format((float) $retrait->commission, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr class="table-light">
                        <th>Montant net remis au client</th>
                        <td class="text-end fw-bold fs-5">{{ number_format((float) $retrait->montant_net, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <th>Solde du carnet après retrait</th>
                        <td class="text-end">{{ number_format((float) $retrait->solde_carnet_apres, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <th>Mode de paiement</th>
                        <td class="text-end">
                            {{ ['especes' => 'Espèces', 'mobile_money' => 'Mobile Money', 'virement' => 'Virement bancaire'][$retrait->mode_paiement] ?? ($retrait->mode_paiement ?: '—') }}
                        </td>
                    </tr>
                    @if ($retrait->reference_paiement)
                        <tr>
                            <th>Référence de paiement</th>
                            <td class="text-end">{{ $retrait->reference_paiement }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th>Motif</th>
                        <td class="text-end">{{ ucfirst(str_replace('_', ' ', $retrait->motif ?? '—')) }}</td>
                    </tr>
                </tbody>
            </table>

            @if ($retrait->note)
                <div class="mt-3">
                    <div class="text-muted small">Note</div>
                    <div>{{ $retrait->note }}</div>
                </div>
            @endif

            <div class="row text-center mt-5 pt-4">
                <div class="col-6">
                    <div class="border-top pt-2">Signature du client</div>
                </div>
                <div class="col-6">
                    <div class="border-top pt-2">Signature / cachet de la caisse</div>
                </div>
            </div>
            <footer class="text-center text-muted small border-top mt-4 pt-3">
                Reçu généré le {{ now()->format('d/m/Y à H:i') }} · Session de caisse #{{ $retrait->cash_session_id ?? '—' }}
            </footer>
        </article>
    </div>

    <style>
        @media print {
            body {
                background: #fff !important;
            }

            .receipt-actions,
            .admin-topbar,
            #sidebar,
            .sidebar,
            .footer {
                display: none !important;
            }

            .content,
            .content .container-fluid {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .receipt {
                max-width: 100% !important;
                border: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
        }

        @media screen {
            .receipt {
                max-width: 760px;
            }
        }
    </style>
@endsection
