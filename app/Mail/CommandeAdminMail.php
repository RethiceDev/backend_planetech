<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommandeAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Commande $commande,
        public string $pdfData
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle commande - ' . $this->commande->reference
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commandes.admin'
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfData,
                'commande-' . $this->commande->reference . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
