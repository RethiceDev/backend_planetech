<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des entretiens</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0; color: #0f2c23; }
        .sous-titre { margin: 4px 0 14px; color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1b4d3e; color: #fff; text-align: left; padding: 7px 6px; font-size: 9px; text-transform: uppercase; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        .nom { font-weight: bold; text-transform: uppercase; }
        .centre { text-align: center; }
        .droite { text-align: right; }
        .pied { margin-top: 14px; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>

    <h1>PLANETECH — Liste des entretiens</h1>
    <p class="sous-titre">
        Édité le {{ now()->format('d/m/Y à H:i') }}
        · {{ $entretiens->count() }} candidat{{ $entretiens->count() > 1 ? 's' : '' }}
        @if($recherche) · Recherche : « {{ $recherche }} » @endif
    </p>

    <table>
        <thead>
            <tr>
                <th class="centre" style="width: 6%">N°</th>
                <th style="width: 30%">Candidat</th>
                <th style="width: 18%">Téléphone</th>
                <th style="width: 26%">Poste visé</th>
                <th style="width: 12%">Date</th>
                <th class="droite" style="width: 8%">Heure</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entretiens as $item)
                <tr>
                    <td class="centre">{{ $loop->iteration }}</td>
                    <td>
                        <span class="nom">{{ $item->nom }}</span><br>
                        {{ $item->prenoms }}
                    </td>
                    <td>{{ $item->telephone ?: '—' }}</td>
                    <td>{{ $item->poste }}</td>
                    <td>{{ $item->created_at->format('d/m/Y') }}</td>
                    <td class="droite">{{ $item->created_at->format('H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="centre">Aucun entretien trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="pied">Document généré automatiquement par l'administration PLANETECH.</p>

</body>
</html>
