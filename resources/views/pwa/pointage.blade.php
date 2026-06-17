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
    
    <!-- <button onclick="goBackToCollecte()" class="btn btn-link text-dark p-0 me-3 border-0">
        <i class="bi bi-arrow-left fs-3"></i>
    </button> -->
    
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
            const [client, cycles, toutesCollectes] = await Promise.all([
                activeDB.clients.get(Number(currentClientId)),
                activeDB.cycles.where('carnet_id').equals(carnet.id).toArray(),
                activeDB.collectes.toArray()
            ]);

            // Vérification logique des cycles terminés vs limite catégorie
            const nbCyclesMax = Number(carnet.category_tontine?.nombre_cycles || 0);
            const nbCyclesTermines = cycles.filter(c => c.statut === 'termine').length;
            const estBloque = nbCyclesMax > 0 && nbCyclesTermines >= nbCyclesMax;

            document.getElementById('client-nom').innerText = client ? `${client.nom} ${client.prenom}` : "Client #" + currentClientId;
            
            const carnetBadge = document.getElementById('carnet-numero');
            if (carnetBadge) {
                carnetBadge.innerText = "Carnet : " + (carnet.numero || "N/A");
                carnetBadge.style.display = 'inline-block';
            }

            const cycleActif = cycles.find(c => c.statut === 'en_cours');

            if (cycleActif) {
                await afficherActionCollecte(cycleActif);
            } else {
                const solde = calculerSolde(cycles, toutesCollectes);
                const aDesTermines = cycles.some(c => c.statut === 'termine' && !c.retire_at);
                
                // Passer estBloque à la fonction de rendu
                afficherActionCreation(carnet.id, solde, aDesTermines, estBloque, nbCyclesMax);
            }

            document.getElementById('loader').classList.add('d-none');
            document.getElementById('content-collecte').classList.remove('d-none');
        } catch (e) { 
            console.error("Init Error:", e); 
            document.getElementById('loader').innerHTML = "<div class='alert alert-danger'>Erreur de chargement.</div>";
        }
    }
    // --- 2. RENDER ---
    async function afficherActionCollecte(cycle) {
        const stats = await getStats(cycle.cycle_uid);
        const fini = stats.fait >= 31;

        document.getElementById('zone-action').innerHTML = `
            <div class="card shadow-sm border-0 mb-4 animate__animated animate__fadeIn" style="border-radius:24px;">
                <div class="card-body text-center p-4">
                    
                    <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                        <small class="text-muted fw-bold text-uppercase">Progression du cycle</small>
                        <span class="badge bg-light text-dark border fw-bold" style="border-radius: 8px;">Mise : ${cycle.montant_journalier} F</span>
                    </div>
                    
                    <input type="hidden" id="prix-unit" value="${cycle.montant_journalier}">
                    
                    <div class="progress mb-3" style="height: 22px; border-radius: 12px; background: #f1f3f5; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                            style="width: ${stats.pct}%; border-radius: 12px; font-weight: bold; font-size: 0.85rem;">
                            ${Math.round(stats.pct)}%
                        </div>
                    </div>

                    <div class="d-flex justify-content-between small fw-bold mb-4 px-1">
                        <span class="text-success"><i class="bi bi-calendar-check me-1"></i>PAYÉ : ${stats.fait} j</span>
                        <span class="text-primary"><i class="bi bi-calendar-minus me-1"></i>RESTE : ${stats.restant} j</span>
                    </div>

                    ${fini ? `
                        <div class="alert alert-success py-3 rounded-4 border-0 shadow-sm animate__animated animate__heartBeat">
                            <i class="bi bi-trophy-fill fs-3 d-block mb-1 text-success"></i>
                            <b class="fs-5">CYCLE COMPLET !</b><br>
                            <small class="text-muted">Toutes les 31 collectes ont été effectuées.</small>
                        </div>
                    ` : `
                        <div class="bg-light rounded-4 p-3 mb-4 border-0" style="background-color: #f8f9fa !important;">
                            <small class="text-secondary fw-bold tracking-wide" style="font-size: 0.75rem;">NOMBRE DE JOURS À POINTER</small>
                            
                            <div class="d-flex justify-content-center align-items-center my-2">
                                <button class="btn btn-white shadow-sm border" onclick="window.changePointage(-1)" 
                                        style="width:50px; height:50px; border-radius:14px; font-size:1.5rem; font-weight: bold; background: #fff;">-</button>
                                
                                <h1 class="mx-4 mb-0 fw-bold display-6 text-dark" id="nb-val" style="min-width: 40px;">1</h1>
                                
                                <button class="btn btn-white shadow-sm border" onclick="window.changePointage(1, ${stats.restant})" 
                                        style="width:50px; height:50px; border-radius:14px; font-size:1.5rem; font-weight: bold; background: #fff;">+</button>
                            </div>
                            
                            <h3 class="fw-bold text-primary mb-0 mt-2" style="font-size: 1.6rem;">
                                <span id="total-txt">${cycle.montant_journalier}</span> <span style="font-size: 1.1rem;">FCFA</span>
                            </h3>
                        </div>
                        
                        <button class="btn btn-primary btn-lg w-100 shadow border-0 fw-bold d-flex align-items-center justify-content-center gap-2" 
                                onclick="window.ouvrirConfirm(${cycle.id}, ${cycle.montant_journalier})"
                                style="border-radius: 16px; height: 60px; background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                            <i class="bi bi-check-circle-fill fs-5"></i> VALIDER LE POINTAGE
                        </button>
                    `}
                    
                    <div class="mt-4 pt-3 border-top">
                        <button class="btn btn-link text-muted text-decoration-none small fw-medium py-1" 
                                onclick="window.ouvrirCloture(${cycle.id})" style="font-size: 0.85rem;">
                            <i class="bi bi-excretion me-1 text-danger"></i> Clôturer le cycle de force
                        </button>
                    </div>

                </div>
            </div>
        `;
    }

    function afficherActionCreation(carnetId, solde, displaySolde,estBloque, nbMax) {
        if (estBloque) {
            document.getElementById('zone-action').innerHTML = `
                <div class="card border-0 shadow-sm p-5 text-center bg-light">
                    <i class="bi bi-lock-fill fs-1 text-danger mb-3"></i>
                    <h5 class="fw-bold">Carnet Clôturé</h5>
                    <p class="text-muted">Ce carnet a atteint sa limite de ${nbMax} cycles. Aucune nouvelle tontine ne peut être ouverte.</p>
                </div>
            `;
            // Trigger optionnel de SweetAlert si l'agent clique sur une zone de contrôle
            return;
        }
        document.getElementById('zone-action').innerHTML = `
            <div class="card border-0 shadow-sm p-4 text-center animate__animated animate__fadeInUp" style="border-radius: 24px;">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle" style="width: 60px; height: 60px;">
                    <i class="bi bi-folder-plus fs-2"></i>
                </div>

                <h5 class="fw-bold mb-1">Nouveau Cycle</h5>
                <p class="small text-muted mb-4">Initialisation d'une nouvelle tontine</p>
                
                <div class="form-group mb-4">
                    <label class="small fw-bold text-secondary d-block mb-2 text-start px-2">
                        <i class="bi bi-cash me-1"></i> Mise journalière (FCFA)
                    </label>
                    
                    <input type="number" 
                        id="input_mise" 
                        class="form-control text-center fw-bold text-dark border-2" 
                        value="300" 
                        min="100" 
                        step="100"
                        inputmode="numeric"
                        style="border-radius: 16px; height: 65px; font-size: 1.5rem; background-color: #f8f9fa;">
                    
                    <div class="d-flex justify-content-between gap-2 mt-2 px-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-2 flex-grow-1" style="border-radius: 10px;" onclick="document.getElementById('input_mise').value = 300">300 F</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-2 flex-grow-1" style="border-radius: 10px;" onclick="document.getElementById('input_mise').value = 500">500 F</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-2 flex-grow-1" style="border-radius: 10px;" onclick="document.getElementById('input_mise').value = 1000">1000 F</button>
                    </div>
                </div>
            
                <button onclick="confirmerOuvertureCycle(${carnetId})" class="btn btn-success btn-lg w-100 fw-bold text-white shadow-sm border-0 d-flex align-items-center justify-content-center gap-2" style="border-radius: 16px; height: 60px; background: linear-gradient(135deg, #198754, #157347);">
                    <i class="bi bi-play-circle-fill fs-5"></i> OUVRIR LE CYCLE
                </button>
            </div>
        `;
    }

    window.confirmerOuvertureCycle = function(carnetId) {
        const miseInput = document.getElementById('input_mise');
        const miseValeur = parseInt(miseInput.value, 10);

        // Vérification de sécurité de base
        if (isNaN(miseValeur) || miseValeur < 100) {
            Swal.fire({
                icon: 'warning',
                title: 'Mise invalide',
                text: 'La mise minimale journalière doit être de 100 FCFA.',
                confirmButtonColor: '#ff9800'
            });
            miseInput.value = 100;
            return;
        }

        // Fenêtre de confirmation stricte
        Swal.fire({
            title: 'Confirmer la mise ?',
            text: `Vous allez ouvrir un cycle avec une mise journalière de ${miseValeur} FCFA pour ce livret.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745', // Vert pour valider
            cancelButtonColor: '#dc3545',  // Rouge pour annuler
            confirmButtonText: 'Oui, ouvrir',
            cancelButtonText: 'Annuler',
            reverseButtons: true // Met le bouton "Valider" à droite, plus naturel sur mobile
        }).then((result) => {
            if (result.isConfirmed) {
                // Si l'agent clique sur "Oui, ouvrir", on lance la fonction Dexie
                window.creerCycle(carnetId, miseValeur);
            }
        });
    };

    window.creerCycle = async (cid, miseValidee) => {
        try {
            const activeDB = getAgentDB();
            
            const existant = await activeDB.cycles
                .where('carnet_id').equals(Number(cid))
                .and(c => c.statut === 'en_cours')
                .first();
                
            if (existant) {
                Swal.fire('Erreur', 'Un cycle est déjà actif sur ce livret.', 'error');
                return;
            }

            const carnet = await activeDB.carnets.get(Number(cid));
            let dateDebut = new Date(); // Date du jour
            let dateFin = calculerDateFin(dateDebut);

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
                montant_journalier: miseValidee, // Utilisation directe de la valeur confirmée
                statut: 'en_cours',
                date_debut: dateDebut.toISOString(),
                date_fin_prevue: dateFin.toISOString(), 
                synced: 0
            });

            Swal.fire({
                icon: 'success',
                title: 'Cycle ouvert !',
                text: `Mise fixée à ${miseValidee} FCFA.`,
                timer: 1500,
                showConfirmButton: false
            }).then(() => initPage());

        } catch (error) {
            console.error(error);
            Swal.fire('Erreur', 'Impossible de sauvegarder le cycle localement.', 'error');
        }
    };
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
            date_saisie: new Date().toISOString(),
            synced: 0
        });

        const s = await getStats(cycle.cycle_uid ?? cycle.id);
        if (s.fait >= 31) {
            await activeDB.cycles.update(cycle.id, { statut: 'termine', date_cloture_reelle: new Date().toISOString(), synced: 0 });
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
     * Calcule la date de fin après 31 jours de collecte
     * Exclut uniquement les dimanches et les jours fériés FIXES.
     */
    function calculerDateFin(dateDepart) {
        const nbJours = 31;
        // Liste des jours fériés FIXES au Togo
        const feriesFixes = [
            '01-01', // Jour de l'an
            '04-27', // Indépendance
            '05-01', // Fête du travail
            '06-21', // Martyr (Note : 21 juin est férié au Togo)
            '08-15', // Assomption
            '11-01', // Toussaint
            '12-25'  // Noël
        ];

        let dateCourante = new Date(dateDepart);
        let joursCollectes = 0;

        while (joursCollectes < nbJours) {
            dateCourante.setDate(dateCourante.getDate() + 1);

            const estDimanche = dateCourante.getDay() === 0;
            
            // On formate la date en 'MM-DD' pour comparer avec notre liste fixe
            const moisJour = ('0' + (dateCourante.getMonth() + 1)).slice(-2) + '-' + 
                            ('0' + dateCourante.getDate()).slice(-2);
            
            const estFerieFixe = feriesFixes.includes(moisJour);

            // On compte le jour seulement si ce n'est pas un dimanche et pas un férié fixe
            if (!estDimanche && !estFerieFixe) {
                joursCollectes++;
            }
        }
        return dateCourante;
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