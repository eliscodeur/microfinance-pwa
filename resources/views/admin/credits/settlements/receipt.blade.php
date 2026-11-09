@extends('admin.layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 receipt-actions">
            <a href="{{ route('admin.credits.show', $credit->credit_uid) }}" class="btn btn-outline-secondary">
                Retour au crédit
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimer le justificatif
            </button>
        </div>

        <article class="receipt mx-auto bg-white border rounded shadow-sm p-4 p-md-5">
            <header class="text-center border-bottom pb-3 mb-4">
                <h1 class="h3 fw-bold mb-1">{{ config('app.name') }}</h1>
                <div class="text-muted text-uppercase small">Justificatif de compensation interne épargne / crédit</div>
                <div class="mt-3 fw-bold">Référence : {{ $settlement->reference }}</div>
                <div class="text-muted small">Ce document ne constitue pas un reçu d'espèces.</div>
            </header>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="text-muted small">Client</div>
                    <strong>{{ $credit->client->nom }} {{ $credit->client->prenom }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Dossier de crédit</div>
                    <strong>{{ $credit->credit_uid }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Date de l'opération</div>
                    <strong>{{ $settlement->date_operation->format('d/m/Y à H:i') }}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Agent</div>
                    <strong>{{ $settlement->admin->name ?? '—' }}</strong>
                </div>
            </div>

            <table class="table table-bordered align-middle mb-4">
                <tbody>
                    <tr class="table-light">
                        <th>Montant imputé au crédit</th>
                        <td class="text-end fw-bold fs-5">{{ number_format((float) $settlement->montant, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <th>Nombre de sources d'épargne mobilisées</th>
                        <td class="text-end">{{ $settlement->sources->count() }}</td>
                    </tr>
                    <tr>
                        <th>Total débité des cycles</th>
                        <td class="text-end fw-semibold">{{ number_format((float) $settlement->montant, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </tbody>
            </table>

            <h2 class="h6 fw-bold">Origine des fonds</h2>
            <table class="table table-sm table-bordered align-middle mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Source d'épargne</th>
                        <th class="text-end">Montant débité et imputé</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($settlement->sources as $source)
                        <tr>
                            <td>
                                @if ($source->cycle_id)
                                    Cycle #{{ $source->cycle_id }} · Carnet {{ $source->cycle->carnet->numero ?? '—' }}
                                @else
                                    Carnet d'épargne #{{ $source->carnet->numero ?? '—' }}
                                @endif
                            </td>
                            <td class="text-end">{{ number_format((float) $source->montant_affecte, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h2 class="h6 fw-bold">Affectation aux échéances</h2>
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Échéance</th>
                        <th>Date d'échéance</th>
                        <th class="text-end">Montant imputé</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($settlement->allocations as $allocation)
                        <tr>
                            <td>{{ $allocation->echeance ? '#' . $allocation->echeance : 'Solde global du crédit' }}</td>
                            <td>{{ $allocation->date_echeance?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-end">{{ number_format((float) $allocation->montant, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($settlement->note)
                <div class="mt-3">
                    <div class="text-muted small">Note d'opération</div>
                    <div>{{ $settlement->note }}</div>
                </div>
            @endif

            <div class="row text-center mt-5 pt-4">
                <div class="col-6"><div class="border-top pt-2">Signature du client</div></div>
                <div class="col-6"><div class="border-top pt-2">Signature / cachet de l'institution</div></div>
            </div>
            <footer class="text-center text-muted small border-top mt-4 pt-3">
                Justificatif généré le {{ now()->format('d/m/Y à H:i') }} · Opération sans mouvement de caisse
            </footer>
        </article>
    </div>

    <style>
        @media print {
            body { background: #fff !important; }
            .receipt-actions, .admin-topbar, #sidebar, .sidebar, .footer { display: none !important; }
            .content, .content .container-fluid { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .receipt { max-width: 100% !important; border: 0 !important; box-shadow: none !important; padding: 0 !important; }
        }
        @media screen {
            .receipt { max-width: 820px; }
        }
    </style>
@endsection
