<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <style>
        @page { margin: 25px 30px 30px 30px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #27323a;
            margin: 0;
        }

        table { width: 100%; border-collapse: collapse; }

        .header { margin-bottom: 12px; }
        .logo-cell { width: 22%; vertical-align: middle; }
        .company-cell { width: 48%; vertical-align: middle; }
        .order-cell { width: 30%; vertical-align: middle; text-align: right; }
        .logo { width: 85px; height: auto; }

        .company-name {
            font-size: 21px;
            font-weight: bold;
            color: #176b4d;
            letter-spacing: 1px;
        }

        .company-description {
            margin-top: 5px;
            font-size: 7.5px;
            line-height: 1.45;
            color: #66736c;
        }

        .order-title {
            font-size: 17px;
            font-weight: bold;
            color: #176b4d;
            text-transform: uppercase;
        }

        .order-reference { margin-top: 5px; font-size: 9px; font-weight: bold; }
        .order-date { margin-top: 3px; font-size: 8px; color: #6b7280; }

        .header-line {
            height: 3px;
            background-color: #176b4d;
            margin-bottom: 15px;
        }

        .client-table { margin-bottom: 17px; }

        .client-card {
            border: 1px solid #dce8e2;
            background-color: #f8fbf9;
            padding: 9px;
        }

        .client-left { width: 50%; padding-right: 5px; vertical-align: top; }
        .client-right { width: 50%; padding-left: 5px; vertical-align: top; }

        .label {
            font-size: 7.5px;
            text-transform: uppercase;
            font-weight: bold;
            color: #176b4d;
            margin-bottom: 5px;
        }

        .client-name { font-size: 11px; font-weight: bold; color: #1f2937; }
        .client-info { margin-top: 4px; font-size: 8.5px; line-height: 1.5; color: #59645f; }

        .section-title {
            margin-bottom: 7px;
            font-size: 10px;
            font-weight: bold;
            color: #176b4d;
            text-transform: uppercase;
        }

        .items-table { margin-top: 5px; }

        .items-table th {
            padding: 7px 5px;
            background-color: #176b4d;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            text-align: left;
        }

        .items-table td {
            padding: 7px 5px;
            border-bottom: 1px solid #e5ebe8;
            font-size: 8.5px;
        }

        .items-table tr:nth-child(even) td { background-color: #f8fbf9; }

        .center { text-align: center; }
        .right { text-align: right; }

        .num { width: 8%; }
        .designation { width: 72%; }
        .quantity { width: 20%; }

        .summary-box {
            margin-top: 15px;
            border: 1px solid #dce8e2;
            background-color: #f8fbf9;
            padding: 10px;
        }

        .summary-title {
            font-size: 8px;
            text-transform: uppercase;
            color: #176b4d;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .summary-text { font-size: 8.5px; line-height: 1.6; color: #59645f; }

        .footer {
            margin-top: 25px;
            padding-top: 7px;
            border-top: 2px solid #176b4d;
            text-align: center;
            font-size: 7px;
            line-height: 1.5;
            color: #6b7280;
        }

        .footer-name { color: #176b4d; font-weight: bold; font-size: 8px; }
    </style>
</head>

<body>

@php
    $logoPath = public_path('images/planetech-logo.png');

    if (!file_exists($logoPath)) {
        $logoPath = public_path('images/logo.png');
    }

    $logoBase64 = null;

    if (file_exists($logoPath)) {
        $logoBase64 = base64_encode(file_get_contents($logoPath));
    }
@endphp

<table class="header">
    <tr>
        <td class="logo-cell">
            @if($logoBase64)
                <img src="data:image/png;base64,{{ $logoBase64 }}" class="logo" alt="PLANETECH SARL">
            @endif
        </td>

        <td class="company-cell">
            <div class="company-name">PLANETECH SARL</div>

            <div class="company-description">
                Tous Travaux de BTP • Conception • Études et réalisation d'ouvrages
                <br>
                Matériel électrique • Plomberie • Réseau informatique • Téléphonie
                <br>
                Climatisation • Vidéosurveillance • Alarmes
                <br>
                Expertise et maîtrise d'ouvrage • Commerce général
            </div>
        </td>

        <td class="order-cell">
            <div class="order-title">Bon de commande</div>
            <div class="order-reference">N° {{ $commande->reference }}</div>
            <div class="order-date">
                Date : {{ $commande->created_at->format('d/m/Y à H:i') }}
            </div>
        </td>
    </tr>
</table>

<div class="header-line"></div>

<table class="client-table">
    <tr>
        <td class="client-left">
            <div class="client-card">
                <div class="label">Client</div>

                <div class="client-name">
                    @if($commande->type_client === 'entreprise')
                        {{ strtoupper($commande->raison_sociale) }}
                    @else
                        {{ strtoupper($commande->nom) }}
                    @endif
                </div>

                @if($commande->type_client === 'entreprise')
                    <div class="client-info">Contact : {{ $commande->nom }}</div>
                @endif
            </div>
        </td>

        <td class="client-right">
            <div class="client-card">
                <div class="label">Coordonnées</div>

                <div class="client-info">
                    Email : {{ $commande->email }}
                    <br>
                    Téléphone : {{ $commande->telephone }}
                </div>
            </div>
        </td>
    </tr>
</table>

<div class="section-title">Détail de la commande</div>

<table class="items-table">
    <thead>
        <tr>
            <th class="num center">N°</th>
            <th class="designation">Désignation</th>
            <th class="quantity center">Quantité</th>
        </tr>
    </thead>

    <tbody>
        @foreach($commande->items as $index => $item)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $item->designation }}</td>
                <td class="center"><strong>{{ $item->quantite }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="summary-box">
    <div class="summary-title">Informations</div>

    <div class="summary-text">
        Référence : <strong>{{ $commande->reference }}</strong>
        <br>
        Statut : Commande reçue
        <br>
        Les prix et la disponibilité des articles seront communiqués
        au client après vérification par PLANETECH SARL.
    </div>
</div>

<div class="footer">
    <div class="footer-name">PLANETECH SARL</div>

    04 BP : 0628 Cotonou •
    RCCM : RB/COT/13-B-9796 •
    IFU N° : 3201200295010

    <br>

    Tél : +229 64 54 99 59 / +229 68 60 18 05

    •

    Email : {{ config('mail.from.address') }}
</div>

</body>
</html>
