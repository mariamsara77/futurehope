<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Designation;
use Spatie\Permission\Models\Role;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    use WithFileUploads;

    public $userId = null;
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $status = 'active';
    public $role_name = '';
    public $designation_id = null;
    public $priority = 999;
    public $profile_status = 'pending';
    public $phone = '';
    public $father_name = '';
    public $mother_name = '';
    public $present_address = '';
    public $permanent_address = '';
    public $education = '';
    public $blood_group = '';
    public $bio = '';
    public $avatar = null;
    public $remove_avatar = false;

    #[Computed]
    public function designations()
    {
        return Designation::where('is_active', true)->orderBy('order')->get();
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function users()
    {
        return User::with(['profile.designation', 'roles'])->latest()->get();
    }

    public function editUser($id)
    {
        $user = User::with(['profile', 'roles'])->findOrFail($id);
        $profile = $user->profile;

        $this->resetValidation();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->status = $user->status;
        $this->role_name = $user->roles->first()?->name ?? 'member';
        $this->designation_id = $profile?->designation_id;
        $this->priority = $profile?->priority ?? 999;
        $this->profile_status = $profile?->status ?? 'pending';
        $this->phone = $profile?->phone ?? '';
        $this->father_name = $profile?->father_name ?? '';
        $this->mother_name = $profile?->mother_name ?? '';
        $this->present_address = $profile?->present_address ?? '';
        $this->permanent_address = $profile?->permanent_address ?? '';
        $this->education = $profile?->education ?? '';
        $this->blood_group = $profile?->blood_group ?? '';
        $this->bio = $profile?->bio ?? '';
        $this->avatar = null;
        $this->remove_avatar = false;

        Flux::modal('edit-user-modal')->show();
    }

    public function save()
    {
        $user = User::findOrFail($this->userId);

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'same:password_confirmation'],
            'status' => ['required', 'in:active,pending,blocked'],
            'role_name' => ['required', 'string', 'exists:roles,name'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
            'profile_status' => ['required', 'in:pending,active,inactive,rejected'],
            'phone' => ['nullable', 'string', 'max:20'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:100'],
            'present_address' => ['nullable', 'string', 'max:500'],
            'permanent_address' => ['nullable', 'string', 'max:500'],
            'education' => ['nullable', 'string', 'max:150'],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $authUser = auth()->user();
        if ($authUser && (int) $authUser->id === (int) $user->id && $this->role_name !== 'admin') {
            $this->addError('role_name', 'নিজের admin role নিজে সরানো যাবে না।');
            return;
        }

        $user->name = trim($this->name);
        $user->email = strtolower(trim($this->email));
        $user->status = $this->status;

        if (filled($this->password)) {
            $user->password = Hash::make($this->password);
        }

        $user->save();
        $user->syncRoles([$this->role_name]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'designation_id' => $this->designation_id ?: null,
                'priority' => (int) $this->priority,
                'status' => $this->profile_status,
                'phone' => filled($this->phone) ? trim($this->phone) : null,
                'father_name' => filled($this->father_name) ? trim($this->father_name) : null,
                'mother_name' => filled($this->mother_name) ? trim($this->mother_name) : null,
                'present_address' => filled($this->present_address) ? trim($this->present_address) : null,
                'permanent_address' => filled($this->permanent_address) ? trim($this->permanent_address) : null,
                'education' => filled($this->education) ? trim($this->education) : null,
                'blood_group' => $this->blood_group ?: null,
                'bio' => filled($this->bio) ? trim($this->bio) : null,
            ]
        );

        if ($this->remove_avatar) {
            $user->clearMediaCollection('avatar');
            $user->forceFill(['avatar' => null])->save();
        }

        if ($this->avatar) {
            $user->addMedia($this->avatar->getRealPath())
                ->usingFileName($this->avatar->getClientOriginalName())
                ->toMediaCollection('avatar');
            $user->forceFill(['avatar' => null])->save();
        }

        Flux::modal('edit-user-modal')->close();
        Flux::toast('User ও profile তথ্য সফলভাবে আপডেট হয়েছে।', variant: 'success');
        $this->resetForm();
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ((int) auth()->id() === (int) $user->id) {
            Flux::toast('নিজের account নিজে delete করা যাবে না।', variant: 'danger');
            return;
        }

        $user->tokens()->delete();
        $user->clearMediaCollection('avatar');
        $user->delete();

        Flux::toast('User সফলভাবে মুছে ফেলা হয়েছে।', variant: 'success');
    }

    private function resetForm(): void
    {
        $this->reset([
            'userId', 'name', 'email', 'password', 'password_confirmation',
            'status', 'role_name', 'designation_id', 'priority', 'profile_status',
            'phone', 'father_name', 'mother_name', 'present_address',
            'permanent_address', 'education', 'blood_group', 'bio',
            'avatar', 'remove_avatar'
        ]);
        $this->status = 'active';
        $this->role_name = '';
        $this->priority = 999;
        $this->profile_status = 'pending';
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Manage Users</flux:heading>
            <flux:subheading>সম্পূর্ণ user account, role, access এবং member profile এখান থেকেই manage করুন।</flux:subheading>
        </div>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Member</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Designation</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar src="{{ $user->avatar_url }}" size="sm" />
                            <div>
                                <div class="font-medium">{{ $user->name }}</div>
                                <div class="text-xs text-zinc-500">#{{ $user->id }}</div>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="blue">{{ $user->roles->first()?->name ?? 'None' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->profile?->designation?->name ?? 'General Member' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $user->status === 'active' ? 'green' : ($user->status === 'pending' ? 'yellow' : 'red') }}">
                            {{ ucfirst($user->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button size="sm" variant="subtle" wire:click="editUser({{ $user->id }})">
                                Edit / Manage
                            </flux:button>
                            @if ((int) auth()->id() !== (int) $user->id)
                                <flux:button size="sm" variant="ghost" color="danger" icon="trash"
                                    wire:confirm="এই user account এবং তার access/profile data delete করতে চান?"
                                    wire:click="deleteUser({{ $user->id }})" />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-8 text-zinc-500">
                        No users found.
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="edit-user-modal" class="md:w-[720px]">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit User & Member Profile</flux:heading>
                <flux:subheading>Account, security, role এবং সম্পূর্ণ biodata একসাথে update করুন।</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="name" label="Full Name" />
                <flux:input wire:model="email" type="email" label="Email" />

                <flux:input wire:model="password" type="password" label="New Password" placeholder="খালি রাখলে পরিবর্তন হবে না" />
                <flux:input wire:model="password_confirmation" type="password" label="Confirm New Password" />

                <flux:select wire:model="status" label="Account Status">
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="pending">Pending</flux:select.option>
                    <flux:select.option value="blocked">Blocked</flux:select.option>
                </flux:select>

                <flux:select wire:model="role_name" label="System Role">
                    @foreach($this->roles as $role)
                        <flux:select.option value="{{ $role->name }}">{{ ucfirst($role->name) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="designation_id" label="Foundation Designation">
                    <flux:select.option value="">General Member</flux:select.option>
                    @foreach($this->designations as $design)
                        <flux:select.option value="{{ $design->id }}">{{ $design->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="profile_status" label="Member Profile Status">
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="pending">Pending</flux:select.option>
                    <flux:select.option value="inactive">Inactive</flux:select.option>
                    <flux:select.option value="rejected">Rejected</flux:select.option>
                </flux:select>

                <flux:input wire:model="priority" type="number" min="1" max="9999" label="Public Display Priority" />
                <flux:input wire:model="phone" label="Phone" />
                <flux:input wire:model="father_name" label="Father's Name" />
                <flux:input wire:model="mother_name" label="Mother's Name" />
                <flux:input wire:model="education" label="Education" />
                <flux:select wire:model="blood_group" label="Blood Group">
                    <flux:select.option value="">Not set</flux:select.option>
                    @foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $group)
                        <flux:select.option value="{{ $group }}">{{ $group }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="present_address" label="Present Address" rows="2" />
            <flux:textarea wire:model="permanent_address" label="Permanent Address" rows="2" />
            <flux:textarea wire:model="bio" label="Short Bio" rows="3" />

            <div class="space-y-3">
                <flux:input wire:model="avatar" type="file" label="Profile Photo" accept="image/jpeg,image/png,image/jpg,image/webp" />
                <flux:checkbox wire:model="remove_avatar" label="Remove current profile photo" />
                @if ($avatar)
                    <div class="text-xs text-zinc-500">New photo selected and will replace the current photo after saving.</div>
                @endif
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save All Changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>