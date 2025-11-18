<?php

namespace App\Interfaces\Services;

interface TransactionEmailServiceInterface
{
    /**
     * Send transaction success emails
     */
    public function sendSuccessEmails(int $senderId, int $receiverId, $transaction, string $type): void;

    /**
     * Send transaction error emails
     */
    public function sendErrorEmails(int $senderId, int $receiverId, float $amount, string $error): void;

    /**
     * Send withdrawal confirmation emails
     */
    public function sendWithdrawalConfirmationEmails($transaction, int $supplierId): void;
}
