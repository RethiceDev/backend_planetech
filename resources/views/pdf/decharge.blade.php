@php
    use Carbon\Carbon;

    $etats = [
        'neuf'                  => 'Neuf',
        'partiellement_utilise' => 'Partiellement utilisé',
        'vieux'                 => 'Usagé',
        'defectueux'            => 'Défectueux',
    ];

    $titres = [
        'argent'         => 'DÉCHARGE DE REMISE DE FONDS',
        'materiel'       => 'DÉCHARGE DE REMISE DE MATÉRIEL',
        'responsabilite' => 'DÉCHARGE DE RESPONSABILITÉ',
    ];
    $titre = $titres[$decharge->type] ?? 'DÉCHARGE';

    $dateLongue = Carbon::parse($decharge->created_at)->locale('fr')->translatedFormat('j F Y');
    $heure      = Carbon::parse($decharge->created_at)->format('H\hi');
    $lieu       = config('decharge.lieu');

    // Montant en lettres (nécessite l'extension PHP intl ; sinon on l'omet)
    $montantLettres = null;
    if ($decharge->type === 'argent' && class_exists(\NumberFormatter::class)) {
        $f = new \NumberFormatter('fr', \NumberFormatter::SPELLOUT);
        $txt = $f->format((int) round((float) $decharge->somme));
        if ($txt !== false) {
            $montantLettres = mb_strtoupper(mb_substr($txt, 0, 1)) . mb_substr($txt, 1) . ' francs CFA';
        }
    }

    // Accords singulier / pluriel pour le matériel
    $nbLignes = $decharge->items->count();
    $nbTotal  = (int) $decharge->items->sum('quantite');
    $pluriel  = $nbTotal > 1;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $decharge->reference }}</title>
    <style>
        @page { margin: 28px 40px 50px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11.5px; color: #1f2937; line-height: 1.6; }

        .entete { width: 100%; border-collapse: collapse; border-bottom: 3px double #000000; margin-bottom: 6px; }
.entete td { vertical-align: middle; padding: 0 0 6px 0; }
.entete .logo-cell { width: 95px; text-align: left; }
.entete .logo-cell img { width: 85px; }
.entete .txt-cell { text-align: center; }
.entete .nom { font-family: 'Times', serif; font-size: 30px; font-weight: bold; letter-spacing: 2px; color: #000000; line-height: 1.1; border-bottom: 1px solid #000000; padding-bottom: 3px; margin-bottom: 4px; }
.entete .lignes { font-family: 'Times', serif; font-size: 9.5px; font-style: italic; font-weight: bold; color: #000000; line-height: 1.45; }
        .titre { text-align: center; margin: 22px 0 4px; font-size: 16px; font-weight: bold; color: #1b4d3e; letter-spacing: 0.5px; }
        .ref { text-align: center; font-size: 10.5px; color: #6b7280; margin-bottom: 20px; }

        p.texte { text-align: justify; margin: 0 0 10px; }

        .encadre { border: 1px solid #bbf7d0; background: #f0fdf4; padding: 10px 14px; margin: 12px 0; }

        table.list { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }
        table.list th { background: #1b4d3e; color: #ffffff; padding: 6px; text-align: left; font-size: 10.5px; border: 1px solid #1b4d3e; }
        table.list td { border: 1px solid #cbd5e1; padding: 6px; font-size: 10.5px; }

        .conclusion { margin-top: 16px; }

        table.sign { width: 100%; margin-top: 26px; border-collapse: collapse; page-break-inside: avoid; }
        table.sign td { width: 50%; vertical-align: top; text-align: center; padding: 0 10px; }
        .sign-titre { font-weight: bold; font-size: 11px; margin-bottom: 6px; }
        .sign-box { height: 90px; border-bottom: 1px solid #94a3b8; }
        .sign-box img { max-height: 80px; max-width: 200px; }
        .sign-nom { font-size: 10.5px; margin-top: 4px; color: #475569; }

        .footer { position: fixed; bottom: -30px; left: 0; right: 0; text-align: center; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 6px; }
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
    <div class="titre">{{ $titre }}</div>
    <div class="ref">Référence : <strong>{{ $decharge->reference }}</strong></div>

    {{-- ===================== PRÉAMBULE (commun) ===================== --}}
    <p class="texte">
        Je soussigné(e), <strong>{{ $decharge->nom }}</strong>,
        agissant en qualité de <strong>{{ $decharge->role }}</strong>@if($decharge->entreprise)
        au sein de <strong>{{ $decharge->entreprise }}</strong>@endif,
        joignable au <strong>{{ $decharge->numero }}</strong>
        et à l'adresse électronique <strong>{{ $decharge->email }}</strong>,
        reconnais et déclare par la présente ce qui suit :
    </p>

    {{-- ===================== CORPS SELON LE TYPE ===================== --}}
    @if($decharge->type === 'argent')

        <p class="texte">
            Avoir reçu de la société <strong>PLANETECH SARL</strong>, ce jour, la somme de
            <strong>{{ number_format((float) $decharge->somme, 0, ',', ' ') }} FCFA</strong>@if($montantLettres)
            (<em>{{ $montantLettres }}</em>)@endif,
            au titre de : <strong>{{ $decharge->motif }}</strong>.
        </p>

        <p class="texte">
            Je m'engage à affecter cette somme exclusivement à l'usage pour lequel elle m'a été remise,
            et à en justifier l'emploi auprès de PLANETECH SARL sur simple demande, pièces à l'appui.
            Tout reliquat éventuel devra lui être restitué dans les meilleurs délais.
        </p>

        <p class="texte">
            En conséquence, <strong>PLANETECH SARL est déchargée de toute obligation de remise</strong>
            relative à cette somme, dont je reconnais avoir pris possession intégralement.
        </p>

    @elseif($decharge->type === 'materiel')

        <p class="texte">
            Avoir reçu de la société <strong>PLANETECH SARL</strong>, ce jour,
            {{ $pluriel ? 'les matériels et/ou documents désignés' : 'le matériel ou document désigné' }}
            ci-après, {{ $pluriel ? 'dans l\'état' : 'dans l\'état' }} indiqué pour chacun :
        </p>

        <table class="list">
            <thead>
                <tr>
                    <th width="28">N°</th>
                    <th>Désignation</th>
                    <th width="40" style="text-align:center;">Qté</th>
                    <th width="115">État à la remise</th>
                </tr>
            </thead>
            <tbody>
                @foreach($decharge->items as $i => $item)
                    <tr>
                        <td style="text-align:center;">{{ $i + 1 }}</td>
                        <td>{{ $item->designation }}</td>
                        <td style="text-align:center;">{{ $item->quantite }}</td>
                        <td>{{ $etats[$item->etat] ?? ucfirst(str_replace('_', ' ', $item->etat)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="texte">
            Je reconnais {{ $pluriel ? 'les avoir reçus' : 'l\'avoir reçu' }} en l'état mentionné ci-dessus,
            et m'engage à {{ $pluriel ? 'les' : 'le' }} conserver avec soin, à {{ $pluriel ? 'les' : 'l\'' }}utiliser
            exclusivement dans le cadre de ma mission et à {{ $pluriel ? 'les' : 'le' }} restituer à
            PLANETECH SARL, sur simple demande, en bon état, sous réserve de l'usure normale.
        </p>

        <p class="texte">
            En cas de perte, de vol ou de détérioration qui me serait imputable, j'en assumerai la responsabilité.
            <strong>PLANETECH SARL est déchargée de toute obligation de remise</strong>
            pour {{ $pluriel ? 'les éléments' : 'l\'élément' }} listé{{ $pluriel ? 's' : '' }} ci-dessus.
        </p>

    @elseif($decharge->type === 'responsabilite')

        <p class="texte">
            Assumer, en toute connaissance de cause, l'entière responsabilité de la situation,
            de l'opération ou de l'engagement décrit ci-dessous :
        </p>

        <div class="encadre">
            {!! nl2br(e($decharge->description)) !!}
        </div>

        <p class="texte">
            Je déclare avoir été pleinement informé(e) des conditions et des risques qui s'y rattachent,
            et agir de ma propre initiative. En conséquence,
            <strong>PLANETECH SARL est dégagée de toute responsabilité</strong>
            à ce titre, pour tout dommage, préjudice ou litige qui pourrait en résulter.
        </p>

    @endif

    {{-- ===================== CONCLUSION (commune) ===================== --}}
    <p class="texte conclusion">
        La présente décharge est établie pour servir et valoir ce que de droit.
    </p>

    <p class="texte">
        Fait @if($lieu) à <strong>{{ $lieu }}</strong>, @endif le <strong>{{ $dateLongue }}</strong> à {{ $heure }}.
    </p>

    {{-- ===================== SIGNATURES ===================== --}}
    <table class="sign">
        <tr>
            <td>
                <div class="sign-titre">Le déclarant<br><span style="font-weight:normal; font-size:9.5px;">(précédé de la mention « Lu et approuvé »)</span></div>
                <div class="sign-box">
                    @if($decharge->signature_data)
                        <img src="{{ $decharge->signature_data }}" alt="Signature">
                    @endif
                </div>
                <div class="sign-nom">{{ $decharge->nom }}</div>
            </td>
            <td>
                <div class="sign-titre">Pour PLANETECH SARL<br><span style="font-weight:normal; font-size:9.5px;">(cachet et signature)</span></div>
                <div class="sign-box"></div>
                <div class="sign-nom">&nbsp;</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        PLANETECH SARL — Décharge {{ $decharge->reference }} — Signée numériquement le {{ $dateLongue }} — © {{ date('Y') }}
    </div>

</body>
</html>
