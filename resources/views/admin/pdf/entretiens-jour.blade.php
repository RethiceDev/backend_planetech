<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Registre d'arrivée - {{ $dateJour }}</title>

    <style>
        @page { margin: 30px 35px 55px 35px; }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
        }

        .bandeau {
            background: #065f46;
            color: #ffffff;
            padding: 18px 22px;
            border-radius: 6px;
        }

        .bandeau table { width: 100%; border-collapse: collapse; }
        .bandeau td { vertical-align: middle; border: 0; padding: 0; }

        .societe {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #a7f3d0;
        }

        .titre { font-size: 20px; font-weight: bold; margin-top: 4px; }
        .date  { font-size: 11px; color: #d1fae5; margin-top: 3px; text-transform: capitalize; }

        .total {
            text-align: right;
        }
        .total .nombre { font-size: 28px; font-weight: bold; }
        .total .libelle { font-size: 9px; color: #a7f3d0; text-transform: uppercase; letter-spacing: 1px; }

        table.liste {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        table.liste thead th {
            background: #ecfdf5;
            color: #065f46;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: left;
            padding: 9px 8px;
            border-bottom: 2px solid #059669;
        }

        table.liste tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        table.liste tbody tr { page-break-inside: avoid; }
        table.liste tbody tr:nth-child(even) td { background: #f8fafc; }

        .rang { font-weight: bold; color: #065f46; width: 30px; }
        .nom  { font-weight: bold; text-transform: uppercase; }
        .poste { color: #065f46; }
        .heure { font-weight: bold; text-align: center; width: 55px; }
        .signature { width: 110px; border-left: 1px dashed #cbd5e1; }

        .vide {
            text-align: center;
            padding: 50px 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .pied {
            position: fixed;
            bottom: -35px;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }

        .pied table { width: 100%; border-collapse: collapse; }
        .pied td { border: 0; padding: 0; }
        .pied .droite { text-align: right; }
        .num-page:after { content: counter(page); }
    </style>
</head>

<body>

    <div class="pied">
        <table>
            <tr>
                <td>PLANETECH SARL · Registre d'arrivée des entretiens · Généré le {{ $genereLe }}</td>
                <td class="droite">Page <span class="num-page"></span></td>
            </tr>
        </table>
    </div>

    <div class="bandeau">
        <table>
            <tr>
                <td>
                    <div class="societe">Planetech Recrutement</div>
                    <div class="titre">Registre d'Arrivée</div>
                    <div class="date">{{ $dateJour }}</div>
                </td>
                <td class="total">
                    <div class="nombre">{{ $entretiens->count() }}</div>
                    <div class="libelle">Candidat{{ $entretiens->count() > 1 ? 's' : '' }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if($entretiens->count() > 0)
        <table class="liste">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Nom</th>
                    <th>Prénom(s)</th>
                     <th>Telephone</th>
                    <th>Poste visé</th>
                    <th style="text-align:center;">Arrivée</th>
                    <th>Émargement</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entretiens as $item)
                    <tr>
                        <td class="rang">{{ $loop->iteration }}</td>
                        <td class="nom">{{ $item->nom }}</td>
                        <td>{{ $item->prenoms }}</td>
                        <td>{{ $item->telephone }}</td>
                        <td class="poste">{{ $item->poste }}</td>
                        <td class="heure">{{ $item->created_at->format('H:i') }}</td>
                        <td class="signature">&nbsp;</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="vide">Aucun candidat enregistré aujourd'hui.</div>
    @endif

</body>
</html>
