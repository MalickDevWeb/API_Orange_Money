<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Interfaces\Notifications\BrevoServiceInterface;
use Illuminate\Support\Facades\Log;

class SendLoginEmailNotification
{
    protected BrevoServiceInterface $brevoService;

    public function __construct(BrevoServiceInterface $brevoService)
    {
        $this->brevoService = $brevoService;
    }

    public function handle(UserLoggedIn $event): void
    {
        try {
            $user = $event->user;

            $subject = "Connexion réussie à votre compte";
            $content = "
                <p>Bonjour <strong>{$user->name}</strong>,</p>
                <p>Une connexion à votre compte a été effectuée le <strong>" . now()->format('d/m/Y à H:i') . "</strong>.</p>
                <p>Si ce n’était pas vous, veuillez sécuriser votre compte immédiatement.</p>
                <p>— L’équipe Sécurité</p>
            ";

            $this->brevoService->sendEmail($user->email, $subject, $content);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l’envoi de l’email de connexion', [
                'user_id' => $event->user->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
