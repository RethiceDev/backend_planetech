<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Proforma - {{ $commande->reference }}</title>
    <style>
        @page { margin: 30px 40px 80px 40px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 12px; line-height: 1.5; margin: 0; }

        /* ===== EN-TÊTE AVEC LOGO ===== */
        table.entete { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1b4d3e; margin-bottom: 20px; }
        table.entete td { vertical-align: middle; padding: 0 0 12px 0; }
        td.logo-cell { width: 110px; padding-right: 15px; }
        td.logo-cell img { width: 100px; height: auto; display: block; }
        td.txt-cell { text-align: center; }
        .nom { font-size: 22px; font-weight: bold; color: #1b4d3e; letter-spacing: 1px; margin-bottom: 4px; }
        .lignes { font-size: 9px; line-height: 1.5; color: #444; }

        .title { text-align: center; font-size: 20px; font-weight: bold; text-decoration: underline; margin: 20px 0 6px 0; letter-spacing: 0.5px; }
        .ref { text-align: center; font-size: 12px; color: #555; margin-bottom: 22px; }

        table.infos { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        table.infos td { vertical-align: top; width: 50%; padding: 0; }
        .bloc-titre { font-size: 10px; font-weight: bold; color: #1b4d3e; margin-bottom: 4px; }
        .bloc p { margin: 0 0 2px 0; }

        table.articles { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.articles th { background: #1b4d3e; color: #fff; font-size: 11px; text-align: left; padding: 8px 6px; }
        table.articles td { border-bottom: 1px solid #ddd; padding: 7px 6px; font-size: 11px; vertical-align: top; }
        .c { text-align: center; }
        .r { text-align: right; white-space: nowrap; }
        .indispo td { color: #999; font-style: italic; }

        table.total { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.total td { padding: 7px 8px; font-size: 12px; font-weight: bold; border: 1px solid #999; }
        table.total td.lib { text-align: center; width: 70%; }
        table.total td.val { text-align: right; white-space: nowrap; }
        table.total tr.ttc td { background: #f2f7f4; color: #1b4d3e; font-size: 13px; }

        .arrete { margin-top: 16px; font-size: 12px; text-align: justify; }

        table.signature { width: 100%; border-collapse: collapse; margin-top: 18px; page-break-inside: avoid; }
        table.signature td.vide { width: 55%; }
        table.signature td.sig { width: 45%; text-align: center; vertical-align: top; }
        .sig-titre { font-weight: bold; text-decoration: underline; font-size: 12px; }
        .sig-image { height: 115px; margin: 4px 0; }
        .sig-image img { max-height: 115px; max-width: 100%; }
        .sig-nom { font-weight: bold; font-size: 12px; }

        .note { margin-top: 22px; font-size: 10px; color: #555; }
        .footer-date { text-align: right; margin-top: 30px; font-style: italic; }

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
                <th class="c" style="width: 10%;">Qté</th>
                <th class="r" style="width: 20%;">Prix unitaire HT</th>
                <th class="r" style="width: 20%;">Montant HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commande->items as $i => $item)
                @if($item->disponible)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->designation }}</td>
                        <td class="c">{{ $item->quantite }}</td>
                        <td class="r">{{ number_format((float) $item->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                        <td class="r">{{ number_format((float) $item->total_ligne, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @else
                    <tr class="indispo">
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->designation }}</td>
                        <td class="c">{{ $item->quantite }}</td>
                        <td class="r" colspan="2">Indisponible</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <table class="total">
        <tr>
            <td class="lib">MONTANT TOTAL HT</td>
            <td class="val">{{ number_format($montantHt, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td class="lib">TVA : {{ $tauxTva }}%</td>
            <td class="val">{{ number_format($montantTva, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr class="ttc">
            <td class="lib">MONTANT TTC</td>
            <td class="val">{{ number_format($montantTtc, 0, ',', ' ') }} FCFA</td>
        </tr>
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

    @php $signataire = ''; @endphp

    <table class="signature">
        <tr>
            <td class="vide"></td>
            <td class="sig">
                <div class="sig-titre"></div>
                <div class="sig-image">
                    @if(!empty($cachet))
                        <img src="{{ $cachet }}" alt="Signature et cachet">
                    @endif
                </div>
                <div class="sig-nom">{{ $signataire }}</div>
            </td>
        </tr>
    </table>

    <div class="legal-footer">
        04 BP : 0624 Cotonou . Compte Bancaire : 010002670139 Ora Bank . RCCM : RB/COT/13-B-9794 - IFU N° : 3201300290010<br>
        Capital social : 1.000.000 FCFA - Tél : +229 01 64 54 99 59 / 0168 68 18 85 - Email : planetechsarlbenin@gmail.com
    </div>

</body>
</html>
