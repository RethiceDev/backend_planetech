<?php

namespace App\Mail;

use App\Models\Devis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DevisEnvoye extends Mailable
{
    use Queueable, SerializesModels;

    public Devis $devis;

    public function __construct(Devis $devis)
    {
        $this->devis = $devis;

        $this->devis->load([
            'demandeDevis',
            'lignes',
        ]);
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
                'devis' => $this->devis,
                'demande' => $this->devis->demandeDevis,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView(
            'emails.devis-pdf',
            [
                'devis' => $this->devis,
                'demande' => $this->devis->demandeDevis,
            ]
        );

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                'Devis-' . $this->devis->numero . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}