<?php

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Flux\Flux;
use App\Jobs\RunArtisanCommandJob;
use Livewire\Attributes\Layout;

new  class extends Component {
    public string $statusMessage = '';
    public array $commandHistory = [];
    public bool $isProcessing = false;
    public int $progress = 0;

    /**
     * Get all custom and system artisan commands mapping.
     */
    protected function getCommands(): array
    {
        return [
            'super_clean_prod' => [
                'title' => 'Super Clean & Cache',
                'cmd' => 'super:clean --prod',
                'icon' => 'sparkles',
                'color' => 'indigo',
                'desc' => 'Clears all and rebuilds production performance cache.',
            ],
            'super_clean' => [
                'title' => 'Super Clean (Clear Only)',
                'cmd' => 'super:clean',
                'icon' => 'arrow-path',
                'color' => 'purple',
                'desc' => 'Clears configuration, route, view, and application cache.',
            ],
            'google_index' => [
                'title' => 'Google Index Sitemap',
                'cmd' => 'google:auto-index-sitemap',
                'icon' => 'bolt',
                'color' => 'amber',
                'desc' => 'Pushes all target sitemap URLs directly to Google Indexing API.',
            ],
            'sitemap_generate' => [
                'title' => 'Regenerate Sitemap',
                'cmd' => 'sitemap:generate',
                'icon' => 'globe-alt',
                'color' => 'emerald',
                'desc' => 'Forces real-time regeneration of the Totthobox sitemap.xml.',
            ],
            'news_scrape_all' => [
                'title' => 'Scrape All News',
                'cmd' => 'news:scrape',
                'icon' => 'cloud-arrow-down',
                'color' => 'blue',
                'desc' => 'Triggers the scraper engine to pull from all active RSS feeds.',
            ],
            'content_improve' => [
                'title' => 'AI Content Auto-Improve',
                'cmd' => 'content:auto-improve',
                'icon' => 'cpu-chip',
                'color' => 'rose',
                'desc' => 'Runs full AI rewrite cycle on matching database content.',
            ],
            'content_improve_dry' => [
                'title' => 'AI Improve Stats (Dry Run)',
                'cmd' => 'content:auto-improve --dry-run',
                'icon' => 'document-chart-bar',
                'color' => 'zinc',
                'desc' => 'Runs a safety audit log of AI content without saving changes.',
            ],
            'telescope_prune' => [
                'title' => 'Prune Telescope Logs',
                'cmd' => 'telescope:prune --hours=24',
                'icon' => 'trash',
                'color' => 'red',
                'desc' => 'Prunes database storage by removing logs older than 24 hours.',
            ],
        ];
    }

    /**
     * Run the selected Artisan command.
     */
    public function runCommand(string $key)
    {
        $commands = $this->getCommands();
        if (!isset($commands[$key])) {
            return;
        }

        $this->isProcessing = true;
        $this->statusMessage = 'Dispatched to Server Queue...';
        $this->progress = 100; // কিউতে পাঠানো শেষ তাই প্রগ্রেস ফুল

        try {
            // লারাভেল কিউ সার্ভারে জবটি পুশ করা হলো
            RunArtisanCommandJob::dispatch($commands[$key]['cmd'], $commands[$key]['title']);

            array_unshift($this->commandHistory, [
                'time' => now()->format('H:i:s'),
                'label' => $commands[$key]['title'],
                'cmd' => 'php artisan ' . $commands[$key]['cmd'],
                'status' => 'success',
                'output' => 'Job successfully pushed to Laravel Queue. The server will safely execute this in the background without affecting your browser session. Monitor live via Laravel Pulse/Horizon dashboard.',
            ]);

            Flux::toast(text: $commands[$key]['title'] . ' added to queue.', variant: 'success');
        } catch (\Exception $e) {
            $this->statusMessage = 'Failed to push job to queue.';
            Log::error("Queue Dispatch Error [{$key}]: " . $e->getMessage());

            array_unshift($this->commandHistory, [
                'time' => now()->format('H:i:s'),
                'label' => $commands[$key]['title'],
                'cmd' => 'php artisan ' . $commands[$key]['cmd'],
                'status' => 'error',
                'output' => $e->getMessage(),
            ]);
            Flux::toast(text: 'Queue dispatch failed.', variant: 'danger');
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Clear the local component terminal panel logs.
     */
    public function clearLogs()
    {
        $this->commandHistory = [];
    }
}; ?>

<div class="space-y-8">
    <header class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="p-2 bg-indigo-500/10 dark:bg-indigo-500/20 rounded-xl">
                <flux:icon icon="cpu-chip" variant="mini" class="text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
                <flux:heading size="xl" level="1">System Infrastructure Dashboard</flux:heading>
                <flux:subheading>Manage background automated actions, scrapers, AI features, and operational caches
                </flux:subheading>
            </div>
        </div>
        <div class="flex gap-4">
            <flux:badge variant="outline" color="zinc" class="font-mono text-xs">Laravel v{{ app()->version() }}
            </flux:badge>
            <flux:badge variant="outline" color="zinc" class="font-mono text-xs">PHP v{{ PHP_VERSION }}</flux:badge>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($this->getCommands() as $key => $info)
                    <div
                        class="flex flex-col justify-between p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl">
                        <div class="space-y-1 mb-4">
                            <div class="flex items-center gap-4">
                                <flux:icon :icon="$info['icon']" variant="mini" class="text-zinc-500" />
                                <flux:heading size="sm" class="font-bold">{{ $info['title'] }}</flux:heading>
                            </div>
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 leading-snug">
                                {{ $info['desc'] }}
                            </flux:text>
                            <div class="pt-1">
                                <span
                                    class="font-mono text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                    php artisan {{ $info['cmd'] }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <flux:button wire:click="runCommand('{{ $key }}')" size="sm"
                                class="w-full justify-center" :disabled="$isProcessing">
                                Execute Command
                            </flux:button>

                            <div wire:loading wire:target="runCommand('{{ $key }}')" class="mt-2">
                                <flux:progress variant="bouncing" color="indigo" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:card class="p-0 bg-zinc-950 border-zinc-900 shadow-2xl rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 bg-zinc-900/40 border-b border-zinc-900">
                    <div class="flex items-center gap-4">
                        <div class="flex gap-2">
                            <div class="w-2.5 h-2 rounded-full bg-rose-500"></div>
                            <div class="w-2.5 h-2 rounded-full bg-amber-500"></div>
                            <div class="w-2.5 h-2 rounded-full bg-zinc-400/10"></div>
                        </div>
                        <flux:text size="xs" class="font-mono font-bold tracking-widest text-zinc-500 uppercase">
                            Console_Outputs_Stream
                        </flux:text>
                    </div>
                    <flux:button wire:click="clearLogs" variant="ghost" size="sm"
                        class="hover:bg-zinc-900 text-zinc-400 border border-zinc-800">
                        Flush History
                    </flux:button>
                </div>

                <div class="p-6 h-[420px] overflow-y-auto font-mono text-xs space-y-4">
                    @forelse($commandHistory as $log)
                        <div class="space-y-1.5 border-b border-zinc-900 pb-4 last: last:pb-0">
                            <div class="flex items-center justify-between text-zinc-500 text-[11px]">
                                <span class="flex items-center gap-2 font-bold">
                                    <span
                                        class="{{ $log['status'] === 'error' ? 'text-rose-500' : 'text-emerald-500' }}">
                                        {{ $log['status'] === 'error' ? '●' : '●' }}
                                    </span>
                                    {{ $log['label'] }}
                                </span>
                                <span>{{ $log['time'] }}</span>
                            </div>
                            <div
                                class="text-zinc-400 bg-zinc-900/60 p-2.5 rounded-lg border border-zinc-900 select-all font-mono whitespace-pre-wrap break-all">
                                <span class="text-zinc-600 block mb-2">$ {{ $log['cmd'] }}</span>
                                {{ $log['output'] }}
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center text-zinc-600 space-y-2 py-20">
                            <flux:icon icon="command-line" variant="outline" class="w-8 h-8 opacity-20" />
                            <flux:text class="italic text-zinc-500">Ready state. Trigger an operational action from
                                above...
                            </flux:text>
                        </div>
                    @endforelse
                </div>
            </flux:card>
        </div>

        <div class="lg:col-span-4 space-y-6">
            <flux:card class="space-y-4">
                <flux:heading size="lg">Environment Context</flux:heading>
                <div class="space-y-2.5">
                    <div
                        class="flex justify-between items-center p-2.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/40 dark:border-zinc-800/30">
                        <flux:text size="sm" class="font-medium">Environment</flux:text>
                        <flux:badge color="primary" variant="pill" class="uppercase text-xs tracking-wider">
                            {{ app()->environment() }}
                        </flux:badge>
                    </div>
                    <div
                        class="flex justify-between items-center p-2.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/40 dark:border-zinc-800/30">
                        <flux:text size="sm" class="font-medium">Application Debug</flux:text>
                        <flux:badge :color="config('app.debug') ? 'rose' : 'emerald'" variant="pill" class="text-xs">
                            {{ config('app.debug') ? 'Active' : 'Disabled' }}
                        </flux:badge>
                    </div>
                    <div
                        class="flex justify-between items-center p-2.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/40 dark:border-zinc-800/30">
                        <flux:text size="sm" class="font-medium">Maintenance App State</flux:text>
                        <flux:badge :color="app()->isDownForMaintenance() ? 'amber' : 'emerald'" variant="pill"
                            class="text-xs">
                            {{ app()->isDownForMaintenance() ? 'Live Maintenance' : 'Operational' }}
                        </flux:badge>
                    </div>
                </div>
            </flux:card>

            <div
                class="relative group p-6 rounded-2xl dark:bg-indigo-700 shadow-xl shadow-indigo-500/10 overflow-hidden">
                <div
                    class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition-transform duration-200">
                    <flux:icon icon="bolt" class="w-32 h-32" />
                </div>
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-3">
                        <flux:icon icon="light-bulb" class="text-indigo-200" variant="mini" />
                        <flux:heading class="text-white font-bold">Cron Management Notice</flux:heading>
                    </div>
                    <flux:text class="text-indigo-100/90 text-xs leading-relaxed">
                        These buttons run interactive server sub-processes via standard artisan outputs.
                        Manual triggers do not affect or interrupt your pre-existing system cron intervals configured in
                        <code>console.php</code>.
                    </flux:text>
                </div>
            </div>
        </div>
    </div>
</div>
