<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Orange Money</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #ff6600;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
        }
        .otp-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ff6600;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            color: #ff6600;
            text-align: center;
            letter-spacing: 5px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
        }
        .button {
            display: inline-block;
            background-color: #ff6600;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #e55a00;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .security-note {
            background-color: #e8f5e8;
            border: 1px solid #c3e6c3;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            font-size: 14px;
        }
        .auto-submit-form {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🧡 Orange Money</div>
            <h1 class="title">Connexion sécurisée</h1>
        </div>

        <p>Bonjour <strong>{{ $user->nom }} {{ $user->prenom }}</strong>,</p>

        <p>Pour finaliser votre connexion à Orange Money, veuillez utiliser l'une des méthodes suivantes :</p>

        <!-- Section OTP Code -->
        <div class="otp-section">
            <h3 style="margin-top: 0; color: #333;">📱 Code de vérification</h3>
            <p>Votre code de vérification à 6 chiffres :</p>
            <div class="otp-code">{{ $otp_code }}</div>
            <p style="text-align: center; margin: 10px 0;">
                <small>Ce code expire dans <strong>10 minutes</strong></small>
            </p>
        </div>

        <!-- Section Lien Auto-Submit -->
        <div style="text-align: center; margin: 30px 0;">
            <p><strong>Ou cliquez sur ce bouton pour vous connecter automatiquement :</strong></p>
            <a href="{{ url('/api/auth/verify/' . $user->id . '/' . $token) }}" class="button">
                🔐 Se connecter automatiquement
            </a>
        </div>

        <!-- Formulaire caché pour auto-submit (au cas où) -->
        <form action="{{ url('/api/auth/verify/' . $user->id . '/' . $token) }}" method="GET" class="auto-submit-form" id="autoSubmitForm">
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="token" value="{{ $token }}">
        </form>

        <!-- Note de sécurité -->
        <div class="security-note">
            <strong>🔒 Sécurité :</strong> Ce lien est personnel et à usage unique.
            Il expire automatiquement après utilisation ou dans 10 minutes.
        </div>

        <!-- Avertissement -->
        <div class="warning">
            <strong>⚠️ Important :</strong> Ne partagez jamais ce code ou ce lien avec qui que ce soit.
            Orange Money ne vous demandera jamais vos codes de sécurité.
        </div>

        <p>Si vous n'avez pas demandé cette connexion, veuillez ignorer cet email.</p>

        <p>Cordialement,<br>
        <strong>L'équipe Orange Money</strong></p>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>© {{ date('Y') }} Orange Money - Tous droits réservés</p>
        </div>
    </div>

    <script>
        // Auto-submit si l'utilisateur clique sur le lien
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.querySelector('.button');
            if (button) {
                button.addEventListener('click', function(e) {
                    // Optionnel : montrer un message de chargement
                    this.innerHTML = '🔄 Connexion en cours...';
                    this.style.backgroundColor = '#cccccc';
                });
            }
        });
    </script>
</body>
</html>
