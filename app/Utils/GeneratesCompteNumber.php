<?php

namespace App\Utils;

use Carbon\Carbon;

trait GeneratesCompteNumber
{
    /**
     * Génère un numéro de compte unique
     * Exemple : PAPA TEUW le 17/08 => TA1708
     */
    public static function generateNumeroCompte(string $nom, string $prenom): string
    {
        $prenomLetter = strtoupper(substr($prenom, -1, 1)); // dernière lettre du prénom
        $nomLetter = strtoupper(substr($nom, 0, 1));        // première lettre du nom

        $datePart = Carbon::now()->format('dmy'); // jour mois année si tu veux juste ddmm => 'dmy' ou 'dm'

        $baseNumero = $prenomLetter . $nomLetter . $datePart;

        // Ajouter un suffixe aléatoire pour éviter doublons
        $uniqueSuffix = rand(10, 99);

        return $baseNumero . $uniqueSuffix;
    }
}
