<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Compte Créé - Orange Money</title>
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
        .account-name {
            font-size: 20px;
            font-weight: 700;
            color: #FF6B35;
            text-align: center;
            margin: 20px 0;
        }
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            background-color: #fff3cd;
            color: #856404;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Nouveau Compte Créé</h1>
            <p>Orange Money - Gestion de Comptes</p>
        </div>

        <div class="content">
            <h2>{{ $message }}</h2>

            <div class="account-name">
                "{{ $account['nom_compte'] }}"
            </div>

            <div class="account-details">
                <div class="detail-row">
                    <span class="detail-label">Numéro de compte:</span>
                    <span class="detail-value">{{ $account['numero_compte'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Titulaire:</span>
                    <span class="detail-value">{{ $account['titulaire'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type de compte:</span>
                    <span class="detail-value">{{ ucfirst($account['type_compte'] ?? 'courant') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Devise:</span>
                    <span class="detail-value">{{ $account['devise'] ?? 'XOF' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Statut:</span>
                    <span class="detail-value">
                        <span class="status">
                            {{ ucfirst($account['statut']) }}
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Solde initial:</span>
                    <span class="detail-value">{{ number_format($account['solde'] ?? 0, 0, ',', ' ') }} {{ $account['devise'] ?? 'XOF' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date de création:</span>
                    <span class="detail-value">{{ $creation_date->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            @if(isset($account['qr_code']) && !empty($account['qr_code']))
            <div class="account-details">
                <div class="detail-row">
                    <span class="detail-label">Code QR du compte:</span>
                    <span class="detail-value">
                        @if(filter_var($account['qr_code'], FILTER_VALIDATE_URL))
                            <div style="text-align: center; margin: 20px 0;">
                                <div style="font-size: 14px; font-weight: 600; color: #495057; margin-bottom: 10px;">Code QR de votre compte</div>
                                <img src="{{ $account['qr_code'] }}" alt="QR Code du compte" style="width: 120px; height: 120px; border: 2px solid #FF6B35; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: block; margin: 0 auto;">
                                <div style="margin-top: 10px; font-size: 12px; color: #6c757d;">
                                    <em>Scannez ce QR code avec votre application mobile</em>
                                </div>
                            </div>
                        @elseif(strpos($account['qr_code'], '⬜') !== false || strpos($account['qr_code'], '⬛') !== false)
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6; text-align: center; margin: 20px 0;">
                                <div style="font-size: 14px; font-weight: 600; color: #495057; margin-bottom: 10px;">Code QR de votre compte</div>
                                <div style="display: inline-block; background-color: white; padding: 8px; border-radius: 4px; border: 1px solid #e9ecef; font-family: 'Courier New', monospace; font-size: 8px; line-height: 1.1; letter-spacing: -0.5px;">
                                    <pre style="margin: 0; white-space: pre; color: #212529;">{!! nl2br(e($account['qr_code'])) !!}</pre>
                                </div>
                                <div style="margin-top: 10px; font-size: 11px; color: #6c757d;">
                                    <em>Scannez ce QR code avec votre application mobile</em>
                                </div>
                            </div>
                        @elseif(strpos($account['qr_code'], 'http') === 0)
                            <div style="text-align: center;">
                                <img src="{{ $account['qr_code'] }}" alt="QR Code du compte" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                                <div style="margin-top: 10px; font-size: 12px; color: #6c757d;">
                                    <em>Scannez ce QR code avec votre application mobile</em>
                                </div>
                            </div>
                        @elseif(strpos($account['qr_code'], 'data:image') === 0)
                            <img src="{{ $account['qr_code'] }}" alt="QR Code du compte" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                        @else
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #dee2e6; text-align: center;">
                                <div style="font-size: 14px; font-weight: 600; color: #495057; margin-bottom: 10px;">Code QR généré</div>
                                <div style="font-family: monospace; font-size: 11px; color: #6c757d; word-break: break-all; line-height: 1.4;">
                                    {{ $account['qr_code'] }}
                                </div>
                                <div style="margin-top: 10px; font-size: 12px; color: #6c757d;">
                                    <em>Utilisez ce code pour scanner avec votre application mobile</em>
                                </div>
                            </div>
                        @endif
                    </span>
                </div>
            </div>
            @endif

            <div class="info">
                <strong>Information importante:</strong><br>
                Votre nouveau compte "{{ $account['nom_compte'] }}" a été créé avec le statut "inactif".
                Vous pouvez l'activer et commencer à l'utiliser immédiatement via votre application mobile ou l'API.
                @if(isset($account['qr_code']) && !empty($account['qr_code']))
                <br><br>
                <strong>Code QR inclus:</strong> Le code QR de votre compte est affiché ci-dessus. Vous pouvez l'utiliser pour des paiements rapides ou l'importer dans votre application mobile.
                @endif
            </div>

            <p>
                Si vous avez des questions concernant ce nouveau compte ou si vous souhaitez
                modifier ses paramètres, n'hésitez pas à contacter notre service client.
            </p>

            <p>
                <strong>Service Client Orange Money:</strong><br>
                📞 77 171 90 13<br>
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
