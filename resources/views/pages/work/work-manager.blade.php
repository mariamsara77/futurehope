<?php

use App\Models\Work;
use App\Models\WorkCategory;
use Flux\Flux;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public ?int $selectedWorkId = null;
    public $viewingWork = null;

    #[Validate('nullable|exists:work_categories,id')]
    public ?int $category_id = null;

    #[Validate('required|in:suggested,voting,approved,running,upcoming,completed,rejected')]
    public string $status = 'suggested';

    #[Validate('boolean')]
    public bool $is_published = false;

    #[Validate('nullable|integer|min:1|max:1000')]
    public int $required_votes = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function viewWork(int $id): void
    {
        $this->viewingWork = Work::with(['user:id,name,email', 'category:id,name'])->findOrFail($id);
        Flux::modal('view-work-modal')->show();
    }

    public function editWork(int $id): void
    {
        $work = Work::findOrFail($id);

        $this->selectedWorkId = $work->id;
        $this->category_id = $work->category_id;
        $this->status = $work->status;
        $this->is_published = (bool) $work->is_published;
        $this->required_votes = (int) $work->required_votes;

        Flux::modal('edit-work-modal')->show();
    }

    public function saveWork(): void
    {
        $this->validate([
            'category_id' => ['nullable', 'exists:work_categories,id'],
            'status' => ['required', 'in:suggested,voting,approved,running,upcoming,completed,rejected'],
            'is_published' => ['boolean'],
            'required_votes' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $work = Work::findOrFail($this->selectedWorkId);
        $work->update([
            'category_id' => $this->category_id ?: null,
            'status' => $this->status,
            'is_published' => $this->is_published,
            'required_votes' => $this->required_votes,
        ]);

        Flux::modal('edit-work-modal')->close();
        Flux::toast('Work updated successfully.', variant: 'success');
        $this->reset(['selectedWorkId', 'category_id', 'status', 'is_published', 'required_votes']);
        $this->status = 'suggested';
        $this->required_votes = 10;
    }

    public function deleteWork(int $id): void
    {
        $work = Work::findOrFail($id);

        if ($work->hasMedia('cover')) {
            $work->clearMediaCollection('cover');
        }

        $work->delete();
        Flux::toast('Work removed successfully.', variant: 'danger');
    }

    public function with(): array
    {
        return [
            'categories' => WorkCategory::where('is_active', true)->orderBy('name')->get(),
            'works' => Work::query()
                ->with(['user:id,name', 'category:id,name'])
                ->when($this->search, function ($query) {
                    $query->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($userQuery) {
                            $userQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                })
                ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <flux:heading size="xl">Manage Work Submissions</flux:heading>
            <flux:subheading>Review, publish, and maintain submitted work proposals.</flux:subheading>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <div class="w-full md:w-64">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by title or author..." icon="magnifying-glass" />
            </div>
            <div class="w-full md:w-48">
                <flux:select wire:model.live="statusFilter" label="Status" class="min-w-0">
                    <flux:select.option value="all">All statuses</flux:select.option>
                    <flux:select.option value="suggested">Suggested</flux:select.option>
                    <flux:select.option value="voting">Voting</flux:select.option>
                    <flux:select.option value="approved">Approved</flux:select.option>
                    <flux:select.option value="running">Running</flux:select.option>
                    <flux:select.option value="upcoming">Upcoming</flux:select.option>
                    <flux:select.option value="completed">Completed</flux:select.option>
                    <flux:select.option value="rejected">Rejected</flux:select.option>
                </flux:select>
            </div>
        </div>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Work</flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Votes</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($works as $work)
                    <flux:table.row :key="$work->id">
                        <flux:table.cell>
                            <div class="space-y-1">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $work->title }}</div>
                                <div class="text-xs text-zinc-500">{{ $work->user?->name ?? 'Guest' }}</div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" variant="subtle">
                                {{ $work->category?->name ?? 'General' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm font-medium">{{ $work->votes_count }}/{{ $work->required_votes }}</div>
                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, (int) round(($work->votes_count / max(1, $work->required_votes)) * 100)) }}%"></div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $work->status === 'approved' ? 'green' : ($work->status === 'rejected' ? 'red' : 'yellow') }}">
                                {{ ucfirst($work->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:button size="sm" variant="ghost" icon="eye" wire:click="viewWork({{ $work->id }})">View</flux:button>
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editWork({{ $work->id }})">Edit</flux:button>
                                <flux:button size="sm" variant="ghost" color="danger" icon="trash" wire:confirm="Delete this work submission?" wire:click="deleteWork({{ $work->id }})">Delete</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-6 text-center text-zinc-500">
                            No work submissions found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <div class="mt-4">
        {{ $works->links() }}
    </div>

    <flux:modal name="view-work-modal" class="md:w-[760px]">
        @if ($viewingWork)
            <div class="space-y-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <flux:heading size="lg">{{ $viewingWork->title }}</flux:heading>
                        <flux:subheading>Submitted by {{ $viewingWork->user?->name ?? 'Guest' }}</flux:subheading>
                    </div>
                    <flux:badge color="{{ $viewingWork->status === 'approved' ? 'green' : ($viewingWork->status === 'rejected' ? 'red' : 'yellow') }}">
                        {{ ucfirst($viewingWork->status) }}
                    </flux:badge>
                </div>

                @if ($viewingWork->hasMedia('cover'))
                    <img src="{{ $viewingWork->getFirstMediaUrl('cover', 'thumb') ?? $viewingWork->getFirstMediaUrl('cover') }}" alt="{{ $viewingWork->title }}" class="h-64 w-full rounded-xl object-cover" />
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="text-xs uppercase tracking-wide text-zinc-500">Category</div>
                        <div class="mt-2 font-medium">{{ $viewingWork->category?->name ?? 'General' }}</div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="text-xs uppercase tracking-wide text-zinc-500">Vote Progress</div>
                        <div class="mt-2 font-medium">{{ $viewingWork->votes_count }} / {{ $viewingWork->required_votes }}</div>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <div class="text-xs uppercase tracking-wide text-zinc-500">Description</div>
                    <div class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                        {{ $viewingWork->description ?: 'No description provided.' }}
                    </div>
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="primary">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="edit-work-modal" class="md:w-[420px]">
        <form wire:submit="saveWork" class="space-y-6">
            <div>
                <flux:heading size="lg">Update Work</flux:heading>
                <flux:subheading>Adjust the publication and review status.</flux:subheading>
            </div>

            <flux:select wire:model="status" label="Status">
                <flux:select.option value="suggested">Suggested</flux:select.option>
                <flux:select.option value="voting">Voting</flux:select.option>
                <flux:select.option value="approved">Approved</flux:select.option>
                <flux:select.option value="running">Running</flux:select.option>
                <flux:select.option value="upcoming">Upcoming</flux:select.option>
                <flux:select.option value="completed">Completed</flux:select.option>
                <flux:select.option value="rejected">Rejected</flux:select.option>
            </flux:select>

            <flux:select wire:model="category_id" label="Category">
                <flux:select.option value="">General</flux:select.option>
                @foreach ($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input type="number" wire:model="required_votes" label="Required Votes" min="1" max="1000" />
            <flux:checkbox wire:model="is_published" label="Published" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save Changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
