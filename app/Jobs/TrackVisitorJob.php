<?php

namespace App\Jobs;

use App\Services\VisitorTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TrackVisitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry 3 times with exponential back-off: 5s → 15s → 30s
     */
    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public int $timeout = 30;

    /**
     * Do not hold the job if the queue worker is restarting —
     * tracking data is non-critical, so drop rather than pile up.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly array $payload
    ) {
        $this->onQueue('tracking');
    }

    public function handle(VisitorTrackingService $service): void
    {
        try {
            $service->processTrackingPayload($this->payload);
        } catch (\Throwable $e) {
            Log::error('TrackVisitorJob failed', [
                'message' => $e->getMessage(),
                'payload' => $this->payload,
            ]);
            throw $e;
        }
    }
}