<?php

use Livewire\Component;
use App\Models\{Visitor, PageView, VisitorSession, VisitorEvent};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use App\Exports\VisitorExport;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    use WithPagination;

    // Filter properties
    public $timeRange = 'week';
    public $chartType = 'visitors';
    public $deviceFilter;
    public $browserFilter;
    public $osFilter;
    public $platformFilter;
    public $userTypeFilter = 'all'; // all, registered, guest
    public $countryFilter;
    public $search = '';
    public $dateFrom;
    public $dateTo;

    // Sorting
    public $sortField = 'last_seen_at';
    public $sortDirection = 'desc';

    // Show filters panel
    public $showFilters = false;

    protected $queryString = [
        'timeRange' => ['except' => 'week'],
        'chartType' => ['except' => 'visitors'],
        'deviceFilter' => ['except' => ''],
        'browserFilter' => ['except' => ''],
        'osFilter' => ['except' => ''],
        'platformFilter' => ['except' => ''],
        'userTypeFilter' => ['except' => 'all'],
        'countryFilter' => ['except' => ''],
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'last_seen_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    protected function getVisitorsList()
    {
        $query = Visitor::with('user')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query
                        ->where('id', 'LIKE', "%{$this->search}%")
                        ->orWhere('hash', 'LIKE', "%{$this->search}%")
                        ->orWhere('city_name', 'LIKE', "%{$this->search}%")
                        ->orWhere('country_code', 'LIKE', "%{$this->search}%")
                        ->orWhereHas('user', function ($q) {
                            $q->where('name', 'LIKE', "%{$this->search}%")->orWhere('email', 'LIKE', "%{$this->search}%");
                        });
                });
            })
            ->orderBy($this->sortField, $this->sortDirection);

        $query = $this->applyAdvancedFilters($query);

        return $query->paginate(10);
    }

    protected function applyAdvancedFilters($query)
    {
        // Apply time range
        $query = $this->applyTimeRange($query);

        // Apply filters
        if ($this->deviceFilter) {
            $query->where('device_type', $this->deviceFilter);
        }

        if ($this->browserFilter) {
            $query->where('browser_family', $this->browserFilter);
        }

        if ($this->osFilter) {
            $query->where('os_family', $this->osFilter);
        }

        if ($this->platformFilter === 'pwa') {
            $query->where('is_pwa', true);
        } elseif ($this->platformFilter === 'browser') {
            $query->where('is_pwa', false);
        }

        if ($this->userTypeFilter === 'registered') {
            $query->whereHas('user');
        } elseif ($this->userTypeFilter === 'guest') {
            $query->whereDoesntHave('user');
        }

        if ($this->countryFilter) {
            $query->where('country_code', $this->countryFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('last_seen_at', '>=', Carbon::parse($this->dateFrom));
        }

        if ($this->dateTo) {
            $query->whereDate('last_seen_at', '<=', Carbon::parse($this->dateTo));
        }

        return $query;
    }

    protected function getBaseQuery()
    {
        $query = Visitor::query();
        $query = $this->applyAdvancedFilters($query);
        return $query;
    }

    protected function applyTimeRange($query)
    {
        $column = 'last_seen_at';

        return match ($this->timeRange) {
            'today' => $query->whereDate($column, today()),
            'yesterday' => $query->whereDate($column, today()->subDay()),
            'week' => $query->whereBetween($column, [today()->startOfWeek(), today()->endOfWeek()]),
            'month' => $query->whereBetween($column, [today()->startOfMonth(), today()->endOfMonth()]),
            'year' => $query->whereBetween($column, [today()->startOfYear(), today()->endOfYear()]),
            '7days' => $query->whereBetween($column, [today()->subDays(7), today()]),
            '30days' => $query->whereBetween($column, [today()->subDays(30), today()]),
            'custom' => $query->when($this->dateFrom && $this->dateTo, function ($q) {
                return $q->whereBetween('last_seen_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()]);
            }),
            default => $query,
        };
    }

    protected function getVisitorsCount($query)
    {
        return (clone $query)->count();
    }

    protected function getPageViewsCount($query)
    {
        return PageView::whereIn('visitor_id', (clone $query)->select('id'))->count();
    }

    protected function getSessionsCount($query)
    {
        return VisitorSession::whereIn('visitor_id', (clone $query)->select('id'))->count();
    }

    protected function getAvgSessionDuration($query)
    {
        return VisitorSession::whereIn('visitor_id', (clone $query)->select('id'))->whereNotNull('seconds_spent')->avg('seconds_spent');
    }

    protected function getTopPages($query, $limit = 5)
    {
        return PageView::whereIn('visitor_id', (clone $query)->select('id'))->select('url', DB::raw('count(*) as views'))->groupBy('url')->orderByDesc('views')->limit($limit)->get();
    }

    protected function getDevices($query)
    {
        return (clone $query)->whereNotNull('device_type')->select('device_type as device', DB::raw('count(*) as visitors'))->groupBy('device_type')->orderByDesc('visitors')->get();
    }

    protected function getBrowsers($query)
    {
        return (clone $query)->whereNotNull('browser_family')->select('browser_family as browser', DB::raw('count(*) as visitors'))->groupBy('browser_family')->orderByDesc('visitors')->get();
    }

    protected function getOperatingSystems($query)
    {
        return (clone $query)->whereNotNull('os_family')->select('os_family as os', DB::raw('count(*) as visitors'))->groupBy('os_family')->orderByDesc('visitors')->get();
    }

    protected function getCountries($query)
    {
        return (clone $query)->whereNotNull('country_code')->select('country_code', DB::raw('count(*) as visitors'))->groupBy('country_code')->orderByDesc('visitors')->limit(10)->get();
    }

    protected function getChangePercentage($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    protected function getPreviousPeriodData()
    {
        $query = Visitor::query();
        $col = 'last_seen_at';

        [$start, $end] = match ($this->timeRange) {
            'today' => [today()->subDay()->startOfDay(), today()->subDay()->endOfDay()],
            'yesterday' => [today()->subDays(2)->startOfDay(), today()->subDays(2)->endOfDay()],
            '7days' => [now()->subDays(13)->startOfDay(), now()->subDays(7)->endOfDay()],
            '30days' => [now()->subDays(59)->startOfDay(), now()->subDays(30)->endOfDay()],
            'week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->subDays(59)->startOfDay(), now()->subDays(30)->endOfDay()],
        };

        $query->whereBetween($col, [$start, $end]);

        return [
            'visitorsCount' => $this->getVisitorsCount($query),
            'pageViewsCount' => $this->getPageViewsCount($query),
            'sessionsCount' => $this->getSessionsCount($query),
            'avgSessionDuration' => $this->getAvgSessionDuration($query),
        ];
    }

    public function resetFilters()
    {
        $this->reset(['timeRange', 'deviceFilter', 'browserFilter', 'osFilter', 'platformFilter', 'userTypeFilter', 'countryFilter', 'search', 'dateFrom', 'dateTo']);
        $this->timeRange = 'week';
    }

    public function with(): array
    {
        $query = $this->getBaseQuery();
        $prev = $this->getPreviousPeriodData();

        // Data Calculation
        $stats = [
            'visitors' => $this->getVisitorsCount($query),
            'pageViews' => $this->getPageViewsCount($query),
            'sessions' => $this->getSessionsCount($query),
            'avgSession' => $this->getAvgSessionDuration($query),
            'pwaInstalls' => $this->getPwaInstallsCount($query), // নতুন মেথড যোগ করুন
        ];

        return [
            'visitors' => $this->getVisitorsList(),
            'devices' => $this->getDevices($query),
            'browsers' => $this->getBrowsers($query),
            'operatingSystems' => $this->getOperatingSystems($query),
            'countries' => $this->getCountries($query),
            'topPages' => $this->getTopPages($query),

            // Stats
            'visitorsCount' => $stats['visitors'],
            'pageViewsCount' => $stats['pageViews'],
            'sessionsCount' => $stats['sessions'],
            'avgSessionDuration' => $stats['avgSession'],
            'totalPwaInstalls' => $stats['pwaInstalls'],

            // Changes
            'visitorsChange' => $this->getChangePercentage($stats['visitors'], $prev['visitorsCount']),
            'pageViewsChange' => $this->getChangePercentage($stats['pageViews'], $prev['pageViewsCount']),
            'sessionsChange' => $this->getChangePercentage($stats['sessions'], $prev['sessionsCount']),
            'avgSessionChange' => $this->getChangePercentage($stats['avgSession'], $prev['avgSessionDuration']),
            'pwaChange' => $this->getChangePercentage($stats['pwaInstalls'], $prev['pwaInstalls'] ?? 0),
        ];
    }

    // নতুন মেথডটি আপনার Visitor বা Analytics মডেলে এভাবে থাকবে
    protected function getPwaInstallsCount($query)
    {
        return (clone $query)->where('has_installed_pwa', true)->count();
    }

    public function exportChartData()
    {
        $query = $this->getBaseQuery();
        return Excel::download(new VisitorExport($query), 'visitors_report_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
}; ?>

<div class="space-y-6">
    <!-- Header with title and actions -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Visitor Analytics</flux:heading>
            <flux:subheading class="mt-1">Track and analyze your visitor behavior</flux:subheading>
        </div>
        <div class="flex gap-4">
            <flux:button wire:click="exportChartData" icon="arrow-down-tray" variant="primary">
                Export Report
            </flux:button>
        </div>
    </div>

    <!-- Main Filters Card -->
    <flux:card class="overflow-visible">
        <div class="space-y-4">
            <!-- Quick Filters Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:select label="Time Range" wire:model.live="timeRange" placeholder="Select period...">
                    <flux:select.option value="today">Today</flux:select.option>
                    <flux:select.option value="yesterday">Yesterday</flux:select.option>
                    <flux:select.option value="7days">Last 7 Days</flux:select.option>
                    <flux:select.option value="30days">Last 30 Days</flux:select.option>
                    <flux:select.option value="week">This Week</flux:select.option>
                    <flux:select.option value="month">This Month</flux:select.option>
                    <flux:select.option value="year">This Year</flux:select.option>
                    <flux:select.option value="custom">Custom Range</flux:select.option>
                </flux:select>

                <flux:select label="Chart Data" wire:model.live="chartType">
                    <flux:select.option value="visitors">Unique Visitors</flux:select.option>
                    <flux:select.option value="pageviews">Page Views</flux:select.option>
                    <flux:select.option value="sessions">Sessions</flux:select.option>
                </flux:select>

                <flux:input label="Search" wire:model.live.debounce="search" placeholder="ID, name, location...">
                    <x-slot name="icon">
                        <flux:icon.search class="size-4" />
                    </x-slot>
                </flux:input>

                <div class="flex items-end gap-4">
                    <flux:button wire:click="$toggle('showFilters')" variant="subtle" class="w-full" icon="funnel">
                        {{ $showFilters ? 'Hide' : 'Show' }} Advanced Filters
                    </flux:button>
                    <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark"
                        tooltip="Reset all filters" />
                </div>
            </div>

            <!-- Custom Date Range -->
            @if ($timeRange === 'custom')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <flux:input type="date" label="From" wire:model.live="dateFrom" />
                    <flux:input type="date" label="To" wire:model.live="dateTo" />
                </div>
            @endif

            <!-- Advanced Filters Panel -->
            @if ($showFilters)
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4 border-t border-zinc-400/25">
                    <flux:select label="Device Type" wire:model.live="deviceFilter" placeholder="All Devices">
                        @foreach ($devices as $device)
                            <flux:select.option value="{{ $device->device }}">
                                {{ ucfirst($device->device) }} ({{ $device->visitors }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Browser" wire:model.live="browserFilter" placeholder="All Browsers">
                        @foreach ($browsers as $browser)
                            <flux:select.option value="{{ $browser->browser }}">
                                {{ $browser->browser }} ({{ $browser->visitors }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Operating System" wire:model.live="osFilter" placeholder="All OS">
                        @foreach ($operatingSystems as $os)
                            <flux:select.option value="{{ $os->os }}">
                                {{ $os->os }} ({{ $os->visitors }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Platform" wire:model.live="platformFilter" placeholder="All Platforms">
                        <flux:select.option value="pwa">PWA App</flux:select.option>
                        <flux:select.option value="browser">Browser</flux:select.option>
                    </flux:select>

                    <flux:select label="User Type" wire:model.live="userTypeFilter" placeholder="All Users">
                        <flux:select.option value="all">All Users</flux:select.option>
                        <flux:select.option value="registered">Registered Users</flux:select.option>
                        <flux:select.option value="guest">Guest Visitors</flux:select.option>
                    </flux:select>

                    <flux:select label="Country" wire:model.live="countryFilter" placeholder="All Countries">
                        @foreach ($countries as $country)
                            <flux:select.option value="{{ $country->country_code }}">
                                {{ $country->country_code }} ({{ $country->visitors }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <!-- Active Filters Display -->
            @php
                $activeFilters = collect([
                    'Device' => $deviceFilter,
                    'Browser' => $browserFilter,
                    'OS' => $osFilter,
                    'Platform' => $platformFilter,
                    'User Type' => $userTypeFilter !== 'all' ? $userTypeFilter : null,
                    'Country' => $countryFilter,
                ])
                    ->filter()
                    ->map(fn($value, $key) => "$key: $value")
                    ->values();
            @endphp

            @if ($activeFilters->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <flux:text size="sm" class="text-zinc-500">Active filters:</flux:text>
                    @foreach ($activeFilters as $filter)
                        <flux:badge size="sm" color="blue" variant="solid" class="flex items-center gap-1">
                            {{ $filter }}
                        </flux:badge>
                    @endforeach
                </div>
            @endif
        </div>
    </flux:card>

    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <flux:card class="relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-20 h-20 -mr-5 -mt-4 bg-blue-50 dark:bg-blue-950/30 rounded-full opacity-20">
            </div>
            <div class="flex justify-between items-start">
                <div>
                    <flux:subheading>Unique Visitors</flux:subheading>
                    <flux:heading size="2xl" class="mt-1">{{ number_format($visitorsCount) }}</flux:heading>
                </div>
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon.users class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-4">
                <flux:badge color="{{ $visitorsChange >= 0 ? 'green' : 'red' }}" size="sm"
                    icon="{{ $visitorsChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                    {{ $visitorsChange >= 0 ? '+' : '' }}{{ round($visitorsChange, 1) }}%
                </flux:badge>
                <flux:text size="xs" class="text-zinc-500">vs previous period</flux:text>
            </div>
        </flux:card>

        <flux:card class="relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-20 h-20 -mr-5 -mt-4 bg-purple-50 dark:bg-purple-950/30 rounded-full opacity-20">
            </div>
            <div class="flex justify-between items-start">
                <div>
                    <flux:subheading>Page Views</flux:subheading>
                    <flux:heading size="2xl" class="mt-1">{{ number_format($pageViewsCount) }}</flux:heading>
                </div>
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <flux:icon.document-text class="size-5 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-4">
                <flux:badge color="{{ $pageViewsChange >= 0 ? 'green' : 'red' }}" size="sm"
                    icon="{{ $pageViewsChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                    {{ $pageViewsChange >= 0 ? '+' : '' }}{{ round($pageViewsChange, 1) }}%
                </flux:badge>
                <flux:text size="xs" class="text-zinc-500">vs previous period</flux:text>
            </div>
        </flux:card>

        <flux:card class="relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-20 h-20 -mr-5 -mt-4 bg-amber-50 dark:bg-amber-950/30 rounded-full opacity-20">
            </div>
            <div class="flex justify-between items-start">
                <div>
                    <flux:subheading>Total Sessions</flux:subheading>
                    <flux:heading size="2xl" class="mt-1">{{ number_format($sessionsCount) }}</flux:heading>
                </div>
                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <flux:icon.chart-bar class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-4">
                <flux:badge color="{{ $sessionsChange >= 0 ? 'green' : 'red' }}" size="sm"
                    icon="{{ $sessionsChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                    {{ $sessionsChange >= 0 ? '+' : '' }}{{ round($sessionsChange, 1) }}%
                </flux:badge>
                <flux:text size="xs" class="text-zinc-500">vs previous period</flux:text>
            </div>
        </flux:card>

        <flux:card class="relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-20 h-20 -mr-5 -mt-4 bg-emerald-50 dark:bg-emerald-950/30 rounded-full opacity-20">
            </div>
            <div class="flex justify-between items-start">
                <div>
                    <flux:subheading>Avg. Duration</flux:subheading>
                    <flux:heading size="2xl" class="mt-1">
                        {{ $avgSessionDuration ? gmdate('i\m s\s', (int) $avgSessionDuration) : '0s' }}
                    </flux:heading>
                </div>
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <flux:icon.clock class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-4">
                <flux:badge color="{{ $avgSessionChange >= 0 ? 'green' : 'red' }}" size="sm"
                    icon="{{ $avgSessionChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                    {{ $avgSessionChange >= 0 ? '+' : '' }}{{ round($avgSessionChange, 1) }}%
                </flux:badge>
                <flux:text size="xs" class="text-zinc-500">vs previous period</flux:text>
            </div>
        </flux:card>
        <flux:card class="relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-20 h-20 -mr-5 -mt-4 bg-indigo-50 dark:bg-indigo-950/30 rounded-full opacity-20">
            </div>
            <div class="flex justify-between items-start">
                <div>
                    <flux:subheading>Total PWA Installs</flux:subheading>
                    <flux:heading size="2xl" class="mt-1">
                        {{ number_format($totalPwaInstalls) }}
                    </flux:heading>
                </div>
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <flux:icon.device-phone-mobile class="size-5 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-4">
                <flux:badge color="{{ $pwaChange >= 0 ? 'green' : 'red' }}" size="sm"
                    icon="{{ $pwaChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                    {{ $pwaChange >= 0 ? '+' : '' }}{{ round($pwaChange, 1) }}%
                </flux:badge>
                <flux:text size="xs" class="text-zinc-500">vs previous period</flux:text>
            </div>
        </flux:card>
    </div>

    <!-- Charts and Analytics Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Pages Card -->
        <flux:card class="lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <flux:heading level="3">Top Visited Pages</flux:heading>
                <flux:badge size="sm" color="zinc">{{ $topPages->count() }} pages</flux:badge>
            </div>
            <div class="space-y-2">
                @forelse ($topPages as $index => $page)
                    <div
                        class="flex items-center justify-between p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div class="flex items-center gap-4 min-w-0 flex-1">
                            <span class="text-xs font-mono text-zinc-400 w-5">#{{ $index + 1 }}</span>
                            <flux:text class="truncate" title="{{ $page->url }}">
                                {{ Str::limit($page->url, 50) }}
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-medium">{{ number_format($page->views) }}</span>
                            <div class="w-16 h-1.5 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full"
                                    style="width: {{ ($page->views / $topPages->first()->views) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <flux:icon.document-text class="size-8 mx-auto text-zinc-400" />
                        <flux:text class="mt-2">No page views data available</flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>

        <!-- Device Distribution Card -->
        <flux:card>
            <flux:heading level="3" class="mb-4">Device Usage</flux:heading>
            <div class="space-y-4">
                @forelse ($devices as $device)
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-4">
                                @php
                                    $deviceIcon = match ($device->device) {
                                        'mobile' => 'device-phone-mobile',
                                        'tablet' => 'device-tablet',
                                        'desktop' => 'computer-desktop',
                                        default => 'device-other',
                                    };
                                @endphp
                                <flux:icon name="{{ $deviceIcon }}" class="size-4 text-zinc-500" />
                                <flux:text>{{ ucfirst($device->device) }}</flux:text>
                            </div>
                            <div class="flex items-center gap-4">
                                <flux:text variant="bold">{{ number_format($device->visitors) }}</flux:text>
                                <flux:text size="xs" class="text-zinc-500 w-12">
                                    {{ round(($device->visitors / max($visitorsCount, 1)) * 100, 1) }}%
                                </flux:text>
                            </div>
                        </div>
                        <div class="w-full bg-zinc-100 dark:bg-zinc-700 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full transition-all duration-200"
                                style="width: {{ ($device->visitors / max($visitorsCount, 1)) * 100 }}%">
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <flux:icon.device-phone-mobile class="size-8 mx-auto text-zinc-400" />
                        <flux:text class="mt-2">No device data</flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>
    </div>

    <!-- Visitors Table Card -->
    <div class="overflow-hidden">
        <div class="flex items-center justify-between p-4">
            <flux:heading level="3">Visitor Details</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Total: {{ $visitors->total() }} visitors
            </flux:text>
        </div>

        <div class="overflow-x-auto">
            <flux:table :paginate="$visitors">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortField === 'id'" :direction="$sortDirection"
                        wire:click="sortBy('id')">ID</flux:table.column>
                    <flux:table.column sortable :sorted="$sortField === 'last_seen_at'" :direction="$sortDirection"
                        wire:click="sortBy('last_seen_at')">Visitor</flux:table.column>
                    <flux:table.column>Software</flux:table.column>
                    <flux:table.column>Device & OS</flux:table.column>
                    <flux:table.column>Location</flux:table.column>
                    <flux:table.column sortable :sorted="$sortField === 'is_pwa'" :direction="$sortDirection"
                        wire:click="sortBy('is_pwa')">Platform</flux:table.column>
                    <flux:table.column sortable :sorted="$sortField === 'last_seen_at'" :direction="$sortDirection"
                        wire:click="sortBy('last_seen_at')">Last Activity</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($visitors as $visitor)
                        <flux:table.row :key="$visitor->id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <flux:table.cell>
                                <span class="font-mono text-xs">#{{ $visitor->id }}</span>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-4">
                                    <flux:avatar size="sm" badge badge:size="xs"
                                        badge:color="{{ $visitor->isOnline() ? 'green' : 'zinc' }}"
                                        src="{{ $visitor->user?->avatar_url }}"
                                        name="{{ $visitor->user?->name ?? 'Guest' }}" />
                                    <div>
                                        @if ($visitor->user)
                                            <flux:heading size="sm">{{ $visitor->user->name }}</flux:heading>
                                            <flux:text size="xs" class="text-zinc-500">
                                                {{ $visitor->user->email }}
                                            </flux:text>
                                        @else
                                            <flux:heading size="sm">Guest Visitor</flux:heading>
                                            <flux:text size="xs" class="text-zinc-500 font-mono">
                                                {{ Str::limit($visitor->hash, 8) }}
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="space-y-1">
                                    <flux:badge size="sm"
                                        color="{{ match ($visitor->browser_family) {
                                            'Chrome' => 'orange',
                                            'Firefox' => 'red',
                                            'Safari' => 'indigo',
                                            'Edge' => 'cyan',
                                            default => 'zinc',
                                        } }}"
                                        class="whitespace-nowrap">
                                        {{ $visitor->browser_family ?? 'Unknown' }}
                                    </flux:badge>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @php
                                    $osFamily = strtolower($visitor->os_family ?? '');

                                    $config = match ($osFamily) {
                                        'androidos', 'android' => [
                                            'color' => 'emerald',
                                            'icon' => 'device-phone-mobile',
                                            'name' => 'Android',
                                        ],
                                        'ios', 'iphone' => [
                                            'color' => 'slate',
                                            'icon' => 'device-phone-mobile',
                                            'name' => 'iOS',
                                        ],
                                        'ipad' => ['color' => 'slate', 'icon' => 'device-tablet', 'name' => 'iPadOS'],
                                        'macos', 'mac' => [
                                            'color' => 'slate',
                                            'icon' => 'computer-desktop',
                                            'name' => 'macOS',
                                        ],
                                        'windows', 'win' => [
                                            'color' => 'sky',
                                            'icon' => 'computer-desktop',
                                            'name' => 'Windows',
                                        ],
                                        'linux' => [
                                            'color' => 'amber',
                                            'icon' => 'computer-desktop',
                                            'name' => 'Linux',
                                        ],
                                        'ubuntu' => [
                                            'color' => 'amber',
                                            'icon' => 'computer-desktop',
                                            'name' => 'Ubuntu',
                                        ],
                                        'chromeos' => [
                                            'color' => 'yellow',
                                            'icon' => 'computer-desktop',
                                            'name' => 'Chrome OS',
                                        ],
                                        default => [
                                            'color' => 'gray',
                                            'icon' => 'question-mark-circle',
                                            'name' => ucfirst($visitor->os_family ?? 'Unknown'),
                                        ],
                                    };
                                @endphp

                                <flux:badge :color="$config['color']" size="sm" class="gap-2 px-2.5 py-1">
                                    <flux:icon :name="$config['icon']" class="size-3.5" />
                                    <span>{{ $config['name'] }}</span>
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-1">
                                    <span>{{ $visitor->city_name ?? 'Unknown' }}</span>
                                    @if ($visitor->country_code)
                                        <span class="text-xs text-zinc-400">({{ $visitor->country_code }})</span>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" color="{{ $visitor->is_pwa ? 'indigo' : 'zinc' }}"
                                    variant="pill">
                                    {{ $visitor->is_pwa ? 'PWA' : 'Web' }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex flex-col">
                                    <span class="text-sm">{{ $visitor->last_seen_at->format('M d, H:i') }}</span>
                                    <span
                                        class="text-xs text-zinc-500">{{ $visitor->last_seen_at->diffForHumans() }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:button variant="ghost" size="sm" icon="eye"
                                    href="{{ route('admin.dashboard.visitor.details', $visitor->id) }}"
                                    tooltip="View details" />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-12">
                                <flux:icon.users class="size-12 mx-auto text-zinc-300" />
                                <flux:heading level="3" class="mt-4">No visitors found</flux:heading>
                                <flux:text class="mt-1">Try adjusting your filters</flux:text>
                                <flux:button wire:click="resetFilters" variant="subtle" size="sm"
                                    class="mt-4">
                                    Reset Filters
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
