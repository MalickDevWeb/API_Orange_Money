<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modification de Compte - Orange Money</title>
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
        .account-details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #FF6B35;
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
        .changes-section {
            background-color: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .changes-title {
            font-size: 18px;
            font-weight: 600;
            color: #0056b3;
            margin-bottom: 15px;
        }
        .change-item {
            background-color: #ffffff;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 8px;
            border-left: 3px solid #007bff;
        }
        .change-field {
            font-weight: 600;
            color: #495057;
        }
        .change-old, .change-new {
            font-size: 14px;
            margin-top: 5px;
        }
        .change-old {
            color: #dc3545;
            text-decoration: line-through;
        }
        .change-new {
            color: #28a745;
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
            <h1>🔄 Modification de Compte</h1>
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
                    <span class="detail-label">Date de modification:</span>
                    <span class="detail-value">{{ $modification_date->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            @if(!empty($changes))
            <div class="changes-section">
                <div class="changes-title">📝 Modifications apportées :</div>
                @foreach($changes as $field => $change)
                <div class="change-item">
                    <div class="change-field">{{ ucfirst(str_replace('_', ' ', $field)) }}</div>
                    @if(isset($change['old']) && isset($change['new']))
                        <div class="change-old">Ancien : {{ $change['old'] }}</div>
                        <div class="change-new">Nouveau : {{ $change['new'] }}</div>
                    @elseif(isset($change['new']))
                        <div class="change-new">Nouveau : {{ $change['new'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            <p>
                Ces modifications ont été apportées avec succès à votre compte.
                Si vous n'êtes pas à l'origine de ces changements, veuillez contacter immédiatement notre service client.
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
