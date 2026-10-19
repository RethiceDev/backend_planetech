<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function track(Request $request)
    {
        $data = $request->validate([
            'page'       => 'required|string|max:191',
            'referrer'   => 'nullable|string|max:500',
            'utm_source' => 'nullable|string|max:50',
        ]);

        $ua = (string) $request->userAgent();

        // On ignore les robots
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|facebookexternalhit|headless|lighthouse|curl|wget|python/i', $ua)) {
            return response()->noContent();
        }

        $page = '/' . trim(strtolower(parse_url($data['page'], PHP_URL_PATH) ?: '/'), '/');

        // insertOrIgnore + index unique = jamais de doublon
        Visit::insertOrIgnore([
            'ip'         => $request->ip(),
            'page'       => $page,
            'source'     => $this->detectSource($data['referrer'] ?? null, $data['utm_source'] ?? null),
            'referrer'   => $data['referrer'] ?? null,
            'user_agent' => mb_substr($ua, 0, 255),
            'visited_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->noContent();
    }

    private function detectSource(?string $referrer, ?string $utm): string
    {
        $host = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : '';
        $text = strtolower(trim((string) $utm)) ?: $host;

        if ($text === '') {
            return 'direct';
        }

        return match (true) {
            str_contains($text, 'facebook') || str_contains($text, 'fb.') || $text === 'fb' => 'facebook',
            str_contains($text, 'instagram') || $text === 'ig'                              => 'instagram',
            str_contains($text, 'google')                                                   => 'google',
            str_contains($text, 'whatsapp') || str_contains($text, 'wa.me')                 => 'whatsapp',
            str_contains($text, 'linkedin') || str_contains($text, 'lnkd.in')               => 'linkedin',
            str_contains($text, 'tiktok')                                                   => 'tiktok',
            str_contains($text, 'youtube') || str_contains($text, 'youtu.be')               => 'youtube',
            str_contains($text, 'twitter') || $text === 'x.com' || $text === 't.co'         => 'x',
            str_contains($text, 'bing')                                                     => 'bing',
            default                                                                         => 'autre',
        };
    }
}
