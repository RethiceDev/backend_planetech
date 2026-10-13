<?php

namespace App\Mail;

use App\Models\DemandeDevis;
use Illuminate\Mail\Mailable;
use Symfony\Component\Mime\Email;

class DevisConfirmationMail extends Mailable
{
    // Pas de ShouldQueue : l'envoi est immédiat.

    public function __construct(public DemandeDevis $demande)
    {
    }

    public function build()
    {
        $logoPath = public_path('images/logo.png');
        $logoDisponible = is_file($logoPath);

        $mail = $this
            ->subject('Confirmation de votre demande de devis - ' . $this->demande->reference)
            ->view('emails.devis-confirmation')
            ->with([
                'demande'        => $this->demande,
                'logoDisponible' => $logoDisponible,
            ]);

        if ($logoDisponible) {
            $mail->withSymfonyMessage(function (Email $message) use ($logoPath) {
                $message->embedFromPath($logoPath, 'planetech-logo', 'image/png');
            });
        }

        return $mail;
    }
}
