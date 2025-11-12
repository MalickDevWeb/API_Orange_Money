<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Utils\GenerateUid;
use App\Utils\HasTypeAndStatus;

// use App\Utils\HasStatus;
// use App\Utils\HasUserType;

class Compte extends Model
{
    use HasFactory, GenerateUid, HasTypeAndStatus;


    protected $fillable = [
        'numero_compte',
        'titulaire',
        'code_marchand',
        'statut',
        'utilisateur_id',
        'qr_code',
    ];


    public function utilisateur()
    {
        return $this->belongsTo(User::class);
    }


    public function transactionsEmises()
    {
        return $this->hasMany(Transaction::class, 'compte_emetteur_id');
    }

    public function transactionsRecues()
    {
        return $this->hasMany(Transaction::class, 'compte_recepteur_id');
    }

    public function getSoldeAttribute(): float
    {
        $emises = $this->transactionsEmises()->sum('montant');
        $recues = $this->transactionsRecues()->sum('montant');

        return $recues - $emises;
    }


    public function getTypeAttribute(): ?string
    {
        return $this->utilisateur?->type;
    }
}
