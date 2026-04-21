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
    
    @forelse(auth()->user()->agent->syncBatches()->latest()->take(3)->get() as $batch)
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

<div id="sync-overlay" class="sync-overlay" style="display: none;">
    <div class="sync-modal-card">
        <div class="mb-3">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
        <h5 id="sync-status" class="fw-bold mb-1">Traitement...</h5>
        <p id="sync-percent" class="text-primary fw-bold mb-3">0%</p>
        <div class="progress mb-4" style="height: 10px; border-radius: 10px;">
            <div id="sync-bar-overlay" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
        </div>
        <button id="btn-cancel-sync-overlay" class="btn btn-light btn-sm text-danger w-100" style="border-radius: 12px;">
            ANNULER L'ATTENTE
        </button>
    </div>
</div>

<div id="mobile-alert-modal" class="modal-mobile-container" style="display: none;">
    <div class="modal-mobile-content">
        <button type="button" class="btn-close-modal-top" onclick="closeAlertModal()">
            <i class="bi bi-x"></i>
        </button>
        <div id="modal-alert-icon" class="mb-3 mt-2"></div>
        <h5 id="modal-alert-title" class="fw-bold px-3"></h5>
        <p id="modal-alert-message" class="text-muted px-3 small"></p>
        <div class="modal-mobile-footer">
            <button onclick="closeAlertModal()" class="btn-modal-close">COMPRIS</button>
        </div>
    </div>
</div>

