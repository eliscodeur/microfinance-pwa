<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - NANA ECO CONSULTING</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --nana-green: #4CAF50;
            --nana-blue: #004b61;
            --nana-red: #b03021;
            --nana-light-bg: #f0f4f2;
        }

        /* 1. Blocage du scroll et centrage parfait */
        body { 
            background: var(--nana-light-bg); 
            height: 100vh; 
            width: 100vw;
            display: flex; 
            align-items: center; 
            justify-content: center;
            overflow: hidden; /* Empêche le scroll */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }
        
        .login-card {
            border: none;
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            overflow: hidden;
            width: 100%;
            max-width: 1000px; /* Largeur optimale pour PC/Tablette */
        }

        .login-side-banner {
            background: linear-gradient(135deg, var(--nana-blue) 0%, #002d3a 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 50px;
        }

        .logo-container {
            width: 130px;
            height: 130px;
            background: white;
            padding: 5px;
            border-radius: 50%;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .btn-nana { 
            border-radius: 12px; 
            padding: 14px; 
            font-weight: 600; 
            background: var(--nana-green); 
            border: none; 
            color: white;
            transition: all 0.3s;
        }
        
        .btn-nana:hover { 
            background: #3d8b40; 
            transform: translateY(-2px);
        }
        
        .form-control { 
            border-radius: 12px; 
            padding: 12px 15px; 
            border: 2px solid #eef1f0; 
        }

        .slogan {
            font-size: 0.85rem;
            border-left: 3px solid var(--nana-green);
            padding-left: 15px;
            margin-top: 20px;
        }

        /* Ajustement pour petites tablettes */
        @media (max-width: 768px) {
            body { 
                overflow-y: auto; /* On autorise le scroll uniquement si l'écran est trop petit pour la carte */
                padding: 20px;
            }
            .login-card { max-width: 500px; }
        }
    </style>
</head>
<body>

<div class="container-fluid d-flex justify-content-center px-3">
    <div class="card login-card">
        <div class="row g-0">
            
            <!-- BANNER SIDE -->
            <div class="col-md-5 login-side-banner d-none d-md-flex">
                <div class="logo-container">
                    <img src="{{ asset('/images/logo.png') }}" alt="Logo NANA ECO">
                </div>
                <h2 class="fw-bold text-center mb-0">NANA ECO</h2>
                <h4 class="text-center opacity-75 fw-light">CONSULTING</h4>
                
                <div class="slogan">
                    Conseil et Orientation Économique.<br>
                    Gestion des Transactions Monétaires.
                </div>

                <div class="mt-auto pt-4 border-top border-white border-opacity-10">
                    <small class="opacity-50">Siège Social Djifa-Kpota</small><br>
                    <small class="opacity-50">+228 93 47 27 68</small>
                </div>
            </div>
            
            <!-- FORM SIDE -->
            <div class="col-md-7 bg-white p-4 p-md-5">
                <div class="mb-5">
                    <h2 class="fw-bold" style="color: var(--nana-blue);">Connexion Admin</h2>
                    <p class="text-muted">Accédez à votre espace de gestion sécurisé.</p>
                </div>

                <form action="{{ route('admin.login') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">Email Professionnel</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-envelope text-nana-blue"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="admin@nanaecoconsulting.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label class="form-label fw-bold text-secondary">Mot de passe</label>
                            <a href="#" class="text-decoration-none small" style="color: var(--nana-red);">Oublié ?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-shield-lock text-nana-blue"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label small text-muted" for="remember">Maintenir la session active</label>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-nana shadow-sm">
                            <i class="bi bi-door-open-fill me-2"></i> Accéder au Dashboard
                        </button>
                    </div>
                </form>

                <div class="mt-5 text-center text-md-start small text-muted opacity-75">
                    &copy; 2026 <strong>NANA ECO CONSULTING</strong>.
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>