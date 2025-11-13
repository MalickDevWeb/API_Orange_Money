<?php

namespace App\Services;

use App\Interfaces\Services\StatisticsServiceInterface;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;

class StatisticsService implements StatisticsServiceInterface
{
    public function getDailyStatistics(): array
    {
        $today = now()->toDateString();

        return [
            'transfers' => Transaction::where('type', TransactionType::TRANSFERT->value)
                ->whereDate('date_transaction', $today)
                ->where('statut', TransactionStatus::REUSSIE->value)
                ->count(),
            'deposits' => Transaction::where('type', TransactionType::DEPOT->value)
                ->whereDate('date_transaction', $today)
                ->where('statut', TransactionStatus::REUSSIE->value)
                ->count(),
            'withdrawals' => Transaction::where('type', TransactionType::RETRAIT->value)
                ->whereDate('date_transaction', $today)
                ->where('statut', TransactionStatus::REUSSIE->value)
                ->count(),
            'merchant_payments' => Transaction::where('type', TransactionType::PAIEMENT->value)
                ->whereDate('date_transaction', $today)
                ->where('statut', TransactionStatus::REUSSIE->value)
                ->count(),
        ];
    }
}
