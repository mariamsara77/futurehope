<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Profile;
use App\Models\Designation;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $selectedProfileId = null;
    public $status = '';
    public $viewingProfile = null;
    public $designation_id = null;
    public $priority = 999;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function viewProfile($id)
    {
        $this->viewingProfile = Profile::with(['user.roles', 'designation'])->findOrFail($id);
        Flux::modal('view-biodata-modal')->show();
    }

    public function editProfile($id)
    {
        $profile = Profile::findOrFail($id);
        $this->selectedProfileId = $profile->id;
        $this->status = $profile->status ?? 'pending';
        $this->designation_id = $profile->designation_id;
        $this->priority = $profile->priority ?? 999;
        
        Flux::modal('edit-biodata-modal')->show();
    }

    public function updateStatus()
    {
        $this->validate([
            'status' => 'required|in:active,inactive,pending,rejected',
            'designation_id' => 'nullable|exists:designations,id',
            'priority' => 'required|integer|min:1|max:9999',
        ];

        $profile = Profile::findOrFail($this->selectedProfileId);
        
        // স্পষ্টভাবে স্ট্যাটাস ফিল্ড আপডেট ও সেভ করা
        $profile->status = $this->status;
        $profile->designation_id = $this->designation_id ?: null;
        $profile->priority = (int) $this->priority;
        $profile->save();

        Flux::modal('edit-biodata-modal')->close();
        Flux::toast('Biodata status updated successfully!', variant: 'success');
        
        $this->reset(['selectedProfileId', 'status', 'designation_id', 'priority']);
    }

    public function deleteProfile($id)
    {
        $profile = Profile::findOrFail($id);
        
        // অ্যাভাটার বা মিডিয়া থাকলে তা ডিলিট করা
        if ($profile->hasMedia('avatar')) {
            $profile->clearMediaCollection('avatar');
        }

        $profile->delete();
        Flux::toast('Biodata deleted successfully!', variant: 'danger');
    }

    public function with(): array
    {
        return [
            'designations' => Designation::where('is_active', true)->orderBy('order')->get(),
            'profiles' => Profile::with(['user.roles', 'designation'])
                ->when($this->search, function ($query) {
                    $query->whereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                    })->orWhere('phone', 'like', '%' . $this->search . '%')
                      ->orWhere('blood_group', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Manage All Biodata</flux:heading>
            <flux:subheading>Review and manage all members' biodata records</flux:subheading>
        </div>

        <div class="w-72">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name, email, phone..."
                icon="magnifying-glass" />
        </div>
    </div>

    <!-- Biodata Table -->
    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Member</flux:table.column>
                <flux:table.column>Phone & Blood</flux:table.column>
                <flux:table.column>Designation</flux:table.column>
                <flux:table.column>Education</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($profiles as $profile)
                <flux:table.row :key="$profile->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar
                                src="{{ $profile->hasMedia('avatar') ? $profile->getFirstMediaUrl('avatar') : asset('default-avatar.png') }}"
                                size="sm" />
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $profile->user?->name ?? 'N/A' }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $profile->user?->email }}
                                </div>
                            </div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="text-sm">{{ $profile->phone ?? 'N/A' }}</div>
                        <div class="text-xs text-zinc-500">Group: <span
                                class="font-semibold text-rose-500">{{ $profile->blood_group ?? 'N/A' }}</span></div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" variant="subtle">
                            {{ $profile->designation?->name ?? 'General Member' }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="text-sm">{{ Str::limit($profile->education, 20) ?: 'N/A' }}</span>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge color="{{ ($profile->status ?? 'active') === 'active' ? 'green' : 'yellow' }}">
                            {{ ucfirst($profile->status ?? 'active') }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="eye"
                                wire:click="viewProfile({{ $profile->id }})" title="View Details" />
                            <flux:button size="sm" variant="ghost" icon="pencil-square"
                                wire:click="editProfile({{ $profile->id }})" title="Change Status" />
                            <flux:button size="sm" variant="ghost" color="danger" icon="trash"
                                wire:confirm="Are you sure you want to delete this biodata?"
                                wire:click="deleteProfile({{ $profile->id }})" title="Delete" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-6 text-zinc-500">
                        No biodata found.
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <div class="mt-4">
        {{ $profiles->links() }}
    </div>

    <!-- View Details Modal -->
    <flux:modal name="view-biodata-modal" class="md:w-2/3 space-y-6">
        @if ($viewingProfile)
        <div>
            <flux:heading size="lg">Biodata Details</flux:heading>
            <flux:subheading>Full information of {{ $viewingProfile->user?->name }}</flux:subheading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center border-b border-zinc-700/40 pb-4">
            <div class="flex justify-center">
                <flux:avatar
                    src="{{ $viewingProfile->hasMedia('avatar') ? $viewingProfile->getFirstMediaUrl('avatar') : asset('default-avatar.png') }}"
                    size="2xl" />
            </div>
            <div class="md:col-span-2 space-y-1">
                <div class="text-xl font-bold">{{ $viewingProfile->user?->name }}</div>
                <div class="text-sm text-zinc-400">{{ $viewingProfile->user?->email }}</div>
                <div class="text-sm">Phone: <span class="font-medium">{{ $viewingProfile->phone ?? 'N/A' }}</span></div>
                <div class="text-sm">Blood Group: <span
                        class="font-semibold text-rose-500">{{ $viewingProfile->blood_group ?? 'N/A' }}</span></div>
                <div class="text-sm">Designation: <span
                        class="font-medium">{{ $viewingProfile->designation?->name ?? 'N/A' }}</span></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="p-3 bg-zinc-800/40 rounded-lg space-y-1">
                <div class="font-semibold text-zinc-400">Father's Name:</div>
                <div>{{ $viewingProfile->father_name ?? 'N/A' }}</div>
            </div>
            <div class="p-3 bg-zinc-800/40 rounded-lg space-y-1">
                <div class="font-semibold text-zinc-400">Mother's Name:</div>
                <div>{{ $viewingProfile->mother_name ?? 'N/A' }}</div>
            </div>
            <div class="p-3 bg-zinc-800/40 rounded-lg space-y-1">
                <div class="font-semibold text-zinc-400">Highest Education:</div>
                <div>{{ $viewingProfile->education ?? 'N/A' }}</div>
            </div>
            <div class="p-3 bg-zinc-800/40 rounded-lg space-y-1">
                <div class="font-semibold text-zinc-400">Account Status:</div>
                <div>{{ ucfirst($viewingProfile->status ?? 'active') }}</div>
            </div>
        </div>

        <div class="space-y-4 text-sm">
            <div class="p-3 bg-zinc-800/40 rounded-lg space-y-1">
                <div class="font-semibold text-zinc-400">Present Address:</div>
                <div>{{ $viewingProfile->present_address ?? 'N/A' }}</div>
            </div>
            <div class="p-3 bg-zinc-800/40 rounded-lg space-y-1">
                <div class="font-semibold text-zinc-400">Permanent Address:</div>
                <div>{{ $viewingProfile->permanent_address ?? 'N/A' }}</div>
            </div>
            <div class="p-3 bg-zinc-800/40 rounded-lg space-y-1">
                <div class="font-semibold text-zinc-400">Short Bio:</div>
                <div>{{ $viewingProfile->bio ?? 'No bio provided.' }}</div>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <flux:modal.close>
                <flux:button variant="primary">Close</flux:button>
            </flux:modal.close>
        </div>
        @endif
    </flux:modal>

    <!-- Status Edit Modal -->
    <flux:modal name="edit-biodata-modal" class="md:w-96 space-y-6">
        <div>
            <flux:heading size="lg">Update Biodata Status</flux:heading>
            <flux:subheading>Change activation status for this user profile</flux:subheading>
        </div>

        <form wire:submit="updateStatus" class="space-y-4">
            <flux:select wire:model="status" label="Status">
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="inactive">Inactive</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
            </flux:select>

            <flux:select wire:model="designation_id" label="Designation">
                <flux:select.option value="">General Member</flux:select.option>
                @foreach ($designations as $designation)
                    <flux:select.option value="{{ $designation->id }}">{{ $designation->name }} ({{ $designation->order }})</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="priority" type="number" min="1" label="Public Display Priority" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Update Status</flux:button>
            </div>
        </form>
    </flux:modal>
</div>