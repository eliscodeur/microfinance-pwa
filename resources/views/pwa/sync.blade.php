@extends('pwa.layouts.app')

@section('content')
<div class="container-fluid p-0" style="background-color: #f8f9fa; min-height: 100vh;">
    <div class="p-4" style="padding-bottom: 180px !important;"> 
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="icon-box bg-primary-subtle rounded-3 p-3 me-3 text-primary">
                        <i class="bi bi-cloud-upload-fill fs-3"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Données à envoyer</h5>
                        <p class="text-muted small mb-0">Stockées sur cet appareil</p>
                    </div>
                </div>
                
                <div class="sync-list">
                    <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                        <span class="text-secondary">Nouveaux Cycles</span>
                        <span id="count-cycles" class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 fw-bold shimmer-loading">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                        <span class="text-secondary">Collectes réalisées</span>
                        <span id="count-collectes" class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 fw-bold shimmer-loading">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-3">
                        <span class="fw-bold text-dark">Montant total</span>
                        <span id="total-amount" class="h4 fw-bold text-primary mb-0 shimmer-loading">0 FCFA</span>
                    </div>
                </div>
            </div>
        </div>

        @if(session('pending_batch'))
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start" style="border-radius: 12px;">
                <i class="bi bi-clock-history text-warning fs-5 me-3 mt-1"></i>
                <div>
                    <strong class="d-block text-dark mb-1">En attente de validation</strong>
                    <span class="small text-muted">L'administrateur vérifie actuellement votre dernier envoi.</span>
                </div>
            </div>
        @endif
        
        <div class="mt-5">
    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Derniers envois</h6>
    
    @forelse(auth()->user()->agent->syncBatches()->latest()->take(5)->get() as $batch)
        <div class="d-flex align-items-center bg-white p-3 rounded-3 shadow-sm mb-2">
            
            @if($batch->status === 'approved')
                <i class="bi bi-check-circle-fill text-success fs-5 me-3"></i>
            @elseif($batch->status === 'rejected')
                <i class="bi bi-x-circle-fill text-danger fs-5 me-3"></i>
            @else
                <i class="bi bi-clock-history text-warning fs-5 me-3"></i>
            @endif

            <div class="flex-grow-1">
                <small class="text-muted d-block">{{ $batch->created_at->format('d/m/Y H:i') }}</small>
                <span class="small text-dark fw-medium">
                    {{ $batch->nb_collectes }} collectes - {{ number_format($batch->total_montant, 0, ',', ' ') }} FCFA
                </span>
            </div>

            @if($batch->status === 'approved')
                <span class="badge rounded-pill bg-success-subtle text-success">Validé</span>
            @elseif($batch->status === 'rejected')
                <span class="badge rounded-pill bg-danger-subtle text-danger">Rejeté</span>
            @else
                <span class="badge rounded-pill bg-warning-subtle text-warning">En attente</span>
            @endif

        </div>
    @empty
        <div class="text-center py-3 text-muted small">Aucun historique récent.</div>
    @endforelse
</div>
    </div>
    
    <div class="bg-white border-top p-3" style="position: fixed; bottom: 60px; left: 0; right: 0; z-index: 1030;">
        <div id="sync-progress-container" class="d-none mb-3">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-primary small fw-bold" id="progress-label">Statut...</span>
                <span class="text-primary small fw-bold" id="progress-percent">0%</span>
            </div>
            <div class="progress" style="height: 10px; border-radius: 5px;">
                <div id="sync-bar-bottom" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
        </div>
        
        <button id="btn-sync" class="btn btn-success w-100 py-3 fw-bold shadow-sm" style="border-radius: 15px;">
            <i class="bi bi-cloud-arrow-up-fill me-2"></i> SYNCHRONISER MAINTENANT
        </button>
        <!-- <p class="text-center mt-2 mb-0" style="font-size: 0.8rem;">
            <small id="last-sync-text" class="text-muted jam-last-sync">Dernière synchro : Calcul en cours...</small>
        </p> -->
    </div>
</div>


