<?php

namespace App\Services\Analytics;

use App\Models\VisitorSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class VisitorTrackingService
{
    public const string COOKIE = 'llb_vid';

    private const array BOT_MARKERS = [
        'bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'whatsapp',
        'telegram', 'preview', 'curl', 'wget', 'python-requests', 'headless',
    ];

    public static function generateId(): string
    {
        return Str::lower(Str::random(40));
    }

    public function visitorId(Request $request): ?string
    {
        $id = (string) $request->cookie(self::COOKIE, '');

        return preg_match('/^[a-z0-9]{20,64}$/', $id) === 1 ? $id : null;
    }

    public function isBot(Request $request): bool
    {
        $agent = Str::lower((string) $request->userAgent());

        if ($agent === '') {
            return true;
        }

        return Str::contains($agent, self::BOT_MARKERS);
    }

    public function ipHash(Request $request): ?string
    {
        $ip = (string) $request->ip();

        return $ip === '' ? null : hash('sha256', $ip.'|'.config('app.key'));
    }

    public function pulse(Request $request, ?string $path): void
    {
        if ($this->isBot($request)) {
            return;
        }

        $visitorId = $this->visitorId($request);

        if ($visitorId === null) {
            return;
        }

        $session = VisitorSession::firstOrCreate(
            ['visitor_id' => $visitorId],
            ['first_seen_at' => now(), 'last_seen_at' => now(), 'page_views' => 0],
        );

        $session->forceFill([
            'last_seen_at' => now(),
            'last_path' => $path !== null ? Str::limit($path, 490, '') : $session->last_path,
        ]);

        if ($path !== null) {
            $session->page_views++;
        }

        $session->save();
    }
}
