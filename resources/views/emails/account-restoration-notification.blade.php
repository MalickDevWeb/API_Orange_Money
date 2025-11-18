<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restauration de Compte - Orange Money</title>
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
            background: linear-gradient(135deg, #28a745, #20c997);
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
        .account-details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
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
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .success strong {
            color: #155724;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .info strong {
            color: #0c5460;
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
            <h1>🔄 Restauration de Compte</h1>
            <p>Orange Money - Gestion de Comptes</p>
        </div>

        <div class="content">
            <h2>{{ $message }}</h2>

            <div class="success">
                <strong>✅ Votre compte a été restauré avec succès !</strong><br>
                Toutes les fonctionnalités sont maintenant disponibles.
            </div>

            <div class="account-details">
                <div class="detail-row">
                    <span class="detail-label">Numéro de compte:</span>
                    <span class="detail-value">{{ $account['numero_compte'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nom du compte:</span>
                    <span class="detail-value">{{ $account['nom_compte'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Titulaire:</span>
                    <span class="detail-value">{{ $account['titulaire'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Statut:</span>
                    <span class="detail-value">{{ ucfirst($account['statut']) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Solde actuel:</span>
                    <span class="detail-value">{{ number_format($account['solde'] ?? 0, 0, ',', ' ') }} {{ $account['devise'] ?? 'XOF' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date de restauration:</span>
                    <span class="detail-value">{{ $restoration_date->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div class="info">
                <strong>Information importante:</strong><br>
                Votre compte "{{ $account['nom_compte'] }}" est maintenant pleinement fonctionnel.
                Vous pouvez l'utiliser pour toutes vos opérations bancaires.
            </div>

            <p>
                Si vous avez des questions concernant cette restauration ou si vous souhaitez
                modifier les paramètres de votre compte, n'hésitez pas à contacter notre service client.
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
