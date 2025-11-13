<?php

namespace App\Models;

use App\Models\Compte;
use App\Utils\GenerateUid;
use App\Utils\HasTypeAndStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Compte> $comptes
 *
 * @method \Illuminate\Database\Eloquent\Relations\HasMany comptes()
 * @method bool isAdmin()
 * @method bool isClient()
 * @method bool isCommercant()
 * @method bool isFournisseur()
 * @method bool isActif()
 * @method bool isInactif()
 * @method bool isSuspendu()
 * @method bool isEnAttente()
 * @method bool update(array $attributes = [], array $options = [])
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, GenerateUid, HasTypeAndStatus;

    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
        'type',
        'statut',
        'password',
        'pin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'otp_expires_at' => 'datetime',
    ];


public function comptes()
{
    return $this->hasMany(Compte::class, 'utilisateur_id'); // <-- préciser la FK
}

// Méthodes explicites pour éviter les erreurs intelephense
public function isAdmin(): bool
{
    return $this->hasType('admin');
}

public function isClient(): bool
{
    return $this->hasType('client');
}

public function isCommercant(): bool
{
    return $this->hasType('commercant');
}

public function isFournisseur(): bool
{
    return $this->hasType('fournisseur');
}

public function isActif(): bool
{
    return $this->hasStatus('actif');
}

public function isInactif(): bool
{
    return $this->hasStatus('inactif');
}

public function isSuspendu(): bool
{
    return $this->hasStatus('suspendu');
}

public function isEnAttente(): bool
{
    return $this->hasStatus('en_attente');
}

}
