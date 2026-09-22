<?php

namespace App\Http\Middleware;

use App\Models\Visitor;
use App\Services\VisitorTrackingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitors
{
    public function __construct(
        protected VisitorTrackingService $trackingService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldSkipTracking($request)) {
            $this->attachVisitorToRequest($request);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Only track successful HTML page loads
        if ($response->getStatusCode() !== 200) {
            return;
        }

        if ($this->shouldSkipTracking($request)) {
            return;
        }

        $this->trackingService->dispatchTracking($request);
    }

    /* -----------------------------------------------------------------
     |  PRIVATE HELPERS
     | ----------------------------------------------------------------- */

    private function attachVisitorToRequest(Request $request): void
    {
        $hash = $this->trackingService->makeHash($request->ip(), (string) $request->userAgent());
        $cacheKey = "visitor:active:{$hash}";

        $visitor = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($hash) {
            return Visitor::where('hash', $hash)->first();
        });

        if (! $visitor instanceof Visitor) {
            return;
        }

        // Sync PWA status if it changed
        $isPwaRequest = $this->trackingService->resolveIsPwa($request);
        if ($visitor->is_pwa !== $isPwaRequest) {
            $visitor->update(['is_pwa' => $isPwaRequest]);
            $this->trackingService->bustVisitorCache($hash);
            $visitor->refresh();
        }

        view()->share('currentVisitor', $visitor);
        $request->attributes->set('current_visitor', $visitor);
    }

    private function shouldSkipTracking(Request $request): bool
    {
        // Always track PWA sync — needed for status updates
        if ($request->is('api/tracking/sync-pwa')) {
            return false;
        }

        // Non-GET or non-HTML requests
        if (! $request->isMethod('GET') || ! $request->acceptsHtml()) {
            return true;
        }

        // Livewire polling & update requests
        // Check both the header and the URL pattern
        if ($request->header('X-Livewire') || $request->is('livewire/*')) {
            return true;
        }

        // Language switch routes
        if ($request->is('lang/*')) {
            return true;
        }

        // All other API routes (except sync-pwa already handled above)
        if ($request->is('api/*')) {
            return true;
        }

        // Admin/debug panels
        if ($request->is('admin/*', 'horizon/*', 'telescope/*', 'nova/*')) {
            return true;
        }

        return false;
    }
}
