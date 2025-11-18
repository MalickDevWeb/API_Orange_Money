<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changement de Compte Actif - Orange Money</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            margin: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px 20px;
        }
        .account-section {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .old-account {
            border-left: 4px solid #dc3545;
        }
        .new-account {
            border-left: 4px solid #28a745;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .old-title {
            color: #dc3545;
        }
        .new-title {
            color: #28a745;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .detail-value {
            color: #212529;
            font-weight: 500;
        }
        .switch-info {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .switch-info strong {
            color: #155724;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Changement de Compte Actif</h1>
            <p>Orange Money - Gestion de Comptes</p>
        </div>

        <div class="content">
            <h2>{{ $message }}</h2>

            <div class="switch-info">
                <strong>✅ Votre compte actif a été changé avec succès !</strong><br>
                Toutes les transactions futures utiliseront maintenant votre nouveau compte principal.
            </div>

            @if(isset($old_account))
            <div class="account-section old-account">
                <div class="section-title old-title">📦 Ancien compte actif (désactivé)</div>
                <div class="detail-row">
                    <span class="detail-label">Numéro de compte:</span>
                    <span class="detail-value">{{ $old_account['numero_compte'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nom du compte:</span>
                    <span class="detail-value">{{ $old_account['nom_compte'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Statut:</span>
                    <span class="detail-value">Inactif</span>
                </div>
            </div>
            @endif

            <div class="account-section new-account">
                <div class="section-title new-title">🎯 Nouveau compte actif (principal)</div>
                <div class="detail-row">
                    <span class="detail-label">Numéro de compte:</span>
                    <span class="detail-value">{{ $new_account['numero_compte'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nom du compte:</span>
                    <span class="detail-value">{{ $new_account['nom_compte'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Statut:</span>
                    <span class="detail-value">Actif</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date d'activation:</span>
                    <span class="detail-value">{{ $switch_date->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <p>
                <strong>Important :</strong> Toutes vos transactions (dépôts, retraits, transferts) utiliseront
                désormais votre nouveau compte principal "{{ $new_account['nom_compte'] }}".
            </p>

            <p>
                Vous pouvez changer de compte actif à tout moment via votre application mobile ou l'API.
            </p>

            <p>
                <strong>Service Client Orange Money:</strong><br>
                📞 77 123 45 67<br>
                📧 support@orangemoney.sn
            </p>
        </div>

        <div class="footer">
            <p>
                Cette notification a été générée automatiquement par l'API Orange Money.<br>
                © {{ date('Y') }} Orange Money - Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>
