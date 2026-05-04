@extends('pwa.layouts.app')

@section('content')
<div class="container py-3">
    <div id="loader" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Chargement du carnet...</p>
    </div>

    <div id="content-collecte" class="d-none">
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 20px; background: linear-gradient(135deg, #0d6efd, #004db0); color: white;">
            <div class="card-body p-4 text-center">
                <h4 class="fw-bold mb-1" id="client-nom">---</h4>
                <p class="mb-0 opacity-75 small" id="carnet-numero">Carnet : ---</p>
            </div>
        </div>

        <div id="zone-action"></div>
    </div>
</div>

<div class="modal fade" id="modalConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mobile-bottom-sheet">
        <div class="modal-content p-2 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Pointage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-2">
                <div class="text-primary mb-3"><i class="fas fa-check-circle fa-3x"></i></div>
                <h4 class="fw-bold">Confirmer ?</h4>
                <p class="text-muted px-3" id="text-confirm-details"></p>
            </div>
            <div class="d-grid gap-2 p-3 pt-0">
                <button type="button" class="btn btn-primary btn-mobile shadow-sm" id="btn-valider-final">OUI, ENREGISTRER</button>
                <button type="button" class="btn btn-light btn-mobile text-muted" data-bs-dismiss="modal">ANNULER</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmCloture" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mobile-bottom-sheet">
        <div class="modal-content p-2 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">Clôture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-2">
                <div class="text-danger mb-3"><i class="bi bi-exclamation-octagon-fill display-2"></i></div>
                <h3 class="fw-bold">Arrêter le cycle ?</h3>
                <p class="text-muted">Cette action est définitive. Le client recevra son bilan final.</p>
            </div>
            <div class="d-grid gap-2 p-3 pt-0">
                <button type="button" class="btn btn-danger btn-mobile shadow-sm" id="btn-valider-cloture-final">OUI, CLÔTURER MAINTENANT</button>
                <button type="button" class="btn btn-light btn-mobile text-muted" data-bs-dismiss="modal">ANNULER</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRecuFinal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered px-3">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close ms-auto" onclick="location.reload()" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <div class="text-center mb-4">
                    <div class="bg-success text-white d-inline-block p-3 rounded-circle mb-2">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                    <h2 class="fw-bold mb-0">BILAN FINAL</h2>
                    <small class="text-muted" id="recu-date">--/--/----</small>
                </div>
                
                <div class="p-3 rounded-4 mb-3" style="background: #f8f9fa; border: 1px dashed #ccc;">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Jours :</span> <b id="recu-jours">0 / 31</b>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Mise :</span> <b id="recu-mise">0 FCFA</b>
                    </div>
                    <div class="d-flex justify-content-between text-danger pt-2 mt-2" style="border-top: 1px solid #dee2e6;">
                        <span class="small">Frais (1j) :</span>
                        <span class="small">- <b id="recu-commission">0</b> FCFA</span>
                    </div>
                </div>

                <div class="text-center py-2">
                    <small class="text-uppercase text-muted fw-bold">Net à reverser</small>
                    <h1 class="display-5 fw-bold text-primary mb-0" id="recu-total">0 FCFA</h1>
                </div>
                
                <button class="btn btn-dark w-100 btn-mobile mt-3" onclick="location.reload()">
                    <i class="fas fa-check-circle me-2"></i> TERMINER & QUITTER
                </button>
            </div>
        </div>
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


<script>
    // --- UTILITAIRES ---
    function generateUUID() {
        return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
            (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
        );
    }

    // FERMETURE PROPRE ET DÉFINITIVE DES MODALS (Anti-blocage)
    function killModal(modalId) {
        const modalEl = document.getElementById(modalId);
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
        
        // On force le nettoyage du DOM au cas où Bootstrap bug
        setTimeout(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style = "";
        }, 150);
    }
</script>



