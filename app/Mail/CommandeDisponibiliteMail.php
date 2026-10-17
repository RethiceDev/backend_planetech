<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommandeDisponibiliteMail extends Mailable
{
    use Queueable, SerializesModels;

    public Commande $commande;

    public bool $tousDisponibles;

    public bool $aucunDisponible;

    public $articlesDisponibles;

    public $articlesIndisponibles;

    /**
     * Contenu binaire du PDF de la proforma (null = pas de pièce jointe).
     */
    public ?string $pdfData;

    public function __construct(Commande $commande, ?string $pdfData = null)
    {
        $this->pdfData = $pdfData;

        /*
        |--------------------------------------------------------------------------
        | Recharger la commande et ses articles depuis la base
        |--------------------------------------------------------------------------
        */

        $this->commande = $commande->fresh(['items']);

        $items = $this->commande->items;


        /*
        |--------------------------------------------------------------------------
        | Articles disponibles
        |--------------------------------------------------------------------------
        */

        $this->articlesDisponibles = $items
            ->filter(function ($item) {
                return $item->disponible === true;
            })
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Articles indisponibles
        |--------------------------------------------------------------------------
        |
        | Tout ce qui n'est PAS explicitement true est considéré
        | comme non disponible pour éviter qu'un NULL soit considéré
        | comme disponible.
        |
        */

        $this->articlesIndisponibles = $items
            ->filter(function ($item) {
                return $item->disponible !== true;
            })
            ->values();


        /*
        |--------------------------------------------------------------------------
        | TOUS disponibles
        |--------------------------------------------------------------------------
        |
        | Très important :
        | il faut que CHAQUE article soit exactement true.
        |
        */

        $this->tousDisponibles =
            $items->isNotEmpty()
            && $items->every(function ($item) {
                return $item->disponible === true;
            });


        /*
        |--------------------------------------------------------------------------
        | AUCUN disponible
        |--------------------------------------------------------------------------
        */

        $this->aucunDisponible =
            $items->isNotEmpty()
            && $items->every(function ($item) {
                return $item->disponible !== true;
            });
    }


    public function build()
    {
        /*
        |--------------------------------------------------------------------------
        | Sujet automatique
        |--------------------------------------------------------------------------
        */

        if ($this->tousDisponibles) {

            $sujet =
                'Confirmation de disponibilité de votre commande '
                . $this->commande->reference;

        } elseif ($this->aucunDisponible) {

            $sujet =
                'Disponibilité de votre commande '
                . $this->commande->reference;

        } else {

            $sujet =
                'Mise à jour de la disponibilité de votre commande '
                . $this->commande->reference;
        }

        $mail = $this
            ->subject($sujet)
            ->view('emails.commande-disponibilite');


        /*
        |--------------------------------------------------------------------------
        | Proforma en PDF (si elle a pu être générée)
        |--------------------------------------------------------------------------
        */

        if ($this->pdfData !== null) {
            $mail->attachData(
                $this->pdfData,
                'Proforma-' . $this->commande->reference . '.pdf',
                ['mime' => 'application/pdf']
            );
        }

        return $mail;
    }
}
