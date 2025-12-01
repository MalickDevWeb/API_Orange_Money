<?php

namespace App\Jobs;

use App\Interfaces\Notifications\BrevoServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $email;
    protected string $subject;
    protected string $htmlContent;
    protected string $type;

    /**
     * Create a new job instance.
     */
    public function __construct(string $email, string $subject, string $htmlContent, string $type = 'general')
    {
        $this->email = $email;
        $this->subject = $subject;
        $this->htmlContent = $htmlContent;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(BrevoServiceInterface $brevoService): void
    {
        try {
            Log::info("Envoi d'email asynchrone", [
                'type' => $this->type,
                'email' => $this->email,
                'subject' => $this->subject
            ]);

            $result = $brevoService->sendMail($this->email, $this->subject, $this->htmlContent);

            Log::info("Email envoyé avec succès", [
                'type' => $this->type,
                'email' => $this->email,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur envoi email asynchrone", [
                'type' => $this->type,
                'email' => $this->email,
                'error' => $e->getMessage()
            ]);

            // Relancer le job en cas d'erreur temporaire
            if ($this->attempts() < 3) {
                $this->release(60); // Réessayer dans 1 minute
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job SendEmailNotification échoué définitivement", [
            'email' => $this->email,
            'type' => $this->type,
            'error' => $exception->getMessage()
        ]);
    }
}
