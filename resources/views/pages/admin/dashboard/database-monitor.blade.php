<?php

use Livewire\Component;
use App\Services\MonitorService;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\Layout;

new  class extends Component {
    public string $tab = 'overview';
    public bool $showKillModal = false;
    public ?int $killPid = null;
    public string $killCmd = '';
    public string $killMessage = '';
    public bool $killSuccess = false;
    public string $searchTable = '';
    public string $searchProcess = '';
    public string $sortProcessBy = 'cpu';

    protected MonitorService $service;

    public function boot(): void
    {
        $this->service = new MonitorService();
    }

    public function getFixedProcesses(int $limit = 30): array
    {
        $raw = Process::run("ps aux --sort=-%cpu | awk 'NR>1 {printf \"%s|%s|%s|%s|%s|%s|%s|\", $1,$2,$3,$4,$5,$6,$8; for(i=11;i<=NF;i++) printf \"%s \", \$i; print \"\"}' | head -n {$limit}")->output();

        $processes = [];
        foreach (explode("\n", trim($raw)) as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $p = explode('|', $line);
            if (count($p) < 8) {
                continue;
            }

            $processes[] = [
                'user' => trim($p[0]),
                'pid' => (int) $p[1],
                'cpu' => (float) $p[2],
                'mem' => (float) $p[3],
                'vsz_mb' => round($p[4] / 1024, 1),
                'rss_mb' => round($p[5] / 1024, 1),
                'stat' => $p[6],
                'command' => trim($p[7]),
            ];
        }
        return $processes;
    }

    public function with(): array
    {
        $server = $this->service->getServerStats();
        $processes = $this->getFixedProcesses(30);
        $tables = $this->service->getTableStats();
        $mysql = $this->service->getMysqlStatus();
        $mysqlProc = $this->service->getMysqlProcessList();
        $jobs = $this->service->getJobStats();
        $diskIO = $this->service->getDiskIO();

        // Process search
        if ($this->searchProcess) {
            $term = strtolower($this->searchProcess);
            $processes = array_filter($processes, fn($p) => str_contains(strtolower($p['command']), $term) || str_contains(strtolower($p['user']), $term) || str_contains((string) $p['pid'], $this->searchProcess));
        }

        // Process sort
        $sortBy = match ($this->sortProcessBy) {
            'mem' => 'mem',
            'rss' => 'rss_mb',
            default => 'cpu',
        };
        usort($processes, fn($a, $b) => $b[$sortBy] <=> $a[$sortBy]);

        // Table search
        $filteredTables = $this->searchTable
            ? array_filter((array) $tables, function ($t) {
                $name = is_object($t) ? $t->name ?? '' : $t['name'] ?? '';
                return str_contains(strtolower($name), strtolower($this->searchTable));
            })
            : $tables;

        return [
            'server' => $server,
            'processes' => array_values($processes),
            'tables' => array_values((array) $filteredTables),
            'allTables' => $tables,
            'mysql' => $mysql,
            'mysqlProcesses' => $mysqlProc,
            'jobs' => $jobs,
            'diskIO' => $diskIO,
            'totalDbSize' => collect($tables)->sum(fn($t) => is_object($t) ? $t->size_mb ?? 0 : $t['size_mb'] ?? 0),
            'updatedAt' => now()->format('H:i:s'),
        ];
    }

    public function confirmKill(int $pid, string $cmd): void
    {
        $this->killPid = $pid;
        $this->killCmd = $cmd;
        $this->killMessage = '';
        $this->killSuccess = false;
        $this->showKillModal = true;
    }

    public function executeKill(): void
    {
        if (!$this->killPid) {
            return;
        }

        $result = $this->service->killProcess($this->killPid);
        $this->killSuccess = $result['success'] ?? false;
        $this->killMessage = $result['message'] ?? 'Unknown result';

        if ($this->killSuccess) {
            $this->dispatch('process-killed');
        }
    }

    public function closeKillModal(): void
    {
        $this->showKillModal = false;
        $this->killPid = null;
        $this->killCmd = '';
        $this->killMessage = '';
    }

    public function setSortProcess(string $by): void
    {
        $this->sortProcessBy = $by;
    }
}; ?>

@php
// Helper to safely get value from array or object
$val = function ($item, string $key, $default = '—') {
if (is_array($item)) {
return $item[$key] ?? $default;
}
if (is_object($item)) {
return $item->{$key} ?? $default;
}
return $default;
};
@endphp

