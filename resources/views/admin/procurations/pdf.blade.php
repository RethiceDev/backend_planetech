<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procuration - {{ $procuration->reference }}</title>
    <style>
        /* En-tête et pied de page déjà pré-imprimés sur le papier : on réserve juste l'espace */
        @page {
            size: A4 portrait;
            margin-top: 45mm;
            margin-bottom: 50mm;
            margin-left: 20mm;
            margin-right: 20mm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            color: #111;
            font-size: 17px;
            line-height: 1.9;
            margin: 0;
            padding: 0;
        }

        .title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            text-decoration: underline;
            margin: 50px 0 0px 0;
        }

        .content {
            text-align: justify;
            text-indent: 30px;
            margin: 0 0 12px 0;
        }

        .actions-list {
            margin: 4px 0 12px 45px;
            padding: 0;
            list-style-type: disc;
        }

        .footer-date {
            text-align: right;
            margin: 14px 40px 0 0;
        }

        /* Bloc de signature */
        table.signature {
            width: 100%;
            border-collapse: collapse;
            margin: 2cm 0 0 0;
            page-break-inside: avoid;
        }
        table.signature td.vide {
            width: 50%;
        }
        table.signature td.sig {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0;
        }
        .sig-titre {
            margin: 0;
            text-transform: uppercase;
        }
        .sig-nom {
            font-style: italic;
            margin: 0;
        }
    </style>
</head>
<body>
    @php
        // Date avec mois en lettres, en majuscules : 10 NOVEMBRE 2026
        $dateLettres = fn($date) => mb_strtoupper(
            \Carbon\Carbon::parse($date)
                ->timezone('Africa/Porto-Novo')
                ->locale('fr')
                ->translatedFormat('d F Y'),
            'UTF-8'
        );
    @endphp

    <div class="title">PROCURATION</div>

    <div class="content">
        Je soussigné Monsieur <strong>{{ $procuration->nom_donnateur }}</strong> agissant en tant que {{ $procuration->role_donnateur }} de la société <strong>PLANETECH SARL</strong>, déclare donner pouvoir par la présente à : {{ $procuration->civilite_procurant ?? 'Monsieur' }} <strong>{{ $procuration->nom_procurant }}</strong>, titulaire du <strong>Certificat d'Identification Personnelle (CIP) N°{{ $procuration->cip_procurant }}</strong> qui expire le {{ \Carbon\Carbon::parse($procuration->expiration_cip)->format('d/m/Y') }}, agissant en qualité de {{ $procuration->role_procurant }} de ladite SOCIETE, afin de me représenter conformément à mes intérêts.
    </div>

    <div class="content">
        Par cette procuration valable du <strong>{{ $dateLettres($procuration->date_debut) }} au {{ $dateLettres($procuration->date_fin) }}</strong> il est en droit d'effectuer l'opération suivante :
    </div>

    <ul class="actions-list">
        @foreach($procuration->actions as $action)
            <li>{{ $action->libelle_action }}</li>
        @endforeach
    </ul>

    <div class="content">
        En foi de quoi, la présente procuration lui est délivrée pour servir et valoir ce que de droit.
    </div>

    <div class="footer-date">
        Faite à Cotonou, le {{ $dateLettres(now()) }}
    </div>

    <table class="signature">
        <tr>
            <td class="vide"></td>
            <td class="sig">
                <p class="sig-titre">Le Directeur Général</p>
                <div style="height: 3cm;margin-bottom:5px"></div> <!-- espace pour signature et cachet -->
                <p class="sig-nom">Armelle ASSOGBA</p>
            </td>
        </tr>
    </table>
</body>
</html>