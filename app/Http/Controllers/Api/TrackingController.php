<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use App\Services\VisitorTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    public function __construct(
        protected VisitorTrackingService $trackingService
    ) {}

    /**
     * Sync PWA install status from the frontend.
     */
    public function syncPwaStatus(Request $request): JsonResponse
    {
        $request->validate([
            'is_pwa'        => 'required|boolean',
            'has_installed' => 'nullable|boolean',
        ]);

        try {
            $ip = $this->resolveClientIp($request);

            /** @var Visitor|null $visitor */
            $visitor = $request->attributes->get('current_visitor')
                ?? $this->trackingService->getOrCreateVisitorFromData([
                    'ip'         => $ip,
                    'user_agent' => (string) $request->userAgent(),
                    'user_id'    => Auth::id(),
                    'is_pwa'     => $this->trackingService->resolveIsPwa($request),
                ]);

            if (! $visitor) {
                return response()->json(['status' => 'ignored'], 200);
            }

            $isPwa        = $request->boolean('is_pwa');
            $hasInstalled = $request->boolean('has_installed');

            $updateData = [
                'is_pwa'       => $isPwa,
                'last_seen_at' => now(),
            ];

            if ($hasInstalled || $isPwa) {
                $updateData['has_installed_pwa'] = true;
            }

            $visitor->update($updateData);

            $hash = $this->trackingService->makeHash(
                $ip,
                (string) $request->userAgent()
            );
            $this->trackingService->bustVisitorCache($hash);

            Log::info('PWA Sync Success', [
                'visitor_id'        => $visitor->id,
                'is_pwa'            => $isPwa,
                'has_installed_pwa' => $visitor->fresh()->has_installed_pwa,
                'ip'                => $ip,
            ]);

            return response()->json([
                'status'        => 'success',
                'is_pwa'        => $isPwa,
                'has_installed' => (bool) $visitor->has_installed_pwa,
                'visitor_id'    => $visitor->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('PWA Sync Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Track client-side events (page view, click, hardware, custom).
     */
    public function trackEvent(Request $request): JsonResponse
    {
        try {
            $ip = $this->resolveClientIp($request);

            /** @var Visitor|null $visitor */
            $visitor = $request->attributes->get('current_visitor')
                ?? $this->trackingService->getOrCreateVisitorFromData([
                    'ip'         => $ip,
                    'user_agent' => (string) $request->userAgent(),
                    'user_id'    => Auth::id(),
                    'is_pwa'     => $this->trackingService->resolveIsPwa($request),
                ]);

            if (! $visitor) {
                return response()->json(['status' => 'ignored'], 200);
            }

            $category = $request->input('category', 'interaction');
            $action   = $request->input('action', 'click');
            $payload  = $request->input('payload', []);
            $label    = $payload['label'] ?? $request->input('label');

            if ($category === 'system') {
                $this->updateVisitorSpecs($request, $visitor, $payload, $ip);
            }

            if ($category === 'page' && $action === 'view') {
                $this->handlePageView($request, $visitor, $payload, $ip);
            }

            $this->trackingService->trackEvent($visitor, $category, $action, $label, $payload);

            return response()->json(['status' => 'success']);
        } catch (\Throwable $e) {
            Log::error('Tracking Controller Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Resolve the real visitor IP when Laravel is behind Cloudflare,
     * Nginx, a load balancer, or another reverse proxy.
     */
    private function resolveClientIp(Request $request): string
    {
        $candidates = [
            $request->header('CF-Connecting-IP'),
            $request->header('X-Real-IP'),
            $request->header('X-Forwarded-For'),
            $request->ip(),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            foreach (explode(',', $candidate) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    private function handlePageView(
        Request $request,
        Visitor $visitor,
        array $payload,
        string $ip
    ): void {
        $url  = $payload['url'] ?? $request->fullUrl();
        $path = $payload['path'] ?? (parse_url($url, PHP_URL_PATH) ?: '/');

        $data = [
            'ip'           => $ip,
            'user_agent'   => (string) $request->userAgent(),
            'url'          => $url,
            'route_name'   => $this->guessRouteName($path, $payload['route_name'] ?? null),
            'referer'      => $payload['referrer'] ?? $request->headers->get('referer'),
            'user_id'      => Auth::id(),
            'is_pwa'       => $this->trackingService->resolveIsPwa($request) || ! empty($payload['is_pwa']),
            'utm_source'   => $payload['utm_source'] ?? $request->query('utm_source'),
            'utm_medium'   => $payload['utm_medium'] ?? $request->query('utm_medium'),
            'utm_campaign' => $payload['utm_campaign'] ?? $request->query('utm_campaign'),
            'load_time_ms' => $payload['load_time_ms'] ?? 0,
            'page_title'   => $payload['title'] ?? null,
            'timestamp'    => now()->toISOString(),
        ];

        $this->trackingService->processTrackingPayload($data);
    }

    private function guessRouteName(?string $path, ?string $frontendName = null): ?string
    {
        if ($frontendName) {
            return $frontendName;
        }

        if (! $path || $path === '/') {
            return 'home';
        }

        $map = [
            '/products' => 'products.index',
            '/cart'     => 'cart',
            '/checkout' => 'checkout',
            '/account'  => 'account',
            '/blog'    => 'blog.index',
            '/contact' => 'contact',
            '/about'   => 'about',
        ];

        if (isset($map[$path])) {
            return $map[$path];
        }

        if (str_starts_with($path, '/products/')) {
            return 'products.show';
        }

        if (str_starts_with($path, '/blog/')) {
            return 'blog.show';
        }

        return str_replace('/', '.', trim($path, '/')) ?: 'home';
    }

    private function updateVisitorSpecs(
        Request $request,
        Visitor $visitor,
        array $data,
        string $ip
    ): void {
        $update = [];

        if (! empty($data['timezone'])) {
            $update['timezone'] = $data['timezone'];
        }

        if (! empty($data['screen_res'])) {
            $res     = $data['screen_res'];
            $current = $visitor->device_model ?? '';

            if (! str_contains($current, $res)) {
                $update['device_model'] = trim($current . ' | ' . $res, ' |');
            }
        }

        if (empty($update)) {
            return;
        }

        $update['last_seen_at'] = now();
        $visitor->update($update);

        $hash = $this->trackingService->makeHash(
            $ip,
            (string) $request->userAgent()
        );

        $this->trackingService->bustVisitorCache($hash);
    }
}