<div class="space-y-6" x-data="{ autoRefresh: false, ticker: null }" x-init="$watch('autoRefresh', v => {
    clearInterval(ticker);
    if (v) ticker = setInterval(() => $wire.$refresh(), 5000);
})">
    {{-- Kill Modal --}}
    <flux:modal wire:model="showKillModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Terminate Process</flux:heading>
                <flux:text class="mt-2">
                    @if ($killMessage)
                    {{ $killMessage }}
                    @else
                    Kill PID <flux:badge color="red">{{ $killPid }}</flux:badge>
                    <span class="font-mono text-sm">{{ $killCmd }}</span>?
                    @endif
                </flux:text>
            </div>

            @if ($killMessage)
            <div class="flex justify-end">
                <flux:button wire:click="closeKillModal" variant="primary">Close</flux:button>
            </div>
            @else
            <div class="flex gap-4 justify-end">
                <flux:button wire:click="closeKillModal" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="executeKill" variant="danger">Kill Process</flux:button>
            </div>
            @endif
        </div>
    </flux:modal>

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">ServerPulse</flux:heading>
            <flux:text class="mt-1">
                {{ $val($server, 'hostname', 'Server') }} · Updated {{ $updatedAt }}
            </flux:text>
        </div>

        <div class="flex items-center gap-4">
            <flux:switch x-model="autoRefresh" label="Auto-refresh (5s)" />
            <flux:button wire:click="$refresh" icon="arrow-path" variant="ghost">Refresh</flux:button>
        </div>
    </div>

    {{-- Tabs --}}
    <flux:tab.group>
        <flux:tabs wire:model="tab" scrollable scrollable:fade>
            <flux:tab name="overview" icon="chart-bar">Overview</flux:tab>
            <flux:tab name="processes" icon="cpu-chip">Processes</flux:tab>
            <flux:tab name="database" icon="circle-stack">Database</flux:tab>
            <flux:tab name="mysql" icon="server">MySQL</flux:tab>
            <flux:tab name="queries" icon="command-line">Queries</flux:tab>
            <flux:tab name="diskio" icon="circle-stack">I/O</flux:tab>
        </flux:tabs>

        {{-- Overview --}}
        <flux:tab.panel name="overview">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
                <flux:card>
                    <flux:text class="text-sm">CPU Load</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ $val($server, 'load') }}</flux:heading>
                </flux:card>
                <flux:card>
                    <flux:text class="text-sm">Memory</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ $val($server, 'memory') }}</flux:heading>
                </flux:card>
                <flux:card>
                    <flux:text class="text-sm">Uptime</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ $val($server, 'uptime') }}</flux:heading>
                </flux:card>
                <flux:card>
                    <flux:text class="text-sm">DB Size</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ number_format($totalDbSize, 1) }} MB</flux:heading>
                </flux:card>
            </div>

            @if (!empty($jobs))
            <div class="mt-8">
                <flux:heading size="lg" class="mb-4">Queue / Jobs</flux:heading>
                <flux:card>
                    <pre
                        class="text-sm overflow-x-auto">{{ is_string($jobs) ? $jobs : json_encode($jobs, JSON_PRETTY_PRINT) }}</pre>
                </flux:card>
            </div>
            @endif
        </flux:tab.panel>

        {{-- Processes --}}
        <flux:tab.panel name="processes">
            <div class="flex gap-4 mt-6 mb-4">
                <flux:input wire:model.live.debounce.300ms="searchProcess" placeholder="Search PID, user or command…"
                    icon="magnifying-glass" class="flex-1" />
                <div class="flex gap-4">
                    <flux:button wire:click="setSortProcess('cpu')"
                        variant="{{ $sortProcessBy === 'cpu' ? 'primary' : 'ghost' }}" size="sm">CPU</flux:button>
                    <flux:button wire:click="setSortProcess('mem')"
                        variant="{{ $sortProcessBy === 'mem' ? 'primary' : 'ghost' }}" size="sm">MEM %
                    </flux:button>
                    <flux:button wire:click="setSortProcess('rss')"
                        variant="{{ $sortProcessBy === 'rss' ? 'primary' : 'ghost' }}" size="sm">RSS</flux:button>
                </div>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>PID</flux:table.column>
                    <flux:table.column>User</flux:table.column>
                    <flux:table.column>CPU %</flux:table.column>
                    <flux:table.column>MEM %</flux:table.column>
                    <flux:table.column>RSS</flux:table.column>
                    <flux:table.column>Command</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($processes as $proc)
                    <flux:table.row :key="$proc['pid']">
                        <flux:table.cell class="font-mono">{{ $proc['pid'] }}</flux:table.cell>
                        <flux:table.cell>{{ $proc['user'] }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge
                                color="{{ $proc['cpu'] > 50 ? 'red' : ($proc['cpu'] > 20 ? 'amber' : 'zinc') }}">
                                {{ number_format($proc['cpu'], 1) }}%
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format($proc['mem'], 1) }}%</flux:table.cell>
                        <flux:table.cell>{{ $proc['rss_mb'] }} MB</flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate font-mono text-sm" title="{{ $proc['command'] }}">
                            {{ $proc['command'] }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button
                                wire:click="confirmKill({{ $proc['pid'] }}, '{{ addslashes($proc['command']) }}')"
                                variant="danger" size="sm" icon="x-mark" square />
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <flux:text>No processes found.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:tab.panel>

        {{-- Database --}}
        <flux:tab.panel name="database">
            <div class="mt-6 mb-4">
                <flux:input wire:model.live.debounce.300ms="searchTable" placeholder="Search tables…"
                    icon="magnifying-glass" class="" />
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>#</flux:table.column>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Size</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($tables as $i => $t)
                    <flux:table.row :key="$val($t, 'name', $i)">
                        <flux:table.cell>{{ $i + 1 }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $val($t, 'name') }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $val($t, 'size_mb', 0), 2) }} MB
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="3">
                            <flux:text>No tables found.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <flux:text class="mt-4">
                Total database size: <strong>{{ number_format($totalDbSize, 2) }} MB</strong>
            </flux:text>
        </flux:tab.panel>

        {{-- MySQL Status --}}
        <flux:tab.panel name="mysql">
            <div class="mt-6">
                <flux:heading size="lg" class="mb-4">MySQL Status</flux:heading>
                <flux:card>
                    @if (!empty($mysql))
                    <pre
                        class="text-sm overflow-x-auto whitespace-pre-wrap">{{ is_string($mysql) ? $mysql : json_encode($mysql, JSON_PRETTY_PRINT) }}</pre>
                    @else
                    <flux:text>No MySQL status data available.</flux:text>
                    @endif
                </flux:card>
            </div>
        </flux:tab.panel>

        {{-- Queries --}}
        <flux:tab.panel name="queries">
            <div class="mt-6">
                <flux:heading size="lg" class="mb-4">Active Queries / Process List</flux:heading>

                @if (!empty($mysqlProcesses))
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Id</flux:table.column>
                        <flux:table.column>User</flux:table.column>
                        <flux:table.column>Host</flux:table.column>
                        <flux:table.column>DB</flux:table.column>
                        <flux:table.column>Command</flux:table.column>
                        <flux:table.column>Time</flux:table.column>
                        <flux:table.column>State</flux:table.column>
                        <flux:table.column>Info</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ((array) $mysqlProcesses as $q)
                        <flux:table.row>
                            <flux:table.cell class="font-mono">{{ $val($q, 'Id') }}</flux:table.cell>
                            <flux:table.cell>{{ $val($q, 'User') }}</flux:table.cell>
                            <flux:table.cell>{{ $val($q, 'Host') }}</flux:table.cell>
                            <flux:table.cell>{{ $val($q, 'db') }}</flux:table.cell>
                            <flux:table.cell>{{ $val($q, 'Command') }}</flux:table.cell>
                            <flux:table.cell>{{ $val($q, 'Time') }}</flux:table.cell>
                            <flux:table.cell>{{ $val($q, 'State') }}</flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate font-mono text-xs">
                                {{ $val($q, 'Info') }}
                            </flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                @else
                <flux:text>No active queries.</flux:text>
                @endif
            </div>
        </flux:tab.panel>

        {{-- Disk I/O --}}
        <flux:tab.panel name="diskio">
            <div class="mt-6">
                <flux:heading size="lg" class="mb-4">Disk I/O</flux:heading>
                <flux:card>
                    @if (!empty($diskIO))
                    <pre
                        class="text-sm overflow-x-auto whitespace-pre-wrap">{{ is_string($diskIO) ? $diskIO : json_encode($diskIO, JSON_PRETTY_PRINT) }}</pre>
                    @else
                    <flux:text>No disk I/O data available.</flux:text>
                    @endif
                </flux:card>
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</div>