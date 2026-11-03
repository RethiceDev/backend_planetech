<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procuration - {{ $procuration->reference }}</title>
    <style>
        /* Marges strictes pour encadrer la zone d'impression entre l'en-tête et la signature physique */
        @page {
            size: A4 portrait;
            margin-top: 45mm;    /* Réserve l'espace pour l'en-tête couleur pré-imprimé */
            margin-bottom: 50mm; /* Réserve l'espace pour la zone basse (signature + nom/titre du DG) */
            margin-left: 20mm;
            margin-right: 20mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
        }

        .title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-decoration: underline;
            margin: 0 0 20px 0;
            letter-spacing: 0.5px;
        }

        .content {
            text-align: justify;
            margin-bottom: 15px;
        }

        .actions-list {
            margin: 10px 0 20px 20px;
        }

        .actions-list li {
            margin-bottom: 4px;
        }

        .footer-date {
            text-align: right;
            margin-top: 25px;
            margin-bottom: 15px;
            font-style: italic;
        }

        /* Bloc de signature positionné en bas pour imprimer le nom et le titre sous la signature du DG */
        table.signature {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            page-break-inside: avoid;
        }
        table.signature td.vide {
            width: 50%;
        }
        table.signature td.sig {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .sig-nom {
            font-weight: bold;
            margin: 0;
        }
        .sig-titre {
            margin: 0;
            font-size: 12px;
            color: #333;
        }
    </style>
</head>
<body>
    <br><br>
    <div class="title">PROCURATION</div>

    <div class="content">
        Je soussigné Monsieur <strong>{{ $procuration->nom_donnateur }}</strong> agissant en tant que <strong>{{ $procuration->role_donnateur }}</strong> de la société <strong>PLANETECH SARL</strong>, déclare donner pouvoir par la présente à : Monsieur/Madame <strong>{{ $procuration->nom_procurant }}</strong>, titulaire du <strong>Certificat d'Identification Personnelle (CIP) N° {{ $procuration->cip_procurant }}</strong> qui expire le {{ \Carbon\Carbon::parse($procuration->expiration_cip)->format('d/m/Y') }}, agissant en qualité de <strong>{{ $procuration->role_procurant }}</strong> de ladite SOCIETE, afin de me représenter conformément à mes intérêts.
    </div>

    <div class="content">
        Par cette procuration valable du <strong>{{ \Carbon\Carbon::parse($procuration->date_debut)->format('d/m/Y') }}</strong> au <strong>{{ \Carbon\Carbon::parse($procuration->date_fin)->format('d/m/Y') }}</strong>, il est en droit d'effectuer l'opération suivante :
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
        Fait à Cotonou, le {{ $procuration->created_at->format('d/m/Y') }}
    </div>

    <!-- Le nom et le titre s'impriment juste sous la zone de signature manuscrite -->
    <table class="signature">
        <tr>
            <td class="vide"></td>
            <td class="sig">
                <!-- Espace vide virtuel de sécurité au-dessus du nom pour que la signature manuscrite du DG repose naturellement dedans -->
                <div style="height: 25px;"></div><br><br><br>
                <p class="sig-nom">Armelle ASSOGBA</p><br><br><br><br>
                <p class="sig-titre">Directeur Général</p>
            </td>
        </tr>
    </table>

</body>
</html>
