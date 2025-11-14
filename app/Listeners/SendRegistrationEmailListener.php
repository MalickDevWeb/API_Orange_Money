<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Interfaces\Notifications\BrevoServiceInterface;
use Illuminate\Support\Facades\View;

class SendRegistrationEmailListener
{
    protected BrevoServiceInterface $brevoService;

    public function __construct(BrevoServiceInterface $brevoService)
    {
        $this->brevoService = $brevoService;
    }

    public function handle(UserRegistered $event)
    {
        $user = $event->user;

        if (!$user->email) {
            return; // No email to send
        }

        $subject = "Bienvenue sur Orange Money - {$user->nom} {$user->prenom}";

        if ($user->isCommercant() || $user->isFournisseur()) {
            $message = "Votre inscription en tant que {$user->type} a été reçue avec succès. Votre compte est actuellement en attente d'approbation par un administrateur. Vous serez notifié une fois l'approbation effectuée.";
        } else {
            $message = "Bienvenue sur Orange Money ! Votre inscription en tant que {$user->type} a été confirmée. Vous pouvez maintenant accéder à toutes les fonctionnalités de l'application.";
        }

        $htmlContent = View::make('emails.registration-notification', [
            'user' => $user,
            'message' => $message,
            'is_pending' => $user->isCommercant() || $user->isFournisseur()
        ])->render();

        $this->brevoService->sendMail($user->email, $subject, $htmlContent);
    }
}
