@php
    // Logo : chargé automatiquement si le contrôleur ne le fournit pas
    if (empty($logo)) {
        $cheminLogo = public_path('images/logo.png');
        if (is_file($cheminLogo)) {
            $logo = 'data:image/png;base64,' . base64_encode(file_get_contents($cheminLogo));
        }
    }

    // Signature + cachet : chargés automatiquement si le contrôleur ne les fournit pas
    if (empty($cachet)) {
        $cheminCachet = storage_path('app/private/cachet-signature.png');
        if (is_file($cheminCachet)) {
            $cachet = 'data:image/png;base64,' . base64_encode(file_get_contents($cheminCachet));
        }
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Proforma - {{ $commande->reference }}</title>
    <style>
        @page { margin: 30px 40px 90px 40px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 12px; line-height: 1.5; margin: 0; }

        /* ===== EN-TÊTE AVEC LOGO ===== */
        table.entete { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1b4d3e; margin-bottom: 18px; }
        table.entete td { vertical-align: middle; padding: 0 0 12px 0; }
        td.logo-cell { width: 110px; padding-right: 15px; }
        td.logo-cell img { width: 100px; height: auto; display: block; }
        td.txt-cell { text-align: center; }
        .nom { font-size: 22px; font-weight: bold; color: #1b4d3e; letter-spacing: 1px; margin-bottom: 4px; }
        .lignes { font-size: 9px; line-height: 1.5; color: #444; }

        /* ===== TITRE ===== */
        .title { text-align: center; font-size: 20px; font-weight: bold; text-decoration: underline; margin: 16px 0 6px 0; letter-spacing: 0.5px; }
        .ref { text-align: center; font-size: 12px; color: #555; margin-bottom: 18px; }

        /* ===== CLIENT / DATE ===== */
        table.infos { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.infos td { vertical-align: top; width: 50%; padding: 0; }
        .bloc-titre { font-size: 10px; font-weight: bold; color: #1b4d3e; margin-bottom: 4px; }
        .bloc p { margin: 0 0 2px 0; }

        /* ===== TABLEAU DES ARTICLES (quadrillé) ===== */
        table.articles { width: 100%; border-collapse: collapse; }
        table.articles th {
            background: #1b4d3e; color: #fff; font-size: 11px; text-align: center;
            padding: 7px 5px; border: 1px solid #000;
        }
        table.articles td {
            border: 1px solid #000; padding: 5px 6px; font-size: 11px; vertical-align: middle;
        }
        table.articles tr { page-break-inside: avoid; }
        .c { text-align: center; }
        .r { text-align: right; white-space: nowrap; }
        .indispo td { color: #888; font-style: italic; }

        /* ===== TOTAUX (dans le même tableau, alignés sur les colonnes) ===== */
        table.articles tr.tot td { font-weight: bold; font-size: 12px; }
        table.articles tr.tot td.lib { text-align: center; }
        table.articles tr.tot td.val { text-align: right; white-space: nowrap; }
        table.articles tr.ttc td { background: #f2f7f4; color: #1b4d3e; font-size: 13px; }

        /* ===== ARRÊTÉ ===== */
        .arrete { margin-top: 14px; font-size: 12px; text-align: justify; }

        .note { margin-top: 12px; font-size: 10px; color: #555; }
        .footer-date { text-align: right; margin-top: 18px; font-style: italic; }

        /* ===== SIGNATURE + CACHET ===== */
        table.signature { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: avoid; }
        table.signature td.vide { width: 55%; }
        table.signature td.sig { width: 45%; text-align: center; vertical-align: top; }
        .sig-image { height: 150px; }
        .sig-image img { max-height: 150px; max-width: 100%; }

        /* ===== PIED DE PAGE ===== */
        .legal-footer {
            position: fixed; bottom: -60px; left: 0; right: 0;
            text-align: center; font-size: 9px; font-style: italic; font-weight: bold;
            color: #1b4d3e; border-top: 1.5px solid #1b4d3e; padding-top: 8px; line-height: 1.6;
        }
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

    <div class="title">FACTURE PROFORMA</div>
    <div class="ref">
        N° {{ str_replace('CMD-', 'PRO-', $commande->reference) }}
        &nbsp;-&nbsp; Commande {{ $commande->reference }}
    </div>

    <table class="infos">
        <tr>
            <td class="bloc">
                <div class="bloc-titre">CLIENT</div>
                @if($commande->type_client === 'entreprise' && $commande->raison_sociale)
                    <p><strong>{{ $commande->raison_sociale }}</strong></p>
                    <p>Contact : {{ $commande->nom }}</p>
                @else
                    <p><strong>{{ $commande->nom }}</strong></p>
                @endif
                <p>{{ $commande->telephone }}</p>
                <p>{{ $commande->email }}</p>
            </td>
            <td class="bloc" style="text-align: right;">
                <div class="bloc-titre">DATE</div>
                <p>Cotonou, le {{ now()->format('d/m/Y') }}</p>
            </td>
        </tr>
    </table>

    <table class="articles">
        <thead>
            <tr>
                <th style="width: 6%;">N°</th>
                <th>Désignation</th>
                <th style="width: 9%;">Qté</th>
                <th style="width: 19%;">Prix unitaire HT</th>
                <th style="width: 19%;">Montant HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commande->items as $i => $item)
                @if($item->disponible)
                    <tr>
                        <td class="c">{{ $i + 1 }}</td>
                        <td>{{ $item->designation }}</td>
                        <td class="c">{{ $item->quantite }}</td>
                        <td class="r">{{ number_format((float) $item->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                        <td class="r">{{ number_format((float) $item->total_ligne, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @else
                    <tr class="indispo">
                        <td class="c">{{ $i + 1 }}</td>
                        <td>{{ $item->designation }}</td>
                        <td class="c">{{ $item->quantite }}</td>
                        <td class="c" colspan="2">Indisponible</td>
                    </tr>
                @endif
            @endforeach

            <tr class="tot">
                <td class="lib" colspan="4">MONTANT TOTAL HT</td>
                <td class="val">{{ number_format($montantHt, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr class="tot">
                <td class="lib" colspan="4">TVA : {{ $tauxTva }}%</td>
                <td class="val">{{ number_format($montantTva, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr class="tot ttc">
                <td class="lib" colspan="4">MONTANT TTC</td>
                <td class="val">{{ number_format($montantTtc, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tbody>
    </table>

    <div class="arrete">
        Arrêté la présente <strong>FACTURE PROFORMA</strong> à la somme de :
        <strong>{{ $montantEnLettres }} ({{ number_format($montantTtc, 0, ',', ' ') }}) FRANCS CFA TTC</strong>
    </div>

    <div class="note">
        Les articles indisponibles ne sont pas compris dans le montant.
        Ce document est une facture proforma : il ne constitue pas une facture définitive.
    </div>

    <div class="footer-date">
        Fait à Cotonou, le {{ now()->format('d/m/Y') }}
    </div>

    <table class="signature">
        <tr>
            <td class="vide"></td>
            <td class="sig">
                <div class="sig-image">
                    @if(!empty($cachet))
                        <img src="{{ $cachet }}" alt="Signature et cachet">
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="legal-footer">
        04 BP : 0624 Cotonou . Compte Bancaire : 010002670139 Ora Bank . RCCM : RB/COT/13-B-9794 - IFU N° : 3201300290010<br>
        Capital social : 1.000.000 FCFA - Tél : +229 01 64 54 99 59 / 0168 68 18 85 - Email : planetechsarlbenin@gmail.com
    </div>

</body>
</html>
