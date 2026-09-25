<?php

namespace App\Services;

use App\Jobs\TrackVisitorJob;
use App\Models\PageView;
use App\Models\Visitor;
use App\Models\VisitorEvent;
use App\Models\VisitorSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class VisitorTrackingService
{
    protected Agent $agent;

    public function __construct()
    {
        $this->agent = new Agent;
    }

    /* -----------------------------------------------------------------
     |  PUBLIC METHODS
     | ----------------------------------------------------------------- */

    public function dispatchTracking(Request $request): void
    {
        $this->agent->setUserAgent($request->userAgent() ?? '');

        if ($this->agent->isRobot()) {
            return;
        }

        $payload = [
            'ip'           => $request->ip(),
            'user_agent'   => (string) $request->userAgent(),
            'url'          => $request->fullUrl(),
            'route_name'   => $request->route()?->getName(),
            'referer'      => $request->headers->get('referer'),
            'user_id'      => Auth::id(),
            'is_pwa'       => $this->resolveIsPwa($request),
            'utm_source'   => $request->query('utm_source'),
            'utm_medium'   => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'load_time_ms' => defined('LARAVEL_START')
                ? (int) round((microtime(true) - LARAVEL_START) * 1000)
                : 0,
            'page_title'   => config('app.current_page_title'),
            'timestamp'    => now()->toISOString(),
        ];

        TrackVisitorJob::dispatch($payload)->onQueue('tracking');
    }

    public function processTrackingPayload(array $data): void
    {
        try {
            $this->agent->setUserAgent($data['user_agent'] ?? '');

            $visitor = $this->getOrCreateVisitorFromData($data);
            if (! $visitor) {
                return;
            }

            $session = $this->getOrCreateSessionFromData($visitor, $data);

            $this->touchVisitor($visitor, $data['user_id'] ?? null);
            $this->touchSession($session);
            $this->recordPageViewFromData($visitor, $session, $data);
        } catch (\Throwable $e) {
            Log::error('VisitorTrackingService::processTrackingPayload failed', [
                'message' => $e->getMessage(),
                'data'    => $data,
            ]);
            throw $e;
        }
    }

    public function trackEvent(
        Visitor $visitor,
        string $category,
        string $action,
        ?string $label = null,
        array $payload = []
    ): void {
        try {
            $session = $this->resolveActiveSession($visitor);

            VisitorEvent::create([
                'session_id'     => $session?->id,
                'visitor_id'     => $visitor->id,
                'event_category' => $category,
                'event_action'   => $action,
                'event_label'    => $label,
                'payload'        => $payload,
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Event tracking failed', [
                'message'  => $e->getMessage(),
                'category' => $category,
                'action'   => $action,
            ]);
        }
    }

    public function getOrCreateVisitor(Request $request): ?Visitor
    {
        $data = [
            'ip'         => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'user_id'    => Auth::id(),
            'is_pwa'     => $this->resolveIsPwa($request),
        ];

        return $this->getOrCreateVisitorFromData($data);
    }

    public function resolveActiveSession(Visitor $visitor): ?VisitorSession
    {
        $cacheKey  = "visitor:session:{$visitor->id}";
        $sessionId = Cache::get($cacheKey);

        if ($sessionId) {
            $session = VisitorSession::find($sessionId);
            if ($session && $session->last_active_at?->gt(now()->subMinutes(30))) {
                return $session;
            }
            Cache::forget($cacheKey);
        }

        return VisitorSession::where('visitor_id', $visitor->id)
            ->where('last_active_at', '>', now()->subMinutes(30))
            ->orderByDesc('last_active_at')
            ->first();
    }

    /* -----------------------------------------------------------------
     |  CORE: getOrCreateVisitorFromData
     | ----------------------------------------------------------------- */

    public function getOrCreateVisitorFromData(array $data): ?Visitor
    {
        $ip   = $data['ip'] ?? request()->ip();
        $ua   = $data['user_agent'] ?? (string) request()->userAgent();
        $hash = $data['hash'] ?? $this->makeHash($ip, $ua);

        $cacheKey = "visitor:{$hash}";

        // 1. Cache — also repair older visitors whose location is still empty.
        $cached = Cache::get($cacheKey);
        if ($cached instanceof Visitor) {
            return $this->ensureVisitorLocation($cached, $ip);
        }

        // 2. DB lookup — location enrichment also applies to existing visitors.
        $visitor = Visitor::where('hash', $hash)->first();
        if ($visitor) {
            $visitor = $this->ensureVisitorLocation($visitor, $ip);
            Cache::put($cacheKey, $visitor, now()->addMinutes(30));
            return $visitor;
        }

        // 3. Enrich browser/device data and server-side IP location before create.
        $this->agent->setUserAgent($ua);

        $browserFamily = $data['browser_family'] ?? $this->agent->browser() ?: null;
        $osFamily      = $data['os_family'] ?? $this->agent->platform() ?: null;
        $deviceType    = $data['device_type'] ?? $this->getDeviceType();
        $isBot         = $data['is_bot'] ?? $this->agent->isRobot();

        $location = $this->resolveIpLocation($ip);
        $countryCode = $data['country_code'] ?? $location['country_code'];
        $cityName    = $data['city_name'] ?? $location['city_name'];

        $isPwa = (bool) ($data['is_pwa'] ?? false);

        // 4. Create (catch duplicate race).
        try {
            $visitor = Visitor::create([
                'hash'              => $hash,
                'user_id'           => $data['user_id'] ?? null,
                'ip_address'        => $ip,
                'browser_family'    => $browserFamily,
                'os_family'         => $osFamily,
                'device_type'       => $deviceType,
                'country_code'      => $countryCode,
                'city_name'         => $cityName,
                'is_bot'            => (bool) $isBot,
                'is_pwa'            => $isPwa,
                'has_installed_pwa' => $isPwa,
                'last_seen_at'      => now(),
            ]);

            Cache::put($cacheKey, $visitor, now()->addMinutes(30));

            return $visitor;
        } catch (\Illuminate\Database\QueryException $e) {
            if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
                $visitor = Visitor::where('hash', $hash)->first();
                if ($visitor) {
                    $visitor = $this->ensureVisitorLocation($visitor, $ip);
                    Cache::put($cacheKey, $visitor, now()->addMinutes(30));
                    return $visitor;
                }
            }
            throw $e;
        }
    }

    /**
     * Fill missing location data for visitors created before location
     * enrichment was working. Existing populated locations are never
     * overwritten on every page view.
     */
    protected function ensureVisitorLocation(Visitor $visitor, ?string $ip): Visitor
    {
        if ($visitor->country_code && $visitor->city_name) {
            return $visitor;
        }

        $location = $this->resolveIpLocation($ip ?: $visitor->ip_address);
        if (! $location['country_code'] && ! $location['city_name']) {
            return $visitor;
        }

        $visitor->forceFill([
            'country_code' => $visitor->country_code ?: $location['country_code'],
            'city_name'    => $visitor->city_name ?: $location['city_name'],
            'last_seen_at' => now(),
        ])->saveQuietly();

        return $visitor->fresh() ?? $visitor;
    }

    /**
     * Resolve a public visitor IP to country/city without requiring a
     * client-side location permission. Results are cached for 24 hours per IP.
     * Two providers are used as a graceful fallback so tracking continues if
     * one provider is temporarily unavailable.
     */
    protected function resolveIpLocation(?string $ip): array
    {
        $empty = [
            'country_code' => null,
            'city_name'    => null,
        ];

        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $empty;
        }

        return Cache::remember(
            "visitor:loc:{$ip}",
            now()->addDay(),
            function () use ($ip, $empty) {
                try {
                    $response = Http::timeout(2.5)
                        ->connectTimeout(1.5)
                        ->acceptJson()
                        ->get('https://ipwho.is/' . rawurlencode($ip));

                    if ($response->successful()) {
                        $data = $response->json();

                        if (is_array($data) && ($data['success'] ?? true)) {
                            $location = [
                                'country_code' => isset($data['country_code'])
                                    ? strtoupper((string) $data['country_code'])
                                    : null,
                                'city_name' => isset($data['city'])
                                    ? trim((string) $data['city'])
                                    : null,
                            ];

                            if ($location['country_code'] || $location['city_name']) {
                                return $location;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::notice('Primary visitor IP location lookup failed', [
                        'message' => $e->getMessage(),
                    ]);
                }

                try {
                    $response = Http::timeout(2.5)
                        ->connectTimeout(1.5)
                        ->acceptJson()
                        ->get('https://ipapi.co/' . rawurlencode($ip) . '/json/');

                    if ($response->successful()) {
                        $data = $response->json();

                        if (is_array($data)) {
                            return [
                                'country_code' => isset($data['country_code'])
                                    ? strtoupper((string) $data['country_code'])
                                    : null,
                                'city_name' => isset($data['city'])
                                    ? trim((string) $data['city'])
                                    : null,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::notice('Fallback visitor IP location lookup failed', [
                        'message' => $e->getMessage(),
                    ]);
                }

                return $empty;
            }
        );
    }

    /* -----------------------------------------------------------------
     |  SESSION + PAGE VIEW
     | ----------------------------------------------------------------- */

    protected function getOrCreateSessionFromData(Visitor $visitor, array $data): VisitorSession
    {
        $cacheKey  = "visitor:session:{$visitor->id}";
        $sessionId = Cache::get($cacheKey);

        if ($sessionId) {
            $session = VisitorSession::find($sessionId);
            if ($session && $session->last_active_at?->gt(now()->subMinutes(30))) {
                return $session;
            }
        }

        $source = $this->parseTrafficSource($data['referer'] ?? null);

        $session = VisitorSession::create([
            'id'             => (string) Str::uuid(),
            'visitor_id'     => $visitor->id,
            'origin_type'    => $source['type'],
            'origin_source'  => $source['source'],
            'entry_url'      => Str::limit($data['url'] ?? '', 500),
            'utm_source'     => $data['utm_source'] ?? null,
            'utm_medium'     => $data['utm_medium'] ?? null,
            'utm_campaign'   => $data['utm_campaign'] ?? null,
            'started_at'     => now(),
            'last_active_at' => now(),
            'hits_count'     => 0,
            'seconds_spent'  => 0,
        ]);

        Cache::put($cacheKey, $session->id, now()->addMinutes(35));

        return $session;
    }

    protected function touchVisitor(Visitor $visitor, $userId = null): void
    {
        $visitor->forceFill([
            'last_seen_at' => now(),
            'user_id'      => $userId ?? $visitor->user_id,
        ])->saveQuietly();
    }

    protected function touchSession(VisitorSession $session): void
    {
        $now     = now();
        $seconds = 0;

        if ($session->last_active_at) {
            $diff    = $session->last_active_at->diffInSeconds($now);
            $seconds = (int) min(max($diff, 0), 300);
        }

        $session->forceFill([
            'last_active_at' => $now,
            'seconds_spent'  => $session->seconds_spent + $seconds,
        ])->saveQuietly();
    }

    protected function recordPageViewFromData(Visitor $visitor, VisitorSession $session, array $data): void
    {
        $url     = Str::limit($data['url'] ?? '', 500);
        $urlHash = sha1($url);

        $recentKey = "pageview:recent:{$session->id}:{$urlHash}";
        if (Cache::has($recentKey)) {
            return;
        }

        $title = $data['page_title']
            ?? Str::headline($data['route_name'] ?? 'Home');

        PageView::create([
            'session_id'   => $session->id,
            'visitor_id'   => $visitor->id,
            'url'          => $url,
            'title'        => $title,
            'url_hash'     => $urlHash,
            'route_name'   => $data['route_name'] ?? null,
            'load_time_ms' => $data['load_time_ms'] ?? 0,
            'created_at'   => now(),
        ]);

        VisitorSession::where('id', $session->id)->increment('hits_count');
        Cache::put($recentKey, true, now()->addSeconds(8));
    }

    /* -----------------------------------------------------------------
     |  HELPERS
     | ----------------------------------------------------------------- */

    protected function parseTrafficSource(?string $referer): array
    {
        if (! $referer) {
            return ['type' => 'direct', 'source' => 'Direct'];
        }

        $host = strtolower(parse_url($referer, PHP_URL_HOST) ?? '');

        $searchEngines = ['google', 'bing', 'yahoo', 'duckduckgo', 'baidu', 'yandex'];
        foreach ($searchEngines as $engine) {
            if (str_contains($host, $engine)) {
                return ['type' => 'organic', 'source' => $host];
            }
        }

        $socialDomains = [
            'facebook', 't.co', 'twitter', 'x.com',
            'instagram', 'linkedin', 'tiktok', 'youtube', 'pinterest',
        ];
        foreach ($socialDomains as $social) {
            if (str_contains($host, $social)) {
                return ['type' => 'social', 'source' => $host];
            }
        }

        return ['type' => 'referral', 'source' => $host];
    }

    protected function getDeviceType(): string
    {
        if ($this->agent->isTablet()) {
            return 'tablet';
        }
        if ($this->agent->isMobile()) {
            return 'mobile';
        }

        return 'desktop';
    }

    public function makeHash(string $ip, string $ua): string
    {
        return hash('sha256', $ip . $ua);
    }

    public function bustVisitorCache(string $hash): void
    {
        Cache::forget("visitor:{$hash}");
        Cache::forget("visitor:active:{$hash}");
        Cache::forget("visitor_v3_{$hash}");
    }

    public function resolveIsPwa(Request $request): bool
    {
        return $request->header('X-App-Mode') === 'standalone'
            || $request->boolean('is_pwa');
    }

    // Legacy
    public function trackRequest(Request $request): void
    {
        $this->dispatchTracking($request);
    }
}
