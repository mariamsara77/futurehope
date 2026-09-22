<?php

use Livewire\Component;
use App\Models\{Visitor, PageView, VisitorSession};

new class extends Component {

    public int $visitors = 0;
    public int $pageViews = 0;
    public int $sessions = 0;
    public int $pwa = 0;

    public function mount()
    {
        $this->visitors = Visitor::count();
        $this->pageViews = PageView::count();
        $this->sessions = VisitorSession::count();
        $this->pwa = Visitor::where('is_pwa', true)->count();
    }

    public function with(): array
    {
        return [
            'topPages' => PageView::query()
                ->select('url')
                ->selectRaw('COUNT(*) as views')
                ->groupBy('url')
                ->orderByDesc('views')
                ->limit(5)
                ->get(),

            'devices' => Visitor::query()
                ->whereNotNull('device_type')
                ->select('device_type')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('device_type')
                ->orderByDesc('total')
                ->get(),
        ];
    }
};
?>

<div class="space-y-6">

    <div>
        <flux:heading size="xl">Admin Dashboard</flux:heading>
        <flux:subheading>
            Welcome back! Here's your site overview.
        </flux:subheading>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <flux:card>
            <flux:subheading>Visitors</flux:subheading>
            <flux:heading size="2xl" class="mt-2">
                {{ number_format($visitors) }}
            </flux:heading>
        </flux:card>

        <flux:card>
            <flux:subheading>Page Views</flux:subheading>
            <flux:heading size="2xl" class="mt-2">
                {{ number_format($pageViews) }}
            </flux:heading>
        </flux:card>

        <flux:card>
            <flux:subheading>Sessions</flux:subheading>
            <flux:heading size="2xl" class="mt-2">
                {{ number_format($sessions) }}
            </flux:heading>
        </flux:card>

        <flux:card>
            <flux:subheading>PWA Installs</flux:subheading>
            <flux:heading size="2xl" class="mt-2">
                {{ number_format($pwa) }}
            </flux:heading>
        </flux:card>

    </div>

    {{-- Analytics --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <flux:card>
            <flux:heading level="3">Top Pages</flux:heading>

            <div class="mt-4 space-y-3">
                @forelse ($topPages as $page)
                <div class="flex justify-between gap-4">
                    <span class="truncate">
                        {{ $page->url }}
                    </span>

                    <flux:badge>
                        {{ number_format($page->views) }}
                    </flux:badge>
                </div>
                @empty
                <flux:text>No page data available.</flux:text>
                @endforelse
            </div>
        </flux:card>

        <flux:card>
            <flux:heading level="3">Device Usage</flux:heading>

            <div class="mt-4 space-y-4">
                @forelse ($devices as $device)

                <div>
                    <div class="flex justify-between mb-1">
                        <span>
                            {{ ucfirst($device->device_type) }}
                        </span>

                        <span>
                            {{ number_format($device->total) }}
                        </span>
                    </div>

                    <div class="h-2 rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="h-2 rounded-full bg-blue-500"
                            style="width: {{ min(($device->total / max($visitors, 1)) * 100, 100) }}%"></div>
                    </div>
                </div>

                @empty
                <flux:text>No device data available.</flux:text>
                @endforelse
            </div>
        </flux:card>

    </div>

</div>