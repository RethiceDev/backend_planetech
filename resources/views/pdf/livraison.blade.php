<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau de Livraison - {{ $livraison->numero_bl }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.4; }
        .header { width: 100%; margin-bottom: 30px; border-bottom: 2px solid #3490dc; padding-bottom: 15px; }
        .header td { vertical-align: top; }
        .logo { max-height: 70px; }
        .company-info { text-align: right; font-size: 12px; color: #555; }
        .title-box { background-color: #f8fafc; border-left: 4px solid #3490dc; padding: 10px; margin-bottom: 25px; }
        .title-box h2 { margin: 0; color: #1e293b; font-size: 18px; }
        .details-table { width: 100%; margin-bottom: 25px; font-size: 13px; }
        .details-table td { padding: 4px 0; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th, .items-table td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; }
        .items-table th { background-color: #f1f5f9; color: #1e293b; font-size: 13px; }
        .text-right { text-align: right; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 11px; text-align: center; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .signatures { width: 100%; margin-top: 50px; }
        .signatures td { width: 50%; text-align: center; font-weight: bold; padding-top: 40px; }
    </style>
</head>
<body>

    <!-- En-tête -->
    <table class="header">
        <tr>
            <td>
                @if(!empty($logo))
                    <img src="{{ $logo }}" class="logo" alt="Logo">
                @else
                    <h2 style="margin:0; color:#3490dc;">ENTREPRISE</h2>
                @endif
            </td>
            <td class="company-info">
                <strong>Bordereau de Livraison</strong><br>
                Date : {{ $livraison->date_livraison->format('d/m/Y') }}<br>
                Réf BL : <strong>{{ $livraison->numero_bl }}</strong>
            </td>
        </tr>
    </table>

    <!-- Informations Client / Commande -->
    <div class="title-box">
        <h2>Client : {{ $commande->raison_sociale ?? $commande->nom }}</h2>
        <p style="margin: 5px 0 0 0; font-size: 13px;">
            Réf Commande : {{ $commande->reference }} |
            @if($commande->numero_bc)
                <strong>Bon de Commande N° : {{ $commande->numero_bc }}</strong>
            @endif
        </p>
    </div>

    <table class="details-table">
        <tr>
            <td><strong>Téléphone :</strong> {{ $commande->telephone ?? 'N/C' }}</td>
            <td><strong>Type de client :</strong> {{ ucfirst($commande->type_client) }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Email :</strong> {{ $commande->email ?? 'N/C' }}</td>
        </tr>
        @if($livraison->observation)
        <tr>
            <td colspan="2"><strong>Observation livraison :</strong> {{ $livraison->observation }}</td>
        </tr>
        @endif
    </table>

    <!-- Tableau des articles livrés -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Désignation / Article</th>
                <th class="text-right">Quantité Commandée (BC)</th>
                <th class="text-right">Quantité Livrée (Ce jour)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($livraison->items as $item)
            <tr>
                <td>{{ $item->commandeItem->designation }}</td>
                <td class="text-right">{{ $item->commandeItem->quantite }}</td>
                <td class="text-right"><strong>{{ $item->quantite_livree }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Signatures -->
    <table class="signatures">
        <tr>
            <td>Cachet & Signature (Livreur / Entreprise)</td>
            <td>Cachet & Signature (Client réceptionnaire)</td>
        </tr>
    </table>

    <!-- Pied de page -->
    <div class="footer">
        Document généré électroniquement - Page 1/1
    </div>

</body>
</html>
