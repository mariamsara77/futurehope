<?php

use App\Models\WorkCategory;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $isEditMode = false;
    public ?int $categoryId = null;

    #[Validate('required|string|min:2|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        Flux::modal('category-modal')->show();
    }

    public function editCategory(int $id): void
    {
        $category = WorkCategory::findOrFail($id);

        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->is_active = (bool) $category->is_active;
        $this->isEditMode = true;

        Flux::modal('category-modal')->show();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255', 'unique:work_categories,name,' . $this->categoryId],
            'slug' => ['nullable', 'string', 'max:255', 'unique:work_categories,slug,' . $this->categoryId],
            'is_active' => ['boolean'],
        ]);

        $slug = trim((string) ($this->slug ?: Str::slug($this->name)));

        WorkCategory::updateOrCreate(
            ['id' => $this->categoryId],
            [
                'name' => trim($this->name),
                'slug' => $slug,
                'is_active' => $this->is_active,
            ]
        );

        Flux::modal('category-modal')->close();
        Flux::toast('Work category saved successfully.', variant: 'success');
        $this->resetForm();
    }

    public function deleteCategory(int $id): void
    {
        $category = WorkCategory::findOrFail($id);

        if ($category->works()->exists()) {
            Flux::toast('This category has linked works and cannot be deleted.', variant: 'warning');
            return;
        }

        $category->delete();
        Flux::toast('Work category deleted.', variant: 'danger');
    }

    public function resetForm(): void
    {
        $this->reset(['categoryId', 'name', 'slug', 'is_active', 'isEditMode']);
        $this->is_active = true;
    }

    public function with(): array
    {
        return [
            'categories' => WorkCategory::query()
                ->when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('slug', 'like', '%' . $this->search . '%');
                })
                ->orderBy('name')
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Manage Work Categories</flux:heading>
            <flux:subheading>Organize and maintain the platform's work categories.</flux:subheading>
        </div>

        <div class="flex items-center gap-3">
            <div class="w-72">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search categories..." icon="magnifying-glass" />
            </div>
            <flux:button wire:click="openCreateModal" variant="primary">Add Category</flux:button>
        </div>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Works</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($categories as $category)
                    <flux:table.row :key="$category->id">
                        <flux:table.cell>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $category->name }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="font-mono text-xs text-zinc-500">{{ $category->slug }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $category->is_active ? 'green' : 'red' }}">
                                {{ $category->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $category->works()->count() }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editCategory({{ $category->id }})">
                                    Edit
                                </flux:button>
                                <flux:button size="sm" variant="ghost" color="danger" icon="trash" wire:confirm="Delete this category?" wire:click="deleteCategory({{ $category->id }})">
                                    Delete
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-6 text-center text-zinc-500">
                            No work categories found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>

    <flux:modal name="category-modal" class="md:w-[420px]">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isEditMode ? 'Edit Category' : 'Create Category' }}</flux:heading>
                <flux:subheading>{{ $isEditMode ? 'Update the selected category.' : 'Add a new work category.' }}</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Category Name" placeholder="Community Project" />
            <flux:input wire:model="slug" label="Slug" placeholder="community-project" />
            <flux:checkbox wire:model="is_active" label="Active" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
