<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'id' => (string) Str::uuid(),
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'telephone' => $this->faker->unique()->numerify('77########'),
            'email' => $this->faker->unique()->safeEmail(),
            'type' => 'client',
            'statut' => 'actif',
            'password' => Hash::make('papa1732'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin()
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'admin',
            'statut' => 'actif',
        ]);
    }

    public function commercant()
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'commercant',
            'statut' => 'en_attente',
        ]);
    }

    public function fournisseur()
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'fournisseur',
            'statut' => 'en_attente',
        ]);
    }
}
