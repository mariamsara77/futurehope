<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Designation;
use Spatie\Permission\Models\Role;
use Flux\Flux;

new class extends Component {
    public $userId = null;
    public $status = '';
    public $designation_id = null;
    public $role_name = '';

    // Computed properties - Livewire state payload হালকা রাখার জন্য
    #[Computed]
    public function designations()
    {
        return Designation::where('is_active', true)->orderBy('order')->get();
    }

    #[Computed]
    public function roles()
    {
        return Role::all();
    }

    #[Computed]
    public function users()
    {
        return User::with(['profile.designation', 'roles'])->get();
    }

    public function editUser($id)
    {
        $user = User::with(['profile', 'roles'])->findOrFail($id);
        
        $this->userId = $user->id;
        $this->status = $user->status;
        $this->designation_id = $user->profile?->designation_id;
        $this->role_name = $user->roles->first()?->name ?? '';
        
        // সঠিক ফ্লাক্স মডাল সিনট্যাক্স (Flux::modal, Flux::modals নয়)
        Flux::modal('edit-user-modal')->show();
    }

    public function save()
    {
        $this->validate([
            'status' => 'required|in:active,pending,blocked',
            'role_name' => 'nullable|string',
            'designation_id' => 'nullable|exists:designations,id',
        ]);

        $user = User::findOrFail($this->userId);
        
        // Update Status
        $user->update(['status' => $this->status]);

        // Update Spatie Role
        if ($this->role_name) {
            $user->syncRoles([$this->role_name]);
        } else {
            $user->syncRoles([]);
        }

        // Update Profile Designation
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['designation_id' => $this->designation_id ?: null]
        );

        // Modal বন্ধ করা
        Flux::modal('edit-user-modal')->close();
        Flux::toast('User updated successfully!', variant: 'success');
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Manage Users</flux:heading>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Designation</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar
                                src="{{ $user->profile?->getFirstMediaUrl('avatar') ?: asset('default-avatar.png') }}"
                                size="sm" />
                            <span class="font-medium">{{ $user->name }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="blue">{{ $user->roles->first()?->name ?? 'None' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->profile?->designation?->name ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge
                            color="{{ $user->status === 'active' ? 'green' : ($user->status === 'pending' ? 'yellow' : 'red') }}">
                            {{ ucfirst($user->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="subtle" wire:click="editUser({{ $user->id }})">
                            Manage
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal -->
    <flux:modal name="edit-user-modal" class="md:w-[400px]">
        <form wire:submit="save" class="space-y-6">
            <flux:heading>Manage User Access</flux:heading>

            <flux:select wire:model="status" label="Account Status">
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
                <flux:select.option value="blocked">Blocked</flux:select.option>
            </flux:select>

            <flux:select wire:model="role_name" label="System Role">
                <flux:select.option value="">Select Role...</flux:select.option>
                @foreach($this->roles as $role)
                <flux:select.option value="{{ $role->name }}">{{ ucfirst($role->name) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="designation_id" label="Foundation Designation">
                <flux:select.option value="">General Member (No Designation)</flux:select.option>
                @foreach($this->designations as $design)
                <flux:select.option value="{{ $design->id }}">{{ $design->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save Changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>