<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7f6;
            color: #27323a;
            padding: 30px;
            margin: 0;
        }

        .container {
            max-width: 650px;
            margin: auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
        }

        .header {
            background: #176b4d;
            color: #ffffff;
            padding: 25px;
        }

        .header h1 {
            margin: 0;
            font-size: 21px;
        }

        .body {
            padding: 25px;
        }

        .reference {
            margin: 18px 0;
            padding: 14px;
            background: #eef7f2;
            border-left: 4px solid #176b4d;
        }

        .total {
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
            color: #176b4d;
        }

        .footer {
            padding: 15px 25px;
            border-top: 1px solid #e5ebe8;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>Merci pour votre commande</h1>
    </div>

    <div class="body">

        <p>
            Bonjour
            <strong>{{ $commande->nom }}</strong>,
        </p>

        <p>
            Nous vous confirmons la bonne réception de votre
            commande auprès de PLANETECH SARL.
        </p>

        <div class="reference">

            <strong>Référence de votre commande :</strong>

            <div style="margin-top: 5px; font-size: 18px;">
                {{ $commande->reference }}
            </div>

        </div>

        <p>
            Votre commande a bien été enregistrée par notre équipe.
            Nous vous contacterons prochainement pour la suite.
        </p>

        <div class="total">
            Montant de la commande :
            {{ number_format(
                $commande->total,
                0,
                ',',
                ' '
            ) }}
            FCFA
        </div>

        <p style="margin-top: 25px;">
            Merci pour votre confiance.
        </p>

        <p>
            <strong>PLANETECH SARL</strong><br>

            Tél :
            +229 64 54 99 59 /
            68 60 18 05

            <br>

            Email :
            {{ config('mail.from.address') }}
        </p>

    </div>

    <div class="footer">
        Ceci est un email automatique de confirmation de commande.
    </div>

</div>

</body>
</html>
