<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation - {{ $attestation->reference }}</title>
    <style>
        @page {
            size: A4;
            margin: 45mm 20mm 50mm 20mm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 13pt;
            line-height: 1.5;
            color: #000000;
            text-align: justify;
            margin: 0px;
            padding: 0;
        }
        .header-ref {
            text-align: left;
            font-size: 11pt;
            margin-bottom: 20px;
            font-weight: bold;
            font-style: italic;
        }
        .title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }
        .content {
            margin-bottom: 20px;
            text-indent: 40px;
        }
        .content p {
            margin-bottom: 12px;
            text-align: justify;
        }
        .signature-table {
            width: 100%;
            margin-top: 1.5cm;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
        }
        .signature-block {
            text-align: center;
            float: right;
            width: 260px;
        }
        .signature-title {
            font-weight: bold;
           
            margin-bottom: 2.2cm; /* Espace réservé pour la signature et le cachet */
        }
        .signature-name {
            font-style: italic;
            text-decoration: underline;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <br><br><br><br>

    <div class="header-ref">
        @if(!empty(trim($attestation->numero)))
            N° : {{ $attestation->numero }}
        @endif
    </div>

    <div class="title">
        ATTESTATION DE {{ strtoupper($attestation->type_label) }}
    </div>

    <div class="content">
        <p>
            Je soussignée, <strong>Monsieur Armelle ASSOGBA</strong>, Directeur Générale de la société 
            <strong>{{ $attestation->societe_emettrice }}</strong>, atteste par la présente que 
            @if($attestation->genre === 'F') la nommée @else le nommé @endif 
            <strong>{{ $attestation->beneficiaire }}</strong> a exercé au sein de notre structure 
            en qualité de <strong>{{ $attestation->poste }}</strong>.
        </p>

        @if($attestation->type === 'fin_stage_professionnel')
            <p>
                Cette période de stage professionnel s'est déroulée du 
                <strong>{{ \Carbon\Carbon::parse($attestation->date_debut)->locale('fr')->translatedFormat('d F Y') ? mb_strtoupper(\Carbon\Carbon::parse($attestation->date_debut)->locale('fr')->translatedFormat('d F Y')) : '' }}</strong> 
                au 
                <strong>{{ \Carbon\Carbon::parse($attestation->date_fin)->locale('fr')->translatedFormat('d F Y') ? mb_strtoupper(\Carbon\Carbon::parse($attestation->date_fin)->locale('fr')->translatedFormat('d F Y')) : '' }}</strong>, 
                soit une durée de <strong>{{ $attestation->duree }}</strong>.
            </p>
            <p>
                Pendant son passage au sein de l'entreprise, 
                @if($attestation->genre === 'F') elle @else il @endif 
                a fait preuve d'un grand professionnalisme, d'assiduité et d'un réel engagement dans les tâches qui 
                @if($attestation->genre === 'F') lui ont été confiées @else lui ont été confiés @endif.
            </p>

        @elseif($attestation->type === 'fin_stage_academique')
            <p>
                Ce stage académique s'est déroulé du 
                <strong>{{ \Carbon\Carbon::parse($attestation->date_debut)->locale('fr')->translatedFormat('d F Y') ? mb_strtoupper(\Carbon\Carbon::parse($attestation->date_debut)->locale('fr')->translatedFormat('d F Y')) : '' }}</strong> 
                au 
                <strong>{{ \Carbon\Carbon::parse($attestation->date_fin)->locale('fr')->translatedFormat('d F Y') ? mb_strtoupper(\Carbon\Carbon::parse($attestation->date_fin)->locale('fr')->translatedFormat('d F Y')) : '' }}</strong>, 
                pour une durée totale de <strong>{{ $attestation->duree }}</strong>.
            </p>
            <p>
                Au cours de cette période, 
                @if($attestation->genre === 'F') elle @else il @endif 
                s'est distingué(e) par sa rigueur, sa curiosité technique et son sérieux dans le cadre de ses travaux de recherche et de mise en pratique.
            </p>

        @elseif($attestation->type === 'travail')
            <p>
                Pendant son passage au sein de l'entreprise du 
                <strong>{{ \Carbon\Carbon::parse($attestation->date_debut)->locale('fr')->translatedFormat('d F Y') ? mb_strtoupper(\Carbon\Carbon::parse($attestation->date_debut)->locale('fr')->translatedFormat('d F Y')) : '' }}</strong> 
                au 
                <strong>{{ \Carbon\Carbon::parse($attestation->date_fin)->locale('fr')->translatedFormat('d F Y') ? mb_strtoupper(\Carbon\Carbon::parse($attestation->date_fin)->locale('fr')->translatedFormat('d F Y')) : '' }}</strong> 
                ({{ $attestation->periode_texte }}), 
                @if($attestation->genre === 'F') elle @else il @endif 
                a occupé avec succès les fonctions de <strong>{{ $attestation->poste }}</strong>.
            </p>
            <p>
                Cette attestation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit.
            </p>

        @elseif($attestation->type === 'bonne_fin_travaux')
            <p>
                Cette attestation est délivrée pour certifier que 
                <strong>{{ $attestation->beneficiaire }}</strong> a exécuté avec entière satisfaction les travaux relatifs à l'objet suivant : 
                <em>« {{ $attestation->objet }} »</em>, réalisés du 
                <strong>{{ \Carbon\Carbon::parse($attestation->date_debut)->locale('fr')->translatedFormat('d F Y') ? mb_strtoupper(\Carbon\Carbon::parse($attestation->date_debut)->locale('fr')->translatedFormat('d F Y')) : '' }}</strong> 
                au 
                <strong>{{ \Carbon\Carbon::parse($attestation->date_fin)->locale('fr')->translatedFormat('d F Y') ? mb_strtoupper(\Carbon\Carbon::parse($attestation->date_fin)->locale('fr')->translatedFormat('d F Y')) : '' }}</strong>.
            </p>
        @endif

        <p>
            En foi de quoi, la présente ATTESTATION lui est délivrée pour servir et valoir ce que de droit.
        </p>
    </div>

    <table class="signature-table">
        <tr>
            <td></td>
            <td>
                <div class="signature-block">
                    <div class="signature-title">LE DIRECTEUR GENERAL</div>
                    <div class="signature-name">Armelle ASSOGBA</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>