<style>
    /* Espacement pour éviter que le contenu ne soit caché par le Tab Bar */
    body { 
        padding-bottom: 70px; 
    }

    /* Effet Skeleton Loading pour le chargement initial des compteurs */
    .shimmer-loading {
        position: relative;
        overflow: hidden;
        color: transparent !important;
        background-color: #e9ecef !important;
        border-radius: 8px;
    }

    .shimmer-loading::after {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(90deg, 
            rgba(255,255,255,0) 0%, 
            rgba(255,255,255,0.6) 50%, 
            rgba(255,255,255,0) 100%);
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer { 
        0% { transform: translateX(-100%); } 
        100% { transform: translateX(100%); } 
    }
    
    /* Fallbacks pour les couleurs Bootstrap si version < 5.3 */
    .bg-primary-subtle { background-color: #cfe2ff; }
    .text-primary { color: #0d6efd !important; }
    .bg-success-subtle { background-color: #d1e7dd; }
    .text-success { color: #198754 !important; }
    .bg-warning-subtle { background-color: #fff3cd; }
    .text-warning { color: #856404 !important; }

    /* Customisation légère pour SweetAlert2 pour coller au style mobile */
    .rounded-4 {
        border-radius: 24px !important;
    }
</style>
<script src="/js/dexie.js"></script>

<script type="module">
    import { getAgentDB, populateDatabase, DBManager, db } from '/js/db-manager.js';

    const getMatricule = () => localStorage.getItem('current_agent_matricule');
    const getSyncKey = () => `pending_sync_job_${getMatricule()}`;
    const POLL_DELAY_MS = 8000;
    
    window.syncRequestInFlight = false;
    window.batchStatusPolling = false;
    let swalLoader = null; // Pour contrôler l'overlay de progression

    // --- INITIALISATION ---
    window.addEventListener('load', async () => {
        try {
            const database = getAgentDB();
            if (database && !database.isOpen()) await database.open();
            
            await window.rafraichirResumeSync();

            const btnSync = document.getElementById('btn-sync');
            if(btnSync) {
                btnSync.disabled = false;
                btnSync.addEventListener('click', window.demarrerAction);
            }

            verifierRepriseAuto();
        } catch (e) {
            console.error("Erreur init:", e);
        }
    });

    // --- FONCTIONS GLOBALES ---
    window.rafraichirResumeSync = async function() {
        const database = getAgentDB();
        if (!database) return;
        if (!database.isOpen()) await database.open();

        const pColl = await database.collectes.where('synced').equals(0).toArray();
        const pCyc = await database.cycles.where('synced').equals(0).count();
        const total = pColl.reduce((sum, c) => sum + parseFloat(c.montant || 0), 0);
        
        if(document.getElementById('count-collectes')) document.getElementById('count-collectes').innerText = pColl.length;
        if(document.getElementById('count-cycles')) document.getElementById('count-cycles').innerText = pCyc;
        if(document.getElementById('total-amount')) {
            document.getElementById('total-amount').innerText = new Intl.NumberFormat('fr-FR').format(total) + ' FCFA';
        }
        // Retire l'effet de chargement une fois les données prêtes
        document.querySelectorAll('.shimmer-loading').forEach(el => el.classList.remove('shimmer-loading'));
    };

    window.demarrerAction = async () => {
        if (window.syncRequestInFlight) return;

        if (!navigator.onLine) {
            Swal.fire('Hors connexion', 'Une connexion internet est requise.', 'refer');
            return;
        }

        const database = getAgentDB();
        const cycles = await database.cycles.where('synced').equals(0).toArray();
        const collectes = await database.collectes.where('synced').equals(0).toArray();
        const agents = await database.agents.where('synced').equals(0).toArray();

        if (cycles.length === 0 && collectes.length === 0) {
            Swal.fire('Déjà à jour', 'Toutes les collectes sont déjà synchronisées.', 'info');
            return;
        }
        // console.log("Données à synchroniser:", `{{ route('pwa.check-sync-permission') }}?matricule=${getMatricule()}&t=${Date.now()}`);
        // return
        try {
            const authRes = await fetch(`{{ route('pwa.check-sync-permission') }}?matricule=${getMatricule()}&t=${Date.now()}`, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Pragma': 'no-cache', 'Cache-Control': 'no-cache' }
            });

            const auth = await authRes.json();

            if (!auth.can_sync) {
                Swal.fire('Non autorisé', 'Votre autorisation a expiré.', 'warning');
                return;
            }
            lancerProcessus(cycles, collectes, agents);

        } catch (e) {
            console.error("Erreur check permission:", e);
            Swal.fire('Erreur', 'Impossible de vérifier votre statut.', 'error');
        }
    };

    async function lancerProcessus(cycles, collectes, agents) {
        window.syncRequestInFlight = true;
        
        swalLoader = Swal.fire({
            title: 'Synchronisation',
            html: '<b id="swal-status">Préparation...</b>',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const agentsPayload = agents.map(a => ({
                id: a.id,
                pin_hash: a.pin_hash
            }));

            // 1. On récupère le matricule de l'agent (ex: "NEC-00002")
            const agentMatricule = getMatricule(); 

            const syncJob = {
                matricule: agentMatricule, // 👈 Ajout crucial pour ton Laravel mis à jour
                sync_uuid: `sync-${agentMatricule}-${Date.now()}`,
                cycles: cycles,
                collectes: collectes,
                agents: agentsPayload
            };

            updateStatusUI("Envoi au serveur...");

            const response = await fetch("{{ route('pwa.sync-data-post') }}", {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                },
                body: JSON.stringify(syncJob) // Contient maintenant le matricule
            });

  

            const result = await response.json();
            if (!response.ok) throw new Error(result.message || "Erreur envoi");

            savePendingSyncJob(result.batch);
            
            updateStatusUI("Attente validation Admin...");
            await surveillerLeBatch(result.batch.sync_uuid);

        } catch (error) {
            Swal.fire('Échec', error.message, 'error');
        } finally {
            window.syncRequestInFlight = false;
        }
    }

    async function surveillerLeBatch(uuid) {
        if (window.batchStatusPolling) return;
        window.batchStatusPolling = true;

        // Si l'overlay n'est pas déjà là (cas de la reprise auto), on l'affiche
        if(!Swal.isVisible()) {
            swalLoader = Swal.fire({
                title: 'Validation en cours',
                html: '<b id="swal-status">En attente de l\'administrateur...</b><br><small>Vous pouvez fermer, le suivi reprendra au retour.</small>',
                showCancelButton: true,
                cancelButtonText: 'Arrêter le suivi',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            }).then((result) => {
                if (result.isDismissed) annulerAttenteBatch();
            });
        }

        try {
            while (true) {
                const job = getPendingSyncJob();
                if (!job || job.sync_uuid !== uuid) break; 

                if (!navigator.onLine) {
                    updateStatusUI("Connexion perdue... en attente");
                    await new Promise(r => setTimeout(r, 5000));
                    continue;
                }

                const url = `{{ route('pwa.sync-batches.status', ['syncUuid' => ':uuid']) }}`.replace(':uuid', uuid);

                try {
                    const res = await fetch(`${url}?nocache=${Date.now()}`, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }
                    });
                    
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);

                    const data = await res.json();
                    const statut = data?.batch?.status || data?.status;

                    if (statut === 'valide' || statut === 'approved') {
                        await finaliserTout(data);
                        break; 
                    } 
                    
                    if (statut === 'rejected') {
                        clearPendingSyncJob(); 
                        Swal.fire('Refusé', 'L\'administrateur a rejeté la demande.', 'error');
                        setTimeout(() => { window.location.reload(); }, 2000);
                        break; 
                    }
                } catch (fetchError) {
                    console.error("Erreur polling:", fetchError);
                }

                await new Promise(r => setTimeout(r, POLL_DELAY_MS));
            }
        } finally {
            window.batchStatusPolling = false;
        }
    }

    async function finaliserTout(serverData) {
        updateStatusUI("Mise à jour locale...");
        
        try {
            await fetch("{{ route('pwa.lock-sync') }}", { 
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
            });

            if (serverData.data) {
                await populateDatabase(serverData.data, { replaceAll: true });
            }

            clearPendingSyncJob();
            Swal.fire({
                icon: 'success',
                title: 'Terminé !',
                text: 'Synchronisation réussie.',
                timer: 2000,
                showConfirmButton: false
            });
            
            setTimeout(() => { window.location.href = "{{ route('pwa.index') }}"; }, 1500);

        } catch (error) {
            Swal.fire('Erreur', 'Échec de mise à jour locale.', 'error');
        }
    }

    // --- HELPERS ---
    function updateStatusUI(text) {
        const el = document.getElementById('swal-status');
        if (el) el.innerText = text;
        const fallback = document.getElementById('sync-status'); // Ton ancien texte dans le pied de page
        if (fallback) fallback.innerText = text;
    }

    function savePendingSyncJob(batch) { localStorage.setItem(getSyncKey(), JSON.stringify(batch)); }
    function getPendingSyncJob() { 
        const data = localStorage.getItem(getSyncKey());
        return data ? JSON.parse(data) : null; 
    }
    function clearPendingSyncJob() { localStorage.removeItem(getSyncKey()); }

    async function initialiserDonneesSiVide() {
        try {
            const database = getAgentDB();
            await database.open(); 
            const clientCount = await database.clients.count();

            if (clientCount > 0) return; 

            Swal.fire({
                title: 'Initialisation',
                text: 'Téléchargement des données...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const res = await fetch("{{ route('pwa.initial-data') }}?t=" + Date.now());
            if (!res.ok) throw new Error("Session expirée");

            const result = await res.json();
           
            const payload = result.data || result;

            // Enregistrement Agent
            const matricule = getMatricule();
            const authData = JSON.parse(localStorage.getItem(`auth_v1_${matricule}`));
            if (authData && payload.agent) {
                await database.agents.put({
                    id: Number(payload.agent.id),
                    nom: payload.agent.nom,
                    matricule: matricule,
                    pin_hash: authData.pin_hash, 
                    synced: 0,
                    updated_at: new Date().toISOString()
                });
            }
            // console.log("Données initiales reçues:", payload);
            // return
            await populateDatabase(payload, { replaceAll: true });

            localStorage.setItem('last_sync', new Date().toISOString());
            window.location.replace("{{ route('pwa.index') }}");

        } catch (e) {
            if (e.message.includes("Session")) window.location.href = "{{ route('agent.login') }}";
        }
    }

    function verifierRepriseAuto() {
        const job = getPendingSyncJob();
        if (job && navigator.onLine) {
            surveillerLeBatch(job.sync_uuid);
        } else {
            initialiserDonneesSiVide();
        }
    }

    async function annulerAttenteBatch() {
        const job = getPendingSyncJob();
        if (job) {
            fetch(`{{ route('pwa.sync-batches.cancel', ['syncUuid' => ':uuid']) }}`.replace(':uuid', job.sync_uuid), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
        }
        clearPendingSyncJob();
        location.reload();
    }
</script>
@endsection