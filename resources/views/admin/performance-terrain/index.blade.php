@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-2 agent-performance">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <div class="performance-eyebrow"><i class="bi bi-activity me-2"></i>Suivi des opérations terrain</div>
            <h1 class="h3 fw-bold mb-1">Performance des agents</h1>
            <p class="text-muted mb-0">Classement mensuel calculé à partir des objectifs des cycles et des collectes enregistrées.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @can('performance.export')
                <a class="btn btn-outline-success" href="{{ route('admin.performance-terrain.export', $filters) }}">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>Exporter CSV
                </a>
            @endcan
            <span class="performance-period"><i class="bi bi-calendar3 me-2"></i>{{ ucfirst($monthLabel) }}</span>
        </div>
    </div>

    @if($report['period']['is_provisional'])
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-start gap-2" role="status">
            <i class="bi bi-info-circle-fill mt-1"></i>
            <div><strong>Période provisoire.</strong> Les résultats vont jusqu’au {{ $report['period']['end']->format('d/m/Y') }}. Les jours ouvrés futurs du mois ne sont pas inclus dans les objectifs.</div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3 p-lg-4">
            <form method="GET" action="{{ route('admin.performance-terrain.index') }}" class="row g-3 align-items-end">
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-semibold" for="mois">Mois</label>
                    <select class="form-select" id="mois" name="mois">
                        @for($month = 1; $month <= 12; $month++)
                            <option value="{{ $month }}" {{ (int) $filters['mois'] === $month ? 'selected' : '' }}>
                                {{ ucfirst(\Carbon\CarbonImmutable::create($filters['annee'], $month, 1)->locale('fr')->translatedFormat('F')) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-semibold" for="annee">Année</label>
                    <input class="form-control" id="annee" name="annee" type="number" min="2020" max="2100" value="{{ $filters['annee'] }}" required>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label small fw-semibold" for="agent_id">Agent</label>
                    <select class="form-select" id="agent_id" name="agent_id">
                        <option value="">Tous les agents</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ (string) $filters['agent_id'] === (string) $agent->id ? 'selected' : '' }}>
                                {{ $agent->nom }} — {{ $agent->code_agent }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label small fw-semibold" for="tri">Classer par</label>
                    <select class="form-select" id="tri" name="tri">
                        <option value="rate" {{ $filters['tri'] === 'rate' ? 'selected' : '' }}>Taux de réalisation</option>
                        <option value="collected" {{ $filters['tri'] === 'collected' ? 'selected' : '' }}>Montant collecté</option>
                        <option value="expected" {{ $filters['tri'] === 'expected' ? 'selected' : '' }}>Objectif attendu</option>
                        <option value="gap" {{ $filters['tri'] === 'gap' ? 'selected' : '' }}>Écart à l’objectif</option>
                    </select>
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Filtrer</button>
                    <a class="btn btn-light border" href="{{ route('admin.performance-terrain.index') }}" aria-label="Réinitialiser les filtres" title="Réinitialiser">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="performance-stat">
                <div class="performance-stat-icon text-primary bg-primary-subtle"><i class="bi bi-bullseye"></i></div>
                <div class="text-muted small">Objectif ouvré</div>
                <div class="performance-stat-value">{{ number_format($report['summary']['expected'], 0, ',', ' ') }} <small>FCFA</small></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="performance-stat">
                <div class="performance-stat-icon text-success bg-success-subtle"><i class="bi bi-cash-stack"></i></div>
                <div class="text-muted small">Collecté</div>
                <div class="performance-stat-value">{{ number_format($report['summary']['collected'], 0, ',', ' ') }} <small>FCFA</small></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="performance-stat">
                <div class="performance-stat-icon text-info bg-info-subtle"><i class="bi bi-speedometer2"></i></div>
                <div class="text-muted small">Taux global</div>
                <div class="performance-stat-value">{{ $report['summary']['rate'] === null ? '—' : number_format($report['summary']['rate'], 1, ',', ' ') . '%' }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="performance-stat">
                <div class="performance-stat-icon text-warning bg-warning-subtle"><i class="bi bi-person-lines-fill"></i></div>
                <div class="text-muted small">Agents classés · cycles</div>
                <div class="performance-stat-value">{{ $report['summary']['agents'] }} <small>· {{ $report['summary']['cycles'] }} cycles</small></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="h6 fw-bold mb-1">Objectifs et collectes par jour</h2>
                            <p class="small text-muted mb-0">Comparaison des montants sur les jours ouvrés du mois.</p>
                        </div>
                        <span class="badge rounded-pill bg-light text-secondary">Lun. – ven.</span>
                    </div>
                    @if(count($report['daily_labels']))
                        <div class="performance-chart"><canvas id="dailyPerformanceChart" aria-label="Courbe des objectifs et collectes journaliers"></canvas></div>
                    @else
                        <div class="performance-empty">Aucun jour ouvré dans la période sélectionnée.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="mb-3">
                        <h2 class="h6 fw-bold mb-1">Meilleurs taux de réalisation</h2>
                        <p class="small text-muted mb-0">Les dix agents les mieux classés sur la période.</p>
                    </div>
                    @if($chartRows->isNotEmpty())
                        <div class="performance-chart performance-chart-ranking"><canvas id="rankingPerformanceChart" aria-label="Comparaison des taux de réalisation des agents"></canvas></div>
                    @else
                        <div class="performance-empty">Pas encore de données d’objectif à comparer.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 px-3 px-lg-4 pt-4 pb-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <h2 class="h5 fw-bold mb-1">Classement exploitable</h2>
                    <p class="small text-muted mb-0">Le taux compare les collectes à l’objectif ouvré attribué à l’agent.</p>
                </div>
                <span class="small text-muted">{{ $rankings->total() }} résultat(s)</span>
            </div>
        </div>
        <div class="card-body p-0 pt-3">
            @if($rankings->isEmpty())
                <div class="performance-empty mx-3 mb-3">Aucune activité trouvée pour ces filtres.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle performance-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3 ps-lg-4">Rang</th>
                                <th>Agent</th>
                                <th>Objectif</th>
                                <th>Collecté</th>
                                <th>Écart</th>
                                <th>Réalisation</th>
                                <th class="text-center">Pointages</th>
                                <th class="text-center">Cycles</th>
                                <th class="text-center pe-3 pe-lg-4">Jours ouvrés</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rankings as $row)
                                <tr>
                                    <td class="ps-3 ps-lg-4">
                                        @if($row['rank'] !== null)
                                            <span class="rank-pill {{ $row['rank'] <= 3 ? 'rank-pill-top' : '' }}">{{ $row['rank'] }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $row['nom'] }}</div>
                                        <div class="small text-muted">{{ $row['code_agent'] }} · {{ $row['actif'] ? 'Actif' : 'Inactif' }}</div>
                                    </td>
                                    <td class="text-nowrap">{{ number_format($row['expected'], 0, ',', ' ') }} F</td>
                                    <td class="text-nowrap fw-semibold">{{ number_format($row['collected'], 0, ',', ' ') }} F</td>
                                    <td class="text-nowrap {{ $row['gap'] < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $row['gap'] > 0 ? '+' : '' }}{{ number_format($row['gap'], 0, ',', ' ') }} F
                                    </td>
                                    <td class="performance-rate-cell">
                                        @if($row['rate'] !== null)
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span class="fw-semibold">{{ number_format($row['rate'], 1, ',', ' ') }}%</span>
                                                <span class="text-muted">{{ $row['rate'] >= 100 ? 'Objectif atteint' : 'En cours' }}</span>
                                            </div>
                                            <div class="progress" role="progressbar" aria-label="Taux de réalisation" aria-valuenow="{{ min(100, $row['rate']) }}" aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar {{ $row['rate'] >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ min(100, max(0, $row['rate'])) }}%"></div>
                                            </div>
                                        @else
                                            <span class="badge bg-light text-secondary">Sans objectif sur la période</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($row['pointages'], 0, ',', ' ') }}</td>
                                    <td class="text-center">{{ $row['cycles'] }}</td>
                                    <td class="text-center pe-3 pe-lg-4">{{ $row['weekdays'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($rankings->hasPages())
                    <div class="px-3 px-lg-4 py-3">{{ $rankings->links() }}</div>
                @endif
            @endif
        </div>
        <div class="card-footer bg-white border-0 px-3 px-lg-4 pb-4">
            <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Les objectifs sont proratisés selon les jours ouvrés, la durée de chaque cycle et l’historique d’affectation des carnets. Les collectes du week-end sont exclues.</small>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .agent-performance { color: #233437; }
    .performance-eyebrow { color: #58805b; font-size: .75rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
    .performance-period { display: inline-flex; align-items: center; padding: .65rem .9rem; border: 1px solid #e4ece8; border-radius: 999px; background: #fff; color: #345b4b; font-size: .875rem; font-weight: 600; }
    .performance-stat { position: relative; height: 100%; min-height: 135px; padding: 1rem 1.1rem; border: 1px solid #edf1ef; border-radius: 1rem; background: #fff; box-shadow: 0 4px 16px rgba(27, 55, 48, .045); }
    .performance-stat-icon { display: grid; width: 38px; height: 38px; place-items: center; margin-bottom: .85rem; border-radius: .75rem; font-size: 1rem; }
    .performance-stat-value { margin-top: .15rem; color: #203735; font-size: clamp(1.05rem, 2vw, 1.4rem); font-weight: 750; line-height: 1.2; }
    .performance-stat-value small { color: #778582; font-size: .72rem; font-weight: 600; }
    .performance-chart { position: relative; min-height: 265px; }
    .performance-chart-ranking { min-height: 265px; }
    .performance-empty { display: grid; min-height: 180px; place-items: center; padding: 1.5rem; border: 1px dashed #dbe5e0; border-radius: .75rem; color: #74817e; text-align: center; }
    .performance-table thead th { padding-top: .8rem; padding-bottom: .8rem; background: #f7faf8; color: #677471; font-size: .7rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
    .performance-table tbody td { padding-top: .9rem; padding-bottom: .9rem; border-color: #f0f3f2; font-size: .875rem; }
    .rank-pill { display: inline-grid; width: 30px; height: 30px; place-items: center; border-radius: 50%; background: #f1f4f3; color: #596663; font-size: .8rem; font-weight: 700; }
    .rank-pill-top { background: #e8f3e7; color: #3d773f; }
    .performance-rate-cell { min-width: 175px; }
    .performance-rate-cell .progress { height: 6px; background: #edf1ef; }
    @media (max-width: 575px) {
        .performance-stat { min-height: 125px; padding: .85rem; }
        .performance-stat-value { font-size: 1rem; }
        .performance-chart { min-height: 230px; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartColors = { expected: '#7798d2', collected: '#3e9b70' };
        const dailyCanvas = document.getElementById('dailyPerformanceChart');
        if (dailyCanvas) {
            new Chart(dailyCanvas, {
                type: 'line',
                data: {
                    labels: @json($report['daily_labels']),
                    datasets: [
                        {
                            label: 'Objectif',
                            data: @json($report['daily_expected']),
                            borderColor: chartColors.expected,
                            backgroundColor: 'rgba(119, 152, 210, .12)',
                            fill: true,
                            tension: .32,
                            pointRadius: 2
                        },
                        {
                            label: 'Collecté',
                            data: @json($report['daily_collected']),
                            borderColor: chartColors.collected,
                            backgroundColor: 'rgba(62, 155, 112, .10)',
                            fill: true,
                            tension: .32,
                            pointRadius: 2
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${new Intl.NumberFormat('fr-FR').format(context.parsed.y)} FCFA` } }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: (value) => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(value) } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        const rankingCanvas = document.getElementById('rankingPerformanceChart');
        if (rankingCanvas) {
            new Chart(rankingCanvas, {
                type: 'bar',
                data: {
                    labels: @json($chartRows->pluck('nom')->values()),
                    datasets: [{
                        label: 'Taux de réalisation (%)',
                        data: @json($chartRows->pluck('rate')->values()),
                        backgroundColor: '#4b8e68',
                        borderRadius: 5,
                        maxBarThickness: 22
                    }]
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { callback: (value) => `${value}%` } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }
    });
</script>
@endpush
