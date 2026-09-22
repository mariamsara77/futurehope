<?php

use Livewire\Component;
use App\Models\Visitor;
use App\Models\VisitorSession;
use App\Models\PageView;
use App\Models\VisitorEvent;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public $visitorId;
    public $visitor;
    public $tab = 'sessions';
    public $sessionDetails = null;

    protected $queryString = ['tab'];

    public function mount($visitorId)
    {
        $this->visitorId = $visitorId;
        $this->loadVisitor();
    }

    public function loadVisitor()
    {
        $this->visitor = Visitor::findOrFail($this->visitorId);
    }

    // Sessions with Pagination (মডেল রিলেশন অনুযায়ী)
    public function getSessionsProperty()
    {
        return $this->visitor->sessions()->orderByDesc('started_at')->paginate(10);
    }

    // Page Views with Pagination
    public function getPageViewsProperty()
    {
        return $this->visitor->pageViews()->orderByDesc('created_at')->paginate(15);
    }

    // Events with Pagination
    public function getEventsProperty()
    {
        return $this->visitor->events()->orderByDesc('created_at')->paginate(15);
    }

    public function loadSessionDetails($sessionId)
    {
        // নতুন রিলেশন লোড করা হয়েছে
        $this->sessionDetails = VisitorSession::with(['pageViews', 'events'])->find($sessionId);
        $this->tab = 'sessions';
    }

    public function resetSessionDetails()
    {
        $this->sessionDetails = null;
    }

    public function changeTab($tab)
    {
        $this->tab = $tab;
        $this->resetPage(); // ট্যাব পরিবর্তনের সময় পেজিনেশন রিসেট হবে
        $this->sessionDetails = null;
    }
}; ?>

