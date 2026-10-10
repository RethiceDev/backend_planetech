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
            padding: 22px 25px;
        }

        .header h1 {
            margin: 0;
            font-size: 21px;
        }

        .body {
            padding: 25px;
        }

        .reference {
            background: #eef7f2;
            border-left: 4px solid #176b4d;
            padding: 12px 15px;
            margin: 18px 0;
        }

        .info {
            margin: 7px 0;
        }

        .label {
            font-weight: bold;
        }

        .total {
            margin-top: 20px;
            padding: 15px;
            background: #176b4d;
            color: #ffffff;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
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
        <h1>Nouvelle commande reçue</h1>
    </div>

    <div class="body">

        <p>
            Une nouvelle commande vient d'être enregistrée
            depuis la boutique PLANETECH.
        </p>

        <div class="reference">
            <strong>Référence :</strong>
            {{ $commande->reference }}
        </div>

        <h3>Client</h3>

        <div class="info">
            <span class="label">Type :</span>
            {{ $commande->type_client === 'entreprise'
                ? 'Entreprise'
                : 'Particulier' }}
        </div>

        @if($commande->type_client === 'entreprise')
            <div class="info">
                <span class="label">Entreprise :</span>
                {{ $commande->raison_sociale }}
            </div>
        @endif

        <div class="info">
            <span class="label">Nom / Contact :</span>
            {{ $commande->nom }}
        </div>

        <div class="info">
            <span class="label">Email :</span>
            {{ $commande->email }}
        </div>

        <div class="info">
            <span class="label">Téléphone :</span>
            {{ $commande->telephone }}
        </div>

        <div class="total">
            Total :
            {{ number_format(
                $commande->total,
                0,
                ',',
                ' '
            ) }}
            FCFA
        </div>

        <p style="margin-top: 22px;">
            Le PDF détaillé de la commande est joint à cet email.
        </p>

    </div>

    <div class="footer">
        PLANETECH SARL — Notification automatique de commande
    </div>

</div>

</body>
</html>
