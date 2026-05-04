<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title>Connexion Agent - NANA Eco Consulting</title>
    
    <link rel="manifest" href="/manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root { --nna-blue: #0d6efd; --nna-bg: #f8f9fa; }
        body { background-color: var(--nna-bg); min-height: 100vh; display: flex; align-items: center; }
        
        .login-card { border: none; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.08); transition: transform 0.2s; }
        
        .logo-icon { 
            width: 75px; height: 75px; background: var(--nna-blue); color: white; 
            border-radius: 22px; display: flex; align-items: center; justify-content: center; 
            margin: 0 auto 20px; font-size: 2.2rem;
        }

        .form-control { 
            border-radius: 14px; padding: 12px; border: 2px solid #edf0f5; 
            background-color: #fcfdfe; transition: all 0.3s;
        }
        .form-control:focus { border-color: var(--nna-blue); box-shadow: none; background-color: #fff; }

        .input-group-text { border-radius: 14px; border: 2px solid #edf0f5; background-color: #fcfdfe; }

        .btn-login { 
            background-color: var(--nna-blue); border: none; border-radius: 14px; 
            padding: 14px; font-weight: 700; transition: all 0.2s;
        }
        .btn-login:active { transform: scale(0.96); }

        #install-banner {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1050;
            background: linear-gradient(90deg, #1a56a6, #0d6efd);
            color: white; padding: 12px; font-size: 0.85rem;
            display: none; align-items: center; justify-content: center;
        }

        .password-toggle { cursor: pointer; border-left: none !important; }

        @media (max-height: 650px) {
            body { align-items: flex-start; padding-top: 30px; }
        }
    </style>
</head>
<body>

    <!-- Bannière d'installation PWA -->
    <div id="install-banner" class="shadow-sm">
        <i class="bi bi-cloud-arrow-down-fill me-2"></i>
        <span>Accéder au mode hors-ligne</span>
        <button id="btn-install-now" class="btn btn-light btn-sm fw-bold ms-3" style="border-radius: 8px;">Installer</button>
    </div>

    <div class="container my-auto">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-6 col-lg-4">
                
                <div class="text-center mb-4">
                    <div class="logo-icon shadow-lg">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h2 class="fw-extrabold text-dark mb-1">Espace Agent</h2>
                    <p class="text-muted small">NANA Eco Consulting - Gestion Tontine</p>
                </div>

                <div class="card login-card p-4">
                    <form id="pwa-login-form">
                        @csrf
                        <div id="error-alert" class="alert alert-danger d-none py-2 px-3 small rounded-3"></div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Matricule Agent</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 text-primary"><i class="bi bi-hash"></i></span>
                                <input type="text" id="username" name="username" 
                                       class="form-control border-start-0" 
                                       placeholder="Ex: NNC-00005" 
                                       inputmode="text" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 text-primary"><i class="bi bi-lock"></i></span>
                                <input type="password" id="password" name="password" 
                                       class="form-control border-start-0 border-end-0" 
                                       placeholder="••••••••" required>
                                <span class="input-group-text bg-white password-toggle" onclick="togglePassword()">
                                    <i id="toggle-icon" class="bi bi-eye text-muted"></i>
                                </span>
                            </div>
                        </div>

                        <button type="submit" id="btn-submit" class="btn btn-primary btn-login w-100 shadow d-flex justify-content-center align-items-center gap-2">
                            <span id="btn-text">Se connecter</span>
                            <div id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                            <i id="btn-icon" class="bi bi-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <p class="text-center mt-4 text-muted small">
                    v2.1.0 &bull; Mode Hors-ligne disponible
                </p>

            </div>
        </div>
    </div>

    <script>
        // Fonction pour afficher/masquer le mot de passe
        function togglePassword() {
            const passInput = document.getElementById('password');
            const icon = document.getElementById('toggle-icon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passInput.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        // Simulation du chargement à la soumission
        document.getElementById('pwa-login-form').addEventListener('submit', function(e) {
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');
            const btnIcon = document.getElementById('btn-icon');
            
            btnText.innerText = "Vérification...";
            btnSpinner.classList.remove('d-none');
            btnIcon.classList.add('d-none');
            // Le reste de ta logique AJAX/Fetch ici
        });
    </script>
</body>
</html>
<script src="{{ asset('js/dexie.js') }}"></script>
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