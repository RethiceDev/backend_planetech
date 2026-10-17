<?php

namespace App\Support;

/**
 * Convertit un entier en lettres, en français.
 * Exemple : 850839 => "huit cent cinquante mille huit cent trente-neuf"
 */
class NombreEnLettres
{
    private const UNITES = [
        'zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize',
    ];

    private const DIZAINES = [
        2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante',
    ];

    public static function convertir(int $n): string
    {
        if ($n < 0) {
            return 'moins ' . self::convertir(-$n);
        }

        if ($n === 0) {
            return 'zéro';
        }

        $milliards = intdiv($n, 1_000_000_000);
        $millions = intdiv($n % 1_000_000_000, 1_000_000);
        $milliers = intdiv($n % 1_000_000, 1000);
        $reste = $n % 1000;

        $morceaux = [];

        if ($milliards > 0) {
            $morceaux[] = self::moinsDeMille($milliards, true)
                . ' milliard' . ($milliards > 1 ? 's' : '');
        }

        if ($millions > 0) {
            $morceaux[] = self::moinsDeMille($millions, true)
                . ' million' . ($millions > 1 ? 's' : '');
        }

        if ($milliers > 0) {
            $morceaux[] = ($milliers === 1 ? '' : self::moinsDeMille($milliers, false) . ' ') . 'mille';
        }

        if ($reste > 0) {
            $morceaux[] = self::moinsDeMille($reste, true);
        }

        return implode(' ', $morceaux);
    }

    /**
     * 1 à 999. $final = le groupe est en fin de nombre
     * (donne "deux cents" et "quatre-vingts" seulement à la fin).
     */
    private static function moinsDeMille(int $n, bool $final): string
    {
        $parties = [];

        $centaines = intdiv($n, 100);
        $reste = $n % 100;

        if ($centaines > 0) {
            $mot = $centaines === 1 ? 'cent' : self::UNITES[$centaines] . ' cent';

            if ($reste === 0 && $centaines > 1 && $final) {
                $mot .= 's';
            }

            $parties[] = $mot;
        }

        if ($reste > 0) {
            $mot = self::moinsDeCent($reste);

            if ($reste === 80 && $final) {
                $mot = 'quatre-vingts';
            }

            $parties[] = $mot;
        }

        return implode(' ', $parties);
    }

    /**
     * 1 à 99 (sans le "s" final de quatre-vingts, géré plus haut).
     */
    private static function moinsDeCent(int $n): string
    {
        if ($n <= 16) {
            return self::UNITES[$n];
        }

        if ($n < 20) {
            return 'dix-' . self::UNITES[$n - 10];
        }

        if ($n < 70) {
            $dizaine = intdiv($n, 10);
            $unite = $n % 10;
            $mot = self::DIZAINES[$dizaine];

            if ($unite === 0) {
                return $mot;
            }

            if ($unite === 1) {
                return $mot . ' et un';
            }

            return $mot . '-' . self::UNITES[$unite];
        }

        if ($n < 80) {
            // 70 à 79 : soixante + (10 à 19)
            if ($n === 71) {
                return 'soixante et onze';
            }

            return 'soixante-' . self::moinsDeCent($n - 60);
        }

        // 80 à 99 : quatre-vingt + (0 à 19)
        if ($n === 80) {
            return 'quatre-vingt';
        }

        return 'quatre-vingt-' . self::moinsDeCent($n - 80);
    }
}
