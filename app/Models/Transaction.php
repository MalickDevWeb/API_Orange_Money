<?php

namespace App\Models;

use App\Events\TransactionCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Utils\GenerateUid;
use App\Utils\HasTypeAndStatus;

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

}
