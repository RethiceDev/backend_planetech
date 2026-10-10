<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Connexion administrateur</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>
        :root {
            --forest: #14532d;
            --forest-dark: #0f3d22;
            --forest-light: #f0fdf4;
            --signal: #f59e0b;
            --text: #1e293b;
            --muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(20, 83, 45, 0.08),
                    transparent 35%
                ),
                #f8fafc;
            color: var(--text);
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }

        .login-container {
            width: 100%;
            max-width: 1050px;
            min-height: 620px;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(15, 61, 34, 0.12);
            display: flex;
        }

        /* Partie gauche */
        .login-presentation {
            width: 46%;
            background:
                linear-gradient(
                    145deg,
                    rgba(15, 61, 34, 0.98),
                    rgba(20, 83, 45, 0.95)
                );
            color: white;
            padding: 55px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .login-presentation::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            top: -100px;
            right: -100px;
        }

        .login-presentation::after {
            content: "";
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(245, 158, 11, 0.08);
            bottom: -100px;
            left: -80px;
        }

        .brand-logo {
            position: relative;
            z-index: 2;
        }

        .brand-logo img {
            max-width: 280px;
            max-height: 165px;
            object-fit: contain;
            object-position: left center;
        }

        .presentation-content {
            position: relative;
            z-index: 2;
        }

        .admin-icon {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }

        .admin-icon i {
            font-size: 32px;
            color: #fff;
        }

        .presentation-content h1 {
            font-size: 38px;
            font-weight: 750;
            line-height: 1.15;
            margin-bottom: 18px;
        }

        .presentation-content p {
            color: rgba(255, 255, 255, 0.75);
            font-size: 15px;
            line-height: 1.8;
            max-width: 400px;
            margin-bottom: 0;
        }

        .presentation-footer {
            position: relative;
            z-index: 2;
            color: rgba(255, 255, 255, 0.55);
            font-size: 13px;
        }

        /* Partie droite */
        .login-form-section {
            width: 54%;
            padding: 55px 65px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-form {
            width: 100%;
            max-width: 420px;
        }

        .login-title {
            margin-bottom: 35px;
        }

        .login-title h2 {
            font-size: 30px;
            font-weight: 750;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .login-title p {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 0;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 5;
        }

        .form-control-custom {
            height: 52px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding-left: 46px;
            padding-right: 45px;
            font-size: 14px;
            transition: all 0.2s ease;
            box-shadow: none !important;
        }

        .form-control-custom:focus {
            border-color: var(--forest);
            box-shadow: 0 0 0 4px rgba(20, 83, 45, 0.08) !important;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #94a3b8;
            z-index: 5;
            padding: 5px;
        }

        .password-toggle:hover {
            color: var(--forest);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
            margin-bottom: 25px;
        }

        .form-check-label {
            font-size: 13px;
            color: var(--muted);
        }

        .form-check-input {
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--forest);
            border-color: var(--forest);
        }

        .forgot-password {
            font-size: 13px;
            font-weight: 600;
            color: var(--forest);
            text-decoration: none;
        }

        .forgot-password:hover {
            color: var(--signal);
        }

        .btn-login {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 12px;
            background: var(--forest);
            color: white;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.25s ease;
        }

        .btn-login:hover {
            background: var(--forest-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(20, 83, 45, 0.18);
            color: white;
        }

        .back-home {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 25px;
            color: var(--muted);
            font-size: 13px;
            text-decoration: none;
        }

        .back-home:hover {
            color: var(--forest);
        }

        .security-message {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            color: #94a3b8;
            font-size: 12px;
        }

        .security-message i {
            color: var(--forest);
        }

        /* Responsive */
        @media (max-width: 991px) {
            .login-container {
                max-width: 600px;
                min-height: auto;
            }

            .login-presentation {
                display: none;
            }

            .login-form-section {
                width: 100%;
                padding: 50px 45px;
            }
        }

        @media (max-width: 576px) {
            .login-wrapper {
                padding: 15px;
            }

            .login-container {
                border-radius: 18px;
            }

            .login-form-section {
                padding: 40px 25px;
            }

            .login-title h2 {
                font-size: 26px;
            }

            .form-options {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>

<body>

<div class="login-wrapper">

    <div class="login-container">

        <!-- =========================
             PARTIE PRESENTATION
        ========================== -->
        <div class="login-presentation">

           <div class="brand-logo">
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="Logo"
                >
            </div>

            <div class="presentation-content">

                <div class="admin-icon">
                    <i class="bi bi-person-gear"></i>
                </div>

                <h1>
                    Espace<br>
                    Administrateur
                </h1>

                <p>
                    Gérez votre plateforme, vos produits, vos commandes
                    et l'ensemble de vos activités depuis votre espace
                    d'administration.
                </p>

            </div>

            <div class="presentation-footer">
                © 2026 — Tous droits réservés
            </div>

        </div>


        <!-- =========================
             FORMULAIRE DE CONNEXION
        ========================== -->
        <div class="login-form-section">

            <div class="login-form">

                <div class="login-title">
                    <h2>Bienvenue </h2>

                    <p>
                        Connectez-vous à votre espace administrateur.
                    </p>
                </div>

                <form action="{{ route('admin.login.submit') }}" method="POST">
    @csrf

    <!-- Email -->
    <div class="mb-4">

        <label
            for="email"
            class="form-label"
        >
            Adresse email
        </label>

        <div class="input-group-custom">

            <i class="bi bi-envelope input-icon"></i>

            <input
                type="email"
                class="form-control form-control-custom @error('email') is-invalid @enderror"
                id="email"
                name="email"
                placeholder="exemple@email.com"
                value="{{ old('email') }}"
                autocomplete="email"
                required
            >

        </div>

        @error('email')
            <div class="text-danger small mt-1">
                {{ $message }}
            </div>
        @enderror

    </div>


    <!-- Mot de passe -->
    <div class="mb-3">

        <label
            for="password"
            class="form-label"
        >
            Mot de passe
        </label>

        <div class="input-group-custom">

            <i class="bi bi-lock input-icon"></i>

            <input
                type="password"
                class="form-control form-control-custom @error('password') is-invalid @enderror"
                id="password"
                name="password"
                placeholder="Entrez votre mot de passe"
                autocomplete="current-password"
                required
            >

            <button
                type="button"
                class="password-toggle"
                onclick="togglePassword()"
                aria-label="Afficher le mot de passe"
            >
                <i
                    id="passwordIcon"
                    class="bi bi-eye"
                ></i>
            </button>

        </div>

        @error('password')
            <div class="text-danger small mt-1">
                {{ $message }}
            </div>
        @enderror

    </div>


    <!-- Options -->
    <div class="form-options">

        <div class="form-check">

            <input
                class="form-check-input"
                type="checkbox"
                id="remember"
                name="remember"
                value="1"
            >

            <label
                class="form-check-label"
                for="remember"
            >
                Se souvenir de moi
            </label>

        </div>

        <a
            href="#"
            class="forgot-password"
        >
            Mot de passe oublié ?
        </a>

    </div>


    <!-- Erreur de connexion -->
    @if(session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            {{ session('error') }}
        </div>
    @endif


    <!-- Succès -->
    @if(session('success'))
        <div class="alert alert-success mt-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif


    <!-- Bouton -->
    <button
        type="submit"
        class="btn btn-login"
    >
        <i class="bi bi-box-arrow-in-right me-2"></i>
        Se connecter
    </button>

</form>


                <!-- Retour site -->
                <a
                    href="http://localhost:5173/"
                    class="back-home"
                >
                    <i class="bi bi-arrow-left"></i>
                    Retour au site
                </a>


                <!-- Sécurité -->
                <div class="security-message">
                    <i class="bi bi-shield-check"></i>
                    Connexion sécurisée
                </div>

            </div>

        </div>

    </div>

</div>


<!-- Bootstrap JS -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>
    function togglePassword() {

        const password = document.getElementById('password');
        const icon = document.getElementById('passwordIcon');

        if (password.type === 'password') {

            password.type = 'text';

            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');

        } else {

            password.type = 'password';

            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');

        }
    }
</script>

</body>
</html>
