<?php

use Livewire\Component;
use App\Models\Designation;
use Flux\Flux;

new class extends Component {
    public $designations;
    public $designationId = null;
    public $name = '';
    public $order = 0;
    public $is_active = true;
    public $isEditMode = false;

    public function mount()
    {
        $this->loadDesignations();
    }

    public function loadDesignations()
    {
        $this->designations = Designation::orderBy('order')->get();
    }

    public function openModal()
    {
        $this->resetForm();
        // modals() এর জায়গায় modal() হবে
        Flux::modal('designation-modal')->show(); 
    }

    public function editMode($id)
    {
        $designation = Designation::findOrFail($id);
        $this->designationId = $designation->id;
        $this->name = $designation->name;
        $this->order = $designation->order;
        $this->is_active = $designation->is_active;
        $this->isEditMode = true;
        
        // modals() এর জায়গায় modal() হবে
        Flux::modal('designation-modal')->show();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|unique:designations,name,' . $this->designationId,
            'order' => 'required|integer',
        ]);

        Designation::updateOrCreate(
            ['id' => $this->designationId],
            [
                'name' => $this->name,
                'order' => $this->order,
                'is_active' => $this->is_active,
            ]
        );

        $this->loadDesignations();
        
        // modals() এর জায়গায় modal() হবে
        Flux::modal('designation-modal')->close();
        Flux::toast('Designation saved!', variant: 'success');
    }

    public function resetForm()
    {
        $this->reset(['designationId', 'name', 'order', 'is_active', 'isEditMode']);
    }
}; ?>

<div>
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Manage Designations</flux:heading>
        <flux:button wire:click="openModal" variant="primary">Add Designation</flux:button>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Order Rank</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($designations as $designation)
                <flux:table.row>
                    <flux:table.cell>{{ $designation->name }}</flux:table.cell>
                    <flux:table.cell>{{ $designation->order }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $designation->is_active ? 'green' : 'red' }}">
                            {{ $designation->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="subtle" wire:click="editMode({{ $designation->id }})">Edit
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal -->
    <flux:modal name="designation-modal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <flux:heading>{{ $isEditMode ? 'Edit Designation' : 'Create Designation' }}</flux:heading>

            <flux:input wire:model="name" label="Designation Name" placeholder="e.g. President" required />
            <flux:input type="number" wire:model="order" label="Sort Order" placeholder="0" required />
            <flux:checkbox wire:model="is_active" label="Is Active?" />

            <div class="flex justify-end gap-2">
                <flux:button x-on:click="$flux.modal('designation-modal').close()" variant="subtle">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>