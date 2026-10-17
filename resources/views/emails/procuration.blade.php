<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procuration - PLANETECH SARL</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial, Helvetica, sans-serif;">
    <div style="max-width:650px; margin:30px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        <div style="background:#1b4d3e; padding:25px; text-align:center;">
            <h1 style="margin:0; color:#ffffff; font-size:24px;">PLANETECH SARL</h1>
            <p style="margin:6px 0 0; color:#d1fae5; font-size:14px;">Attestation de Procuration Officielle</p>
        </div>
        <div style="padding:30px;">
            <p style="font-size:15px; color:#374151;">Bonjour <strong>{{ $procuration->nom_procurant }}</strong>,</p>
            <p style="font-size:15px; line-height:1.7; color:#4b5563;">
                Veuillez trouver ci-joint votre document de procuration officiel (Ref : <strong>{{ $procuration->reference }}</strong>) au format PDF, valable du {{ \Carbon\Carbon::parse($procuration->date_debut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($procuration->date_fin)->format('d/m/Y') }}.
            </p>
            <p style="font-size:14px; margin-top:30px; color:#374151;">
                Cordialement,<br><strong>L'équipe PLANETECH SARL</strong>
            </p>
        </div>
        <div style="background:#f9fafb; padding:18px; text-align:center; border-top:1px solid #f1f5f9;">
            <p style="margin:0; font-size:12px; color:#9ca3af;">© {{ date('Y') }} PLANETECH SARL — Document sécurisé.</p>
        </div>
    </div>
</body>
</html>
