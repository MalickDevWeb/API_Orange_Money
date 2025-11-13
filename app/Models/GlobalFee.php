<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_fee',
        'merchant_percentage',
    ];

    protected $casts = [
        'transaction_fee' => 'decimal:2',
        'merchant_percentage' => 'decimal:2',
    ];
}
