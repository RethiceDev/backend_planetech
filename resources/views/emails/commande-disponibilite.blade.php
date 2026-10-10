<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilité de votre commande</title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #f4f7f6;
    font-family: Arial, Helvetica, sans-serif;
    color: #1f2937;
">

<div style="
    width: 100%;
    padding: 30px 0;
">

    <div style="
        max-width: 680px;
        margin: 0 auto;
        background-color: #ffffff;
        border-radius: 16px;
        overflow: hidden;
    ">

        {{-- EN-TÊTE --}}
        <div style="
            padding: 28px 30px;
            background-color: #047857;
            color: #ffffff;
        ">

            <div style="
                font-size: 23px;
                font-weight: bold;
            ">
                PLANETECH SARL
            </div>

            <div style="
                margin-top: 8px;
                font-size: 14px;
                color: #d1fae5;
            ">
                Vérification de la disponibilité de votre commande
            </div>

        </div>


        {{-- CONTENU --}}
        <div style="padding: 30px;">

            {{-- CLIENT --}}
            <p style="
                margin: 0 0 18px;
                font-size: 15px;
                line-height: 1.6;
            ">
                Bonjour
                <strong>
                    {{ $commande->nom ?: $commande->raison_sociale ?: 'Client' }}
                </strong>,
            </p>


            <p style="
                margin: 0;
                font-size: 14px;
                line-height: 1.7;
                color: #374151;
            ">
                Nous avons effectué la vérification de la disponibilité
                des articles de votre commande
                <strong>{{ $commande->reference }}</strong>.
            </p>


            {{-- MESSAGE AUTOMATIQUE SELON LA DISPONIBILITÉ --}}

            @if($tousDisponibles)

                <div style="
                    margin-top: 24px;
                    padding: 18px;
                    background-color: #ecfdf5;
                    border: 1px solid #a7f3d0;
                    border-radius: 12px;
                ">

                    <div style="
                        font-size: 15px;
                        font-weight: bold;
                        color: #047857;
                    ">
                        Tous les articles sont disponibles
                    </div>

                    <div style="
                        margin-top: 7px;
                        font-size: 13px;
                        line-height: 1.6;
                        color: #065f46;
                    ">
                        Nous vous confirmons que l'ensemble des articles
                        de votre commande est actuellement disponible.
                    </div>

                </div>

            @elseif($aucunDisponible)

                <div style="
                    margin-top: 24px;
                    padding: 18px;
                    background-color: #fef2f2;
                    border: 1px solid #fecaca;
                    border-radius: 12px;
                ">

                    <div style="
                        font-size: 15px;
                        font-weight: bold;
                        color: #dc2626;
                    ">
                        Articles actuellement indisponibles
                    </div>

                    <div style="
                        margin-top: 7px;
                        font-size: 13px;
                        line-height: 1.6;
                        color: #991b1b;
                    ">
                        Après vérification, aucun des articles de votre
                        commande n'est actuellement disponible.
                        Nous vous invitons à nous contacter afin de
                        connaître les prochaines possibilités.
                    </div>

                </div>

            @else

                <div style="
                    margin-top: 24px;
                    padding: 18px;
                    background-color: #fffbeb;
                    border: 1px solid #fde68a;
                    border-radius: 12px;
                ">

                    <div style="
                        font-size: 15px;
                        font-weight: bold;
                        color: #b45309;
                    ">
                        Disponibilité partielle
                    </div>

                    <div style="
                        margin-top: 7px;
                        font-size: 13px;
                        line-height: 1.6;
                        color: #92400e;
                    ">
                        Certains articles de votre commande sont
                        disponibles tandis que d'autres ne le sont
                        actuellement pas.
                    </div>

                </div>

            @endif


            {{-- TITRE --}}
            <div style="
                margin-top: 30px;
                margin-bottom: 12px;
                font-size: 16px;
                font-weight: bold;
                color: #111827;
            ">
                Détail des articles
            </div>


            {{-- ARTICLES DISPONIBLES --}}
            @if($articlesDisponibles->count() > 0)

                <div style="
                    margin-top: 15px;
                    padding: 14px 16px;
                    background-color: #ecfdf5;
                    border-radius: 10px;
                ">

                    <div style="
                        margin-bottom: 10px;
                        font-size: 13px;
                        font-weight: bold;
                        color: #047857;
                    ">
                        Articles disponibles
                    </div>

                    @foreach($articlesDisponibles as $item)

                        <div style="
                            padding: 11px 0;
                            border-bottom: 1px solid #d1fae5;
                        ">

                            <div style="
                                font-size: 13px;
                                font-weight: bold;
                                color: #1f2937;
                            ">
                                {{ $item->designation }}
                            </div>

                            <div style="
                                margin-top: 4px;
                                font-size: 12px;
                                color: #6b7280;
                            ">
                                Quantité : {{ $item->quantite }}
                            </div>

                        </div>

                    @endforeach

                </div>

            @endif


            {{-- ARTICLES INDISPONIBLES --}}
            @if($articlesIndisponibles->count() > 0)

                <div style="
                    margin-top: 15px;
                    padding: 14px 16px;
                    background-color: #fef2f2;
                    border-radius: 10px;
                ">

                    <div style="
                        margin-bottom: 10px;
                        font-size: 13px;
                        font-weight: bold;
                        color: #dc2626;
                    ">
                        Articles indisponibles
                    </div>

                    @foreach($articlesIndisponibles as $item)

                        <div style="
                            padding: 11px 0;
                            border-bottom: 1px solid #fee2e2;
                        ">

                            <div style="
                                font-size: 13px;
                                font-weight: bold;
                                color: #1f2937;
                            ">
                                {{ $item->designation }}
                            </div>

                            <div style="
                                margin-top: 4px;
                                font-size: 12px;
                                color: #6b7280;
                            ">
                                Quantité : {{ $item->quantite }}
                            </div>

                        </div>

                    @endforeach

                </div>

            @endif


            {{-- TOTAL --}}
            <div style="
                margin-top: 25px;
                padding: 18px;
                background-color: #f8fafc;
                border-radius: 12px;
                border: 1px solid #e5e7eb;
            ">

                <div style="
                    font-size: 13px;
                    color: #6b7280;
                ">
                    Référence de commande
                </div>

                <div style="
                    margin-top: 4px;
                    font-size: 14px;
                    font-weight: bold;
                    color: #111827;
                ">
                    {{ $commande->reference }}
                </div>


                <div style="
                    margin-top: 16px;
                    padding-top: 14px;
                    border-top: 1px solid #e5e7eb;
                ">

                    <div style="
                        font-size: 13px;
                        color: #6b7280;
                    ">
                        Total de la commande
                    </div>

                    <div style="
                        margin-top: 4px;
                        font-size: 17px;
                        font-weight: bold;
                        color: #047857;
                    ">
                        {{ number_format($commande->total, 0, ',', ' ') }} FCFA
                    </div>

                </div>

            </div>


            {{-- CONCLUSION --}}
            @if($tousDisponibles)

                <p style="
                    margin-top: 25px;
                    font-size: 13px;
                    line-height: 1.7;
                    color: #374151;
                ">
                    Votre commande peut donc être traitée sur la base
                    de la disponibilité actuelle des articles.
                    Pour toute information complémentaire, n'hésitez
                    pas à contacter PLANETECH SARL.
                </p>

            @elseif($aucunDisponible)

                <p style="
                    margin-top: 25px;
                    font-size: 13px;
                    line-height: 1.7;
                    color: #374151;
                ">
                    Pour toute information concernant la disponibilité
                    future de ces articles ou toute autre possibilité,
                    nous vous invitons à contacter PLANETECH SARL.
                </p>

            @else

                <p style="
                    margin-top: 25px;
                    font-size: 13px;
                    line-height: 1.7;
                    color: #374151;
                ">
                    Nous vous invitons à nous contacter afin de convenir
                    des prochaines étapes concernant les articles
                    actuellement indisponibles.
                </p>

            @endif


            {{-- CONTACT --}}
            <div style="
                margin-top: 25px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
            ">

                <div style="
                    font-size: 13px;
                    font-weight: bold;
                    color: #111827;
                ">
                    PLANETECH SARL
                </div>

                <div style="
                    margin-top: 6px;
                    font-size: 12px;
                    line-height: 1.6;
                    color: #6b7280;
                ">
                    Cotonou, Bénin<br>
                    Tél. : +229 01 64 54 99 59<br>
                    WhatsApp : +229 97 89 29 82<br>
                    Email : planetechsarlbenin@gmail.com
                </div>

            </div>

        </div>


        {{-- PIED DE PAGE --}}
        <div style="
            padding: 18px 30px;
            background-color: #f8fafc;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
        ">

            PLANETECH SARL — Cotonou, Bénin

        </div>

    </div>

</div>

</body>

</html>