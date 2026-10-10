<?php

namespace App\Mail;

use App\Models\DemandeDevis;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DevisConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public DemandeDevis $demande;

    public function __construct(DemandeDevis $demande)
    {
        $this->demande = $demande;
    }

    public function build()
    {
        return $this
            ->subject(
                'Confirmation de votre demande de devis - ' .
                $this->demande->reference
            )
            ->view('emails.devis-confirmation')
            ->with([
                'demande' => $this->demande,
            ])
            ->withSymfonyMessage(function ($message) {

                $logoPath = public_path('images/logo.png');

                if (file_exists($logoPath)) {
                    $message->embedFromPath(
                        $logoPath,
                        'planetech-logo'
                    );
                }
            });
    }
}