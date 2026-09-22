<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

new class extends Component {
    use WithFileUploads;

    public $phone = '';
    public $father_name = '';
    public $mother_name = '';
    public $present_address = '';
    public $permanent_address = '';
    public $education = '';
    public $blood_group = '';
    public $bio = '';
    public $image;
    
    public $existingAvatarUrl = null;

    public function mount()
    {
        $user = Auth::user();
        $profile = $user->profile;

        if ($profile) {
            $this->phone = $profile->phone ?? '';
            $this->father_name = $profile->father_name ?? '';
            $this->mother_name = $profile->mother_name ?? '';
            $this->present_address = $profile->present_address ?? '';
            $this->permanent_address = $profile->permanent_address ?? '';
            $this->education = $profile->education ?? '';
            $this->blood_group = $profile->blood_group ?? '';
            $this->bio = $profile->bio ?? '';
            
            if ($profile->hasMedia('avatar')) {
                $this->existingAvatarUrl = $profile->getFirstMediaUrl('avatar');
            }
        }
    }

    public function saveProfile()
    {
        // ফাইল সঠিকভাবে আপডেট হয়েছে কিনা তা নিশ্চিত করে ভ্যালিডেশন
        $this->validate([
            'phone' => 'nullable|string|max:20',
            'image' => 'nullable|image|max:2048', // Max 2MB
        ]);

        $user = Auth::user();
        
        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $this->phone,
                'father_name' => $this->father_name,
                'mother_name' => $this->mother_name,
                'present_address' => $this->present_address,
                'permanent_address' => $this->permanent_address,
                'education' => $this->education,
                'blood_group' => $this->blood_group,
                'bio' => $this->bio,
            ]
        );

        // টেম্পোরারি ফাইল চেক করে নিরাপদে ফাইল সেভ করা
        if ($this->image && $this->image->exists()) {
            try {
                $profile->clearMediaCollection('avatar');
                $profile->addMedia($this->image->getRealPath())
                        ->usingFileName($this->image->getClientOriginalName())
                        ->toMediaCollection('avatar');

                $this->existingAvatarUrl = $profile->getFirstMediaUrl('avatar');
                $this->reset('image'); // ফাইল আপলোড শেষে টেম্পোরারি প্রপার্টি রিসেট
            } catch (\Exception $e) {
                Flux::toast('Image upload failed, but profile data was saved.', variant: 'warning');
            }
        }

        Flux::toast('Profile updated successfully!', variant: 'success');
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">My Biodata</flux:heading>

    <form wire:submit="saveProfile" class="space-y-6">
        <flux:card>
            <div class="flex items-center gap-6 mb-6">
                @php
                $avatarSrc = $existingAvatarUrl ?? asset('default-avatar.png');
                if ($image) {
                try {
                $avatarSrc = $image->temporaryUrl();
                } catch (\Exception $e) {
                // temporaryUrl মেথড ফেইল করলে ফলব্যাক ইমেজ দেখাবে
                $avatarSrc = $existingAvatarUrl ?? asset('default-avatar.png');
                }
                }
                @endphp

                <flux:avatar src="{{ $avatarSrc }}" size="xl" />
                <flux:input type="file" wire:model="image" label="Profile Photo" accept="image/*" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input wire:model="phone" label="Phone Number" placeholder="+8801..." />
                <flux:select wire:model="blood_group" label="Blood Group">
                    <flux:select.option value="">Select...</flux:select.option>
                    <flux:select.option value="A+">A+</flux:select.option>
                    <flux:select.option value="A-">A-</flux:select.option>
                    <flux:select.option value="B+">B+</flux:select.option>
                    <flux:select.option value="B-">B-</flux:select.option>
                    <flux:select.option value="O+">O+</flux:select.option>
                    <flux:select.option value="O-">O-</flux:select.option>
                    <flux:select.option value="AB+">AB+</flux:select.option>
                    <flux:select.option value="AB-">AB-</flux:select.option>
                </flux:select>

                <flux:input wire:model="father_name" label="Father's Name" />
                <flux:input wire:model="mother_name" label="Mother's Name" />
                <flux:input wire:model="education" label="Highest Education" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <flux:textarea wire:model="present_address" label="Present Address" rows="3" />
                <flux:textarea wire:model="permanent_address" label="Permanent Address" rows="3" />
            </div>

            <div class="mt-6">
                <flux:textarea wire:model="bio" label="Short Bio" rows="4"
                    placeholder="Write something about yourself..." />
            </div>
        </flux:card>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save Changes</flux:button>
        </div>
    </form>
</div>