<style>
    /* Correction de l'espacement pour le Tab Bar existant */
    body { padding-bottom: 60px; } /* Hauteur de ton tab bar Accueil/Clients/Sync */

    /* Idée : Skeleton Loading Effet (S'active sur .shimmer-loading en JS) */
    .shimmer-loading {
        position: relative;
        overflow: hidden;
        color: transparent !important;
        background-color: #e9ecef !important;
    }
    .shimmer-loading::after {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.6) 50%, rgba(255,255,255,0) 100%);
        animation: shimmer 1.5s infinite;
    }
    @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
    
    /* Bootstrap 5.3 Utility Fallbacks for Subtles */
    .bg-primary-subtle { background-color: #cfe2ff; }
    .text-primary { color: #0d6efd !important; }
    .bg-success-subtle { background-color: #d1e7dd; }
    .text-success { color: #198754 !important; }
    .bg-secondary-subtle { background-color: #e2e3e5; }
    .text-secondary { color: #6c757d !important; }
    .bg-warning-subtle { background-color: #fff3cd; }
    .text-warning { color: #856404 !important; }
    .modal-mobile-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        backdrop-filter: blur(4px);
    }

    .modal-mobile-content {
        background: white;
        width: 100%;
        max-width: 320px;
        border-radius: 24px;
        text-align: center;
        padding-top: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        animation: slideUp 0.3s ease-out;
    }

    .modal-mobile-footer {
        border-top: 1px solid #f0f0f0;
        margin-top: 20px;
    }

    .btn-modal-close {
        width: 100%;
        background: none;
        border: none;
        padding: 15px;
        color: #007bff;
        font-weight: bold;
        letter-spacing: 1px;
    }

    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .modal-mobile-content {
        position: relative; /* Important pour positionner la croix par rapport au contenu */
        background: white;
        width: 100%;
        max-width: 320px;
        border-radius: 24px;
        text-align: center;
        padding-top: 30px; /* Un peu plus d'espace en haut */
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        animation: slideUp 0.3s ease-out;
    }

    .btn-close-modal-top {
        position: absolute;
        top: 12px;
        right: 12px;
        background: #f0f2f5;
        border: none;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #65676b;
        font-size: 20px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-close-modal-top:hover {
        background: #e4e6eb;
    }

    .modal-mobile-footer {
        border-top: 1px solid #f0f0f0;
        margin-top: 20px;
    }
    .sync-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px);
        z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .sync-modal-card {
        background: white; width: 100%; max-width: 350px; padding: 30px;
        border-radius: 28px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    /* Modale d'alerte mobile */
    .modal-mobile-container {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5); z-index: 10000;
        display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .modal-mobile-content {
        position: relative; background: white; width: 100%; max-width: 320px;
        border-radius: 24px; text-align: center; padding-top: 35px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2); animation: slideUp 0.3s ease-out;
    }
    .btn-close-modal-top {
        position: absolute; top: 12px; right: 12px; background: #f0f2f5;
        border: none; width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; color: #65676b;
    }
    .modal-mobile-footer { border-top: 1px solid #f0f0f0; margin-top: 20px; }
    .btn-modal-close {
        width: 100%; background: none; border: none; padding: 15px;
        color: #007bff; font-weight: bold;
    }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
<script type="module">
    import { db, populateDatabase } from '/js/db-manager.js';

    const PENDING_SYNC_KEY = 'pending_sync_job';
    const POLL_DELAY_MS = 8000;
    window.syncRequestInFlight = false;
    window.batchStatusPolling = false;

    // --- INITIALISATION ---
    window.addEventListener('load', async () => {
        try {
            if (!db.isOpen()) await db.open();
            
            await window.rafraichirResumeSync();

            const btnSync = document.getElementById('btn-sync');
            if(btnSync) {
                btnSync.disabled = false;
                btnSync.addEventListener('click', window.demarrerAction);
            }

            const btnCancel = document.getElementById('btn-cancel-sync-overlay');
            if(btnCancel) btnCancel.addEventListener('click', annulerAttenteBatch);

            // On appelle la reprise auto APRES que toutes les fonctions soient définies
            verifierRepriseAuto();
        } catch (e) {
            console.error("Erreur init:", e);
        }
    });

    // --- FONCTIONS GLOBALEMENT ACCESSIBLES ---
    window.rafraichirResumeSync = async function() {
        if (!db.isOpen()) await db.open();
        const pColl = await db.collectes.where('synced').equals(0).toArray();
        const pCyc = await db.cycles.where('synced').equals(0).count();
        const total = pColl.reduce((sum, c) => sum + parseFloat(c.montant || 0), 0);
        
        if(document.getElementById('count-collectes')) document.getElementById('count-collectes').innerText = pColl.length;
        if(document.getElementById('count-cycles')) document.getElementById('count-cycles').innerText = pCyc;
        if(document.getElementById('total-amount')) {
            document.getElementById('total-amount').innerText = new Intl.NumberFormat('fr-FR').format(total) + ' FCFA';
        }
        document.querySelectorAll('.shimmer-loading').forEach(el => el.classList.remove('shimmer-loading'));
    };

    window.demarrerAction = async () => {
        if (window.syncRequestInFlight) return;

        if (!navigator.onLine) {
            window.showAlertModal('Hors connexion', 'Une connexion internet est requise pour synchroniser.', 'error');
            return;
        }

        const cycles = await db.cycles.where('synced').equals(0).toArray();
        const collectes = await db.collectes.where('synced').equals(0).toArray();

        if (cycles.length === 0 && collectes.length === 0) {
            window.showAlertModal('Déjà à jour', 'Toutes vos collectes locales sont déjà synchronisées.', 'info');
            return;
        }
        
        try {
            const authRes = await fetch("{{ route('pwa.check-sync-permission') }}?t=" + Date.now(), {
                method: 'GET',
                cache: 'no-store', // <--- FORCE LE NAVIGATEUR À IGNORER LE CACHE
                headers: {
                    'Pragma': 'no-cache',
                    'Cache-Control': 'no-cache'
                }
            });

            const auth = await authRes.json();
            
            if (!auth.can_sync) {
                window.showAlertModal(
                    'La synchronisation n\'est pas autorisée', 
                    'contactez l\'admin', 
                    'warning'
                );
                return;
            }

            

            lancerProcessus(cycles, collectes);

        } catch (e) {
            console.error("Erreur check permission:", e);
            window.showAlertModal('Erreur', 'Impossible de vérifier votre statut.', 'error');
        }
    };

    async function lancerProcessus(cycles, collectes) {
        window.syncRequestInFlight = true;
        
        // ÉTAPE 1 : OVERLAY ACTIF (Envoi des données)
        afficherOverlayProgress(true); 
        document.getElementById('sync-progress-container').classList.add('d-none');
        
        updateProgressUI(20, "Préparation...", 'overlay');

        try {
            const syncJob = {
                sync_uuid: `sync-${Date.now()}`,
                cycles: cycles,
                collectes: collectes
            };

            updateProgressUI(40, "Envoi au serveur...", 'overlay');
            
            const response = await fetch("{{ route('pwa.sync-data-post') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(syncJob)
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.message || "Erreur envoi");

            savePendingSyncJob(result.batch);
            
            // ÉTAPE 2 : BASCULE VERS LE BANDEAU (Attente Admin)
            // On libère l'interface pour l'agent
            afficherOverlayProgress(false); 
            document.getElementById('sync-progress-container').classList.remove('d-none');
            
            updateProgressUI(60, "Attente validation Admin...");

            // Surveillance en arrière-plan
            await surveillerLeBatch(result.batch.sync_uuid);

        } catch (error) {
            afficherOverlayProgress(false);
            window.showAlertModal('Échec', error.message, 'error');
        } finally {
            window.syncRequestInFlight = false;
        }
    }

    
    async function surveillerLeBatch(uuid) {
        // 1. Verrou pour éviter les doublons
        if (window.batchStatusPolling) return;
        window.batchStatusPolling = true;

        console.log("Démarrage surveillance du batch :", uuid);

        try {
            // Boucle tant qu'un job est présent
            while (getPendingSyncJob()) {
                if (!navigator.onLine) {
                    console.warn("Mode hors-ligne, attente reconnexion...");
                    await new Promise(r => setTimeout(r, 5000));
                    continue;
                }

                const timestamp = new Date().getTime();
                const url = `{{ route('pwa.sync-batches.status', ['syncUuid' => ':uuid']) }}`.replace(':uuid', uuid);

                const res = await fetch(`${url}?nocache=${timestamp}`, {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                });
                
                if (!res.ok) {
                    console.error("Erreur serveur (HTTP " + res.status + ")");
                    await new Promise(r => setTimeout(r, 5000));
                    continue;
                }

                const data = await res.json();
                
                // 2. Extraction ULTRA-SÉCURISÉE du statut
                // On vérifie toutes les structures possibles (data.batch.status OU data.status)
                let statut = null;
                if (data && data.batch && data.batch.status) {
                    statut = data.batch.status;
                } else if (data && data.status) {
                    statut = data.status;
                }

                console.log("Réponse serveur :", data, "Statut détecté :", statut);

                if (statut === 'valide' || statut === 'approved') {
                    console.log("Validation détectée ! Finalisation...");
                    if (typeof finaliserTout === 'function') {
                        await finaliserTout(data);
                    } else {
                        console.error("La fonction finaliserTout n'existe pas !");
                    }
                    break;
                } 
                else if (statut === 'rejected') {
                    clearPendingSyncJob();
                    if (typeof afficherOverlayProgress === 'function') afficherOverlayProgress(false);
                    window.showAlertModal('Refusé', 'L\'administrateur a rejeté la demande.', 'error');
                    break;
                }

                // Attendre avant la prochaine vérification (par défaut 3s)
                const delay = window.POLL_DELAY_MS || 3000;
                await new Promise(r => setTimeout(r, delay));
            }
        } catch (e) {
            console.error("CRASH dans surveillerLeBatch :", e);
        } finally {
            window.batchStatusPolling = false;
        }
    }

    async function finaliserTout(serverData) {
        // 1. On informe que le serveur a validé et qu'on traite les données
        updateProgressUI(90, "Mise à jour finale...");
        
        try {
            if (serverData.data) {
                // Mise à jour de IndexedDB avec les données fraîches du serveur
                await populateDatabase(serverData.data, { replaceAll: true });
            }

            // 2. On affiche le succès final sur les deux barres (Bandeau et Overlay)
            updateProgressUI(100, "Synchronisation réussie !");
            
            // Nettoyage du localStorage pour éviter de boucler sur ce job
            clearPendingSyncJob();
            //  Changer l'autorisation de synchro de l'agent
            await fetch("{{ route('pwa.lock-sync') }}", { 
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            // 3. Délai de confort pour que l'agent voit le résultat
            setTimeout(() => {
                window.location.href = "{{ route('pwa.index') }}";
            }, 1500);

        } catch (error) {
            console.error("Erreur lors de la finalisation :", error);
            window.showAlertModal('Erreur locale', 'Les données ont été validées mais le stockage local a échoué.', 'error');
        }
    }

    // --- HELPERS ET PERSISTANCE ---
    window.showAlertModal = function(title, message, type) {
        const modal = document.getElementById('mobile-alert-modal');
        let icon = '<i class="bi bi-info-circle text-primary fs-1"></i>';
        if(type === 'error') icon = '<i class="bi bi-x-circle text-danger fs-1"></i>';
        if(type === 'warning') icon = '<i class="bi bi-exclamation-triangle text-warning fs-1"></i>';
        if(type === 'success') icon = '<i class="bi bi-check-circle text-success fs-1"></i>';

        document.getElementById('modal-alert-icon').innerHTML = icon;
        document.getElementById('modal-alert-title').innerText = title;
        document.getElementById('modal-alert-message').innerText = message;
        modal.style.display = 'flex';
    };

    window.closeAlertModal = () => document.getElementById('mobile-alert-modal').style.display = 'none';

    // Fonction pour gérer les deux types d'affichage
    function updateProgressUI(val, text, mode = 'both') {
        const barBottom = document.getElementById('sync-bar-bottom'); // Renomme l'ID dans le HTML
        const barOverlay = document.getElementById('sync-bar-overlay'); // Renomme l'ID dans le HTML
        const pctOverlay = document.getElementById('sync-percent');
        const pctBottom = document.getElementById('progress-percent');
        const stOverlay = document.getElementById('sync-status');
        const stBottom = document.getElementById('progress-label');

        // Mise à jour des barres
        if(barBottom) barBottom.style.width = val + '%';
        if(barOverlay) barOverlay.style.width = val + '%';
        
        // Mise à jour des textes
        if(pctOverlay) pctOverlay.innerText = val + '%';
        if(pctBottom) pctBottom.innerText = val + '%';
        if(stOverlay) stOverlay.innerText = text;
        if(stBottom) stBottom.innerText = text;
    }

    function afficherOverlayProgress(show) {
        const overlay = document.getElementById('sync-overlay');
        if(overlay) overlay.style.display = show ? 'flex' : 'none';
    }

    function savePendingSyncJob(batch) { localStorage.setItem(PENDING_SYNC_KEY, JSON.stringify(batch)); }
    function getPendingSyncJob() { return JSON.parse(localStorage.getItem(PENDING_SYNC_KEY)); }
    function clearPendingSyncJob() { localStorage.removeItem(PENDING_SYNC_KEY); }

    async function initialiserDonneesSiVide() {
        // 1. Vérifie si on vient de se faire "rejeter" du dashboard pour éviter la boucle
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('init') && await db.clients.count() > 0) {
            
            window.location.replace("{{ route('pwa.index') }}");  
        }

        try {
            const clientCount = await db.clients.count();
            if (clientCount === 0) {
                afficherOverlayProgress(true);
                updateProgressUI(10, "Connexion au serveur...");

                const res = await fetch("{{ route('pwa.initial-data') }}");
                const result = await res.json();
                
                const payload = result.data ? result.data : result;

                updateProgressUI(50, "Stockage des données...");
                await populateDatabase(payload, { replaceAll: true });
                updateProgressUI(100, "Initialisation terminée !");
                localStorage.setItem('last_sync', new Date().toISOString());
                // 2. Petit délai pour laisser Dexie fermer ses transactions
                setTimeout(() => {
                    window.location.replace("{{ route('pwa.index') }}");
                },500);
            }
        } catch (e) {
            console.error("Erreur init:", e);
            afficherOverlayProgress(false);
        }
    }
    function verifierRepriseAuto() {
        const job = getPendingSyncJob();
        
        if (job && navigator.onLine) {
            // Cas A : Il y avait une synchronisation en attente (batch non validé)
            afficherOverlayProgress(true);
            updateProgressUI(70, "Reprise du suivi de validation...");
            surveillerLeBatch(job.sync_uuid);
        } else {
            // Cas B : Pas de synchro en cours, on vérifie si on doit télécharger les données
            initialiserDonneesSiVide();
        }
    }

    async function annulerAttenteBatch() {
        const job = getPendingSyncJob();
        if (job) {
            fetch(`{{ route('pwa.sync-batches.cancel', ['syncUuid' => ':uuid']) }}`.replace(':uuid', job.sync_uuid), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
        }
        clearPendingSyncJob();
        location.reload();
    }
</script>

@endsection