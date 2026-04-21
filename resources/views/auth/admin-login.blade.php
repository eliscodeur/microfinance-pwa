<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Connexion | MicroFinance Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7f6;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-side-banner {
            background: linear-gradient(45deg, #0d6efd, #0a58ca);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px;
        }
        .btn-primary {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card login-card">
                <div class="row g-0">
                    <div class="col-md-5 login-side-banner d-none d-md-flex">
                        <h2 class="fw-bold">NANA CONSULTING</h2>
                        <p class="mt-3">Plateforme de gestion des collectes et suivi des agents de terrain.</p>
                        <hr class="w-25">
                        <small class="opacity-75">Système sécurisé v1.0</small>
                    </div>
                    
                    <div class="col-md-7 bg-white p-5">
                        <div class="mb-4 text-center text-md-start">
                            <h3 class="fw-bold">Bienvenue</h3>
                            <p class="text-muted">Connectez-vous pour accéder à l'administration</p>
                        </div>

                        <form action="{{ route('admin.login') }}" method="POST">
                            @csrf
                            
                            <div class="mb-3">
                                <label class="form-label">Adresse Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="nom@finance.com" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label">Mot de passe</label>
                                    <a href="#" class="text-decoration-none small">Oublié ?</a>
                                </div>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>

                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Rester connecté</label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary shadow-sm">
                                    Se connecter
                                </button>
                            </div>
                        </form>

                        <div class="mt-5 text-center text-muted small">
                            &copy; 2026 Nano Tech - Tous droits réservés.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>