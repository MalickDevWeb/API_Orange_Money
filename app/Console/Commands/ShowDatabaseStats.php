<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Console\Command;

class ShowDatabaseStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'show:database-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display database statistics including user, account, and transaction counts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Database Statistics:');
        $this->line('Users: ' . User::count());
        $this->line('Comptes: ' . Compte::count());
        $this->line('Transactions: ' . Transaction::count());
        $this->line('OTP Codes: ' . \App\Models\OtpCode::count());
        $this->line('Balance Requests: ' . \App\Models\BalanceRequest::count());
    }
}
