@php
    if (empty($logo)) {
        $candidats = glob(public_path('{,images/,img/,assets/,storage/}*logo*.{png,jpg,jpeg,PNG,JPG}'), GLOB_BRACE);
        if (!empty($candidats)) {
            $fichier = $candidats[0];
            $ext = strtolower(pathinfo($fichier, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
            $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fichier));
        }
    }
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procuration - {{ $procuration->reference }}</title>
    <style>
        @page { margin: 30px 40px 80px 40px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 14px; line-height: 1.8; margin: 0; }

        table.entete { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1b4d3e; margin-bottom: 20px; }
        table.entete td { vertical-align: middle; padding: 0 0 12px 0; }
        td.logo-cell { width: 110px; padding-right: 15px; }
        td.logo-cell img { width: 100px; height: auto; display: block; }
        td.txt-cell { text-align: center; }
        .nom { font-size: 22px; font-weight: bold; color: #1b4d3e; letter-spacing: 1px; margin-bottom: 4px; }
        .lignes { font-size: 9px; line-height: 1.5; color: #444; }

        .title { text-align: center; font-size: 22px; font-weight: bold; text-decoration: underline; margin: 30px 0; letter-spacing: 0.5px; }
        .content { text-align: justify; margin-bottom: 20px; }
        .actions-list { margin: 15px 0 25px 20px; }
        .actions-list li { margin-bottom: 6px; }
        .footer-date { text-align: right; margin-top: 40px; font-style: italic; }

        table.signature { width: 100%; border-collapse: collapse; margin-top: 30px; page-break-inside: avoid; }
        table.signature td.vide { width: 55%; }
        table.signature td.sig { width: 45%; text-align: center; vertical-align: top; }
        .sig-nom { font-weight: bold; margin: 0; }
        .sig-titre { margin: 0; }

        .legal-footer { position: fixed; bottom: -50px; left: 0; right: 0; text-align: center; font-size: 9px; color: #666; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>

    <table class="entete">
        <tr>
            <td class="logo-cell">
                @if(!empty($logo))
                    <img src="{{ $logo }}" alt="PLANETECH SARL">
                @endif
            </td>
            <td class="txt-cell">
                <div class="nom">PLANETECH SarL</div>
                <div class="lignes">
                    Tous Travaux de BTP - Conception - Etudes et de Réalisation d'Ouvrage d'Art<br>
                    Vente et Négoce de Matériel Electrique - Plomberie - Réseau Informatique - Téléphone<br>
                    Climatiseur - Fond Caméra de Surveillance - Alarme - Incendie et Intrusion<br>
                    Expertise et Maîtrise d'Ouvrage - Commerce Général
                </div>
            </td>
        </tr>
    </table>

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

    <table class="signature">
        <tr>
            <td class="vide"></td>
            <td class="sig">
                <p class="sig-nom">Armelle ASSOGBA</p><br><br>
                <p class="sig-titre">Directeur Général</p>
            </td>
        </tr>
    </table>

    <div class="legal-footer">
        04 BP : 0624 Cotonou . Compte Bancaire : 010002670139 Ora Bank . RCCM : RB/COT/13-B-9794 - IFU N° : 3201300290010<br>
        Capital social : 1.000.000 FCFA - Tél : +229 01 64 54 99 59 / 0168 68 18 85 - Email : planetechsarlbenin@gmail.com
    </div>

</body>
</html>
