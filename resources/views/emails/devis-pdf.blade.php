<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <style>
        @page {
            margin: 14px 24px 18px 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #27323a;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        .page {
            width: 100%;
        }

        /* =========================================================
           ÉVITER LES COUPURES INUTILES
        ========================================================== */

        .header,
        .document-header,
        .info-table,
        .object-box,
        .bottom-table,
        .amount-words,
        .signature-table,
        .footer {
            page-break-inside: avoid;
        }

        .lines {
            page-break-inside: auto;
        }

        .lines tr {
            page-break-inside: avoid;
        }

        .lines thead {
            display: table-header-group;
        }

        /* =========================================================
           EN-TÊTE COMPACT
        ========================================================== */

        .header {
            width: 100%;
            height: 82px;
            margin: 0;
        }

        .header-logo {
            width: 20%;
            vertical-align: middle;
            text-align: left;
            padding-right: 8px;
        }

        .logo {
            width: 78px;
            max-height: 72px;
            height: auto;
        }

        .header-company {
            width: 80%;
            vertical-align: middle;
            padding-left: 8px;
        }

        .company-name {
            font-size: 21px;
            font-weight: bold;
            color: #176b4d;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .company-subtitle {
            width: 100%;
            font-size: 7px;
            line-height: 1.35;
            color: #5a665f;
        }

        .header-line {
            height: 2px;
            background-color: #176b4d;
            width: 100%;
            margin-top: 2px;
            margin-bottom: 9px;
        }

        /* =========================================================
           TITRE PROFORMA
        ========================================================== */

        .document-header {
            width: 100%;
            margin-bottom: 10px;
        }

        .document-title {
            width: 58%;
            vertical-align: middle;
        }

        .document-meta {
            width: 42%;
            vertical-align: middle;
            text-align: right;
        }

        .document-label {
            font-size: 7px;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.6px;
            margin-bottom: 2px;
        }

        .offer-title {
            font-size: 10px;
            font-weight: bold;
            color: #374151;
        }

        .proforma-badge {
            display: inline-block;
            background-color: #176b4d;
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            padding: 6px 10px;
            letter-spacing: 0.7px;
        }

        .proforma-number {
            margin-top: 4px;
            font-size: 8px;
            font-weight: bold;
            color: #374151;
        }

        .document-date {
            margin-top: 2px;
            font-size: 7px;
            color: #6b7280;
            line-height: 1.35;
        }

        /* =========================================================
           CLIENT
        ========================================================== */

        .info-table {
            width: 100%;
            margin-bottom: 9px;
        }

        .client-left,
        .client-right {
            width: 50%;
            vertical-align: top;
        }

        .client-left {
            padding-right: 4px;
        }

        .client-right {
            padding-left: 4px;
        }

        .info-card {
            border: 1px solid #dce8e2;
            background-color: #f8fbf9;
            padding: 7px 8px;
            min-height: 53px;
        }

        .info-title {
            font-size: 7px;
            text-transform: uppercase;
            color: #176b4d;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-main {
            font-size: 9px;
            font-weight: bold;
            color: #1f2937;
        }

        .info-text {
            margin-top: 2px;
            font-size: 7.5px;
            color: #5b6470;
            line-height: 1.3;
        }

        /* =========================================================
           OBJET
        ========================================================== */

        .object-box {
            width: 100%;
            border-left: 3px solid #176b4d;
            background-color: #eef7f2;
            padding: 6px 8px;
            margin-bottom: 9px;
        }

        .object-title {
            font-size: 7px;
            color: #176b4d;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .object-text {
            font-size: 8px;
            color: #1f2937;
            line-height: 1.35;
        }

        /* =========================================================
           TABLEAU DES LIGNES
        ========================================================== */

        .lines {
            width: 100%;
        }

        .lines thead th {
            background-color: #176b4d;
            color: #ffffff;
            padding: 5px 4px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .lines tbody td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5ebe8;
            font-size: 7.8px;
            vertical-align: middle;
            line-height: 1.3;
        }

        .lines tbody tr:nth-child(even) td {
            background-color: #f8fbf9;
        }

        .col-num {
            width: 6%;
            text-align: center;
        }

        .col-designation {
            width: 39%;
        }

        .col-unite {
            width: 10%;
            text-align: center;
        }

        .col-qty {
            width: 10%;
            text-align: center;
        }

        .col-pu {
            width: 17.5%;
            text-align: right;
        }

        .col-total {
            width: 17.5%;
            text-align: right;
        }

        /* =========================================================
           CONDITIONS + TOTAUX
        ========================================================== */

        .bottom-table {
            width: 100%;
            margin-top: 9px;
        }

        .conditions-cell {
            width: 55%;
            vertical-align: top;
            padding-right: 5px;
        }

        .totals-cell {
            width: 45%;
            vertical-align: top;
        }

        .conditions-box {
            border: 1px solid #dce8e2;
            background-color: #f8fbf9;
            padding: 7px;
        }

        .conditions-title {
            font-size: 7px;
            color: #176b4d;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .condition-row {
            margin-bottom: 3px;
            font-size: 7.5px;
            color: #4b5563;
            line-height: 1.3;
        }

        .condition-label {
            font-weight: bold;
            color: #1f2937;
        }

        .totals {
            width: 100%;
            border: 1px solid #dce8e2;
        }

        .totals td {
            padding: 5px 7px;
            font-size: 7.8px;
            border-bottom: 1px solid #e5ebe8;
        }

        .totals-label {
            text-align: left;
            color: #4b5563;
        }

        .totals-value {
            text-align: right;
            font-weight: bold;
            color: #1f2937;
        }

        .total-final td {
            background-color: #176b4d;
            color: #ffffff;
            border-bottom: none;
            font-size: 9px;
            font-weight: bold;
            padding-top: 6px;
            padding-bottom: 6px;
        }

        .total-final .totals-label,
        .total-final .totals-value {
            color: #ffffff;
        }

        /* =========================================================
           MONTANT EN LETTRES
        ========================================================== */

        .amount-words {
            margin-top: 8px;
            padding: 6px 8px;
            border: 1px solid #dce8e2;
            background-color: #f8fbf9;
            font-size: 7.8px;
            line-height: 1.35;
        }

        .amount-words-title {
            color: #176b4d;
            font-weight: bold;
        }

        /* =========================================================
           SIGNATURE
        ========================================================== */

        .signature-table {
            width: 100%;
            margin-top: 10px;
        }

        .signature-left {
            width: 60%;
        }

        .signature-right {
            width: 40%;
            text-align: center;
        }

        .signature-title {
            font-size: 7px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .signature-image {
            height: 85px;
            margin-bottom: 2px;
        }

        .signature-image img {
            max-height: 85px;
            max-width: 100%;
        }

        .signature-name {
            font-size: 8.5px;
            font-weight: bold;
            color: #1f2937;
        }

        .signature-role {
            margin-top: 2px;
            font-size: 7px;
            color: #6b7280;
        }

        .signature-line {
            border-top: 1px solid #9ca3af;
            width: 130px;
            margin: 18px auto 4px auto;
        }

        /* =========================================================
           PIED DE PAGE
        ========================================================== */

        .footer {
            margin-top: 10px;
            border-top: 1px solid #176b4d;
            padding-top: 5px;
            text-align: center;
            font-size: 6.5px;
            line-height: 1.35;
            color: #6b7280;
        }

        .footer-company {
            font-weight: bold;
            color: #176b4d;
            font-size: 7px;
            margin-bottom: 1px;
        }
    </style>
</head>

<body>

@php

    /* =========================================================
       LOGO
    ========================================================== */

    $logoPath = public_path('images/planetech-logo.png');

    if (!file_exists($logoPath)) {
        $logoPath = public_path('images/logo.png');
    }

    $logoBase64 = null;

    if (file_exists($logoPath)) {
        $logoBase64 = base64_encode(
            file_get_contents($logoPath)
        );
    }

    /* =========================================================
       SIGNATURE + CACHET
       Fichier : storage/app/private/cachet-signature.png
       (PNG à fond transparent, hors du dossier public)
    ========================================================== */

    $cachetPath = storage_path('app/private/cachet-signature.png');

    $cachetBase64 = file_exists($cachetPath)
        ? base64_encode(file_get_contents($cachetPath))
        : null;

    /* =========================================================
       CALCUL TVA
    ========================================================== */

    $baseApresRemise =
        $devis->sous_total - $devis->remise;

    $montantTva =
        $baseApresRemise * ($devis->tva / 100);

@endphp


<div class="page">

    {{-- =========================================================
         EN-TÊTE
    ========================================================== --}}

    <table class="header">

        <tr>

            <td class="header-logo">

                @if($logoBase64)

                    <img
                        src="data:image/png;base64,{{ $logoBase64 }}"
                        class="logo"
                        alt="PLANETECH SARL"
                    >

                @endif

            </td>

            <td class="header-company">

                <div class="company-name">
                    PLANETECH SARL
                </div>

                <div class="company-subtitle">

                    Tous Travaux de BTP • Conception • Études et de Réalisation d’Ouvrage d’Art
                    <br>
                    Vente et Négoce de Matériel Électrique • Plomberie • Réseau Informatique • Téléphonie
                    <br>
                    Climatiseur • Fond Caméra de Surveillance • Alarme Incendie et Intrusion
                    <br>
                    Expertise et Maîtrise d’Ouvrage • Commerce Général

                </div>

            </td>

        </tr>

    </table>


    {{-- Ligne de séparation --}}

    <div class="header-line"></div>


    {{-- =========================================================
         TITRE PROFORMA
    ========================================================== --}}

    <table class="document-header">

        <tr>

            <td class="document-title">

                <div class="document-label">
                    DOCUMENT COMMERCIAL
                </div>

                <div class="offer-title">
                    Offre de fourniture et / ou de prestation
                </div>

            </td>

            <td class="document-meta">

                <div class="proforma-badge">
                    FACTURE PROFORMA
                </div>

                <div class="proforma-number">
                    N° {{ $devis->numero }}
                </div>

                <div class="document-date">

                    Émise le :
                    {{ $devis->date_emission->format('d/m/Y') }}

                    <br>

                    Valable jusqu'au :
                    {{ $devis->date_validite->format('d/m/Y') }}

                </div>

            </td>

        </tr>

    </table>


    {{-- =========================================================
         CLIENT
    ========================================================== --}}

    <table class="info-table">

        <tr>

            <td class="client-left">

                <div class="info-card">

                    <div class="info-title">
                        Client
                    </div>

                    <div class="info-main">

                        @if($demande->type_client === 'societe')
                            {{ strtoupper($demande->raison_sociale) }}
                        @else
                            {{ strtoupper($demande->nom) }}
                        @endif

                    </div>

                    @if($demande->type_client === 'societe')

                        <div class="info-text">
                            Contact : {{ $demande->nom }}
                        </div>

                    @endif

                    <div class="info-text">

                        Email : {{ $demande->email }}

                        <br>

                        Téléphone :
                        {{ $demande->indicatif }}
                        {{ $demande->telephone }}

                    </div>

                </div>

            </td>


            <td class="client-right">

                <div class="info-card">

                    <div class="info-title">
                        Informations
                    </div>

                    <div class="info-text">

                        <strong>Référence demande :</strong>
                        {{ $demande->reference }}

                        <br>

                        <strong>Domaine :</strong>
                        {{ $demande->domaine }}

                        <br>

                        <strong>Délai :</strong>
                        {{ $devis->delai_realisation }} jours

                        <br>

                        <strong>Conditions de paiement :</strong>
                        {{ $devis->conditions_paiement }}

                    </div>

                </div>

            </td>

        </tr>

    </table>


    {{-- =========================================================
         OBJET
    ========================================================== --}}

    <div class="object-box">

        <div class="object-title">
            Objet du devis
        </div>

        <div class="object-text">

            Fourniture de matériel et/ou réalisation des prestations
            conformément aux besoins exprimés par le client.

        </div>

    </div>


    {{-- =========================================================
         TABLEAU
    ========================================================== --}}

    <table class="lines">

        <thead>

            <tr>

                <th class="col-num">
                    N°
                </th>

                <th class="col-designation">
                    Désignation
                </th>

                <th class="col-unite">
                    Unité
                </th>

                <th class="col-qty">
                    Qté
                </th>

                <th class="col-pu">
                    PU HT
                </th>

                <th class="col-total">
                    Montant HT
                </th>

            </tr>

        </thead>

        <tbody>

            @foreach($devis->lignes as $index => $ligne)

                <tr>

                    <td class="col-num">
                        {{ $index + 1 }}
                    </td>

                    <td class="col-designation">
                        <strong>
                            {{ $ligne->designation }}
                        </strong>
                    </td>

                    <td class="col-unite">
                        {{ $ligne->unite ?? 'U' }}
                    </td>

                    <td class="col-qty">
                        {{ $ligne->quantite }}
                    </td>

                    <td class="col-pu">

                        {{ number_format(
                            $ligne->prix_unitaire,
                            0,
                            ',',
                            ' '
                        ) }}

                    </td>

                    <td class="col-total">

                        <strong>

                            {{ number_format(
                                $ligne->total_ligne,
                                0,
                                ',',
                                ' '
                            ) }}

                        </strong>

                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>


    {{-- =========================================================
         CONDITIONS + TOTAUX
    ========================================================== --}}

    <table class="bottom-table">

        <tr>

            <td class="conditions-cell">

                <div class="conditions-box">

                    <div class="conditions-title">
                        Conditions commerciales
                    </div>

                    <div class="condition-row">

                        <span class="condition-label">
                            Validité :
                        </span>

                        Offre valable jusqu'au
                        {{ $devis->date_validite->format('d/m/Y') }}.

                    </div>

                    <div class="condition-row">

                        <span class="condition-label">
                            Délai :
                        </span>

                        {{ $devis->delai_realisation }}
                        jours après validation.

                    </div>

                    <div class="condition-row">

                        <span class="condition-label">
                            Paiement :
                        </span>

                        {{ $devis->conditions_paiement }}

                    </div>

                    <div class="condition-row">

                        <span class="condition-label">
                            Devise :
                        </span>

                        FCFA.

                    </div>

                </div>

            </td>


            <td class="totals-cell">

                <table class="totals">

                    <tr>

                        <td class="totals-label">
                            Montant HT
                        </td>

                        <td class="totals-value">

                            {{ number_format(
                                $devis->sous_total,
                                0,
                                ',',
                                ' '
                            ) }}

                            FCFA

                        </td>

                    </tr>


                    @if($devis->remise > 0)

                        <tr>

                            <td class="totals-label">
                                Remise
                            </td>

                            <td class="totals-value">

                                -

                                {{ number_format(
                                    $devis->remise,
                                    0,
                                    ',',
                                    ' '
                                ) }}

                                FCFA

                            </td>

                        </tr>

                    @endif


                    <tr>

                        <td class="totals-label">
                            TVA ({{ $devis->tva }}%)
                        </td>

                        <td class="totals-value">

                            {{ number_format(
                                $montantTva,
                                0,
                                ',',
                                ' '
                            ) }}

                            FCFA

                        </td>

                    </tr>


                    <tr class="total-final">

                        <td class="totals-label">
                            TOTAL TTC
                        </td>

                        <td class="totals-value">

                            {{ number_format(
                                $devis->total_ttc,
                                0,
                                ',',
                                ' '
                            ) }}

                            FCFA

                        </td>

                    </tr>

                </table>

            </td>

        </tr>

    </table>


    {{-- =========================================================
         MONTANT EN LETTRES
    ========================================================== --}}

    <div class="amount-words">

        <span class="amount-words-title">
            Montant arrêté :
        </span>

        La présente facture proforma est arrêtée à la somme de

        <strong>

            {{ number_format(
                $devis->total_ttc,
                0,
                ',',
                ' '
            ) }}

            FCFA TTC.

        </strong>

    </div>


    {{-- =========================================================
         SIGNATURE
    ========================================================== --}}

    <table class="signature-table">

        <tr>

            <td class="signature-left">
                &nbsp;
            </td>

            <td class="signature-right">

                <div class="signature-title">
                </div>

                @if($cachetBase64)

                    <div class="signature-image">

                        <img
                            src="data:image/png;base64,{{ $cachetBase64 }}"
                            alt="Signature et cachet"
                        >

                    </div>

                @else

                    <div class="signature-line"></div>

                @endif

                <div class="signature-name">

                </div>

                <div class="signature-role">
                </div>

            </td>

        </tr>

    </table>


    {{-- =========================================================
         PIED DE PAGE
    ========================================================== --}}

    <div class="footer">

        <div class="footer-company">
            PLANETECH SARL
        </div>

        04 BP : 0628 Cotonou •
        RCCM : RB/COT/13-B-9796 •
        IFU N° : 3201200295010

        <br>

        Compte Orabank :
        01592670139 Ora Bank
        • Capital social :
        1.000.000 FCFA

        <br>

        Tél :
        +229 64 54 99 59 /
        +229 68 60 18 05
        •
        Email :
        planetechsarlbenin@gmail.com

    </div>

</div>

</body>

</html>
