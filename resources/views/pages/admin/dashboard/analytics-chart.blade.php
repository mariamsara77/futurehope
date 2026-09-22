<?php

use Livewire\Component;
use App\Models\Visitor;
use App\Models\VisitorSession;
use App\Models\PageView;
use App\Models\VisitorEvent;

new class extends Component {
    public string $timeRange = '7days';

    // Chart datasets (fed to ApexCharts)
    public array $chartData = ['labels' => [], 'visitors' => [], 'sessions' => [], 'pageviews' => []]; // combined trend
    public array $sourceChartData = ['labels' => [], 'values' => []]; // origin_type breakdown (donut)
    public array $pageChartData = ['labels' => [], 'values' => []]; // top pages (bar)
    public array $eventChartData = ['labels' => [], 'values' => []]; // top events (bar)

    // Summary stats
    public int $totalVisitors = 0;
    public int $totalSessions = 0;
    public int $totalPageViews = 0;
    public int $totalEvents = 0;
    public string $avgSessionDuration = '0m 0s';

    // Table datasets
    public array $topSources = [];
    public array $topCampaigns = [];
    public array $topPages = [];
    public array $topEvents = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function updatedTimeRange(): void
    {
        $this->loadData();
    }

    protected function startDate()
    {
        return match ($this->timeRange) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(6)->startOfDay(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->subDays(6)->startOfDay(),
        };
    }

    protected function loadData(): void
    {
        $startDate = $this->startDate();

        $this->loadOverviewTrend($startDate);
        $this->loadSummary($startDate);
        $this->loadSources($startDate);
        $this->loadPages($startDate);
        $this->loadEvents($startDate);

        // Push fresh data to every chart at once; JS decides which to actually redraw.
        $this->dispatch('update-charts', visitorChart: $this->chartData, sourceChart: $this->sourceChartData, pageChart: $this->pageChartData, eventChart: $this->eventChartData);
    }

    protected function loadOverviewTrend($startDate): void
    {
        // Count per calendar day for each metric, keyed by raw date (Y-m-d) so all three
        // series can be aligned onto the exact same set of x-axis buckets.
        $visitors = Visitor::query()->where('is_bot', false)->where('first_seen_at', '>=', $startDate)->selectRaw('DATE(first_seen_at) as d')->selectRaw('COUNT(*) as total')->groupBy('d')->pluck('total', 'd');

        $sessions = VisitorSession::query()->where('started_at', '>=', $startDate)->selectRaw('DATE(started_at) as d')->selectRaw('COUNT(*) as total')->groupBy('d')->pluck('total', 'd');

        $pageViews = PageView::query()->where('created_at', '>=', $startDate)->selectRaw('DATE(created_at) as d')->selectRaw('COUNT(*) as total')->groupBy('d')->pluck('total', 'd');

        $dateKeys = collect()->merge($visitors->keys())->merge($sessions->keys())->merge($pageViews->keys())->unique()->sort()->values();

        $this->chartData = [
            'labels' => $dateKeys->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toArray(),
            'visitors' => $dateKeys->map(fn($d) => (int) ($visitors[$d] ?? 0))->toArray(),
            'sessions' => $dateKeys->map(fn($d) => (int) ($sessions[$d] ?? 0))->toArray(),
            'pageviews' => $dateKeys->map(fn($d) => (int) ($pageViews[$d] ?? 0))->toArray(),
        ];
    }

    protected function loadSummary($startDate): void
    {
        $this->totalVisitors = Visitor::query()->where('is_bot', false)->where('first_seen_at', '>=', $startDate)->count();

        $sessions = VisitorSession::query()
            ->where('started_at', '>=', $startDate)
            ->get(['seconds_spent']);

        $this->totalSessions = $sessions->count();

        $avgSeconds = (int) round($sessions->avg('seconds_spent') ?? 0);
        $this->avgSessionDuration = sprintf('%dm %ds', intdiv($avgSeconds, 60), $avgSeconds % 60);

        $this->totalPageViews = PageView::query()->where('created_at', '>=', $startDate)->count();

        $this->totalEvents = VisitorEvent::query()->where('created_at', '>=', $startDate)->count();
    }

    protected function loadSources($startDate): void
    {
        $origins = VisitorSession::query()->where('started_at', '>=', $startDate)->selectRaw('origin_type, COUNT(*) as total')->groupBy('origin_type')->orderByDesc('total')->get();

        $this->sourceChartData = [
            'labels' => $origins->pluck('origin_type')->map(fn($v) => $v ?: 'Unknown')->toArray(),
            'values' => $origins->pluck('total')->map(fn($v) => (int) $v)->toArray(),
        ];

        $this->topSources = VisitorSession::query()->where('started_at', '>=', $startDate)->whereNotNull('origin_source')->selectRaw('origin_source, COUNT(*) as total')->groupBy('origin_source')->orderByDesc('total')->limit(8)->get()->toArray();

        $this->topCampaigns = VisitorSession::query()->where('started_at', '>=', $startDate)->whereNotNull('utm_campaign')->selectRaw('utm_source, utm_medium, utm_campaign, COUNT(*) as total')->groupBy('utm_source', 'utm_medium', 'utm_campaign')->orderByDesc('total')->limit(8)->get()->toArray();
    }

    protected function loadPages($startDate): void
    {
        $pages = PageView::query()->where('created_at', '>=', $startDate)->selectRaw('COALESCE(title, url) as label')->selectRaw('url')->selectRaw('COUNT(*) as total')->selectRaw('AVG(view_duration) as avg_duration')->groupBy('label', 'url')->orderByDesc('total')->limit(10)->get();

        $this->pageChartData = [
            'labels' => $pages->pluck('label')->toArray(),
            'values' => $pages->pluck('total')->map(fn($v) => (int) $v)->toArray(),
        ];

        $this->topPages = $pages
            ->map(
                fn($p) => [
                    'label' => $p->label,
                    'url' => $p->url,
                    'total' => (int) $p->total,
                    'avg_duration' => (int) round((float) $p->avg_duration),
                ],
            )
            ->toArray();
    }

    protected function loadEvents($startDate): void
    {
        $events = VisitorEvent::query()->where('created_at', '>=', $startDate)->selectRaw("CONCAT(event_category, ' / ', event_action) as label")->selectRaw('COUNT(*) as total')->groupBy('label')->orderByDesc('total')->limit(10)->get();

        $this->eventChartData = [
            'labels' => $events->pluck('label')->toArray(),
            'values' => $events->pluck('total')->map(fn($v) => (int) $v)->toArray(),
        ];

        $this->topEvents = VisitorEvent::query()->where('created_at', '>=', $startDate)->selectRaw('event_category, event_action, COUNT(*) as total')->groupBy('event_category', 'event_action')->orderByDesc('total')->limit(10)->get()->toArray();
    }
}; ?>

