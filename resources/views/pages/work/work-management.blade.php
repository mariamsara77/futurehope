<?php

use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkUpdate;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $publicationFilter = 'all';
    public string $categoryFilter = 'all';

    public ?int $selectedWorkId = null;
    public ?Work $editingWork = null;
    public $viewingWork = null;

    public string $title = '';
    public string $description = '';
    public string $submitted_name = '';
    public string $submitted_email = '';
    public ?int $category_id = null;
    public string $status = 'voting';
    public bool $is_published = false;
    public int $required_votes = 10;
    public array $newImages = [];

    public ?int $editingUpdateId = null;
    public string $updateTitle = '';
    public string $updateDescription = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPublicationFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function viewWork(int $id): void
    {
        $this->viewingWork = Work::with([
            'user:id,name,email',
            'category:id,name',
            'media',
            'votes.user:id,name,email',
            'updates.user:id,name',
        ])->findOrFail($id);

        Flux::modal('work-view-modal')->show();
    }

    public function editWork(int $id): void
    {
        $work = Work::with(['user:id,name,email', 'category:id,name', 'media', 'updates.user:id,name'])->findOrFail($id);

        $this->selectedWorkId = $work->id;
        $this->editingWork = $work;
        $this->title = (string) $work->title;
        $this->description = (string) $work->description;
        $this->submitted_name = (string) ($work->submitted_name ?? $work->user?->name ?? '');
        $this->submitted_email = (string) ($work->submitted_email ?? $work->user?->email ?? '');
        $this->category_id = $work->category_id;
        $this->status = (string) $work->status;
        $this->is_published = (bool) $work->is_published;
        $this->required_votes = max(1, (int) $work->required_votes);
        $this->newImages = [];

        $this->resetUpdateForm();

        Flux::modal('work-edit-modal')->show();
    }

    public function saveWork(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:20000'],
            'submitted_name' => ['nullable', 'string', 'max:100'],
            'submitted_email' => ['nullable', 'email', 'max:255'],
            'category_id' => ['nullable', 'exists:work_categories,id'],
            'status' => ['required', 'in:suggested,voting,approved,running,upcoming,completed,rejected'],
            'is_published' => ['boolean'],
            'required_votes' => ['required', 'integer', 'min:1', 'max:1000'],
            'newImages' => ['array', 'max:10'],
            'newImages.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $work = Work::findOrFail($this->selectedWorkId);

        $work->update([
            'title' => trim($this->title),
            'description' => trim($this->description),
            'submitted_name' => trim($this->submitted_name) ?: null,
            'submitted_email' => trim($this->submitted_email) ?: null,
            'category_id' => $this->category_id ?: null,
            'status' => $this->status,
            'is_published' => $this->is_published,
            'required_votes' => $this->required_votes,
        ]);

        foreach ($this->newImages as $image) {
            $work->addMedia($image)->toMediaCollection('gallery');
        }

        $this->editingWork = $work->fresh(['user:id,name,email', 'category:id,name', 'media', 'updates.user:id,name']);
        $this->newImages = [];

        Flux::modal('work-edit-modal')->close();
        Flux::toast('Work details updated successfully.', variant: 'success');
        $this->resetWorkForm();
    }

    public function removeImage(int $mediaId): void
    {
        $work = Work::findOrFail($this->selectedWorkId);
        $media = $work->media()->whereKey($mediaId)->first();

        if (!$media) {
            Flux::toast('Image was not found on this work.', variant: 'warning');
            return;
        }

        $media->delete();

        $this->editingWork = $work->fresh(['media', 'updates.user:id,name']);
        Flux::toast('Work image removed.', variant: 'success');
    }

    public function saveUpdate(): void
    {
        $this->validate([
            'updateTitle' => ['required', 'string', 'max:200'],
            'updateDescription' => ['nullable', 'string', 'max:10000'],
        ]);

        $work = Work::findOrFail($this->selectedWorkId);

        if ($this->editingUpdateId) {
            $update = $work->updates()->findOrFail($this->editingUpdateId);
            $update->update([
                'title' => trim($this->updateTitle),
                'description' => trim($this->updateDescription),
            ]);

            Flux::toast('Work update edited successfully.', variant: 'success');
        } else {
            WorkUpdate::create([
                'work_id' => $work->id,
                'user_id' => auth()->id(),
                'title' => trim($this->updateTitle),
                'description' => trim($this->updateDescription),
            ]);

            Flux::toast('Work update added successfully.', variant: 'success');
        }

        $this->editingWork = $work->fresh(['media', 'updates.user:id,name']);
        $this->resetUpdateForm();
    }

    public function editUpdate(int $id): void
    {
        $work = Work::findOrFail($this->selectedWorkId);
        $update = $work->updates()->findOrFail($id);

        $this->editingUpdateId = $update->id;
        $this->updateTitle = (string) $update->title;
        $this->updateDescription = (string) ($update->description ?? '');
    }

    public function deleteUpdate(int $id): void
    {
        $work = Work::findOrFail($this->selectedWorkId);
        $work->updates()->whereKey($id)->firstOrFail()->delete();

        $this->editingWork = $work->fresh(['media', 'updates.user:id,name']);
        $this->resetUpdateForm();
        Flux::toast('Work update deleted.', variant: 'success');
    }

    public function deleteWork(int $id): void
    {
        $work = Work::findOrFail($id);
        $work->delete();

        if ($this->selectedWorkId === $id) {
            $this->resetWorkForm();
            Flux::modal('work-edit-modal')->close();
        }

        Flux::toast('Work moved to trash.', variant: 'danger');
    }

    public function resetWorkForm(): void
    {
        $this->reset([
            'selectedWorkId',
            'editingWork',
            'title',
            'description',
            'submitted_name',
            'submitted_email',
            'category_id',
            'is_published',
            'newImages',
            'editingUpdateId',
            'updateTitle',
            'updateDescription',
        ]);

        $this->status = 'voting';
        $this->required_votes = 10;
    }

    public function resetUpdateForm(): void
    {
        $this->editingUpdateId = null;
        $this->updateTitle = '';
        $this->updateDescription = '';
    }

    public function with(): array
    {
        return [
            'categories' => WorkCategory::query()
                ->orderBy('name')
                ->get(),
            'works' => Work::query()
                ->with(['user:id,name,email', 'category:id,name', 'media'])
                ->when($this->search, function ($query) {
                    $search = trim($this->search);

                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', '%' . $search . '%')
                            ->orWhere('description', 'like', '%' . $search . '%')
                            ->orWhere('submitted_name', 'like', '%' . $search . '%')
                            ->orWhere('submitted_email', 'like', '%' . $search . '%')
                            ->orWhereHas('user', function ($userQuery) use ($search) {
                                $userQuery->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('email', 'like', '%' . $search . '%');
                            });
                    });
                })
                ->when(
                    $this->statusFilter !== 'all',
                    fn ($query) => $query->where('status', $this->statusFilter)
                )
                ->when(
                    $this->publicationFilter === 'published',
                    fn ($query) => $query->where('is_published', true)
                )
                ->when(
                    $this->publicationFilter === 'unpublished',
                    fn ($query) => $query->where('is_published', false)
                )
                ->when(
                    $this->categoryFilter !== 'all',
                    fn ($query) => $query->where('category_id', (int) $this->categoryFilter)
                )
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <flux:heading size="xl">Work Management</flux:heading>
            <flux:subheading>
                Full administrative control for work records, publication, images, votes, and progress updates.
            </flux:subheading>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search title, author, email..."
                icon="magnifying-glass"
            />

            <flux:select wire:model.live="statusFilter" label="Status">
                <flux:select.option value="all">All statuses</flux:select.option>
                <flux:select.option value="suggested">Suggested</flux:select.option>
                <flux:select.option value="voting">Voting</flux:select.option>
                <flux:select.option value="approved">Approved</flux:select.option>
                <flux:select.option value="running">Running</flux:select.option>
                <flux:select.option value="upcoming">Upcoming</flux:select.option>
                <flux:select.option value="completed">Completed</flux:select.option>
                <flux:select.option value="rejected">Rejected</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="publicationFilter" label="Publication">
                <flux:select.option value="all">All publication states</flux:select.option>
                <flux:select.option value="published">Published</flux:select.option>
                <flux:select.option value="unpublished">Unpublished</flux:select.option>
            </flux:select>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        <flux:select wire:model.live="categoryFilter" label="Category">
            <flux:select.option value="all">All categories</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:card class="overflow-hidden p-0">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Work</flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Votes</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Published</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($works as $work)
                    @php
                        $progress = min(
                            100,
                            (int) round(($work->votes_count / max(1, $work->required_votes)) * 100)
                        );
                    @endphp
                    <flux:table.row :key="$work->id">
                        <flux:table.cell>
                            <div class="min-w-56 space-y-1">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $work->title }}</div>
                                <div class="flex flex-wrap gap-2 text-xs text-zinc-500">
                                    <span>{{ $work->submitted_name ?: ($work->user?->name ?? 'Guest') }}</span>
                                    <span>•</span>
                                    <span>#{{ $work->id }}</span>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" variant="subtle">
                                {{ $work->category?->name ?? 'General' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="min-w-28">
                                <div class="text-sm font-medium">{{ $work->votes_count }}/{{ $work->required_votes }}</div>
                                <div class="mt-1 h-1.5 w-28 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $progress }}%"></div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $work->status === 'approved' ? 'green' : ($work->status === 'rejected' ? 'red' : 'yellow') }}">
                                {{ ucfirst($work->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $work->is_published ? 'green' : 'zinc' }}">
                                {{ $work->is_published ? 'Yes' : 'No' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-xs text-zinc-500">
                                {{ $work->created_at?->format('Y-m-d H:i') }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:button size="sm" variant="ghost" icon="eye" wire:click="viewWork({{ $work->id }})">
                                    View
                                </flux:button>
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editWork({{ $work->id }})">
                                    Manage
                                </flux:button>
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    color="danger"
                                    icon="trash"
                                    wire:confirm="Move this work to trash?"
                                    wire:click="deleteWork({{ $work->id }})"
                                >
                                    Trash
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-10 text-center text-zinc-500">
                            No work records found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <div>
        {{ $works->links() }}
    </div>

    <flux:modal name="work-view-modal" class="md:w-[900px]">
        @if ($viewingWork)
            <div class="space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <flux:heading size="lg">{{ $viewingWork->title }}</flux:heading>
                        <flux:subheading>
                            Work #{{ $viewingWork->id }} ·
                            {{ $viewingWork->submitted_name ?: ($viewingWork->user?->name ?? 'Guest') }}
                        </flux:subheading>
                    </div>

                    <div class="flex gap-2">
                        <flux:badge color="{{ $viewingWork->status === 'approved' ? 'green' : ($viewingWork->status === 'rejected' ? 'red' : 'yellow') }}">
                            {{ ucfirst($viewingWork->status) }}
                        </flux:badge>
                        <flux:badge color="{{ $viewingWork->is_published ? 'green' : 'zinc' }}">
                            {{ $viewingWork->is_published ? 'Published' : 'Unpublished' }}
                        </flux:badge>
                    </div>
                </div>

                @if ($viewingWork->media->isNotEmpty())
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($viewingWork->media as $media)
                            <img
                                src="{{ $media->getUrl('thumb') ?: $media->getUrl() }}"
                                alt="{{ $viewingWork->title }}"
                                class="h-44 w-full rounded-xl object-cover"
                            />
                        @endforeach
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="text-xs uppercase tracking-wide text-zinc-500">Category</div>
                        <div class="mt-2 font-medium">{{ $viewingWork->category?->name ?? 'General' }}</div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="text-xs uppercase tracking-wide text-zinc-500">Votes</div>
                        <div class="mt-2 font-medium">{{ $viewingWork->votes_count }} / {{ $viewingWork->required_votes }}</div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="text-xs uppercase tracking-wide text-zinc-500">Created</div>
                        <div class="mt-2 font-medium">{{ $viewingWork->created_at?->format('Y-m-d H:i') }}</div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="text-xs uppercase tracking-wide text-zinc-500">Submitted By</div>
                        <div class="mt-2 font-medium">{{ $viewingWork->submitted_name ?: ($viewingWork->user?->name ?? 'Guest') }}</div>
                        <div class="mt-1 text-sm text-zinc-500">{{ $viewingWork->submitted_email ?: ($viewingWork->user?->email ?? '—') }}</div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="text-xs uppercase tracking-wide text-zinc-500">Last Updated</div>
                        <div class="mt-2 font-medium">{{ $viewingWork->updated_at?->format('Y-m-d H:i') }}</div>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <div class="text-xs uppercase tracking-wide text-zinc-500">Description</div>
                    <div class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                        {{ $viewingWork->description ?: 'No description provided.' }}
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <flux:heading size="sm">Voters</flux:heading>
                        <div class="mt-3 space-y-2">
                            @forelse ($viewingWork->votes as $vote)
                                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                    <div>
                                        <div class="text-sm font-medium">{{ $vote->user?->name ?? 'Unknown user' }}</div>
                                        <div class="text-xs text-zinc-500">{{ $vote->user?->email ?? '' }}</div>
                                    </div>
                                    <div class="text-xs text-zinc-500">{{ $vote->created_at?->format('Y-m-d H:i') }}</div>
                                </div>
                            @empty
                                <div class="text-sm text-zinc-500">No votes yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <flux:heading size="sm">Progress Updates</flux:heading>
                        <div class="mt-3 space-y-3">
                            @forelse ($viewingWork->updates as $update)
                                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <div class="font-medium">{{ $update->title }}</div>
                                    <div class="mt-1 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $update->description }}</div>
                                    <div class="mt-2 text-xs text-zinc-500">
                                        {{ $update->user?->name ?? 'Admin' }} · {{ $update->created_at?->format('Y-m-d H:i') }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-zinc-500">No updates yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <flux:button variant="ghost" wire:click="editWork({{ $viewingWork->id }})">Manage Work</flux:button>
                    <flux:modal.close>
                        <flux:button variant="primary">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="work-edit-modal" class="md:w-[980px]">
        @if ($editingWork)
            <form wire:submit="saveWork" class="space-y-6">
                <div>
                    <flux:heading size="lg">Manage Work #{{ $editingWork->id }}</flux:heading>
                    <flux:subheading>
                        Update the full record. Existing votes remain intact.
                    </flux:subheading>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="title" label="Work Title" />
                    <flux:select wire:model="category_id" label="Category">
                        <flux:select.option value="">General</flux:select.option>
                        @foreach ($categories as $category)
                            <flux:select.option value="{{ $category->id }}">
                                {{ $category->name }}{{ $category->is_active ? '' : ' (Inactive)' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="description" label="Description" rows="7" />

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="submitted_name" label="Submitted Name" />
                    <flux:input wire:model="submitted_email" label="Submitted Email" type="email" />
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:select wire:model="status" label="Status">
                        <flux:select.option value="suggested">Suggested</flux:select.option>
                        <flux:select.option value="voting">Voting</flux:select.option>
                        <flux:select.option value="approved">Approved</flux:select.option>
                        <flux:select.option value="running">Running</flux:select.option>
                        <flux:select.option value="upcoming">Upcoming</flux:select.option>
                        <flux:select.option value="completed">Completed</flux:select.option>
                        <flux:select.option value="rejected">Rejected</flux:select.option>
                    </flux:select>

                    <flux:input
                        type="number"
                        wire:model="required_votes"
                        label="Required Votes"
                        min="1"
                        max="1000"
                    />

                    <div class="flex items-end">
                        <flux:checkbox wire:model="is_published" label="Published" />
                    </div>
                </div>

                <div class="space-y-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <flux:heading size="sm">Work Images</flux:heading>
                        <flux:subheading>Remove old images or upload additional gallery images.</flux:subheading>
                    </div>

                    @if ($editingWork->media->isNotEmpty())
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($editingWork->media as $media)
                                <div class="relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                                    <img
                                        src="{{ $media->getUrl('thumb') ?: $media->getUrl() }}"
                                        alt="{{ $editingWork->title }}"
                                        class="h-36 w-full object-cover"
                                    />
                                    <div class="absolute right-2 top-2">
                                        <flux:button
                                            size="xs"
                                            variant="danger"
                                            icon="trash"
                                            wire:click.prevent="removeImage({{ $media->id }})"
                                            wire:confirm="Remove this image from the work?"
                                        >
                                            Remove
                                        </flux:button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-zinc-500">No images attached.</div>
                    @endif

                    <input
                        type="file"
                        wire:model="newImages"
                        multiple
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        class="block w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:me-4 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:file:bg-zinc-800"
                    />

                    @error('newImages.*')
                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                    @enderror

                    <div wire:loading wire:target="newImages" class="text-sm text-zinc-500">
                        Uploading images...
                    </div>
                </div>

                <div class="space-y-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <flux:heading size="sm">Progress Updates</flux:heading>
                            <flux:subheading>Add and maintain public progress/history updates for this work.</flux:subheading>
                        </div>
                        @if ($editingUpdateId)
                            <flux:button size="sm" variant="ghost" wire:click="resetUpdateForm">Cancel Edit</flux:button>
                        @endif
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <flux:input wire:model="updateTitle" label="Update Title" placeholder="Project update" />
                        <flux:textarea wire:model="updateDescription" label="Update Description" rows="3" />
                    </div>

                    <div class="flex justify-end">
                        <flux:button type="button" variant="subtle" wire:click="saveUpdate">
                            {{ $editingUpdateId ? 'Update Progress Entry' : 'Add Progress Update' }}
                        </flux:button>
                    </div>

                    <div class="space-y-3">
                        @forelse ($editingWork->updates as $update)
                            <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="font-medium">{{ $update->title }}</div>
                                        <div class="mt-1 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">
                                            {{ $update->description }}
                                        </div>
                                        <div class="mt-2 text-xs text-zinc-500">
                                            {{ $update->user?->name ?? 'Admin' }} · {{ $update->created_at?->format('Y-m-d H:i') }}
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 gap-2">
                                        <flux:button size="xs" variant="ghost" wire:click="editUpdate({{ $update->id }})">
                                            Edit
                                        </flux:button>
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            color="danger"
                                            wire:click="deleteUpdate({{ $update->id }})"
                                            wire:confirm="Delete this progress update?"
                                        >
                                            Delete
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-zinc-500">No progress updates have been added.</div>
                        @endforelse
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
                    <flux:button
                        type="button"
                        variant="ghost"
                        color="danger"
                        icon="trash"
                        wire:click="deleteWork({{ $editingWork->id }})"
                        wire:confirm="Move this work to trash?"
                    >
                        Move to Trash
                    </flux:button>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="subtle">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Save Work Changes</flux:button>
                    </div>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
