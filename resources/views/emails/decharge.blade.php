<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de Décharge - PLANETECH SARL</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial, Helvetica, sans-serif;">

    <div style="max-width:650px; margin:30px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">

        <!-- HEADER -->
        <div style="background:#1b4d3e; padding:25px; text-align:center;">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="PLANETECH SARL" width="120" style="display:block; margin:0 auto 12px; border:0;">
            @endif
            <h1 style="margin:0; color:#ffffff; font-size:24px;">PLANETECH SARL</h1>
            <p style="margin:6px 0 0; color:#d1fae5; font-size:14px; font-weight:500;">Attestation & Fiche de Décharge Officielle</p>
        </div>

        <!-- CONTENU -->
        <div style="padding:30px;">
            <p style="font-size:15px; color:#374151;">
                Bonjour <strong>{{ $decharge->nom }}</strong>,
            </p>

            <p style="font-size:15px; line-height:1.7; color:#4b5563;">
                Veuillez trouver ci-dessous le récapitulatif officiel de la décharge n° <strong>{{ $decharge->reference }}</strong> établie le {{ $decharge->created_at->format('d/m/Y à H:i') }}.
            </p>

            <!-- INFOS SIGNATAIRE -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px; margin:20px 0;">
                <h3 style="margin:0 0 10px; color:#0f172a; font-size:15px; border-bottom:1px solid #cbd5e1; padding-bottom:6px;">Partie Prenante</h3>
                <p style="margin:4px 0; font-size:14px; color:#334155;"><strong>Nom & Prénom :</strong> {{ $decharge->nom }}</p>
                <p style="margin:4px 0; font-size:14px; color:#334155;"><strong>Rôle :</strong> {{ $decharge->role }}</p>
                @if($decharge->entreprise)
                    <p style="margin:4px 0; font-size:14px; color:#334155;"><strong>Entreprise :</strong> {{ $decharge->entreprise }}</p>
                @endif
                <p style="margin:4px 0; font-size:14px; color:#334155;"><strong>Téléphone :</strong> {{ $decharge->numero }}</p>
                <p style="margin:4px 0; font-size:14px; color:#334155;"><strong>E-mail :</strong> {{ $decharge->email }}</p>
            </div>

            <!-- DETAILS SELON TYPE -->
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:20px; margin:20px 0;">
                <h3 style="margin:0 0 12px; color:#166534; font-size:16px;">
                    Détails du Transfert (Type : {{ strtoupper($decharge->type) }})
                </h3>

                @if($decharge->type === 'argent')
                    <p style="margin:0 0 8px; color:#166534; font-size:15px;">
                        <strong>Montant :</strong> {{ number_format((float) $decharge->somme, 0, ',', ' ') }} FCFA
                    </p>
                    <p style="margin:0; color:#166534; font-size:14px;">
                        <strong>Motif :</strong> {{ $decharge->motif }}
                    </p>
                @elseif($decharge->type === 'materiel')
                    <table style="width:100%; border-collapse:collapse; margin-top:10px;">
                        <thead>
                            <tr style="background:#dcfce7; text-align:left;">
                                <th style="padding:8px; font-size:13px; color:#166534; border:1px solid #bbf7d0;">Désignation</th>
                                <th style="padding:8px; font-size:13px; color:#166534; border:1px solid #bbf7d0; width:70px;">Qté</th>
                                <th style="padding:8px; font-size:13px; color:#166534; border:1px solid #bbf7d0; width:120px;">État</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($decharge->items as $item)
                                <tr>
                                    <td style="padding:8px; font-size:13px; color:#14532d; border:1px solid #bbf7d0;">{{ $item->designation }}</td>
                                    <td style="padding:8px; font-size:13px; color:#14532d; border:1px solid #bbf7d0; text-align:center;">{{ $item->quantite }}</td>
                                    <td style="padding:8px; font-size:13px; color:#14532d; border:1px solid #bbf7d0;">{{ ucfirst(str_replace('_', ' ', $item->etat)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @elseif($decharge->type === 'responsabilite')
                    <p style="margin:0; color:#166534; font-size:14px;">
                        <strong>Description :</strong><br>{!! nl2br(e($decharge->description)) !!}
                    </p>
                @endif
            </div>

            <!-- SIGNATURE -->
            @php
                $sigRaw = preg_replace('#^data:image/\w+;base64,#i', '', (string) $decharge->signature_data);
                $sigBin = base64_decode($sigRaw, true);
            @endphp
            @if($sigBin)
                <div style="margin-top:25px; text-align:center; background:#ffffff; border:1px dashed #cbd5e1; border-radius:10px; padding:15px;">
                    <p style="margin:0 0 10px; font-size:13px; color:#64748b; font-weight:bold;">SIGNATURE NUMÉRIQUE ENREGISTRÉE</p>
                    <img src="{{ $message->embedData($sigBin, 'signature.png', 'image/png') }}" alt="Signature" style="max-height:80px; width:auto;">
                </div>
            @endif
                        <p style="font-size:13px; color:#64748b; margin-top:20px;">
                            📎 Le document officiel au format PDF est joint à cet e-mail.
                        </p>

            <p style="font-size:14px; margin-top:30px; color:#374151;">
                Cordialement,<br><strong>L'équipe PLANETECH SARL</strong>
            </p>
        </div>

        <div style="background:#f9fafb; padding:18px; text-align:center; border-top:1px solid #f1f5f9;">
            <p style="margin:0; font-size:12px; color:#9ca3af;">
                © {{ date('Y') }} PLANETECH SARL — Document sécurisé.
            </p>
        </div>

    </div>
</body>
</html>
