<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Compte Créé - {{ $account['nom_compte'] }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 30px 20px;
        }
        .account-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #FF6B35;
        }
        .account-name {
            font-size: 18px;
            font-weight: 600;
            color: #FF6B35;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: #495057;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .qr-title {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
        }
        .qr-image {
            width: 120px;
            height: 120px;
            border: 2px solid #FF6B35;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 0 auto 15px auto;
            display: block;
        }
        .qr-note {
            font-size: 12px;
            color: #6c757d;
            font-style: italic;
        }
        .important-info {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .important-title {
            font-size: 16px;
            font-weight: 600;
            color: #1976d2;
            margin-bottom: 10px;
        }
        .important-text {
            color: #424242;
            margin-bottom: 10px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer h3 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 16px;
        }
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #6c757d;
            font-size: 14px;
        }
        .emoji {
            font-size: 18px;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .contact-info {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="emoji">🎉 Nouveau Compte Créé</h1>
            <p>Orange Money - Gestion de Comptes</p>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $user['nom'] }} {{ $user['prenom'] }}</strong>,</p>

            <p>Un nouveau compte a été créé avec succès sur votre espace client.</p>

            <div class="account-info">
                <div class="account-name">"{{ $account['nom_compte'] }}"</div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Numéro de compte</div>
                        <div class="info-value">{{ $account['numero_compte'] }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Titulaire</div>
                        <div class="info-value">{{ $account['titulaire'] }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Type de compte</div>
                        <div class="info-value">{{ ucfirst($account['type_compte'] ?? 'Courant') }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Devise</div>
                        <div class="info-value">{{ $account['devise'] ?? 'XOF' }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Statut</div>
                        <div class="info-value">{{ ucfirst($account['statut']) }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Solde initial</div>
                        <div class="info-value">{{ number_format($account['solde'], 0, ',', ' ') }} {{ $account['devise'] ?? 'XOF' }}</div>
                    </div>
                </div>

                <div class="info-item" style="margin-top: 15px;">
                    <div class="info-label">Date de création</div>
                    <div class="info-value">{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
                </div>
            </div>

            @if(isset($account['qr_code']) && !empty($account['qr_code']) && str_contains($account['qr_code'], 'http'))
            <div class="qr-section">
                <div class="qr-title">Code QR de votre compte</div>
                <img src="{{ $account['qr_code'] }}" alt="QR Code du compte" class="qr-image" onerror="this.style.display='none'">
                <div class="qr-note">
                    <em>Scannez ce QR code avec votre application mobile</em>
                </div>
            </div>
            @else
            <div class="qr-section" style="background-color: #ffebee; border: 1px solid #f44336;">
                <div class="qr-title" style="color: #d32f2f;">⚠️ Code QR non disponible</div>
                <div class="qr-note" style="color: #d32f2f;">
                    Le code QR de votre compte sera généré sous peu. Vous pouvez contacter le support pour l'obtenir.
                </div>
            </div>
            @endif

            <div class="important-info">
                <div class="important-title">📋 Information importante</div>
                <div class="important-text">
                    Votre nouveau compte <strong>"{{ $account['nom_compte'] }}"</strong> a été créé avec le statut <strong>"{{ ucfirst($account['statut']) }}"</strong>.
                    Vous pouvez l'activer et commencer à l'utiliser immédiatement via votre application mobile ou l'API.
                </div>
                <div class="important-text">
                    <strong>Code QR inclus :</strong> Le code QR de votre compte est affiché ci-dessus. Vous pouvez l'utiliser pour des paiements rapides ou l'importer dans votre application mobile.
                </div>
                <div class="important-text">
                    Si vous avez des questions concernant ce nouveau compte ou si vous souhaitez modifier ses paramètres, n'hésitez pas à contacter notre service client.
                </div>
            </div>
        </div>

        <div class="footer">
            <h3>Service Client Orange Money</h3>
            <div class="contact-info">
                <div class="contact-item">
                    <span class="emoji">📞</span>
                    <span>77 171 90 13</span>
                </div>
                <div class="contact-item">
                    <span class="emoji">📧</span>
                    <span>support@orangemoney.sn</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
