<?php

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public ?ContactMessage $selected = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }

    public function viewMessage(int $id): void
    {
        $this->selected = ContactMessage::findOrFail($id);
        Flux\Flux::modal('contact-message-view')->show();
    }

    public function retryMessage(int $id): void
    {
        $message = ContactMessage::findOrFail($id);

        if ($message->mail_status === 'sent') {
            Flux\Flux::toast('এই বার্তাটি ইতিমধ্যে পাঠানো হয়েছে।', variant: 'success');
            return;
        }

        try {
            $recipient = config('mail.contact_to');

            if (!$recipient || (app()->isProduction() && config('mail.default') === 'log')) {
                throw new \RuntimeException('Production mail delivery is not configured.');
            }

            $message->forceFill([
                'mail_status' => 'pending',
                'mail_error' => null,
            ])->save();

            Mail::to($recipient)->send(new ContactMessageMail($message));

            $message->forceFill([
                'mail_status' => 'sent',
                'mail_error' => null,
            ])->save();

            Flux\Flux::toast('বার্তাটি আবার সফলভাবে পাঠানো হয়েছে।', variant: 'success');
        } catch (\Throwable $e) {
            report($e);

            $message->forceFill([
                'mail_status' => 'failed',
                'mail_error' => $e->getMessage(),
            ])->save();

            Flux\Flux::toast('ইমেইল পাঠানো যায়নি। Mail configuration পরীক্ষা করুন।', variant: 'danger');
        }
    }

    public function with(): array
    {
        return [
            'messages' => ContactMessage::query()
                ->when($this->status !== 'all', fn ($q) => $q->where('mail_status', $this->status))
                ->when($this->search, fn ($q) => $q->where(function ($inner) {
                    $inner->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('subject', 'like', '%' . $this->search . '%');
                }))
                ->latest()->paginate(15),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <flux:heading size="xl">Contact Messages</flux:heading>
            <flux:subheading>Manage messages received from the public contact forms.</flux:subheading>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name, email or subject" icon="magnifying-glass" />
            <flux:select wire:model.live="status">
                <flux:select.option value="all">All mail statuses</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
                <flux:select.option value="sent">Sent</flux:select.option>
                <flux:select.option value="failed">Failed</flux:select.option>
            </flux:select>
        </div>
    </div>

    <flux:card class="overflow-hidden p-0">
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Subject</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column align="end">Action</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($messages as $message)
                        <flux:table.row :key="$message->id">
                            <flux:table.cell>
                                <div class="font-medium">{{ $message->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $message->email }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $message->subject }}</flux:table.cell>
                            <flux:table.cell><flux:badge>{{ ucfirst($message->mail_status) }}</flux:badge></flux:table.cell>
                            <flux:table.cell>{{ $message->created_at?->format('d M Y, h:i A') }}</flux:table.cell>
                            <flux:table.cell align="end"><div class="flex justify-end gap-2"><flux:button size="sm" wire:click="viewMessage({{ $message->id }})">View</flux:button>@if($message->mail_status !== "sent")<flux:button size="sm" variant="subtle" wire:click="retryMessage({{ $message->id }})">Resend</flux:button>@endif</div></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="5" class="py-8 text-center text-zinc-500">No contact messages found.</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    {{ $messages->links() }}

    <flux:modal name="contact-message-view" class="md:w-[720px]">
        @if($selected)
            <div class="space-y-5">
                <div><flux:heading size="lg">{{ $selected->subject }}</flux:heading><flux:subheading>{{ $selected->name }} · {{ $selected->email }}</flux:subheading></div>
                @if($selected->phone)<div><flux:text>{{ $selected->phone }}</flux:text></div>@endif
                <div class="whitespace-pre-line rounded-xl border border-zinc-200 bg-zinc-50 p-5 leading-7 dark:border-zinc-700 dark:bg-zinc-900">{{ $selected->message }}</div>
                @if($selected->mail_error)<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">Mail delivery error is recorded for this message.</div>@endif
                <div class="flex justify-end"><flux:modal.close><flux:button>Close</flux:button></flux:modal.close></div>
            </div>
        @endif
    </flux:modal>
</div>