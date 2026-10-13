<?php

namespace App\Mail;

use App\Models\Devis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DevisEnvoye extends Mailable
{
    // Pas de ShouldQueue : l'envoi est immédiat.

    public function __construct(public Devis $devis)
    {
        $this->devis->loadMissing(['demandeDevis', 'lignes']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre devis commercial - ' . $this->devis->numero,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.devis-envoye',
            with: [
                'devis'   => $this->devis,
                'demande' => $this->devis->demandeDevis,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                function () {
                    return Pdf::loadView('emails.devis-pdf', [
                            'devis'   => $this->devis,
                            'demande' => $this->devis->demandeDevis,
                        ])
                        ->setPaper('a4')
                        ->output();
                },
                'Devis-' . $this->devis->numero . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
