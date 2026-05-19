@extends('pwa.layouts.app') 
@section('header')
<div class="d-flex align-items-center w-100 bg-white py-1">
    
        <button onclick="toggleSidebar()" class="btn btn-link text-dark p-0 me-3 border-0">
            <i class="bi bi-list fs-3 me-3"></i>
        </button>
    
    <div class="d-flex align-items-center flex-grow-1">
        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-graph-up-arrow me-3 fs-5"></i>Performances & stats</h5>
    </div>

</div>
@endsection
@section('content')
<div class="container-fluid px-3 py-4">
    
    <!-- <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Mes Performances</h4>
            <small class="text-muted">Données synchronisées en local</small>
        </div>
        <div class="bg-primary bg-opacity-10 p-2 rounded-3">
            <i class="bi bi-graph-up-arrow text-primary fs-4"></i>
        </div>
    </div> -->

    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm rounded-4 bg-gradient-primary text-white p-3 h-100">
                <small class="opacity-75 text-uppercase fw-semibold" style="font-size: 0.75rem;">Volume Global</small>
                <h3 class="fw-bold my-2" id="kpi-volume-global">0 F</h3>
                <small class="opacity-50" style="font-size: 0.7rem;">Depuis mes débuts</small>
            </div>
        </div>

        <div class="col-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Cycles Terminés</small>
                <h3 class="fw-bold text-dark my-2" id="kpi-cycles-termines">0</h3>
                <small class="text-success fw-medium" style="font-size: 0.7rem;">
                    <i class="bi bi-check-circle-fill"></i> Clôturés à 100%
                </small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold mb-0 text-dark">Évolution des collectes</h6>
            <span class="badge bg-light text-muted rounded-pill px-2 py-1" style="font-size: 0.7rem;">6 derniers mois</span>
        </div>
        
        <div id="chart-evolution" style="min-height: 250px;"></div>
        
        <div id="chart-empty" class="text-center py-5 d-none">
            <i class="bi bi-bar-chart text-muted opacity-50" style="font-size: 2.5rem;"></i>
            <p class="text-muted small mt-2">Aucun historique disponible.<br>Lancez une synchronisation.</p>
        </div>
    </div>

</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    }
</style>
<script src="{{ asset('js/apexcharts.min.js') }}"></script>

<script type="module">
    // On garde tes imports d'origine sans rien modifier
    import { getAgentDB } from '/js/db-manager.js';

    // Formatage des montants en FCFA
    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount) + ' F';
    }

    // Nom complet des mois pour le graphique (version compacte mobile)
    const listMoisMappage = {
        '01': 'Jan', '02': 'Fév', '03': 'Mar', '04': 'Avr', '05': 'Mai', '06': 'Juin',
        '07': 'Juil', '08': 'Aoû', '09': 'Sep', '10': 'Oct', '11': 'Nov', '12': 'Déc'
    };

    function formatLibelleMois(anneeMois) {
        if (!anneeMois || !anneeMois.includes('-')) return anneeMois;
        const [annee, mois] = anneeMois.split('-');
        return `${listMoisMappage[mois]} ${annee.substring(2)}`;
    }

    async function initialiserInterfaceStats() {
        // On conserve TA méthode d'initialisation de la DB
        const activeDB = getAgentDB();
        if (!activeDB) return;
        
        try {
            // 🛠️ SÉCURITÉ DEXIE : Forcer l'ouverture si la DB n'est pas encore prête
            if (typeof activeDB.isOpen === 'function' && !activeDB.isOpen()) {
                await activeDB.open();
            }

            // 1. Récupération de l'agent actuellement connecté localement
            const localAgent = await activeDB.agents.limit(1).first();
            if (!localAgent) {
                console.warn("⚠️ Aucun agent trouvé dans la base locale.");
                return;
            }

            // 2. Lecture des statistiques brutes dans ta table Dexie
            const statsData = await activeDB.agent_stats.get(Number(localAgent.id));
            if (!statsData || !statsData.historique_courbe || statsData.historique_courbe.length === 0) {
                document.getElementById('chart-evolution').classList.add('d-none');
                document.getElementById('chart-empty').classList.remove('d-none');
                return;
            }

            // 3. Remplissage des compteurs KPI
            document.getElementById('kpi-volume-global').innerText = formatMoney(statsData.volume_historique_global);
            document.getElementById('kpi-cycles-termines').innerText = statsData.total_historique_cycles_termines;

            // 4. Extraction et tri des axes X et Y pour la courbe
            const courbeData = statsData.historique_courbe || [];
            const categoriesX = courbeData.map(item => formatLibelleMois(item.mois));
            const dataY = courbeData.map(item => parseFloat(item.total_volume || 0));

            // 5. Configuration d'ApexCharts adaptée pour le tactile Mobile
            const options = {
                chart: {
                    type: 'area',
                    height: 230, 
                    toolbar: { show: false }, 
                    sparkline: { enabled: false },
                    animations: { enabled: true }
                },
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: 2.5, // Ligne légèrement affinée pour écran mobile
                    colors: ['#4e73df']
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.03,
                        stops: [0, 90, 100]
                    }
                },
                series: [{
                    name: 'Volume Collecté',
                    data: dataY
                }],
                xaxis: {
                    categories: categoriesX,
                    labels: {
                        style: { colors: '#6e707e', fontSize: '10px' },
                        hideOverlappingLabels: true, // Empêche les mois de se chevaucher en largeur
                        rotate: 0 // Force l'écriture horizontale pour une lecture facile
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            // Affiche le montant brut formaté proprement sans le suffixe "k"
                            return new Intl.NumberFormat('fr-FR').format(val);
                        },
                        style: { colors: '#6e707e', fontSize: '10px' }
                    }
                },
                // 📱 BULLE D'INFO FIXÉE EN HAUT À DROITE POUR NE PAS ÊTRE MASQUÉE PAR LE POUCE
                tooltip: {
                    shared: true,
                    intersect: false,
                    followCursor: false, 
                    fixed: {
                        enabled: true,
                        position: 'topRight',
                        offsetX: -10,
                        offsetY: -20,
                    },
                    y: {
                        formatter: function (val) { return formatMoney(val); }
                    }
                },
                grid: {
                    borderColor: '#eaecf4',
                    strokeDashArray: 4,
                    padding: { left: 0, right: 10, bottom: 0 } // On serre les marges au maximum
                },
                theme: {
                    monochrome: {
                        enabled: true,
                        color: '#4e73df',
                        shadeTo: 'light',
                        shadeIntensity: 0.65
                    }
                }
            };

            // Nettoyage de sécurité avant injection
            const container = document.querySelector("#chart-evolution");
            if (container) {
                container.innerHTML = "";
                const chart = new ApexCharts(container, options);
                chart.render();
            }

        } catch (error) {
            console.error("❌ Erreur au chargement des statistiques locales :", error);
        }
    }

    // 🚀 EXÉCUTION SÉCURISÉE POUR LES MODULES JS
    if (document.readyState === "loading") {
        document.addEventListener('DOMContentLoaded', initialiserInterfaceStats);
    } else {
        initialiserInterfaceStats();
    }
</script>
@endsection


