<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VisitorEvent;
use App\Services\VisitorTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivitySyncController extends Controller
{
    public function __construct(
        protected VisitorTrackingService $trackingService
    ) {}

    /**
     * Sync offline/buffered activity events from the client.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'activities' => 'required|array|max:100',
            'activities.*.type' => 'required|string|max:50',
            'activities.*.key' => 'required|string|max:100',
            'activities.*.value' => 'nullable',
            'activities.*.timestamp' => 'required|numeric',
            'activities.*.id' => 'nullable|string',
        ]);

        try {
            $visitor = $request->attributes->get('current_visitor')
                ?? $this->trackingService->getOrCreateVisitor($request);

            $session = $visitor
                ? $this->trackingService->resolveActiveSession($visitor)
                : null;

            $count = 0;

            foreach ($validated['activities'] as $item) {
                $ts = $this->normalizeTimestamp((int) $item['timestamp']);

                // Sanity check: reject future timestamps and extremely old ones
                $now = now();
                if ($ts > $now->timestamp + 60 || $ts < $now->subDays(30)->timestamp) {
                    continue;
                }

                VisitorEvent::create([
                    'visitor_id' => $visitor?->id,
                    'session_id' => $session?->id,
                    'event_category' => $item['type'],
                    'event_action' => $item['key'],
                    'event_label' => is_array($item['value'] ?? null)
                                            ? json_encode($item['value'])
                                            : ($item['value'] ?? null),
                    'payload' => $item,
                    'created_at' => now()->setTimestamp($ts),
                ]);

                $count++;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Synced successfully',
                'count' => $count,
            ]);

        } catch (\Throwable $e) {
            Log::error('Offline sync failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Sync failed',
            ], 500);
        }
    }

    /* -----------------------------------------------------------------
     |  PRIVATE HELPERS
     | ----------------------------------------------------------------- */

    /**
     * Normalize a timestamp that may be in milliseconds or seconds.
     * JavaScript Date.now() returns milliseconds — Unix timestamps are seconds.
     */
    private function normalizeTimestamp(int $ts): int
    {
        // If timestamp has 13+ digits, it's in milliseconds
        return $ts > 1_000_000_000_000 ? (int) ($ts / 1000) : $ts;
    }
}