@extends('admin.layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 receipt-actions">
            <a href="{{ route('admin.carnets.show', $depot->carnet) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour au carnet
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimer le reçu
            </button>
        </div>

        <article class="receipt mx-auto bg-white border rounded shadow-sm p-4 p-md-5">
            <header class="text-center border-bottom pb-3 mb-4">
                <h1 class="h3 fw-bold mb-1">{{ config('app.name') }}</h1>
                <div class="text-muted text-uppercase small">Reçu de dépôt d’épargne</div>
                <div class="mt-3 fw-bold">Référence : {{ $depot->reference_operation ?: 'DEP-HIST-' . $depot->id }}</div>
            </header>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="text-muted small">Bénéficiaire / titulaire</div>
                    <strong>{{ $depot->client->nom }} {{ $depot->client->prenom }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Téléphone du titulaire</div>
                    <strong>{{ $depot->client->telephone ?: '—' }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Carnet d’épargne</div>
                    <strong>#{{ $depot->carnet->numero }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Date et heure de l’opération</div>
                    <strong>{{ $depot->date_depot->format('d/m/Y à H:i') }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Personne ayant remis les fonds</div>
                    <strong>{{ $depot->payeur_nom ?: $depot->client->nom . ' ' . $depot->client->prenom }}</strong>
                    @if ($depot->payeur_type === 'tiers')
                        <div class="small text-muted">
                            {{ $depot->payeur_lien ?: 'Tiers' }}
                            @if ($depot->payeur_telephone)
                                · {{ $depot->payeur_telephone }}
                            @endif
                        </div>
                    @else
                        <div class="small text-muted">Titulaire du carnet</div>
                    @endif
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Enregistré par</div>
                    <strong>{{ $depot->user->name ?? '—' }}</strong>
                </div>
            </div>

            <table class="table table-bordered align-middle">
                <tbody>
                    <tr class="table-light">
                        <th>Montant reçu</th>
                        <td class="text-end fw-bold fs-5">{{ number_format((float) $depot->montant, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <th>Solde du carnet avant dépôt</th>
                        <td class="text-end">
                            {{ $depot->solde_carnet_avant !== null ? number_format((float) $depot->solde_carnet_avant, 0, ',', ' ') . ' FCFA' : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Solde du carnet après dépôt</th>
                        <td class="text-end">
                            {{ $depot->solde_carnet_apres !== null ? number_format((float) $depot->solde_carnet_apres, 0, ',', ' ') . ' FCFA' : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Mode de paiement</th>
                        <td class="text-end">
                            {{ ['especes' => 'Espèces', 'mobile_money' => 'Mobile Money', 'virement' => 'Virement bancaire'][$depot->mode_paiement] ?? '—' }}
                        </td>
                    </tr>
                    @if ($depot->reference_paiement)
                        <tr>
                            <th>Référence du paiement</th>
                            <td class="text-end">{{ $depot->reference_paiement }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            @if ($depot->commentaire)
                <div class="mt-3">
                    <div class="text-muted small">Note</div>
                    <div>{{ $depot->commentaire }}</div>
                </div>
            @endif

            @if ($depot->payeur_type === 'tiers')
                <p class="small text-muted mt-3">
                    Les renseignements sur le payeur tiers sont déclaratifs et ne constituent pas une vérification d’identité.
                </p>
            @endif

            <div class="row text-center mt-5 pt-4">
                <div class="col-6"><div class="border-top pt-2">Signature du déposant</div></div>
                <div class="col-6"><div class="border-top pt-2">Signature / cachet de la caisse</div></div>
            </div>
            <footer class="text-center text-muted small border-top mt-4 pt-3">
                Reçu généré le {{ now()->format('d/m/Y à H:i') }} · Session de caisse #{{ $depot->cash_session_id ?? '—' }}
            </footer>
        </article>
    </div>

    <style>
        @media print {
            body { background: #fff !important; }
            .receipt-actions, .admin-topbar, #sidebar, .sidebar, .navbar { display: none !important; }
            .receipt { border: 0 !important; box-shadow: none !important; }
            .container { max-width: 100% !important; }
        }
    </style>
@endsection
