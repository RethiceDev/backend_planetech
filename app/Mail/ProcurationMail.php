<?php

namespace App\Mail;

use App\Models\Procuration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class ProcurationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $procuration;

    public function __construct(Procuration $procuration)
    {
        $this->procuration = $procuration;
    }

    public function build()
    {
        $pdf = Pdf::loadView('admin.procurations.pdf', ['procuration' => $this->procuration]);

        return $this->subject('Procuration Officielle - Ref : ' . $this->procuration->reference)
                    ->view('emails.procuration')
                    ->attachData($pdf->output(), 'Procuration-' . $this->procuration->reference . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}
