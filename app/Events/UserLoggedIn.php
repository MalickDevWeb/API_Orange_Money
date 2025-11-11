<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public User $user;
    public string $ipAddress;
    public string $userAgent;
    public \DateTime $loginAt;

    /**
     * Créer une nouvelle instance d'événement
     */
    public function __construct(User $user, string $ipAddress = null, string $userAgent = null)
    {
        $this->user = $user;
        $this->ipAddress = $ipAddress ?? request()->ip();
        $this->userAgent = $userAgent ?? request()->userAgent();
        $this->loginAt = now();
    }

    /**
     * Obtenir le canal de broadcast pour l'événement
     */
    public function broadcastOn(): array
    {
        return [];
    }

    /**
     * Obtenir le nom de l'événement de broadcast
     */
    public function broadcastAs(): string
    {
        return 'user.logged.in';
    }

    /**
     * Déterminer si l'événement doit être diffusé
     */
    public function broadcastWhen(): bool
    {
        return false; // Pas de broadcast en temps réel pour cet événement
    }
}
