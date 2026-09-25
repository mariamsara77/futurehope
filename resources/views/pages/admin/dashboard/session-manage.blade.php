<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Session;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;

new  class extends Component {
    use WithPagination;

    public $search = '';
    public $filterUser = '';
    public $filterStatus = 'all';
    public $filterDevice = 'all';
    public $filterBrowser = 'all';
    public $sortField = 'last_activity';
    public $sortDirection = 'desc';
    public $selectedSessions = [];
    public $bulkAction = '';
    public $showFilters = false;
    public $showStats = true;
    public $perPage = 15;
    public $deviceStats = [];
    public $sessionCount = 0;
    public $showExportModal = false;
    public $exportFormat = 'csv';
    public $locationData = [];

    protected $listeners = ['refreshSessions' => '$refresh'];

    public function mount()
    {
        $this->generateDeviceStats();
    }

    public function deleteSession($sessionId)
    {
        $session = Session::with('user')->findOrFail($sessionId);

        if (session()->getId() === $session->id) {
            $this->dispatch('show-toast', type: 'error', title: 'Action Denied', message: 'Cannot terminate your own session');
            return;
        }

        $userName = $session->user ? $session->user->name : 'Guest';
        $session->delete();

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'terminated_user' => $userName,
            ])
            ->log('terminated_session');

        $this->dispatch('show-toast', type: 'success', title: 'Session Terminated', message: "Session terminated for {$userName}");
        $this->generateDeviceStats();
    }

    public function clearAllSessions()
    {
        $currentSessionId = session()->getId();
        $otherSessionsCount = Session::where('id', '!=', $currentSessionId)->count();

        if ($otherSessionsCount === 0) {
            $this->dispatch('show-toast', type: 'info', title: 'No Sessions', message: 'No other sessions to clear');
            return;
        }

        Session::where('id', '!=', $currentSessionId)->delete();

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['sessions_terminated' => $otherSessionsCount])
            ->log('cleared_all_sessions');

        $this->dispatch('show-toast', type: 'success', title: 'Sessions Cleared', message: "{$otherSessionsCount} sessions cleared successfully");
        $this->generateDeviceStats();
        $this->selectedSessions = [];
    }

    public function clearExpiredSessions()
    {
        $expiredCount = Session::where('last_activity', '<=', now()->subMinutes(config('session.lifetime')))->count();

        if ($expiredCount === 0) {
            $this->dispatch('show-toast', type: 'info', title: 'No Expired Sessions', message: 'No expired sessions to clear');
            return;
        }

        Session::where('last_activity', '<=', now()->subMinutes(config('session.lifetime')))->delete();

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['sessions_cleared' => $expiredCount])
            ->log('cleared_expired_sessions');

        $this->dispatch('show-toast', type: 'success', title: 'Expired Sessions Cleared', message: "{$expiredCount} expired sessions removed");
        $this->generateDeviceStats();
    }

    public function applyBulkAction()
    {
        if (empty($this->selectedSessions) || empty($this->bulkAction)) {
            $this->dispatch('show-toast', type: 'warning', title: 'Action Required', message: 'Please select sessions and an action');
            return;
        }

        $currentSessionId = session()->getId();
        $filteredSessions = array_filter($this->selectedSessions, fn($id) => $id !== $currentSessionId);

        if (empty($filteredSessions)) {
            $this->dispatch('show-toast', type: 'warning', title: 'Selection Error', message: 'Cannot perform bulk action on your own session');
            return;
        }

        switch ($this->bulkAction) {
            case 'terminate':
                $count = Session::whereIn('id', $filteredSessions)->delete();
                $message = "{$count} sessions terminated";
                $logType = 'bulk_terminate_sessions';
                break;

            case 'mark_expired':
                $count = Session::whereIn('id', $filteredSessions)->update(['last_activity' => now()->subMinutes(config('session.lifetime') + 1)->timestamp]);
                $message = "{$count} sessions marked as expired";
                $logType = 'bulk_mark_expired';
                break;

            default:
                $this->dispatch('show-toast', type: 'error', title: 'Invalid Action', message: 'Selected action is not valid');
                return;
        }

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'session_ids' => $filteredSessions,
                'count' => $count,
                'action' => $this->bulkAction,
            ])
            ->log($logType);

        $this->dispatch('show-toast', type: 'success', title: 'Bulk Action Complete', message: $message);

        $this->selectedSessions = [];
        $this->bulkAction = '';
        $this->generateDeviceStats();
    }

    public function selectAll()
    {
        $currentSessionId = session()->getId();
        $this->selectedSessions = $this->sessions->filter(fn($session) => $session->id !== $currentSessionId)->pluck('id')->toArray();
    }

    public function clearSelection()
    {
        $this->selectedSessions = [];
    }

    public function clearAllFilters()
    {
        $this->reset(['search', 'filterUser', 'filterStatus', 'filterDevice', 'filterBrowser']);
        $this->resetPage();
    }

    public function toggleSessionStatus($sessionId)
    {
        $session = Session::findOrFail($sessionId);

        if (session()->getId() === $session->id) {
            $this->dispatch('show-toast', type: 'error', title: 'Action Denied', message: 'Cannot modify your own session status');
            return;
        }

        $isExpired = Carbon::createFromTimestamp($session->last_activity)->addMinutes(config('session.lifetime'))->isPast();

        if ($isExpired) {
            $session->update(['last_activity' => now()->timestamp]);
            $message = 'Session reactivated';
        } else {
            $session->update(['last_activity' => now()->subMinutes(config('session.lifetime') + 1)->timestamp]);
            $message = 'Session expired manually';
        }

        $this->dispatch('show-toast', type: 'success', title: 'Status Updated', message: $message);
        $this->generateDeviceStats();
    }

    public function generateDeviceStats()
    {
        $sessions = Session::with('user')->get();
        $agent = new Agent();

        $stats = [
            'browsers' => [],
            'devices' => [],
            'platforms' => [],
            'active' => 0,
            'expired' => 0,
            'total' => $sessions->count(),
        ];

        foreach ($sessions as $session) {
            $agent->setUserAgent($session->user_agent);

            $browser = $agent->browser() ?: 'Unknown';
            $stats['browsers'][$browser] = ($stats['browsers'][$browser] ?? 0) + 1;

            $device = $agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : 'Desktop');
            $stats['devices'][$device] = ($stats['devices'][$device] ?? 0) + 1;

            $platform = $agent->platform() ?: 'Unknown';
            $stats['platforms'][$platform] = ($stats['platforms'][$platform] ?? 0) + 1;

            $isExpired = Carbon::createFromTimestamp($session->last_activity)->addMinutes(config('session.lifetime'))->isPast();

            if ($isExpired) {
                $stats['expired']++;
            } else {
                $stats['active']++;
            }
        }

        $this->deviceStats = $stats;
        $this->sessionCount = $stats['total'];
    }

    public function parseUserAgent($userAgent)
    {
        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return [
            'browser' => $agent->browser() ?? 'Unknown',
            'version' => $agent->version($agent->browser()) ?? '',
            'platform' => $agent->platform() ?? 'Unknown',
            'device' => $agent->device() ?: ($agent->isMobile() ? 'Mobile' : 'Desktop'),
            'is_mobile' => $agent->isMobile(),
            'is_tablet' => $agent->isTablet(),
            'is_desktop' => $agent->isDesktop(),
            'is_bot' => $agent->isRobot(),
        ];
    }

    public function getLocationFromIP($ip)
    {
        if ($ip === '127.0.0.1' || str_starts_with($ip, '192.168.')) {
            return [
                'country' => 'Local Network',
                'city' => 'Local',
                'isp' => 'Local Network',
                'countryCode' => 'LOCAL',
            ];
        }

        return Cache::remember("ip_location_{$ip}", 86400, function () use ($ip) {
            try {
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,isp");

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['status']) && $data['status'] === 'success') {
                        return [
                            'country' => $data['country'] ?? 'Unknown',
                            'city' => $data['city'] ?? 'Unknown',
                            'isp' => $data['isp'] ?? 'Unknown ISP',
                            'countryCode' => $data['countryCode'] ?? 'UN',
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Fail silently
            }

            return [
                'country' => 'Unknown',
                'city' => 'Unknown',
                'isp' => 'Unknown ISP',
                'countryCode' => 'UN',
            ];
        });
    }

    public function exportSessions()
    {
        $sessions = Session::with('user')->get();
        $data = [];

        foreach ($sessions as $session) {
            $agentInfo = $this->parseUserAgent($session->user_agent);
            $location = $this->getLocationFromIP($session->ip_address);
            $isExpired = Carbon::createFromTimestamp($session->last_activity)->addMinutes(config('session.lifetime'))->isPast();

            $data[] = [
                'User' => $session->user?->name ?? 'Guest',
                'Email' => $session->user?->email ?? 'N/A',
                'IP Address' => $session->ip_address,
                'Location' => $location['city'] . ', ' . $location['country'],
                'ISP' => $location['isp'],
                'Browser' => $agentInfo['browser'] . ' ' . $agentInfo['version'],
                'Platform' => $agentInfo['platform'],
                'Device' => $agentInfo['device'],
                'Last Activity' => Carbon::createFromTimestamp($session->last_activity)->format('Y-m-d H:i:s'),
                'Status' => $isExpired ? 'Expired' : 'Active',
                'User Agent' => $session->user_agent,
            ];
        }

        $this->showExportModal = false;

        if ($this->exportFormat === 'csv') {
            return $this->exportToCSV($data);
        } else {
            return $this->exportToJSON($data);
        }
    }

    private function exportToCSV($data)
    {
        $filename = 'sessions_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(
            function () use ($data) {
                $file = fopen('php://output', 'w');
                if (!empty($data)) {
                    fputcsv($file, array_keys($data[0]));
                    foreach ($data as $row) {
                        fputcsv($file, $row);
                    }
                }
                fclose($file);
            },
            $filename,
            $headers,
        );
    }

    private function exportToJSON($data)
    {
        $filename = 'sessions_' . date('Y-m-d_H-i-s') . '.json';
        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Type' => 'application/json',
        ]);
    }

    public function refreshData()
    {
        $this->generateDeviceStats();
        $this->dispatch('show-toast', type: 'info', title: 'Data Refreshed', message: 'Session data has been refreshed');
    }

    public function updating($property, $value)
    {
        if (in_array($property, ['search', 'filterUser', 'filterStatus', 'filterDevice', 'filterBrowser', 'perPage'])) {
            $this->resetPage();
        }
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

    public function with(): array
    {
        $query = Session::with('user')
            ->when($this->search, function ($q) {
                $q->where(function ($subQ) {
                    $subQ
                        ->where('ip_address', 'like', "%{$this->search}%")
                        ->orWhere('user_agent', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($userQ) {
                            $userQ->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->filterUser, function ($q) {
                $q->where('user_id', $this->filterUser);
            })
            ->when($this->filterStatus === 'active', function ($q) {
                $q->where('last_activity', '>', now()->subMinutes(config('session.lifetime'))->timestamp);
            })
            ->when($this->filterStatus === 'expired', function ($q) {
                $q->where('last_activity', '<=', now()->subMinutes(config('session.lifetime'))->timestamp);
            });

        $sessions = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);

        return [
            'sessions' => $sessions,
            'users' => User::whereHas('sessions')->orderBy('name')->get(),
            'stats' => $this->deviceStats,
            'browsersList' => array_keys($this->deviceStats['browsers'] ?? []),
            'devicesList' => ['desktop', 'mobile', 'tablet'],
        ];
    }
};
?>

<section class="space-y-6">
    <flux:card>
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <flux:heading size="xl" level="h1">Session Management</flux:heading>
                <flux:subheading>Monitor and manage all active user sessions</flux:subheading>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <flux:button icon="arrow-path" wire:click="refreshData" variant="ghost">Refresh</flux:button>
                <flux:button icon="document-arrow-down" wire:click="$set('showExportModal', true)" variant="ghost">
                    Export
                </flux:button>
                <flux:button icon="trash" wire:click="clearExpiredSessions" variant="ghost">Clear Expired
                </flux:button>
                <flux:button icon="arrow-right-start-on-rectangle" wire:click="clearAllSessions"
                    onclick="return confirm('This will logout all users except you. Continue?')" variant="danger">Logout
                    All</flux:button>
            </div>
        </div>

        <flux:separator variant="subtle" class="my-4" />

        <div class="flex flex-wrap gap-4">
            <flux:badge color="indigo" icon="chart-bar">Total: {{ $sessionCount }}</flux:badge>
            <flux:badge color="green" icon="check-circle">Active: {{ $stats['active'] ?? 0 }}</flux:badge>
            <flux:badge color="red" icon="x-circle">Expired: {{ $stats['expired'] ?? 0 }}</flux:badge>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-600">
                <flux:icon.bolt />
            </div>
            <div>
                <flux:subheading>Active Sessions</flux:subheading>
                <flux:heading size="lg">{{ $stats['active'] ?? 0 }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600">
                <flux:icon.device-phone-mobile />
            </div>
            <div>
                <flux:subheading>Unique Devices</flux:subheading>
                <flux:heading size="lg">{{ count($stats['devices'] ?? []) }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg text-purple-600">
                <flux:icon.globe-alt />
            </div>
            <div>
                <flux:subheading>Different Browsers</flux:subheading>
                <flux:heading size="lg">{{ count($stats['browsers'] ?? []) }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg text-yellow-600">
                <flux:icon.clock />
            </div>
            <div>
                <flux:subheading>Lifetime (Min)</flux:subheading>
                <flux:heading size="lg">{{ config('session.lifetime') }}</flux:heading>
            </div>
        </flux:card>
    </div>

    <flux:card class="space-y-4">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="search"
                    placeholder="Search sessions..." />
            </div>
            <div class="flex gap-4">
                <flux:select wire:model.live="perPage" class="w-32">
                    <option value="10">10 / Page</option>
                    <option value="25">25 / Page</option>
                    <option value="50">50 / Page</option>
                </flux:select>
                <flux:button wire:click="clearAllFilters" variant="ghost">Reset</flux:button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:select wire:model.live="filterUser">
                <option value="">All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterStatus">
                <option value="all">All Status</option>
                <option value="active">Active Only</option>
                <option value="expired">Expired Only</option>
            </flux:select>

            <flux:select wire:model.live="filterDevice">
                <option value="all">All Devices</option>
                <option value="desktop">Desktop</option>
                <option value="mobile">Mobile</option>
            </flux:select>

            <flux:select wire:model.live="filterBrowser">
                <option value="all">All Browsers</option>
                @foreach ($browsersList as $browser)
                    <option value="{{ $browser }}">{{ $browser }}</option>
                @endforeach
            </flux:select>
        </div>

        <flux:table :paginate="$sessions">
            <flux:table.columns>
                <flux:table.column>User</flux:table.column>
                <flux:table.column>Connection</flux:table.column>
                <flux:table.column>Device</flux:table.column>
                <flux:table.column sortable :sorted="$sortField === 'last_activity'" :direction="$sortDirection"
                    wire:click="sortBy('last_activity')">Last Activity</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($sessions as $session)
                    @php
                        $agentInfo = $this->parseUserAgent($session->user_agent);
                        $isCurrent = session()->getId() === $session->id;
                        $isExpired = \Carbon\Carbon::createFromTimestamp($session->last_activity)
                            ->addMinutes(config('session.lifetime'))
                            ->isPast();
                    @endphp
                    <flux:table.row :class="$isCurrent ? 'bg-zinc-50 dark:bg-white/5' : ''">
                        <flux:table.cell>
                            <div class="flex items-center gap-4">
                                <flux:avatar size="sm" name="{{ $session->user?->name ?? 'Guest' }}" />
                                <div>
                                    <div class="font-medium flex items-center gap-4">
                                        {{ $session->user?->name ?? 'Guest' }}
                                        @if ($isCurrent)
                                            <flux:badge size="sm" color="blue">You</flux:badge>
                                        @endif
                                    </div>
                                    <div class="text-xs text-zinc-500">{{ $session->user?->email ?? 'Guest Session' }}
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm">
                                <div>{{ $session->ip_address }}</div>
                                <div class="text-xs text-zinc-500">
                                    {{ $this->getLocationFromIP($session->ip_address)['city'] ?? 'Unknown' }}
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-4">
                                @if (!$agentInfo['is_mobile'])
                                    <flux:icon.computer-desktop color="lime" variant="solid" />
                                @else
                                    <flux:icon.device-phone-mobile color="orange" variant="solid" />
                                @endif

                                <div class="flex flex-col">
                                    <span class="text-sm font-medium leading-none">
                                        {{ $agentInfo['browser'] }}
                                    </span>
                                    <span class="text-xs text-zinc-500">
                                        on {{ $agentInfo['platform'] }}
                                    </span>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm">
                                <div class="{{ $isExpired ? 'text-red-500' : 'text-zinc-900 dark:text-white' }}">
                                    {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->format('H:i:s') }}
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            @if (!$isCurrent)
                                <flux:dropdown>
                                    <flux:button variant="ghost" icon="ellipsis-horizontal" size="sm" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="deleteSession('{{ $session->id }}')"
                                            icon="trash" variant="danger">Terminate</flux:menu.item>
                                        <flux:menu.item
                                            onclick="navigator.clipboard.writeText('{{ $session->ip_address }}')"
                                            icon="clipboard">Copy IP</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-10 text-zinc-500">
                            No sessions found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="export-sessions" class="min-w-[24rem]">
        <div class="space-y-6">
            <flux:heading size="lg">Export Data</flux:heading>
            <flux:select wire:model="exportFormat" label="Format">
                <option value="csv">CSV (Excel)</option>
                <option value="json">JSON</option>
            </flux:select>
            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="exportSessions" variant="primary">Download</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
