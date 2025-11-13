<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification de Transaction - Orange Money</title>
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
        .transaction-details {
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
        .amount {
            font-size: 24px;
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
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
        }
        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status.failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .warning strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Notification de Transaction</h1>
            <p>Orange Money - Service Financier</p>
        </div>

        <div class="content">
            <h2>{{ $message }}</h2>

            @if($transaction)
            <div class="amount">
                {{ number_format($transaction->montant, 0, ',', ' ') }} FCFA
            </div>

            <div class="transaction-details">
                <div class="detail-row">
                    <span class="detail-label">Référence:</span>
                    <span class="detail-value">{{ $transaction->reference }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type:</span>
                    <span class="detail-value">{{ ucfirst($transaction->type) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ $transaction->date_transaction->format('d/m/Y H:i') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Statut:</span>
                    <span class="detail-value">
                        <span class="status {{ $transaction->statut == 'reussie' ? 'success' : ($transaction->statut == 'echouee' ? 'failed' : 'pending') }}">
                            {{ ucfirst($transaction->statut) }}
                        </span>
                    </span>
                </div>
                @if($transaction->note)
                <div class="detail-row">
                    <span class="detail-label">Note:</span>
                    <span class="detail-value">{{ $transaction->note }}</span>
                </div>
                @endif
            </div>
            @endif

            @if($role === 'sender')
                <div class="warning">
                    <strong>Important:</strong> Conservez cette référence pour toute réclamation ou suivi de transaction.
                </div>
            @endif

            <p>
                Si vous n'êtes pas à l'origine de cette transaction ou si vous avez des questions,
                contactez immédiatement notre service client.
            </p>

            <p>
                <strong>Service Client Orange Money:</strong><br>
                📞 77 123 45 67<br>
                📧 support@orangemoney.sn
            </p>
        </div>

        <div class="footer">
            <p>
                Cette transaction a été traitée via l'API Orange Money.<br>
                © {{ date('Y') }} Orange Money - Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>
