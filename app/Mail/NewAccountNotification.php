<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewAccountNotification extends Mailable
{
    use Queueable, SerializesModels;

    public array $userData;
    public array $accountData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $userData, array $accountData = [])
    {
        $this->userData = $userData;
        $this->accountData = $accountData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue - Votre compte bancaire a été créé',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-account-notification',
            with: [
                'user' => $this->userData,
                'account' => $this->accountData,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