<div class="space-y-6">
    <flux:card>
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-fuchsia-100 dark:bg-fuchsia-900/30 rounded-lg">
                    <flux:icon name="user" class="text-fuchsia-600" />
                </div>
                <div>
                    <flux:heading size="xl">Visitor #{{ substr($visitor->hash, 0, 8) }}</flux:heading>
                    <flux:subheading>IP: {{ $visitor->ip_address }} • {{ $visitor->is_bot ? '🤖 Bot' : '👤 Real User' }}
                    </flux:subheading>
                </div>
            </div>
            <flux:button variant="ghost" href="{{ route('admin.dashboard') }}" icon="arrow-left">Back</flux:button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card variant="subtle" class="p-3">
                <p class="text-xs uppercase text-zinc-500">System</p>
                <p class="font-semibold">{{ $visitor->browser_family ?: 'Unknown' }} on
                    {{ $visitor->os_family ?: 'OS' }}
                </p>
                <p class="text-xs text-zinc-400">{{ $visitor->device_type }} {{ $visitor->device_model }}</p>
            </flux:card>

            <flux:card variant="subtle" class="p-3">
                <p class="text-xs uppercase text-zinc-500">Location</p>
                <p class="font-semibold">{{ $visitor->city_name ?: 'Unknown City' }}</p>
                <p class="text-xs text-zinc-400">{{ $visitor->country_code }} ({{ $visitor->timezone }})</p>
            </flux:card>

            <flux:card variant="subtle" class="p-3">
                <p class="text-xs uppercase text-zinc-500">App Version / Type</p>
                <p class="font-semibold">{{ $visitor->is_pwa ? 'PWA Installed' : 'Web Browser' }}</p>
                <p class="text-xs text-zinc-400">v{{ $visitor->app_version ?: '1.0.0' }}</p>
            </flux:card>

            <flux:card variant="subtle" class="p-3">
                <p class="text-xs uppercase text-zinc-500">First Seen</p>
                <p class="font-semibold">{{ $visitor->first_seen_at->format('M j, Y') }}</p>
                <p class="text-xs text-zinc-400">{{ $visitor->first_seen_at->diffForHumans() }}</p>
            </flux:card>
        </div>
    </flux:card>

    <flux:tab.group>
        <flux:tabs wire:model="tab">
            <flux:tab name="sessions" icon="clock">Sessions
            </flux:tab>
            <flux:tab name="pageviews" icon="eye">Page Views
            </flux:tab>
            <flux:tab name="events" icon="bolt">Events</flux:tab>
        </flux:tabs>
        <flux:tab.panel name="sessions">
            <div class="space-y-4">
                @if ($sessionDetails)
                <flux:card class="relative">
                    <div class="flex items-center justify-between mb-6">
                        <flux:heading size="lg">Session Detail: {{ substr($sessionDetails->id, 0, 8) }}
                        </flux:heading>
                        <flux:button size="sm" variant="subtle" wire:click="resetSessionDetails">Back to
                            List
                        </flux:button>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <div>
                            <flux:label>Source</flux:label>
                            <p class="font-medium capitalize">{{ $sessionDetails->origin_type }}
                                ({{ $sessionDetails->origin_source ?: 'Direct' }})</p>
                        </div>
                        <div>
                            <flux:label>Time Spent</flux:label>
                            <p class="font-medium">{{ $sessionDetails->seconds_spent }}s</p>
                        </div>
                        <div>
                            <flux:label>Hits</flux:label>
                            <p class="font-medium">{{ $sessionDetails->hits_count }} views</p>
                        </div>
                        <div>
                            <flux:label>UTM Campaign</flux:label>
                            <p class="font-medium text-xs">{{ $sessionDetails->utm_campaign ?: '-' }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <flux:heading size="sm">Timeline</flux:heading>
                        @foreach ($sessionDetails->pageViews as $pv)
                        <div
                            class="flex items-center gap-4 p-2 text-sm border-l-2 border-fuchsia-500 bg-zinc-50 dark:bg-zinc-800/50">
                            <span class="text-xs text-zinc-400">{{ $pv->created_at->format('H:i:s') }}</span>
                            <span class="flex-1 truncate">{{ $pv->title }}</span>
                            <span
                                class="text-xs bg-zinc-200 dark:bg-zinc-700 px-2 py-0.5 rounded">{{ $pv->load_time_ms }}ms</span>
                        </div>
                        @endforeach
                    </div>
                </flux:card>
                @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Started</flux:table.column>
                        <flux:table.column>Source</flux:table.column>
                        <flux:table.column>Engagement</flux:table.column>
                        <flux:table.column align="end">Actions</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->sessions as $session)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="text-sm">{{ $session->started_at->format('M j, H:i') }}</div>
                                <div class="text-xs text-zinc-400">
                                    {{ $session->started_at->diffForHumans() }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" variant="subtle">{{ $session->origin_type }}
                                </flux:badge>
                                <span class="text-xs text-zinc-500 ml-1">{{ $session->origin_source }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-xs">{{ $session->hits_count }} views •
                                    {{ $session->seconds_spent }}s spent
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="sm" variant="ghost"
                                    wire:click="loadSessionDetails('{{ $session->id }}')">
                                    Details</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                {{ $this->sessions->links() }}
                @endif
            </div>
        </flux:tab.panel>
        <flux:tab.panel name="pageviews">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Time</flux:table.column>
                    <flux:table.column>Page Title / URL</flux:table.column>
                    <flux:table.column>Route</flux:table.column>
                    <flux:table.column align="end">Performance</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->pageViews as $pv)
                    <flux:table.row>
                        <flux:table.cell class="text-xs">{{ $pv->created_at->format('H:i:s') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium truncate max-w-xs">{{ $pv->title ?: 'No Title' }}
                            </div>
                            <div class="text-xs text-zinc-400 truncate max-w-xs">{{ $pv->url }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">{{ $pv->route_name }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <span class="text-sm {{ $pv->load_time_ms > 1000 ? 'text-orange-500' : 'text-green-500' }}">
                                {{ $pv->load_time_ms }}ms
                            </span>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            <div class="mt-4">{{ $this->pageViews->links() }}</div>
        </flux:tab.panel>
        <flux:tab.panel name="events">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Time</flux:table.column>
                    <flux:table.column>Category</flux:table.column>
                    <flux:table.column>Action</flux:table.column>
                    <flux:table.column>Label / Data</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->events as $event)
                    <flux:table.row>
                        <flux:table.cell class="text-xs">{{ $event->created_at->format('H:i:s') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" variant="subtle">
                                {{ $event->event_category }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $event->event_action }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-xs">{{ $event->event_label }}</div>
                            @if ($event->payload)
                            <pre
                                class="text-xs bg-zinc-100 dark:bg-zinc-800 p-1 mt-1 rounded">{{ json_encode($event->payload) }}</pre>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            <div class="mt-4">{{ $this->events->links() }}</div>
        </flux:tab.panel>
    </flux:tab.group>
</div>