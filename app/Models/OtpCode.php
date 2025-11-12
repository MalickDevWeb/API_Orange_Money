<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Utils\GenerateUid;

class OtpCode extends Model
{
    use HasFactory, GenerateUid;

    protected $fillable = [
        'user_id',
        'code',
        'phone_number',
        'expires_at',
        'used_at',
        'attempts',
        'type', // 'registration', 'login', 'transaction', etc.
        'data',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'attempts' => 'integer',
        'data' => 'array',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si le code OTP est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Vérifier si le code OTP a été utilisé
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    /**
     * Marquer le code comme utilisé
     */
    public function markAsUsed(): void
    {
        $this->update([
            'used_at' => now(),
        ]);
    }

    /**
     * Incrémenter le nombre de tentatives
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Vérifier si le nombre maximum de tentatives est atteint
     */
    public function hasExceededMaxAttempts(int $maxAttempts = 3): bool
    {
        return $this->attempts >= $maxAttempts;
    }

    /**
     * Générer un nouveau code OTP
     */
    public static function generateCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Créer un nouveau code OTP pour un utilisateur
     *
     * @param string $userId
     * @param string $phoneNumber
     * @param string $type
     * @param array $data
     */
    public static function createForUser(string $userId, string $phoneNumber, string $type = 'login', array $data = []): self
    {
        // Invalider les anciens codes du même type pour cet utilisateur
        self::where('user_id', $userId)
            ->where('type', $type)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        return self::create([
            'user_id' => $userId,
            'code' => self::generateCode(),
            'phone_number' => $phoneNumber,
            'expires_at' => now()->addMinutes(30), // Expire dans 30 minutes
            'type' => $type,
            'attempts' => 0,
            'data' => $data,
        ]);
    }

    /**
     * Trouver un code OTP valide
     *
     * @param string $code
     * @param string $phoneNumber
     * @param string $type
     */
    public static function findValidCode(string $code, string $phoneNumber, string $type = 'login'): ?self
    {
        return self::where('code', $code)
            ->where('phone_number', $phoneNumber)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