<div class="space-y-6 antialiased" x-data="{ tab: 'overview' }">

    <style>
        .apexcharts-tooltip {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }
    </style>
    {{-- Top Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center gap-4">
                <div class="p-1.5 bg-pink-500 rounded-lg shadow-lg shadow-pink-500/20">
                    <flux:icon.chart-bar class="w-5 h-5 text-white" variant="mini" />
                </div>
                <flux:heading size="xl" class="font-black tracking-tight text-zinc-800 dark:text-zinc-100">
                    Analytics Dashboard
                </flux:heading>
            </div>
            <flux:subheading class="ml-9">Visitors, sessions, page views & events — all in one place</flux:subheading>
        </div>

        <div class="flex items-center gap-4">
            <div wire:loading.flex class="px-2 items-center gap-4">
                <flux:icon.loading />
                <span class="text-xs font-bold tracking-widest">Syncing</span>
            </div>

            <flux:select wire:model.live="timeRange" variant="listbox"
                class="border-none! shadow-none! bg-transparent min-w-[140px]!">
                <flux:select.option value="today">Today</flux:select.option>
                <flux:select.option value="7days">Last 7 Days</flux:select.option>
                <flux:select.option value="month">This Month</flux:select.option>
                <flux:select.option value="year">This Year</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Summary Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <flux:card class="p-4">
            <div class="text-xs font-bold tracking-widest text-zinc-500 uppercase">Visitors</div>
            <div class="text-2xl font-black text-zinc-800 dark:text-zinc-100 mt-1">
                {{ number_format($totalVisitors) }}
            </div>
        </flux:card>
        <flux:card class="p-4">
            <div class="text-xs font-bold tracking-widest text-zinc-500 uppercase">Sessions</div>
            <div class="text-2xl font-black text-zinc-800 dark:text-zinc-100 mt-1">
                {{ number_format($totalSessions) }}
            </div>
        </flux:card>
        <flux:card class="p-4">
            <div class="text-xs font-bold tracking-widest text-zinc-500 uppercase">Page Views</div>
            <div class="text-2xl font-black text-zinc-800 dark:text-zinc-100 mt-1">
                {{ number_format($totalPageViews) }}
            </div>
        </flux:card>
        <flux:card class="p-4">
            <div class="text-xs font-bold tracking-widest text-zinc-500 uppercase">Events</div>
            <div class="text-2xl font-black text-zinc-800 dark:text-zinc-100 mt-1">
                {{ number_format($totalEvents) }}
            </div>
        </flux:card>
        <flux:card class="p-4">
            <div class="text-xs font-bold tracking-widest text-zinc-500 uppercase">Avg. Session</div>
            <div class="text-2xl font-black text-zinc-800 dark:text-zinc-100 mt-1">
                {{ $avgSessionDuration }}
            </div>
        </flux:card>
    </div>

    {{-- Tab Nav --}}
    <div class="flex items-center gap-1 p-1 bg-zinc-400/10 rounded-xl w-fit">
        <button type="button" @click="tab = 'overview'"
            :class="tab === 'overview' ? 'bg-zinc-400/25 text-black dark:text-white' :
                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
            class="px-4 py-2 rounded-lg text-xs font-bold tracking-wide transition-all">
            Overview
        </button>
        <button type="button" @click="tab = 'sources'; $nextTick(() => window.renderSourceChart())"
            :class="tab === 'sources' ? 'bg-zinc-400/25 text-black dark:text-white' :
                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
            class="px-4 py-2 rounded-lg text-xs font-bold tracking-wide transition-all">
            Sources
        </button>
        <button type="button" @click="tab = 'pages'; $nextTick(() => window.renderPageChart())"
            :class="tab === 'pages' ? 'bg-zinc-400/25 text-black dark:text-white' :
                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
            class="px-4 py-2 rounded-lg text-xs font-bold tracking-wide transition-all">
            Pages
        </button>
        <button type="button" @click="tab = 'events'; $nextTick(() => window.renderEventChart())"
            :class="tab === 'events' ? 'bg-zinc-400/25 text-black dark:text-white' :
                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
            class="px-4 py-2 rounded-lg text-xs font-bold tracking-wide transition-all">
            Events
        </button>
    </div>
    <div wire:ignore>

        {{-- OVERVIEW TAB --}}
        <div x-show="tab === 'overview'" x-cloak>
            <flux:card>
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-4">
                        <span class="relative flex h-2 w-2">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-pink-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-pink-500"></span>
                        </span>
                        <span class="text-xs font-bold tracking-widest text-zinc-500">Real-time Traffic</span>
                    </div>
                </div>
                <div class="p-4 md:p-6">
                    <div id="main-visitor-chart" class="w-full min-h-[380px]"></div>
                </div>
            </flux:card>
        </div>

        {{-- SOURCES TAB --}}
        <div x-show="tab === 'sources'" x-cloak class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card>
                <div class="px-6 py-4">
                    <span class="text-xs font-bold tracking-widest text-zinc-500">Traffic by Origin</span>
                </div>
                <div class="p-4 md:p-6">
                    <div wire:ignore id="source-chart" class="w-full min-h-[320px]"></div>
                </div>
            </flux:card>

            <div class="space-y-6">
                <flux:card class="p-6">
                    <div class="text-xs font-bold tracking-widest text-zinc-500 mb-4">Top Referral Sources</div>
                    <div class="space-y-2">
                        @forelse ($topSources as $source)
                            <div class="flex items-center justify-between py-2 border-b border-zinc-400/25 last:">
                                <span
                                    class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $source['origin_source'] }}</span>
                                <span
                                    class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($source['total']) }}</span>
                            </div>
                        @empty
                            <div class="text-sm text-zinc-400 py-4 text-center">No data for this range</div>
                        @endforelse
                    </div>
                </flux:card>

                <flux:card class="p-6">
                    <div class="text-xs font-bold tracking-widest text-zinc-500 mb-4">Top UTM Campaigns</div>
                    <div class="space-y-2">
                        @forelse ($topCampaigns as $c)
                            <div class="flex items-center justify-between py-2 border-b border-zinc-400/25 last:">
                                <div class="min-w-0">
                                    <div class="text-sm text-zinc-700 dark:text-zinc-300 truncate">
                                        {{ $c['utm_campaign'] }}
                                    </div>
                                    <div class="text-[11px] text-zinc-400">{{ $c['utm_source'] }} /
                                        {{ $c['utm_medium'] }}
                                    </div>
                                </div>
                                <span
                                    class="text-sm font-bold text-zinc-800 dark:text-zinc-100 shrink-0 ml-2">{{ number_format($c['total']) }}</span>
                            </div>
                        @empty
                            <div class="text-sm text-zinc-400 py-4 text-center">No campaign data for this range</div>
                        @endforelse
                    </div>
                </flux:card>
            </div>
        </div>

        {{-- PAGES TAB --}}
        <div x-show="tab === 'pages'" x-cloak class="space-y-6">
            <flux:card>
                <div class="px-6 py-4">
                    <span class="text-xs font-bold tracking-widest text-zinc-500">Top Pages by Views</span>
                </div>
                <div class="p-4 md:p-6">
                    <div wire:ignore id="page-chart" class="w-full min-h-[360px]"></div>
                </div>
            </flux:card>

            <flux:card class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="text-left text-xs font-bold tracking-widest text-zinc-500 uppercase border-b border-zinc-400/25">
                                <th class="pb-4 pr-4">Page</th>
                                <th class="pb-4 pr-4">URL</th>
                                <th class="pb-4 pr-4 text-right">Views</th>
                                <th class="pb-4 text-right">Avg. Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topPages as $page)
                                <tr class="border-b border-zinc-400/25 last:">
                                    <td class="py-2.5 pr-4 font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $page['label'] }}
                                    </td>
                                    <td class="py-2.5 pr-4 text-zinc-400 truncate max-w-[240px]">{{ $page['url'] }}
                                    </td>
                                    <td class="py-2.5 pr-4 text-right font-bold text-zinc-800 dark:text-zinc-100">
                                        {{ number_format($page['total']) }}
                                    </td>
                                    <td class="py-2.5 text-right text-zinc-500">{{ $page['avg_duration'] }}s</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-zinc-400">No page views for this
                                        range
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>

        {{-- EVENTS TAB --}}
        <div x-show="tab === 'events'" x-cloak class="space-y-6">
            <flux:card>
                <div class="px-6 py-4">
                    <span class="text-xs font-bold tracking-widest text-zinc-500">Top Events</span>
                </div>
                <div class="p-4 md:p-6">
                    <div wire:ignore id="event-chart" class="w-full min-h-[360px]"></div>
                </div>
            </flux:card>

            <flux:card class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="text-left text-xs font-bold tracking-widest text-zinc-500 uppercase border-b border-zinc-400/25">
                                <th class="pb-4 pr-4">Category</th>
                                <th class="pb-4 pr-4">Action</th>
                                <th class="pb-4 text-right">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topEvents as $event)
                                <tr class="border-b border-zinc-400/25 last:">
                                    <td class="py-2.5 pr-4 font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $event['event_category'] }}
                                    </td>
                                    <td class="py-2.5 pr-4 text-zinc-500">{{ $event['event_action'] }}</td>
                                    <td class="py-2.5 text-right font-bold text-zinc-800 dark:text-zinc-100">
                                        {{ number_format($event['total']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-zinc-400">No events for this range
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    </div>
</div>

@script
    <script>
        const brandColor = '#f705eb';
        const isDark = () => document.documentElement.classList.contains('dark');

        // Keep chart instances + last known data on window so tab buttons can reach them.
        window.__analyticsCharts = window.__analyticsCharts || {};
        window.__analyticsData = window.__analyticsData || {
            visitorChart: $wire.chartData,
            sourceChart: $wire.sourceChartData,
            pageChart: $wire.pageChartData,
            eventChart: $wire.eventChartData,
        };

        const baseGrid = () => ({
            show: true,
            borderColor: isDark() ? '#27272a' : '#f4f4f5',
            strokeDashArray: 6,
            position: 'back',
            xaxis: {
                lines: {
                    show: false
                }
            },
            yaxis: {
                lines: {
                    show: true
                }
            },
            padding: {
                top: 10,
                right: 10,
                bottom: 0,
                left: 10
            },
        });

        const baseTooltip = () => ({
            theme: isDark() ? 'dark' : 'light',
            style: {
                fontSize: '12px'
            }
        });

        // ---- Combined trend: Visitors + Sessions + Page Views (multi-color area/line) ----
        const overviewColors = [brandColor, '#6366f1', '#22c55e']; // pink / indigo / green
        const overviewNames = ['Visitors', 'Sessions', 'Page Views'];

        const renderVisitorChart = () => {
            const el = document.querySelector('#main-visitor-chart');
            if (!el || typeof ApexCharts === 'undefined') return;
            const data = window.__analyticsData.visitorChart;

            const options = {
                series: [{
                        name: overviewNames[0],
                        data: data.visitors
                    },
                    {
                        name: overviewNames[1],
                        data: data.sessions
                    },
                    {
                        name: overviewNames[2],
                        data: data.pageviews
                    },
                ],
                chart: {
                    type: 'area',
                    height: 380,
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeout',
                        speed: 1000
                    },
                    background: 'transparent'
                },
                colors: overviewColors,
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.02,
                        stops: [0, 100]
                    },
                },
                stroke: {
                    curve: 'smooth',
                    width: 2.5,
                    colors: overviewColors,
                    lineCap: 'round'
                },
                grid: baseGrid(),
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '12px',
                    fontWeight: 600,
                    labels: {
                        colors: '#71717a'
                    },
                    markers: {
                        radius: 12
                    },
                },
                markers: {
                    size: 0,
                    colors: overviewColors,
                    strokeColors: isDark() ? '#18181b' : '#fff',
                    strokeWidth: 3,
                    hover: {
                        size: 6,
                        strokeWidth: 2
                    }
                },
                xaxis: {
                    categories: data.labels,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    labels: {
                        style: {
                            colors: '#71717a',
                            fontSize: '12px',
                            fontWeight: 500
                        },
                        offsetY: 5
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#71717a',
                            fontSize: '12px',
                            fontWeight: 500
                        },
                        formatter: (v) => v.toLocaleString()
                    }
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    theme: isDark() ? 'dark' : 'light',
                    shared: true,
                    intersect: false,
                    custom: function({
                        series,
                        dataPointIndex,
                        w
                    }) {
                        const label = w.globals.categoryLabels[dataPointIndex] ?? w.globals.labels[
                            dataPointIndex];

                        const rows = series.map((s, idx) => `
            <div class="flex items-center justify-between gap-6 py-1">
                <span class="flex items-center gap-4">
                    <span class="w-2.5 h-2 rounded-sm" style="background:${overviewColors[idx]}"></span>
                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">${overviewNames[idx]}</span>
                </span>
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">${(s[dataPointIndex] ?? 0).toLocaleString()}</span>
            </div>
        `).join('');

                        return `
            <div class="bg-white dark:bg-zinc-800 !border !border-zinc-200 dark:!border-white/10 !shadow-xl rounded-xl p-3 min-w-[180px]">
                <div class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-2 pb-2 border-b border-zinc-100 dark:border-white/5">
                    ${label}
                </div>
                <div class="flex flex-col gap-1">
                    ${rows}
                </div>
            </div>
        `;
                    },
                },
            };

            if (window.__analyticsCharts.visitor) window.__analyticsCharts.visitor.destroy();
            window.__analyticsCharts.visitor = new ApexCharts(el, options);
            window.__analyticsCharts.visitor.render();
        };

        // ---- Sources (donut) ----
        window.renderSourceChart = () => {
            const el = document.querySelector('#source-chart');
            if (!el || typeof ApexCharts === 'undefined') return;
            const data = window.__analyticsData.sourceChart;

            const options = {
                series: data.values,
                chart: {
                    type: 'donut',
                    height: 320,
                    background: 'transparent'
                },
                labels: data.labels,
                colors: ['#f705eb', '#6366f1', '#22c55e', '#f59e0b', '#06b6d4', '#ec4899'],
                legend: {
                    position: 'bottom',
                    labels: {
                        colors: '#71717a'
                    },
                    fontSize: '12px'
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '11px'
                    }
                },
                tooltip: baseTooltip(),
                stroke: {
                    colors: [isDark() ? '#18181b' : '#fff']
                },
            };

            if (window.__analyticsCharts.source) window.__analyticsCharts.source.destroy();
            window.__analyticsCharts.source = new ApexCharts(el, options);
            window.__analyticsCharts.source.render();
        };

        // ---- Pages (horizontal bar) ----
        window.renderPageChart = () => {
            const el = document.querySelector('#page-chart');
            if (!el || typeof ApexCharts === 'undefined') return;
            const data = window.__analyticsData.pageChart;

            const options = {
                series: [{
                    name: 'Views',
                    data: data.values
                }],
                chart: {
                    type: 'bar',
                    height: 360,
                    toolbar: {
                        show: false
                    },
                    background: 'transparent'
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '55%'
                    }
                },
                colors: [brandColor],
                grid: baseGrid(),
                xaxis: {
                    categories: data.labels,
                    labels: {
                        style: {
                            colors: '#71717a',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#71717a',
                            fontSize: '12px'
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: baseTooltip(),
            };

            if (window.__analyticsCharts.page) window.__analyticsCharts.page.destroy();
            window.__analyticsCharts.page = new ApexCharts(el, options);
            window.__analyticsCharts.page.render();
        };

        // ---- Events (bar) ----
        window.renderEventChart = () => {
            const el = document.querySelector('#event-chart');
            if (!el || typeof ApexCharts === 'undefined') return;
            const data = window.__analyticsData.eventChart;

            const options = {
                series: [{
                    name: 'Count',
                    data: data.values
                }],
                chart: {
                    type: 'bar',
                    height: 360,
                    toolbar: {
                        show: false
                    },
                    background: 'transparent'
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '55%'
                    }
                },
                colors: ['#6366f1'],
                grid: baseGrid(),
                xaxis: {
                    categories: data.labels,
                    labels: {
                        style: {
                            colors: '#71717a',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#71717a',
                            fontSize: '12px'
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: baseTooltip(),
            };

            if (window.__analyticsCharts.event) window.__analyticsCharts.event.destroy();
            window.__analyticsCharts.event = new ApexCharts(el, options);
            window.__analyticsCharts.event.render();
        };

        // Initial render (overview tab is visible by default)
        setTimeout(renderVisitorChart, 100);

        // Livewire pushes fresh data whenever the time-range filter changes.
        $wire.on('update-charts', (payload) => {
            window.__analyticsData = {
                visitorChart: payload.visitorChart,
                sourceChart: payload.sourceChart,
                pageChart: payload.pageChart,
                eventChart: payload.eventChart,
            };

            // Always keep the overview chart in sync since it's visible by default.
            if (window.__analyticsCharts.visitor) {
                window.__analyticsCharts.visitor.updateOptions({
                    xaxis: {
                        categories: payload.visitorChart.labels
                    }
                });
                window.__analyticsCharts.visitor.updateSeries([{
                        name: overviewNames[0],
                        data: payload.visitorChart.visitors
                    },
                    {
                        name: overviewNames[1],
                        data: payload.visitorChart.sessions
                    },
                    {
                        name: overviewNames[2],
                        data: payload.visitorChart.pageviews
                    },
                ]);
            } else {
                renderVisitorChart();
            }

            // For the other charts: only redraw if they've already been rendered (i.e. their tab was opened).
            if (window.__analyticsCharts.source) window.renderSourceChart();
            if (window.__analyticsCharts.page) window.renderPageChart();
            if (window.__analyticsCharts.event) window.renderEventChart();
        });
    </script>
@endscript
