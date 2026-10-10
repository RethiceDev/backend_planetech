<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Votre devis PLANETECH</title>
</head>

<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial, Helvetica, sans-serif;">

    <div style="max-width:650px; margin:30px auto; background:#ffffff; border-radius:12px; overflow:hidden;">

        <!-- HEADER -->
        <div style="background:#1b4d3e; padding:25px; text-align:center;">

            <img
                src="{{ $message->embed(public_path('images/logo.png')) }}"
                alt="PLANETECH SARL"
                width="140"
                style="
                    display:block;
                    width:140px;
                    max-width:140px;
                    height:auto;
                    margin:0 auto 15px;
                    border:0;
                    outline:none;
                    text-decoration:none;
                "
            >

            <h1 style="margin:0; color:#ffffff; font-size:26px;">
                PLANETECH SARL
            </h1>

            <p style="margin:8px 0 0; color:#d1fae5;">
                Votre devis commercial
            </p>

        </div>

        <!-- CONTENU -->
        <div style="padding:30px;">

            <p style="font-size:15px; color:#374151;">
                Bonjour
                <strong>
                    {{ $demande->type_client === 'societe'
                        ? $demande->raison_sociale
                        : $demande->nom }}
                </strong>,
            </p>

            <p style="font-size:15px; line-height:1.7; color:#4b5563;">
                Nous avons le plaisir de vous transmettre votre devis
                commercial concernant votre demande
                <strong>{{ $demande->reference }}</strong>.
            </p>

            <!-- INFORMATIONS DU DEVIS -->
            <div style="
                background:#f0fdf4;
                border:1px solid #bbf7d0;
                border-radius:10px;
                padding:20px;
                margin:25px 0;
            ">

                <p style="margin:0 0 10px; color:#166534;">
                    <strong>Numéro du devis :</strong>
                    {{ $devis->numero }}
                </p>

                <p style="margin:0 0 10px; color:#166534;">
                    <strong>Date d'émission :</strong>
                    {{ $devis->date_emission->format('d/m/Y') }}
                </p>

                <p style="margin:0; color:#166534;">
                    <strong>Total TTC :</strong>
                    {{ number_format($devis->total_ttc, 0, ',', ' ') }} FCFA
                </p>

            </div>

            <p style="font-size:15px; line-height:1.7; color:#4b5563;">
                Vous trouverez le devis complet en pièce jointe de cet e-mail
                au format PDF.
            </p>

            <p style="font-size:15px; line-height:1.7; color:#4b5563;">
                Nous restons à votre disposition pour toute information
                complémentaire.
            </p>

            <p style="font-size:15px; color:#374151;">
                Cordialement,<br>
                <strong>L'équipe PLANETECH SARL</strong>
            </p>

        </div>

        <!-- FOOTER -->
        <div style="background:#f9fafb; padding:20px; text-align:center;">

            <p style="margin:0; font-size:12px; color:#9ca3af;">
                © {{ date('Y') }} PLANETECH SARL — Tous droits réservés.
            </p>

        </div>

    </div>

</body>

</html>
