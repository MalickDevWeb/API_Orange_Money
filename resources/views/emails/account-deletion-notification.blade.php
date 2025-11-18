<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppression de Compte - Orange Money</title>
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
            background: linear-gradient(135deg, #dc3545, #c82333);
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
            border-left: 4px solid #dc3545;
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
        .warning {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .warning strong {
            color: #721c24;
        }
        .reason {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .reason-title {
            font-weight: 600;
            color: #856404;
            margin-bottom: 10px;
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
            <h1>🗑️ Suppression de Compte</h1>
            <p>Orange Money - Gestion de Comptes</p>
        </div>

        <div class="content">
            <h2>{{ $message }}</h2>

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
                    <span class="detail-label">Solde au moment de la suppression:</span>
                    <span class="detail-value">{{ number_format($account['solde'] ?? 0, 0, ',', ' ') }} {{ $account['devise'] ?? 'XOF' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date de suppression:</span>
                    <span class="detail-value">{{ $deletion_date->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            @if(isset($reason) && !empty($reason))
            <div class="reason">
                <div class="reason-title">ℹ️ Raison de la suppression :</div>
                <p>{{ $reason }}</p>
            </div>
            @endif

            <div class="warning">
                <strong>⚠️ Information importante :</strong><br>
                Ce compte a été supprimé de votre espace client. Toutes les données associées
                ont été supprimées définitivement et ne peuvent pas être récupérées.
            </div>

            <p>
                Si cette suppression n'était pas souhaitée ou si vous avez des questions,
                veuillez contacter immédiatement notre service client.
            </p>

            <p>
                <strong>Service Client Orange Money:</strong><br>
                📞 77 123 45 67<br>
                📧 support@orangemoney.sn
            </p>

            <p style="font-size: 12px; color: #6c757d;">
                <em>Cette action est irréversible. Pour créer un nouveau compte,
                utilisez l'option "Créer un compte" dans votre application.</em>
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
