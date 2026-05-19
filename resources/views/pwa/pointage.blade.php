@extends('pwa.layouts.app')

@section('header')
<style>
    body { background-color: #f8f9fa; }

    /* Typographie épurée pour l'en-tête de pointage */
    .header-pointage-title {
        font-size: 1.15rem;
        font-weight: 600;
        color: #212529;
    }
</style>

<div class="d-flex align-items-center w-100 bg-white py-1">
    
    <button onclick="goBackToCollecte()" class="btn btn-link text-dark p-0 me-3 border-0">
        <i class="bi bi-arrow-left fs-3"></i>
    </button>
    
    <div class="d-flex align-items-center justify-content-between flex-grow-1">
        <span id="client-nom" class="header-pointage-title text-truncate me-2">Chargement...</span>
        <span id="carnet-numero" class="badge bg-primary px-3 shadow-sm rounded-pill" style="display: none;">Carnet : ---</span>
    </div>

</div>
@endsection

@section('content')
<div class="container py-3" style="padding-bottom: 80px;">
    
    <div id="loader" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Chargement du carnet...</p>
    </div>

    <div id="content-collecte" class="d-none">
        <div id="zone-action"></div>
    </div>
</div>

<style>
    /* Design spécifique Mobile */
    .mobile-bottom-sheet {
        position: fixed;
        bottom: 0;
        margin: 0;
        width: 100%;
    }
    .mobile-bottom-sheet .modal-content {
        border-top-left-radius: 25px;
        border-top-right-radius: 25px;
        border-radius: 25px 25px 0 0;
    }
    .btn-mobile {
        height: 55px;
        border-radius: 15px;
        font-weight: bold;
        font-size: 1.1rem;
    }
    .btn-white { background: white; }
    .fw-black { font-weight: 900; }
</style>

<script type="module">
    import { db, getAgentDB } from '/js/db-manager.js';
    
    function generateUUID() {
        return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
            (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
        );
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    let carnetId = urlParams.get('carnet_id');
    const matricule = localStorage.getItem('current_agent_matricule');
    const sessionData = JSON.parse(localStorage.getItem(`auth_v1_${matricule}`));
    const agentId = sessionData ? sessionData.id : null;
    let currentClientId = null;

    if (!carnetId) {
        const pathSegments = window.location.pathname.split('/');
        const lastSegment = pathSegments[pathSegments.length - 1];
        if (!isNaN(lastSegment) && lastSegment !== "") {
            carnetId = lastSegment;
        }
    }

    // --- 1. INITIALISATION ---
    async function initPage() {
        try {
            const activeDB = getAgentDB();
            if (!activeDB.isOpen()) await activeDB.open();
            
            const carnet = await activeDB.carnets.get(Number(carnetId));
            if (!carnet) {
                document.getElementById('loader').innerHTML = "<div class='alert alert-warning'>Carnet introuvable.</div>";
                return;
            }

            currentClientId = carnet.client_id;
            const client = await activeDB.clients.get(Number(currentClientId));
            
            // Injection stricte dans les conteneurs d'affichage du Header
            document.getElementById('client-nom').innerText = client ? `${client.nom} ${client.prenom}` : "Client #" + currentClientId;
            
            const carnetBadge = document.getElementById('carnet-numero');
            if (carnetBadge) {
                carnetBadge.innerText = "Carnet : " + (carnet.numero || "N/A");
                carnetBadge.style.display = 'inline-block';
            }

            const cycles = await activeDB.cycles.where('carnet_id').equals(carnet.id).toArray();
            const cycleActif = cycles.find(c => c.statut === 'en_cours');

            if (cycleActif) {
                await afficherActionCollecte(cycleActif);
            } else {
                const toutesCollectes = await activeDB.collectes.toArray();
                const solde = calculerSolde(cycles, toutesCollectes);
                const aDesTermines = cycles.some(c => c.statut === 'termine' && !c.retire_at);
                afficherActionCreation(carnet.id, solde, aDesTermines);
            }

            document.getElementById('loader').classList.add('d-none');
            document.getElementById('content-collecte').classList.remove('d-none');
        } catch (e) { 
            console.error("Init Error:", e); 
            document.getElementById('loader').innerHTML = "<div class='alert alert-danger'>Erreur de chargement des données locales.</div>";
        }
    }

    // --- 2. RENDER ---
    async function afficherActionCollecte(cycle) {
        const stats = await getStats(cycle.cycle_uid);
        const fini = stats.fait >= 31;

        document.getElementById('zone-action').innerHTML = `
            <div class="card shadow-sm border-0 mb-4" style="border-radius:20px;">
                <div class="card-body text-center p-4">
                    <h6 class="text-muted fw-bold mb-3">PROGRESSION DU CYCLE</h6>
                    <input type="hidden" id="prix-unit" value="${cycle.montant_journalier}">
                    
                    <div class="progress mb-3" style="height: 25px; border-radius: 20px; background: #eee;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: ${stats.pct}%">
                            ${Math.round(stats.pct)}%
                        </div>
                    </div>

                    <div class="d-flex justify-content-between small fw-bold mb-4">
                        <span>PAYÉ : ${stats.fait} j</span>
                        <span class="text-primary">RESTE : ${stats.restant} j</span>
                    </div>

                    ${fini ? `
                        <div class="alert alert-success py-3 rounded-4">
                            <b>CYCLE COMPLET !</b><br><small>Vous pouvez clôturer ce cycle.</small>
                        </div>
                    ` : `
                        <div class="bg-light rounded-4 p-4 mb-4 border">
                            <small class="text-muted fw-bold">NOMBRE DE JOURS À POINTER</small>
                            <div class="d-flex justify-content-center align-items-center my-3">
                                <button class="btn btn-white shadow-sm border-0" onclick="window.changePointage(-1)" style="width:55px; height:55px; border-radius:15px; font-size:1.5rem;">-</button>
                                <h1 class="mx-4 mb-0 fw-black" id="nb-val">1</h1>
                                <button class="btn btn-white shadow-sm border-0" onclick="window.changePointage(1, ${stats.restant})" style="width:55px; height:55px; border-radius:15px; font-size:1.5rem;">+</button>
                            </div>
                            <h3 class="text-primary fw-bold mb-0"><span id="total-txt">${cycle.montant_journalier}</span> FCFA</h3>
                        </div>
                        <button class="btn btn-primary btn-lg w-100 btn-mobile shadow" onclick="window.ouvrirConfirm(${cycle.id}, ${cycle.montant_journalier})">
                            VALIDER LE POINTAGE
                        </button>
                    `}
                    
                    <button class="btn btn-link text-danger text-decoration-none mt-4 fw-bold" onclick="window.ouvrirCloture(${cycle.id})">
                        <i class="bi bi-x-circle me-1"></i> CLÔTURER LE CYCLE MAINTENANT
                    </button>
                </div>
            </div>
        `;
    }

    function afficherActionCreation(carnetId, solde, displaySolde) {
        document.getElementById('zone-action').innerHTML = `
            <div class="card border-0 shadow-sm p-4 text-center" style="border-radius: 20px;">
                <h5 class="fw-bold mb-3">Nouveau Cycle</h5>
                <label class="small text-muted mb-1">Mise journalière (FCFA)</label>
                <input type="number" 
                    id="input_mise" 
                    class="form-control mb-3 text-center fs-4 fw-bold" 
                    value="300" 
                    min="100" 
                    inputmode="numeric"
                    onchange="if(this.value < 100) this.value = 100;"
                    style="border-radius: 15px; height: 60px;">
               
                <button onclick="window.creerCycle(${carnetId})" class="btn btn-warning btn-lg w-100 fw-bold text-white shadow" style="border-radius: 15px; height: 60px;">
                    OUVRIR LE CYCLE
                </button>
            </div>
        `;
    }
    
    // --- 3. LOGIQUE & COMMANDES ---
    window.changePointage = (v, max) => {
        const el = document.getElementById('nb-val');
        const totalTxt = document.getElementById('total-txt');
        const prixUnit = parseInt(document.getElementById('prix-unit').value) || 0;
        
        let n = parseInt(el.innerText) + v;
        if (n < 1) return;
        
        if (max && n > max) {
            Swal.fire({
                icon: 'warning',
                title: 'Limite atteinte',
                text: `Il ne reste que ${max} pointages possibles pour ce cycle.`,
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        el.innerText = n;
        totalTxt.innerText = (n * prixUnit).toLocaleString();
    };

    window.ouvrirConfirm = async (id, mise) => {
        const nb = document.getElementById('nb-val').innerText;
        const total = nb * mise;
        const jour = nb == 1 ? "jour" : "jours";
        
        const result = await Swal.fire({
            title: 'Confirmer le pointage',
            html: `Enregistrer <b>${nb} ${jour}</b> soit <b>${total.toLocaleString()} FCFA</b> ?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, valider',
            cancelButtonText: 'Annuler'
        });

        if (result.isConfirmed) {
            window.savePointage(id, mise, nb);
        }
    };

    window.savePointage = async (id, mise, nb) => {
        const activeDB = getAgentDB();
        const cycle = await activeDB.cycles.get(Number(id));
        const cycle_uid = cycle.cycle_uid;

        await activeDB.collectes.add({
            collecte_uid: generateUUID(),
            cycle_id: cycle.id,
            cycle_uid: cycle_uid,
            client_id: cycle.client_id,
            agent_id: Number(agentId),
            pointage: parseInt(nb),
            montant: parseInt(nb * mise),
            date: new Date().toISOString().split('T')[0],
            synced: 0
        });

        const s = await getStats(cycle.cycle_uid ?? cycle.id);
        if (s.fait >= 31) {
            await activeDB.cycles.update(cycle.id, { statut: 'termine', completed_at: new Date().toISOString(), synced: 0 });
            window.showBilan(cycle.id);
        } else {
            Swal.fire({
                icon: 'success',
                title: 'Enregistré !',
                timer: 1500,
                showConfirmButton: false
            });
            initPage();
        }
    };

    window.ouvrirCloture = async (id) => {
        const result = await Swal.fire({
            title: 'Clôturer le cycle ?',
            text: "Cette action mettra fin définitivement aux pointages de ce cycle.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, clôturer',
            cancelButtonText: 'Annuler'
        });

        if (result.isConfirmed) {
            const activeDB = getAgentDB();
            await activeDB.cycles.update(Number(id), { 
                statut: 'termine', 
                completed_at: new Date().toISOString(), 
                synced: 0 
            });
            window.showBilan(id);
        }
    };

    window.showBilan = async (id) => {
        const activeDB = getAgentDB();
        const cycle = await activeDB.cycles.get(Number(id));
        const s = await getStats(cycle.cycle_uid ?? cycle.id);
        const mise = cycle.montant_journalier;
        const comm = s.fait > 0 ? mise : 0;
        const net = Math.max(0, (s.fait * mise) - comm);

        Swal.fire({
            title: 'Bilan du Cycle',
            html: `
                <div class="text-start small p-2">
                    <p class="mb-1">Date: <b>${new Date().toLocaleDateString()}</b></p>
                    <p class="mb-1">Jours pointés: <b>${s.fait} / 31</b></p>
                    <p class="mb-3">Mise journalière: <b>${mise} FCFA</b></p>
                    <hr>
                    <p class="mb-2 text-muted">Commission (1ère mise): <b class="text-danger">-${comm} FCFA</b></p>
                    <h5 class="text-center mt-3 mb-1 fw-bold text-uppercase" style="letter-spacing:1px;">Solde Net Généré :</h5>
                    <h2 class="text-center text-success fw-black">${net.toLocaleString()} FCFA</h2>
                </div>
            `,
            icon: 'success',
            confirmButtonText: 'Fermer',
            confirmButtonColor: '#0d6efd'
        }).then(() => {
            initPage();
        });
    };

    window.creerCycle = async (cid) => {
        const activeDB = getAgentDB();
        const existant = await activeDB.cycles
            .where('carnet_id').equals(Number(cid))
            .and(c => c.statut === 'en_cours')
            .first();
            
        if (existant) {
            Swal.fire('Erreur', 'Un cycle est déjà actif sur ce livret.', 'error');
            return;
        }

        const mise = parseInt(document.getElementById('input_mise').value) || 300;
        const carnet = await activeDB.carnets.get(Number(cid));
        
        let dateDebut = new Date();
        let dateFin = new Date(dateDebut);
        let joursAjoutes = 0;
        while (joursAjoutes < 31) {
            dateFin.setDate(dateFin.getDate() + 1);
            if (dateFin.getDay() !== 0) joursAjoutes++; // Hors dimanche
        }
       
        await activeDB.cycles.add({
            cycle_uid: generateUUID(),
            carnet_id: carnet.id,
            client_id: carnet.client_id,
            agent_id: Number(agentId),
            montant_journalier: mise,
            statut: 'en_cours',
            date_debut: dateDebut.toISOString(),
            date_fin_prevue: dateFin.toISOString(), 
            synced: 0
        });

        Swal.fire({
            icon: 'success',
            title: 'Cycle ouvert !',
            timer: 1500,
            showConfirmButton: false
        }).then(() => initPage());
    };

    async function getStats(uid) {
        const activeDB = getAgentDB();
        const colls = await activeDB.collectes.where('cycle_uid').equals(String(uid)).toArray();
        const fait = colls.reduce((a, b) => a + b.pointage, 0);
        return { fait, restant: 31 - fait, pct: (fait / 31) * 100 };
    }

    function calculerSolde(cycles, colls) {
        return cycles.filter(c => c.statut === 'termine' && !c.retire_at).reduce((acc, c) => {
            const uid = c.cycle_uid;
            const collectesDuCycle = colls.filter(o => o.cycle_uid === uid);
            const sommeCollectes = collectesDuCycle.reduce((s, o) => s + o.montant, 0);
            const commission = collectesDuCycle.length > 0 ? c.montant_journalier : 0;
            return acc + (sommeCollectes - commission);
        }, 0);
    }

    /**
     * Commande de routage de retour vers les livrets d'épargne du client ciblé
     */
    window.goBackToCollecte = function() {
        if (currentClientId) {
            window.location.href = `/pwa/carnet?client_id=${currentClientId}`;
        } else {
            window.location.href = '/pwa/clients';
        }
    };

    document.addEventListener('DOMContentLoaded', initPage);
</script>
@endsection