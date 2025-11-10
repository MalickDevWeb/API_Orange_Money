<?php

namespace Database\Factories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompteFactory extends Factory
{
    protected $model = Compte::class;

    public function definition()
    {
        return [
            'id' => (string) Str::uuid(),
            'numero_compte' => $this->faker->unique()->numerify('CMPT########'),
            'titulaire' => $this->faker->name(),
            'code_marchand' => null,
            'statut' => 'actif',
            // 'utilisateur_id' -> à remplir dans le seeder en l'attachant au user
        ];
    }

    /** state pour compte marchand (ajoute code_marchand) */
    public function marchand()
    {
        return $this->state(fn(array $attributes) => [
            'code_marchand' => 'MRC-' . strtoupper(Str::random(8)),
        ]);
    }
}
