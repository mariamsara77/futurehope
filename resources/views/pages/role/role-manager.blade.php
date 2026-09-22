<?php

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Flux\Flux;

new class extends Component {
    public $roleId = null;
    public $name = '';
    public array $selectedPermissions = [];

    public function openModal()
    {
        $this->reset(['roleId', 'name', 'selectedPermissions']);
        $this->resetValidation();
        Flux::modal('role-modal')->show();
    }

    public function editRole($id)
    {
        $this->resetValidation();
        $role = Role::findById($id);
        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        Flux::modal('role-modal')->show();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $this->roleId,
        ]);

        $role = $this->roleId 
            ? Role::findById($this->roleId) 
            : Role::create(['name' => strtolower(trim($this->name)), 'guard_name' => 'web']);

        $role->name = strtolower(trim($this->name));
        $role->save();

        $role->syncPermissions($this->selectedPermissions);

        Flux::modal('role-modal')->close();
        Flux::toast('Role & permissions saved successfully!', variant: 'success');
        $this->reset(['roleId', 'name', 'selectedPermissions']);
    }

    public function deleteRole($id)
    {
        $role = Role::findById($id);

        if ($role->name === 'admin') {
            Flux::toast('Admin role cannot be deleted!', variant: 'danger');
            return;
        }

        $role->delete();
        Flux::toast('Role deleted successfully!', variant: 'danger');
    }

    public function with(): array
    {
        $allPermissions = Permission::all();
        $groupedPermissions = $allPermissions->groupBy(function ($permission) {
            return explode('.', $permission->name)[0] ?? 'general';
        });

        return [
            'roles' => Role::with('permissions')->get(),
            'groupedPermissions' => $groupedPermissions,
        ];
    }
}; ?>

<div>
    <!-- Header Section -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Roles & Permissions</flux:heading>
            <flux:subheading>Manage roles and assign permissions module wise</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openModal">
            Create Role
        </flux:button>
    </div>

    <!-- Roles Table -->
    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>#</flux:table.column>
                <flux:table.column>Role Name</flux:table.column>
                <flux:table.column>Permissions</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($roles as $role)
                <flux:table.row :key="$role->id">
                    <flux:table.cell>{{ $role->id }}</flux:table.cell>
                    <flux:table.cell class="font-medium">
                        <flux:badge color="indigo" inset="top bottom">{{ ucfirst($role->name) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1 max-w-xl">
                            @forelse ($role->permissions as $perm)
                            <flux:badge size="sm" variant="subtle">{{ $perm->name }}</flux:badge>
                            @empty
                            <span class="text-xs text-zinc-500">No permissions assigned</span>
                            @endforelse
                        </div>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-2">
                            <flux:button size="sm" variant="ghost" icon="pencil-square"
                                wire:click="editRole({{ $role->id }})" />
                            <flux:button size="sm" variant="ghost" color="danger" icon="trash"
                                wire:confirm="Are you sure you want to delete this role?"
                                wire:click="deleteRole({{ $role->id }})" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center py-6 text-zinc-500">
                        No roles found.
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal Form -->
    <flux:modal name="role-modal" class="md:w-3/5 space-y-6">
        <div>
            <flux:heading size="lg">{{ $roleId ? 'Edit Role & Permissions' : 'Create New Role' }}</flux:heading>
            <flux:subheading>Assign specific permissions to this role.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="name" label="Role Name" placeholder="e.g. manager, editor, admin" required />

            <div class="space-y-3">
                <flux:label>Assign Permissions</flux:label>

                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-96 overflow-y-auto p-3 border border-zinc-700/30 rounded-lg">
                    @foreach ($groupedPermissions as $group => $permissions)
                    <div class="p-3 bg-zinc-800/40 rounded-md border border-zinc-700/40 space-y-2">
                        <div class="font-semibold text-sm capitalize text-zinc-300 border-b border-zinc-700/50 pb-1">
                            {{ $group }} Module
                        </div>

                        @foreach ($permissions as $permission)
                        <flux:checkbox wire:model="selectedPermissions" :value="$permission->name"
                            :label="$permission->name" />
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-700/50">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    Save Role
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>