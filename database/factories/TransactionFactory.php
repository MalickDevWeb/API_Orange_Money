<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => $this->faker->randomElement(['depot','retrait','transfert','paiement']),
            'montant' => $this->faker->randomFloat(2, 100, 100000),
            'reference' => strtoupper(Str::random(12)),
            'statut' => $this->faker->randomElement(['en_cours','reussie','echouee']),
            'note' => $this->faker->optional()->sentence(),
            // compte_emetteur_id et compte_recepteur_id devront être fournis lors du seeding
            'date_transaction' => now(),
        ];
    }
}
