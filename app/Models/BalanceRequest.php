<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Utils\GenerateUid;
use App\Utils\HasTypeAndStatus;

class BalanceRequest extends Model
{
    use HasFactory, GenerateUid, HasTypeAndStatus;

    protected $fillable = [
        'supplier_id',
        'montant',
        'statut',
        'motif_rejet',
        'admin_id',
        'traitee_at',
    ];

    protected $casts = [
        'montant' => 'float',
        'traitee_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
