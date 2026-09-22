<?php

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public $permissionId = null;
    public $name = '';
    public $search = '';
    public $isEditMode = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetForm();
        Flux::modal('permission-modal')->show();
    }

    public function resetForm()
    {
        $this->reset(['permissionId', 'name', 'isEditMode']);
        $this->resetValidation();
    }

    public function editPermission($id)
    {
        $this->resetValidation();
        $permission = Permission::findOrFail($id);
        $this->permissionId = $permission->id;
        $this->name = $permission->name;
        $this->isEditMode = true;

        Flux::modal('permission-modal')->show();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $this->permissionId,
        ]);

        Permission::updateOrCreate(
            ['id' => $this->permissionId],
            [
                'name' => strtolower(trim($this->name)),
                'guard_name' => 'web',
            ]
        );

        Flux::modal('permission-modal')->close();
        
        Flux::toast(
            $this->isEditMode ? 'Permission updated successfully!' : 'Permission created successfully!', 
            variant: 'success'
        );

        $this->resetForm();
    }

    public function deletePermission($id)
    {
        Permission::findOrFail($id)->delete();
        Flux::toast('Permission deleted successfully!', variant: 'danger');
    }

    public function with(): array
    {
        return [
            'permissions' => Permission::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                ->orderBy('name')
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <!-- Header Section -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Permission Management</flux:heading>
            <flux:subheading>System level permissions and access controls</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openModal">
            Add Permission
        </flux:button>
    </div>

    <!-- Search Box -->
    <div class="mb-4 max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search permissions..."
            icon="magnifying-glass" />
    </div>

    <!-- Permissions Table -->
    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>#</flux:table.column>
                <flux:table.column>Permission Name</flux:table.column>
                <flux:table.column>Guard</flux:table.column>
                <flux:table.column>Created At</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($permissions as $permission)
                <flux:table.row :key="$permission->id">
                    <flux:table.cell>{{ $permission->id }}</flux:table.cell>
                    <flux:table.cell class="font-medium">
                        <flux:badge color="zinc" inset="top bottom">{{ $permission->name }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" variant="subtle">{{ $permission->guard_name }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $permission->created_at?->format('d M, Y') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-2">
                            <flux:button size="sm" variant="ghost" icon="pencil-square"
                                wire:click="editPermission({{ $permission->id }})" />
                            <flux:button size="sm" variant="ghost" color="danger" icon="trash"
                                wire:confirm="Are you sure you want to delete this permission?"
                                wire:click="deletePermission({{ $permission->id }})" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-6 text-zinc-500">
                        No permissions found.
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $permissions->links() }}
    </div>

    <!-- Modal Form -->
    <flux:modal name="permission-modal" class="md:w-114 space-y-6">
        <div>
            <flux:heading size="lg">{{ $isEditMode ? 'Edit Permission' : 'Create Permission' }}</flux:heading>
            <flux:subheading>
                {{ $isEditMode ? 'Update existing permission details.' : 'Add a new permission string (e.g., users.create, posts.edit).' }}
            </flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="name" label="Permission Name" placeholder="e.g. users.create, roles.edit"
                required />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $isEditMode ? 'Update' : 'Create' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>