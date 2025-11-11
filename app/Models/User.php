<?php

namespace App\Models;

use App\Utils\GenerateUid;
use App\Utils\HasTypeAndStatus;
// use App\Utils\HasUserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, GenerateUid ,HasTypeAndStatus;

    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
        'type',
        'password',
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


}
