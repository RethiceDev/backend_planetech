<?php

return [
    // Adresse qui reçoit une copie de chaque décharge
    'admin_email' => env('DECHARGE_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),

    // Ville affichée dans « Fait à ... » (laisser vide pour ne rien afficher)
    'lieu' => env('DECHARGE_LIEU'),
];
