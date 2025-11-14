<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Utils\GenerateUid;
use App\Utils\HasTypeAndStatus;
use App\Enums\TransactionStatus;

// use App\Utils\HasStatus;
// use App\Utils\HasUserType;

class Compte extends Model
{
    use HasFactory, SoftDeletes, GenerateUid, HasTypeAndStatus;


    protected $fillable = [
        'numero_compte',
        'titulaire',
        'nom_compte',
        'code_marchand',
        'statut',
        'utilisateur_id',
        'client_id',
        'type_compte',
        'devise',
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
        $received = $this->transactionsRecues()->where('statut', TransactionStatus::REUSSIE)->sum('montant');
        $sent = $this->transactionsEmises()->where('statut', TransactionStatus::REUSSIE)->sum('montant');
        return $received - $sent;
    }

    public function getTypeAttribute(): ?string
    {
        return $this->utilisateur?->type;
    }

    public function getRouteKeyName()
    {
        return 'numero_compte';
    }
}
