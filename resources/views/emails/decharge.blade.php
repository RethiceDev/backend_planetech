<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche de Décharge - PLANETECH SARL</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif;">

    <div style="max-width:650px; margin:30px auto; background-color:#ffffff; border-radius:12px; overflow:hidden;">

        <!-- EN-TÊTE -->
        <div style="background-color:#1b4d3e; padding:25px; text-align:center;">

            @if(file_exists(public_path('images/logo.png')))
                <img
                    src="{{ $message->embed(public_path('images/logo.png')) }}"
                    alt="Logo PLANETECH SARL"
                    width="120"
                    style="display:block; margin:0 auto 12px; border:0; max-width:100%; height:auto;"
                >
            @endif

            <h1 style="margin:0; color:#ffffff; font-size:24px;">
                PLANETECH SARL
            </h1>

            <p style="margin:6px 0 0; color:#d1fae5; font-size:14px;">
                Attestation et fiche de décharge officielle
            </p>
        </div>

        <!-- CONTENU PRINCIPAL -->
        <div style="padding:30px;">

            <!-- SALUTATION -->
            <p style="font-size:15px; color:#374151; margin-top:0;">
                Bonjour
                <strong>
                    @if($decharge->type === 'materiel')
                        {{ $decharge->fournisseur_nom ?: $decharge->nom }}
                    @else
                        {{ $decharge->nom }}
                    @endif
                </strong>,
            </p>

            <p style="font-size:15px; line-height:1.7; color:#4b5563;">
                Veuillez trouver ci-dessous le récapitulatif de la décharge
                n° <strong>{{ $decharge->reference }}</strong>,
                établie le
                <strong>
                    {{ optional($decharge->created_at)->format('d/m/Y à H:i') ?? 'Date non disponible' }}
                </strong>.
            </p>

            <!-- INFORMATIONS DE LA PARTIE PRENANTE -->
            <div style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px; margin:20px 0;">

                <h3 style="margin:0 0 12px; color:#0f172a; font-size:15px; border-bottom:1px solid #cbd5e1; padding-bottom:8px;">
                    Informations de la partie prenante
                </h3>

                <p style="margin:6px 0; font-size:14px; color:#334155;">
                    <strong>Nom et prénom :</strong>
                    {{ $decharge->nom ?: 'Non renseigné' }}
                </p>

                <p style="margin:6px 0; font-size:14px; color:#334155;">
                    <strong>Rôle :</strong>
                    {{ $decharge->role ?: 'Non renseigné' }}
                </p>

                @if($decharge->entreprise)
                    <p style="margin:6px 0; font-size:14px; color:#334155;">
                        <strong>Entreprise :</strong>
                        {{ $decharge->entreprise }}
                    </p>
                @endif

                @if($decharge->numero)
                    <p style="margin:6px 0; font-size:14px; color:#334155;">
                        <strong>Téléphone :</strong>
                        {{ $decharge->numero }}
                    </p>
                @endif

                @if($decharge->email)
                    <p style="margin:6px 0; font-size:14px; color:#334155;">
                        <strong>E-mail :</strong>
                        {{ $decharge->email }}
                    </p>
                @endif

            </div>

            <!-- INFORMATIONS DU FOURNISSEUR OU LIVREUR -->
            @if($decharge->type === 'materiel')

                <div style="background-color:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:18px; margin:20px 0;">

                    <h3 style="margin:0 0 12px; color:#1e40af; font-size:15px; border-bottom:1px solid #bfdbfe; padding-bottom:8px;">
                        Fournisseur / Livreur
                    </h3>

                    <p style="margin:6px 0; font-size:14px; color:#1e3a8a;">
                        <strong>Nom et prénom :</strong>
                        {{ $decharge->fournisseur_nom ?: 'Non renseigné' }}
                    </p>

                    @if($decharge->fournisseur_entreprise)
                        <p style="margin:6px 0; font-size:14px; color:#1e3a8a;">
                            <strong>Entreprise :</strong>
                            {{ $decharge->fournisseur_entreprise }}
                        </p>
                    @endif

                    @if($decharge->fournisseur_numero)
                        <p style="margin:6px 0; font-size:14px; color:#1e3a8a;">
                            <strong>Téléphone :</strong>
                            {{ $decharge->fournisseur_numero }}
                        </p>
                    @endif

                    @if($decharge->fournisseur_email)
                        <p style="margin:6px 0; font-size:14px; color:#1e3a8a;">
                            <strong>E-mail :</strong>
                            {{ $decharge->fournisseur_email }}
                        </p>
                    @endif

                </div>

            @endif

            <!-- DÉTAILS DE LA DÉCHARGE -->
            <div style="background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:20px; margin:20px 0;">

                <h3 style="margin:0 0 15px; color:#166534; font-size:16px;">
                    Détails de la décharge
                    (type : {{ strtoupper($decharge->type) }})
                </h3>

                <!-- DÉCHARGE D'ARGENT -->
                @if($decharge->type === 'argent')

                    <p style="margin:0 0 10px; color:#166534; font-size:15px;">
                        <strong>Montant :</strong>
                        {{ number_format((float) $decharge->somme, 0, ',', ' ') }} FCFA
                    </p>

                    @if($decharge->motif)
                        <p style="margin:0; color:#166534; font-size:14px; line-height:1.6;">
                            <strong>Motif :</strong>
                            {{ $decharge->motif }}
                        </p>
                    @endif

                <!-- DÉCHARGE DE MATÉRIEL -->
                @elseif($decharge->type === 'materiel')

                    @if($decharge->items && $decharge->items->count() > 0)

                        <table
                            role="presentation"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            style="width:100%; border-collapse:collapse; margin-top:10px;"
                        >
                            <thead>
                                <tr style="background-color:#dcfce7; text-align:left;">

                                    <th style="padding:9px 6px; font-size:12px; color:#166534; border:1px solid #bbf7d0;">
                                        Désignation
                                    </th>

                                    <th style="padding:9px 6px; font-size:12px; color:#166534; border:1px solid #bbf7d0; text-align:center;">
                                        Qté
                                    </th>

                                    <th style="padding:9px 6px; font-size:12px; color:#166534; border:1px solid #bbf7d0;">
                                        État
                                    </th>

                                    <th style="padding:9px 6px; font-size:12px; color:#166534; border:1px solid #bbf7d0; text-align:right;">
                                        Prix unitaire
                                    </th>

                                    <th style="padding:9px 6px; font-size:12px; color:#166534; border:1px solid #bbf7d0; text-align:right;">
                                        Prix global
                                    </th>

                                </tr>
                            </thead>

                            <tbody>
                                @foreach($decharge->items as $item)

                                    <tr style="background-color:#ffffff;">

                                        <!-- DÉSIGNATION -->
                                        <td style="padding:9px 6px; font-size:12px; color:#14532d; border:1px solid #bbf7d0; overflow-wrap:anywhere;">
                                            {{ $item->designation ?: 'Non renseignée' }}
                                        </td>

                                        <!-- QUANTITÉ -->
                                        <td style="padding:9px 6px; font-size:12px; color:#14532d; border:1px solid #bbf7d0; text-align:center;">
                                            {{ $item->quantite !== null && $item->quantite !== '' ? $item->quantite : '—' }}
                                        </td>

                                        <!-- ÉTAT -->
                                        <td style="padding:9px 6px; font-size:12px; color:#14532d; border:1px solid #bbf7d0;">
                                            {{ $item->etat ? ucfirst(str_replace('_', ' ', $item->etat)) : '—' }}
                                        </td>

                                        <!-- PRIX UNITAIRE -->
                                        <td style="padding:9px 6px; font-size:12px; color:#14532d; border:1px solid #bbf7d0; text-align:right; white-space:nowrap;">
                                            @if($item->prix_unitaire !== null && $item->prix_unitaire !== '')
                                                {{ number_format((float) $item->prix_unitaire, 0, ',', ' ') }} FCFA
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <!-- PRIX GLOBAL -->
                                        <td style="padding:9px 6px; font-size:12px; color:#14532d; border:1px solid #bbf7d0; text-align:right; white-space:nowrap;">
                                            @if($item->prix_global !== null && $item->prix_global !== '')
                                                {{ number_format((float) $item->prix_global, 0, ',', ' ') }} FCFA
                                            @else
                                                —
                                            @endif
                                        </td>

                                    </tr>

                                @endforeach
                            </tbody>
                        </table>

                    @else

                        <p style="margin:0; color:#166534; font-size:14px;">
                            Aucun matériel n'est enregistré dans cette décharge.
                        </p>

                    @endif

                    @if($decharge->motif)
                        <p style="margin:15px 0 0; color:#166534; font-size:14px; line-height:1.6;">
                            <strong>Motif :</strong>
                            {{ $decharge->motif }}
                        </p>
                    @endif

                <!-- DÉCHARGE DE RESPONSABILITÉ -->
                @elseif($decharge->type === 'responsabilite')

                    <p style="margin:0; color:#166534; font-size:14px; line-height:1.7;">
                        <strong>Description :</strong><br>
                        {!! nl2br(e($decharge->description ?: 'Aucune description renseignée.')) !!}
                    </p>

                    @if($decharge->motif)
                        <p style="margin:12px 0 0; color:#166534; font-size:14px; line-height:1.6;">
                            <strong>Motif :</strong>
                            {{ $decharge->motif }}
                        </p>
                    @endif

                @endif

            </div>

            <!-- SIGNATURE NUMÉRIQUE -->
            @php
                $signatureData = (string) ($decharge->signature_data ?? '');

                $sigRaw = preg_replace(
                    '#^data:image/[a-zA-Z0-9.+-]+;base64,#i',
                    '',
                    $signatureData
                );

                $sigBin = $sigRaw !== ''
                    ? base64_decode($sigRaw, true)
                    : false;
            @endphp

            @if($sigBin !== false && $sigBin !== '')
                <div style="margin-top:25px; text-align:center; background-color:#ffffff; border:1px dashed #cbd5e1; border-radius:10px; padding:15px;">

                    <p style="margin:0 0 10px; font-size:13px; color:#64748b; font-weight:bold;">
                        SIGNATURE NUMÉRIQUE ENREGISTRÉE
                    </p>

                    <img
                        src="{{ $message->embedData($sigBin, 'signature.png', 'image/png') }}"
                        alt="Signature numérique"
                        style="display:inline-block; max-height:80px; max-width:100%; width:auto; border:0;"
                    >

                </div>
            @endif

            <!-- PIÈCE JOINTE -->
            <div style="margin-top:22px; padding:14px; background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">

                <p style="font-size:13px; line-height:1.6; color:#64748b; margin:0;">
                    <strong>Document joint :</strong>
                    le document officiel de décharge au format PDF est joint à cet e-mail.
                </p>

            </div>

            <!-- FORMULE DE POLITESSE -->
            <p style="font-size:14px; line-height:1.7; margin-top:25px; color:#374151;">
                Cordialement,<br>
                <strong>L'équipe PLANETECH SARL</strong>
            </p>

        </div>

        <!-- PIED DE PAGE -->
        <div style="background-color:#f9fafb; padding:18px; text-align:center; border-top:1px solid #f1f5f9;">

            <p style="margin:0; font-size:12px; line-height:1.6; color:#9ca3af;">
                © {{ date('Y') }} PLANETECH SARL — Document officiel.
            </p>

            <p style="margin:5px 0 0; font-size:11px; color:#9ca3af;">
                Cet e-mail a été généré automatiquement. Merci de conserver le PDF joint.
            </p>

        </div>

    </div>

</body>
</html>