<script type="module">
    import { db } from '/js/db-manager.js';

    const urlParams = new URLSearchParams(window.location.search);
    let carnetId = urlParams.get('carnet_id');
    const agentId = localStorage.getItem('agent_id') || 0;
    if (!carnetId) {
        const pathSegments = window.location.pathname.split('/');
        const lastSegment = pathSegments[pathSegments.length - 1];
        if (!isNaN(lastSegment) && lastSegment !== "") {
            carnetId = lastSegment;
        }
    }
    console.log("ID Carnet détecté :", carnetId);
    // --- 1. INITIALISATION ---
    async function initPage() {
        try {
            if (!db.isOpen()) await db.open();
            
            const carnet = await db.carnets.get(Number(carnetId));
            if (!carnet) {
                document.getElementById('loader').innerHTML = "<div class='alert alert-warning'>Carnet introuvable.</div>";
                return;
            }

            const client = await db.clients.get(Number(carnet.client_id));
            document.getElementById('client-nom').innerText = client ? client.nom + " " + client.prenom : "Client #" + carnet.client_id;
            document.getElementById('carnet-numero').innerText = "Carnet : " + (carnet.numero || "N/A");

            const cycles = await db.cycles.where('carnet_id').equals(carnet.id).toArray();
            const cycleActif = cycles.find(c => c.statut === 'en_cours');

            if (cycleActif) {
                await afficherActionCollecte(cycleActif);
            } else {
                const toutesCollectes = await db.collectes.toArray();
                const solde = calculerSolde(cycles, toutesCollectes);
                const aDesTermines = cycles.some(c => c.statut === 'termine' && !c.retire_at);
                afficherActionCreation(carnet.id, solde, aDesTermines);
            }

            document.getElementById('loader').classList.add('d-none');
            document.getElementById('content-collecte').classList.remove('d-none');
        } catch (e) { console.error("Init Error:", e); }
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
                style="border-radius: 15px; height: 60px; font-family: 'Courier New', monospace;">
               
                <button onclick="window.creerCycle(${carnetId})" class="btn btn-warning btn-lg w-100 fw-bold text-white shadow" style="border-radius: 15px; height: 60px;">
                    OUVRIR LE CYCLE
                </button>
            </div>
        `;
    }

    // --- 3. LOGIQUE ---
    window.changePointage = (v, max) => {
        const el = document.getElementById('nb-val');
        let n = parseInt(el.innerText) + v;
        if (n < 1 || (max && n > max)) return;
        el.innerText = n;
        document.getElementById('total-txt').innerText = n * parseInt(document.getElementById('prix-unit').value);
    };

    window.ouvrirConfirm = (id, mise) => {
        const nb = document.getElementById('nb-val').innerText;
        document.getElementById('text-confirm-details').innerHTML = `Enregistrer <b>${nb} jours</b> soit <b>${nb*mise} FCFA</b> ?`;
        document.getElementById('btn-valider-final').onclick = () => window.savePointage(id, mise, nb);
        new bootstrap.Modal(document.getElementById('modalConfirm')).show();
    };

    window.savePointage = async (id, mise, nb) => {
        killModal('modalConfirm');
        const cycle = await db.cycles.get(Number(id));
        const cycle_uid = cycle.cycle_uid;
        await db.collectes.add({
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
            await db.cycles.update(cycle.id, { statut: 'termine', completed_at: new Date().toISOString(), synced: 0 });
            window.showBilan(cycle.id);
        } else {
            location.reload();
        }
    };

    window.ouvrirCloture = (id) => {
        document.getElementById('btn-valider-cloture-final').onclick = async () => {
            killModal('modalConfirmCloture');
            // On marque juste comme terminé et non synchronisé
            await db.cycles.update(Number(id), { 
                statut: 'termine', 
                completed_at: new Date().toISOString(), 
                synced: 0 
            });
            window.showBilan(id);
        };
        new bootstrap.Modal(document.getElementById('modalConfirmCloture')).show();
    };

    window.showBilan = async (id) => {
        const cycle = await db.cycles.get(Number(id));
        const s = await getStats(cycle.cycle_uid ?? cycle.id);
        const mise = cycle.montant_journalier;
        const comm = s.fait > 0 ? mise : 0;
        const net = Math.max(0, (s.fait * mise) - comm);

        document.getElementById('recu-date').innerText = new Date().toLocaleDateString();
        document.getElementById('recu-jours').innerText = `${s.fait} / 31`;
        document.getElementById('recu-mise').innerText = mise + " FCFA";
        document.getElementById('recu-commission').innerText = "- " + comm + " FCFA";
        document.getElementById('recu-total').innerText = net + " FCFA";

        new bootstrap.Modal(document.getElementById('modalRecuFinal')).show();
    };

    window.creerCycle = async (cid) => {
        const mise = parseInt(document.getElementById('input_mise').value) || 300;
        const carnet = await db.carnets.get(Number(cid));
        
        // --- Calcul de la date de fin (31 jours hors dimanches) ---
        let dateDebut = new Date();
        let dateFin = new Date(dateDebut);
        let joursAjoutes = 0;
        const objectifJours = 31;

        while (joursAjoutes < objectifJours) {
            dateFin.setDate(dateFin.getDate() + 1); // On avance d'un jour
            if (dateFin.getDay() !== 0) { // 0 correspond au Dimanche
                joursAjoutes++;
            }
        }
        // -------------------------------------------------------
        console.log(dateFin.toISOString())
        
        await db.cycles.add({
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

        location.reload();
    };

    async function getStats(uid) {
        const colls = await db.collectes.where('cycle_uid').equals(String(uid)).toArray();
        const fait = colls.reduce((a, b) => a + b.pointage, 0);
        return { fait, restant: 31 - fait, pct: (fait / 31) * 100 };
    }

    function calculerSolde(cycles, colls) {
        return cycles.filter(c => c.statut === 'termine' && !c.retire_at).reduce((acc, c) => {
            const uid = String(c.cycle_uid ?? c.id);
            const sommeCollectes = colls.filter(o => String(o.cycle_id) === uid)
                                        .reduce((s, o) => s + o.montant, 0);
            
            const commission = c.montant_journalier; // 1 jour de frais
            return acc + (sommeCollectes - commission);
        }, 0);
    }

    initPage();
</script>
@endsection