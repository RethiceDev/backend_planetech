<?php

namespace App\Mail;

use App\Models\Decharge;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DechargeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $decharge;

    public function __construct(Decharge $decharge)
    {
        $this->decharge = $decharge;
    }

    public function build()
    {
        $this->subject('Fiche de Décharge Officielle - ' . $this->decharge->reference)
             ->view('emails.decharge');

        // Pièce jointe PDF
        try {
            $this->decharge->loadMissing('items');

            // Logo en base64 (DomPDF le lit sans accès réseau)
            $logo = null;
            $logoPath = public_path('images/logo.png');
            if (file_exists($logoPath)) {
                $logo = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            }

            $pdf = Pdf::loadView('pdf.decharge', [
                'decharge' => $this->decharge,
                'logo'     => $logo,
            ])->setPaper('a4', 'portrait');

            $this->attachData(
                $pdf->output(),
                $this->decharge->reference . '.pdf',
                ['mime' => 'application/pdf']
            );
        } catch (\Throwable $e) {
            // Si le PDF échoue, le mail part quand même (sans pièce jointe)
            Log::error("Échec génération PDF pour {$this->decharge->reference} : " . $e->getMessage());
        }

        return $this;
    }
}
