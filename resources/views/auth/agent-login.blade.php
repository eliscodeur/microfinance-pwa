<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Connexion Agent - Tontine</title>
    <link rel="manifest" href="/manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; }
        .login-card { border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .btn-primary { background-color: #0d6efd; border: none; border-radius: 12px; padding: 12px; font-weight: bold; }
        .form-control { border-radius: 12px; padding: 12px; border: 2px solid #eee; }
        .form-control:focus { border-color: #0d6efd; box-shadow: none; }
        .logo-icon { width: 70px; height: 70px; background: #0d6efd; color: white; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem; }
        /* Pour le chargement */
        .spinner-login { display: none; width: 1.2rem; height: 1.2rem; }
    </style>
</head>
<body>

    <div class="container mb-3">
        <div id="install-banner" style="display:none; background: #1a56a6; color: white; padding: 15px; text-align: center;">
        <span>Installer l'application pour travailler hors-ligne</span>
        <button id="btn-install-now" class="btn btn-light btn-sm" style="margin-left: 10px;">Installer</button>
    </div>
    <div class="row justify-content-center">
        <div class="col-12 col-md-5">
            <div class="text-center mb-4">
                <div class="logo-icon shadow">
                    <i class="bi bi-person-badge"></i>
                </div>
                <h3 class="fw-bold text-dark">Espace Agent</h3>
                <p class="text-muted">Connectez-vous avec votre matricule</p>
            </div>

            <div class="card login-card shadow-sm p-4">
                <form id="pwa-login-form">
                    @csrf
                    <div id="error-alert" class="alert alert-danger d-none small"></div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Matricule NNC</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-2 border-end-0"><i class="bi bi-hash text-primary"></i></span>
                            <input type="text" id="username" name="username" class="form-control border-2 border-start-0" 
                                   placeholder="Ex: NNC-00005" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-2 border-end-0"><i class="bi bi-lock text-primary"></i></span>
                            <input type="password" id="password" name="password" class="form-control border-2 border-start-0" 
                                   placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" id="btn-submit" class="btn btn-primary w-100 shadow-sm d-flex justify-content-center align-items-center gap-2">
                        <span id="btn-text">Se connecter</span>
                        <div id="btn-spinner" class="spinner-border spinner-login" role="status"></div>
                        <i id="btn-icon" class="bi bi-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://unpkg.com/dexie/dist/dexie.js"></script>
<script type="module">
    import { db } from '/js/db-manager.js';  
   
    document.getElementById('pwa-login-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const elements = {
            matricule: document.getElementById('username').value,
            password: document.getElementById('password').value,
            alertBox: document.getElementById('error-alert'),
            btn: document.getElementById('btn-submit'),
            spinner: document.getElementById('btn-spinner'),
            icon: document.getElementById('btn-icon')
        };

        const setUIState = (loading) => {
            elements.btn.disabled = loading;
            elements.spinner.style.display = loading ? 'inline-block' : 'none';
            elements.icon.style.display = loading ? 'none' : 'inline-block';
            if (loading) elements.alertBox.classList.add('d-none');
        };

        setUIState(true);
        try {
            const dbExists = await Dexie.exists('TontineAppDB');
            
            if (dbExists) {
                // --- LOGIQUE HORS-LIGNE ---
                const savedMatricule = localStorage.getItem('agent_matricule');
                const savedHash = localStorage.getItem('agent_pwd_hash');
                const currentHash = btoa(unescape(encodeURIComponent(elements.password)));

                if (savedMatricule === elements.matricule && savedHash === currentHash) {
                    localStorage.setItem('session_active', 'true');
                    window.location.href = "/pwa/dashboard";
                    return;
                } else {
                    throw new Error("Identifiants incorrects (Mode local)");
                }

            } else {
                // --- LOGIQUE PREMIÈRE CONNEXION (Online) ---
                if (!navigator.onLine) {
                    throw new Error("⚠️ Mode hors-ligne : Première connexion impossible. Veuillez retrouver du réseau.");
                }

                const response = await fetch("{{ route('agent.login.submit') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ username: elements.matricule, password: elements.password })
                });

                // --- CORRECTION ICI : Extraire les data AVANT de vérifier response.ok ---
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || "Erreur lors de l'authentification");
                }

                if(data.agent && data.agent.sync === 0) {
                    throw new Error("Votre compte n'est pas encore autorisé à être synchronisé.");
                }

                // SUCCESS : Stockage pour le futur offline
                localStorage.setItem('session_active', 'true');
                localStorage.setItem('agent_id', data.agent.id);
                localStorage.setItem('agent_nom', data.agent.nom);
                localStorage.setItem('agent_matricule', data.agent.matricule || elements.matricule);
                localStorage.setItem('agent_photo', data.agent.photo || '/img/default-avatar.png');
                localStorage.setItem('agent_pwd_hash', btoa(unescape(encodeURIComponent(elements.password))));
                localStorage.setItem('last_login', new Date().toISOString());
                
                if (data.token) localStorage.setItem('auth_token', data.token);

                // Direction la synchronisation initiale de la base Dexie
                window.location.href = "/pwa/sync";
            }

        } catch (error) {
            console.error("Erreur Login:", error);
            elements.alertBox.textContent = (error.message === 'Failed to fetch') 
                ? "Impossible de joindre le serveur. Vérifiez votre connexion." 
                : error.message;
            elements.alertBox.classList.remove('d-none');
            document.getElementById('password').value = ""; 
            setUIState(false);
        }
    });

    // Gestion PWA
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(() => console.log('SW enregistré'))
                .catch(err => console.log('Erreur SW', err));
        });
    }

    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        const banner = document.getElementById('install-banner');
        if (banner) banner.style.display = 'block';
    });

    const installBtn = document.getElementById('btn-install-now');
    if (installBtn) {
        installBtn.addEventListener('click', () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((result) => {
                    if (result.outcome === 'accepted') {
                        document.getElementById('install-banner').style.display = 'none';
                    }
                    deferredPrompt = null;
                });
            }
        });
    }
</script>
</body>
</html>