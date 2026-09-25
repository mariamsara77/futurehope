<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Layout;

new  class extends Component {
    use WithPagination;

    public $search = '';

    public function getListeners()
    {
        return ['echo-private:user.' . auth()->id() . ',.notificationReceived' => '$refresh'];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteReport($id)
    {
        DB::table('notifications')->where('id', $id)->delete();
        session()->flash('message', 'Report deleted successfully.');
    }

    public function markAsRead($id)
    {
        // কারেন্ট ইউজার বাদ দিয়ে সরাসরি গ্লোবাল টেবিল থেকে নোটিফিকেশন খোঁজা হচ্ছে
        $notification = DatabaseNotification::find($id);
        if ($notification && !$notification->read_at) {
            $notification->markAsRead();
        }
    }

    public function rendering($view)
    {
        // সরাসরি DatabaseNotification::query() ব্যবহার করায় সব ইউজারের ডাটা একসাথে আসবে
        $reports = DatabaseNotification::query()
            ->where('type', 'App\Notifications\MissingDataNotification')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('data->message', 'like', '%' . $this->search . '%')->orWhere('data->title', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(15);

        return $view->with(['reports' => $reports]);
    }
}; ?>

<div class="">
    <div class="">
        <div>
            <flux:heading size="xl" level="1">Missing Data Insights</flux:heading>
            <flux:subheading>Manage and respond to user search requests.</flux:subheading>
        </div>

        <div class="flex items-center gap-4">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search by user or query..." clearable />
        </div>
    </div>




    <flux:table :paginator="$reports">
        <flux:table.columns>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Reporter</flux:table.column>
            <flux:table.column>Action / Title</flux:table.column>
            <flux:table.column>Query Details</flux:table.column>
            <flux:table.column>Time</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($reports as $report)
                @php
                    $data = $report->data; // আপনার নতুন স্ট্যান্ডার্ড অ্যারে
                    $senderId = $data['sender_id'] ?? null;
                    $sender = $senderId ? User::find($senderId) : null;
                    $isUnread = is_null($report->read_at);
                @endphp

                <flux:table.row :key="$report->id"
                    class="{{ $isUnread ? 'bg-amber-50/20 dark:bg-amber-900/5' : '' }}">
                    {{-- Status --}}
                    <flux:table.cell>
                        @if ($isUnread)
                            <div class="flex items-center gap-4">
                                <span class="relative flex h-2 w-2">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                </span>
                                <flux:text size="xs" class="text-amber-600 font-bold uppercase tracking-widest">New
                                </flux:text>
                            </div>
                        @else
                            <flux:icon.check-circle variant="mini" class="w-4  h-4 text-zinc-300" />
                        @endif
                    </flux:table.cell>

                    {{-- User / Visitor --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-4">
                            <flux:avatar src="{{ $sender?->avatar_url ?? $sender?->photo }}"
                                name="{{ $sender?->name ?? 'Guest' }}" class="rounded-lg" />
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-semibold {{ $sender ? 'text-zinc-800 dark:text-white' : 'text-zinc-500 italic' }}">
                                    {{ $sender ? $sender->name : 'Guest Visitor' }}
                                </span>
                                <span class="text-xs text-zinc-400 font-bold tracking-tighter">
                                    {{ $sender ? 'Member' : 'Public' }}
                                </span>
                            </div>
                        </div>
                    </flux:table.cell>

                    {{-- Action / Title --}}
                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $data['title'] ?? 'Reported missing data' }}
                            </span>
                            <a href="{{ $data['action_url'] ?? '#' }}" target="_blank"
                                class="text-[11px] text-indigo-500 hover:underline flex items-center gap-41">
                                <flux:icon.link variant="micro" class="w-4 h-3" />
                                Source Link
                            </a>
                        </div>
                    </flux:table.cell>

                    {{-- Search Query (The Message) --}}
                    <flux:table.cell>
                        <flux:badge color="zinc" variant="subtle" class="font-mono text-[11px] px-2">
                            {{ $data['message'] ?? 'No Query Provided' }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Time --}}
                    <flux:table.cell>
                        <flux:text size="xs" class="whitespace-nowrap tabular-nums">
                            {{ $report->created_at->diffForHumans(short: true) }}
                        </flux:text>
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-4 items-center">
                            @if ($isUnread)
                                <flux:button wire:click="markAsRead('{{ $report->id }}')" variant="ghost"
                                    size="sm" icon="eye" tooltip="Mark Read" />
                            @endif

                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item href="{{ $data['action_url'] ?? '#' }}" target="_blank"
                                        icon="arrow-top-right-on-square">Go to Page</flux:menu.item>

                                    @if ($sender)
                                        <flux:menu.item href="{{ route('users.show', $sender->slug) }}"
                                            icon="user-circle">
                                            User Details</flux:menu.item>
                                    @endif

                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="deleteReport('{{ $report->id }}')"
                                        wire:confirm="Delete this report permanently?" variant="danger" icon="trash">
                                        Delete Report</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-20">
                        <flux:icon.magnifying-glass class="mx-auto w-10 h-10 text-zinc-300 mb-2" />
                        <flux:heading>No reports found</flux:heading>
                        <flux:subheading>Try adjusting your search query.</flux:subheading>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
