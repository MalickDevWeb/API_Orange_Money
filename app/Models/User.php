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
 * @method \Illuminate\Database\Eloquent\Relations\HasMany adminActions()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany targetAdminActions()
 * @method bool isAdmin()
 * @method bool isClient()
 * @method bool isCommercant()
 * @method bool isFournisseur()
 * @method bool isActif()
 * @method bool isInactif()
 * @method bool isSuspendu()
 * @method bool isEnAttente()
 * @method bool canTransfer()
 * @method bool canTransferToClient()
 * @method bool canPayMerchant()
 * @method bool isBanned()
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
        'transfer_enabled',
        'can_transfer_to_client',
        'can_pay_merchant',
        'banned',
        'tax_percentage',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'otp_expires_at' => 'datetime',
        'transfer_enabled' => 'boolean',
        'can_transfer_to_client' => 'boolean',
        'can_pay_merchant' => 'boolean',
        'banned' => 'boolean',
        'tax_percentage' => 'decimal:2',
    ];


public function comptes()
{
    return $this->hasMany(Compte::class, 'utilisateur_id'); // <-- préciser la FK
}

public function adminActions()
{
    return $this->hasMany(AdminAction::class, 'admin_id');
}

public function targetAdminActions()
{
    return $this->hasMany(AdminAction::class, 'target_user_id');
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

public function canTransfer(): bool
{
    return $this->transfer_enabled && !$this->banned;
}

public function canTransferToClient(): bool
{
    return $this->can_transfer_to_client && $this->canTransfer();
}

public function canPayMerchant(): bool
{
    return $this->can_pay_merchant && $this->canTransfer();
}

public function isBanned(): bool
{
    return $this->banned;
}

}
