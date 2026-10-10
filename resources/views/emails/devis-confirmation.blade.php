<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <title>Confirmation de votre demande de devis</title>
</head>

<body style="margin:0; padding:0; background:#f4f7f6; font-family:Arial, sans-serif;">

    <div style="max-width:600px; margin:40px auto; background:white; border-radius:12px; overflow:hidden;">

        {{-- Header --}}
        <div style="background:#064e3b; padding:30px; text-align:center;">

            <img
                src="cid:planetech-logo"
                alt="PLANETECH"
                width="120"
                style="
                    display:block;
                    width:120px;
                    height:auto;
                    margin:0 auto 15px;
                    border:0;
                    outline:none;
                    text-decoration:none;
                "
            >

            <h1 style="margin:0; color:white; font-size:24px;">
                PLANETECH
            </h1>

            <p style="margin:8px 0 0; color:#d1fae5;">
                Confirmation de votre demande
            </p>

        </div>

        {{-- Contenu --}}
        <div style="padding:35px;">

            <p style="font-size:16px; color:#333;">
                Bonjour <strong>{{ $demande->nom }}</strong>,
            </p>

            <p style="font-size:15px; line-height:1.7; color:#555;">
                Nous avons bien reçu votre demande de devis.
                Notre équipe va étudier votre demande et vous contactera
                dans les meilleurs délais.
            </p>

            {{-- Référence --}}
            <div style="
                margin:25px 0;
                padding:20px;
                background:#ecfdf5;
                border-left:4px solid #059669;
                border-radius:6px;
            ">

                <p style="margin:0 0 8px; color:#555;">
                    Référence de votre demande
                </p>

                <strong style="font-size:20px; color:#064e3b;">
                    {{ $demande->reference }}
                </strong>

            </div>

            {{-- Informations --}}
            <h3 style="color:#064e3b;">
                Récapitulatif
            </h3>

            <p style="font-size:14px; color:#555;">
                <strong>Type de client :</strong>
                {{ $demande->type_client === 'societe' ? 'Société' : 'Particulier' }}
            </p>

            @if($demande->raison_sociale)
                <p style="font-size:14px; color:#555;">
                    <strong>Société :</strong>
                    {{ $demande->raison_sociale }}
                </p>
            @endif

            <p style="font-size:14px; color:#555;">
                <strong>Domaine :</strong>
                {{ $demande->domaine }}
            </p>

            <p style="font-size:14px; color:#555;">
                <strong>Téléphone :</strong>
                {{ $demande->indicatif }} {{ $demande->telephone }}
            </p>

            <p style="font-size:14px; color:#555;">
                <strong>Votre besoin :</strong><br>
                {{ $demande->besoins }}
            </p>

            <p style="margin-top:30px; font-size:15px; color:#555; line-height:1.6;">
                Merci pour votre confiance.
            </p>

            <p style="font-size:15px; color:#555;">
                Cordialement,<br>
                <strong>L'équipe PLANETECH</strong>
            </p>

        </div>

        {{-- Footer --}}
        <div style="background:#f0fdf4; padding:20px; text-align:center;">

            <p style="margin:0; font-size:12px; color:#6b7280;">
                Cet email est envoyé automatiquement.
            </p>

            <p style="margin:6px 0 0; font-size:12px; color:#6b7280;">
                © {{ date('Y') }} PLANETECH
            </p>

        </div>

    </div>

</body>
</html>