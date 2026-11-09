@extends('admin.layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 receipt-actions">
            <a href="{{ route('admin.carnets.show', $collecte->cycle->carnet) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour au carnet
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimer le reçu
            </button>
        </div>

        <article class="receipt mx-auto bg-white border rounded shadow-sm p-4 p-md-5">
            <header class="text-center border-bottom pb-3 mb-4">
                <h1 class="h3 fw-bold mb-1">{{ config('app.name') }}</h1>
                <div class="text-muted text-uppercase small">Reçu de collecte tontine</div>
                <div class="mt-3 fw-bold">
                    Référence : {{ $collecte->reference_operation ?: 'COL-HIST-' . $collecte->id }}
                </div>
            </header>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="text-muted small">Bénéficiaire / titulaire</div>
                    <strong>{{ $collecte->client->nom }} {{ $collecte->client->prenom }}</strong>
                    <div class="small text-muted">{{ $collecte->client->telephone ?: '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Carnet tontine</div>
                    <strong>#{{ $collecte->cycle->carnet->numero }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Cycle</div>
                    <strong>{{ $collecte->cycle->cycle_uid ?: '#' . $collecte->cycle_id }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Date et heure de l’opération</div>
                    <strong>{{ $collecte->date_saisie->format('d/m/Y à H:i') }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Personne ayant remis les fonds</div>
                    <strong>{{ $collecte->payeur_nom ?: $collecte->client->nom . ' ' . $collecte->client->prenom }}</strong>
                    @if ($collecte->payeur_type === 'tiers')
                        <div class="small text-muted">
                            {{ $collecte->payeur_lien ?: 'Tiers' }}
                            @if ($collecte->payeur_telephone)
                                · {{ $collecte->payeur_telephone }}
                            @endif
                        </div>
                    @else
                        <div class="small text-muted">Titulaire du carnet</div>
                    @endif
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Enregistré par</div>
                    <strong>{{ $collecte->user->name ?? '—' }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Agent du cycle</div>
                    <strong>{{ $collecte->agent ? $collecte->agent->nom . ' ' . $collecte->agent->prenom : '—' }}</strong>
                </div>
            </div>

            <table class="table table-bordered align-middle">
                <tbody>
                    <tr>
                        <th>Pointages / parts</th>
                        <td class="text-end">{{ $collecte->pointage }}</td>
                    </tr>
                    <tr>
                        <th>Mise journalière</th>
                        <td class="text-end">{{ number_format((float) $collecte->cycle->montant_journalier, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr class="table-light">
                        <th>Montant reçu en espèces</th>
                        <td class="text-end fw-bold fs-5">{{ number_format((float) $collecte->montant, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </tbody>
            </table>

            @if ($collecte->payeur_type === 'tiers')
                <p class="small text-muted mt-3">
                    Les renseignements sur le payeur tiers sont déclaratifs et ne constituent pas une vérification d’identité.
                </p>
            @endif

            <div class="row text-center mt-5 pt-4">
                <div class="col-6"><div class="border-top pt-2">Signature du déposant</div></div>
                <div class="col-6"><div class="border-top pt-2">Signature / cachet de la caisse</div></div>
            </div>
            <footer class="text-center text-muted small border-top mt-4 pt-3">
                Reçu généré le {{ now()->format('d/m/Y à H:i') }} · Session de caisse #{{ $collecte->cash_session_id ?? '—' }}
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
