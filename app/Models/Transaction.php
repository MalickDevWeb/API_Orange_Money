<?php

namespace App\Models;

use App\Events\TransactionCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Utils\GenerateUid;
use App\Utils\HasTypeAndStatus;
use App\Enums\TransactionType;

// use App\Utils\HasStatus;

class Transaction extends Model
{
    use HasFactory, GenerateUid, HasTypeAndStatus;

    protected static function booted()
    {
        static::created(function ($transaction) {
            TransactionCreated::dispatch($transaction);
        });
    }

    protected $fillable = [
        'type',
        'montant',
        'reference',
        'statut',
        'note',
        'compte_emetteur_id',
        'compte_recepteur_id',
        'date_transaction',
        'frais',
    ];

    protected $casts = [
        'date_transaction' => 'datetime',
        'montant' => 'float',
        'frais' => 'float',
    ];


    public function compteEmetteur()
    {
        return $this->belongsTo(Compte::class, 'compte_emetteur_id');
    }


    public function compteRecepteur()
    {
        return $this->belongsTo(Compte::class, 'compte_recepteur_id');
    }

    /**
     * Retourne le montant avec le signe approprié selon l'utilisateur
     * +montant pour les transactions entrantes (réceptions)
     * -montant pour les transactions sortantes (envois, retraits, paiements)
     */
    public function getMontantSigneAttribute(): string
    {
        $user = auth()->user();

        // Si pas d'utilisateur authentifié, retourner le montant brut
        if (!$user) {
            return number_format($this->montant, 2, '.', '');
        }

        // Déterminer si l'utilisateur est émetteur ou récepteur
        $isEmetteur = $this->compte_emetteur_id && $this->compteEmetteur &&
                      $this->compteEmetteur->utilisateur_id === $user->id;

        $isRecepteur = $this->compte_recepteur_id && $this->compteRecepteur &&
                       $this->compteRecepteur->utilisateur_id === $user->id;

        // Logique simplifiée : - pour les transactions sortantes, + pour les transactions entrantes
        if ($isEmetteur) {
            // L'utilisateur est émetteur = transaction sortante = -
            return '-' . number_format($this->montant, 2, '.', '');
        } elseif ($isRecepteur) {
            // L'utilisateur est récepteur = transaction entrante = +
            return '+' . number_format($this->montant, 2, '.', '');
        }

        // Par défaut, retourner le montant brut pour les admins ou autres cas
        return number_format($this->montant, 2, '.', '');
    }

}
