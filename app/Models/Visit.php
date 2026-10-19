<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Visit extends Model
{
    protected $fillable = [
        'ip', 'page', 'source', 'referrer', 'user_agent', 'visited_on',
    ];

    protected $casts = ['visited_on' => 'date'];

    private const MOIS_COURTS = ['Janv', 'Févr', 'Mars', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
    private const MOIS_LONGS  = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    /**
     * Une "visite" = un visiteur unique (IP) par jour.
     */
    public static function yearEvolution(): array
    {
        $now       = Carbon::now();
        $startYear = $now->copy()->startOfYear();
        $startPrev = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $from      = $startPrev->lt($startYear) ? $startPrev : $startYear;

        $perDay = DB::table('visits')
            ->whereBetween('visited_on', [$from->toDateString(), $now->toDateString()])
            ->selectRaw('visited_on, COUNT(DISTINCT ip) AS total')
            ->groupBy('visited_on')
            ->pluck('total', 'visited_on');

        $sumMonth = fn (Carbon $m): int => (int) $perDay
            ->filter(fn ($v, $d) => substr((string) $d, 0, 7) === $m->format('Y-m'))
            ->sum();

        // a) Mois terminés de l'année en cours
        $monthLabels = [];
        $monthTotals = [];
        for ($m = 1; $m < $now->month; $m++) {
            $monthLabels[] = self::MOIS_COURTS[$m - 1];
            $monthTotals[] = $sumMonth(Carbon::create($now->year, $m, 1));
        }

        // b) Mois en cours, jour par jour
        $dayLabels = [];
        $dayTotals = [];
        for ($d = 1; $d <= $now->day; $d++) {
            $date = Carbon::create($now->year, $now->month, $d)->toDateString();
            $dayLabels[] = str_pad((string) $d, 2, '0', STR_PAD_LEFT);
            $dayTotals[] = (int) ($perDay[$date] ?? 0);
        }

        // c) Total du mois + évolution vs mois dernier
        $currentTotal  = $sumMonth($now);
        $previousTotal = $sumMonth($startPrev);
        $evolution = $previousTotal > 0
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1)
            : null;

        // Provenance des visiteurs ce mois-ci
        $sources = DB::table('visits')
            ->whereBetween('visited_on', [$now->copy()->startOfMonth()->toDateString(), $now->toDateString()])
            ->selectRaw('source, COUNT(DISTINCT ip, visited_on) AS total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($s) => ['source' => $s->source, 'total' => (int) $s->total])
            ->all();

        return [
            'year'            => $now->year,
            'monthLabels'     => $monthLabels,
            'monthTotals'     => $monthTotals,
            'dayLabels'       => $dayLabels,
            'dayTotals'       => $dayTotals,
            'moisLabel'       => self::MOIS_LONGS[$now->month - 1],
            'moisTotal'       => $currentTotal,
            'precedentLabel'  => self::MOIS_LONGS[$startPrev->month - 1],
            'precedentTotal'  => $previousTotal,
            'evolution'       => $evolution,
            'sources'         => $sources,
        ];
    }
}
