<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Utils\GenerateUid;
use App\Utils\HasTypeAndStatus;

// use App\Utils\HasStatus;

class Transaction extends Model
{
    use HasFactory, GenerateUid, HasTypeAndStatus;

    protected $fillable = [
        'type',
        'montant',
        'reference',
        'statut',
        'note',
        'compte_emetteur_id',
        'compte_recepteur_id',
        'date_transaction',
    ];

    protected $casts = [
        'date_transaction' => 'datetime',
        'montant' => 'float',
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
