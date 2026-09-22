<?php

use App\Enums\IndexingStatus;
use App\Jobs\ImportUrlsFromCsvJob;
use App\Jobs\IndexUrlJob;
use App\Models\IndexedUrl;
use App\Services\GoogleIndexingService;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithFileUploads, WithPagination;

    #[Validate('required|url|max:2048')]
    public string $url = ''; // ৫MB পর্যন্ত

    #[Validate('required|file|mimes:csv,txt|max:5120')]
    public $csvFile = null;

    #[Url(history: true)]
    public string $search = '';

    public ?int $editingUrlId = null;

    #[Validate('required|url|max:2048')]
    public string $editUrlValue = '';

    public bool $isModalOpen = false;

    #[Url(history: true)]
    public string $filterStatus = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function urls()
    {
        return IndexedUrl::query()->search($this->search)->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))->latest()->paginate(10);
    }

    #[Computed]
    public function remainingQuota(): int
    {
        return app(GoogleIndexingService::class)->remainingQuota();
    }

    // 1. এক-ক্লিকে ইনডেক্সিং (রেট-লিমিটেড, ডুপ্লিকেট-চেকড, নন-ব্লকিং কিউ জব)
    public function submitForIndexing(): void
    {
        $this->validate(['url' => 'required|url|max:2048']);

        // ভিজিটরের ম্যানুয়াল স্প্যাম-সাবমিট আটকাতে (Google কোটার থ্রটলিং IndexUrlJob middleware-এ,
        // এখানকার লিমিটার শুধু ফর্ম স্প্যাম আটকাচ্ছে)
        $throttleKey = 'submit-indexing:' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, maxAttempts: 20)) {
            Flux::toast('একটু ধীরে! কিছুক্ষণ পর আবার চেষ্টা করুন।', variant: 'warning');

            return;
        }
        RateLimiter::hit($throttleKey, decaySeconds: 60);

        if (!app(GoogleIndexingService::class)->isConfigured()) {
            Flux::toast('গুগল এপিআই ক্রেডেনশিয়াল সেটআপ করা নেই।', variant: 'danger');

            return;
        }

        try {
            $record = IndexedUrl::firstOrCreate(['url' => $this->url], ['source' => 'manual', 'status' => IndexingStatus::Pending]);

            if ($record->wasRecentlyCreated === false && $record->status === IndexingStatus::Success) {
                Flux::toast('এই URL টি আগেই সফলভাবে ইনডেক্স করা হয়েছে। আবার পুশ করা হচ্ছে...', variant: 'info');
            }

            $record->update(['status' => IndexingStatus::Queued, 'source' => $record->source ?: 'manual']);

            IndexUrlJob::dispatch($record->id);

            Flux::toast('ইনডেক্সিং কিউতে যোগ হয়েছে, শীঘ্রই প্রসেস হবে।', variant: 'success');
            $this->reset('url');
            unset($this->urls);
        } catch (\Throwable $e) {
            Log::error('submitForIndexing: ব্যর্থ হয়েছে।', ['url' => $this->url, 'error' => $e->getMessage()]);
            Flux::toast('কিছু একটা সমস্যা হয়েছে, আবার চেষ্টা করুন।', variant: 'danger');
        }
    }

    // 2. CSV Import — বড় ফাইলও ব্লক না করে ব্যাকগ্রাউন্ড জবে প্রসেস হয়
    public function importCsv(): void
    {
        $this->validate(['csvFile' => 'required|file|mimes:csv,txt|max:5120']);

        try {
            $storedPath = $this->csvFile->store('csv-imports');

            ImportUrlsFromCsvJob::dispatch($storedPath);

            Flux::toast('CSV আপলোড হয়েছে, ব্যাকগ্রাউন্ডে ইমপোর্ট শুরু হয়েছে।', variant: 'success');
            $this->reset('csvFile');
        } catch (\Throwable $e) {
            Log::error('importCsv: ব্যর্থ হয়েছে।', ['error' => $e->getMessage()]);
            Flux::toast('CSV আপলোড করতে সমস্যা হয়েছে, আবার চেষ্টা করুন।', variant: 'danger');
        }
    }

    // 3. Edit
    public function edit(int $indexedUrlId): void
    {
        $record = IndexedUrl::findOrFail($indexedUrlId);

        $this->editingUrlId = $record->id;
        $this->editUrlValue = $record->url;
        $this->isModalOpen = true;
    }

    public function update(): void
    {
        $this->validate(['editUrlValue' => 'required|url|max:2048']);

        $record = IndexedUrl::findOrFail($this->editingUrlId);

        $duplicate = IndexedUrl::where('url', $this->editUrlValue)->where('id', '!=', $record->id)->exists();

        if ($duplicate) {
            $this->addError('editUrlValue', 'এই URL টি ইতিমধ্যে তালিকায় আছে।');

            return;
        }

        $record->update(['url' => $this->editUrlValue]);

        $this->isModalOpen = false;
        $this->editingUrlId = null;
        unset($this->urls);

        Flux::toast('URL আপডেট হয়েছে।', variant: 'success');
    }

    // 4. Delete — ফ্রন্টএন্ডে wire:confirm দিয়ে কনফার্মেশন নেওয়া হয়
    public function delete(int $indexedUrlId): void
    {
        IndexedUrl::findOrFail($indexedUrlId)->delete();

        unset($this->urls);
        Flux::toast('URL মুছে ফেলা হয়েছে।', variant: 'success');
    }

    public function retry(int $indexedUrlId): void
    {
        $record = IndexedUrl::findOrFail($indexedUrlId);

        if (!app(GoogleIndexingService::class)->isConfigured()) {
            Flux::toast('গুগল এপিআই ক্রেডেনশিয়াল সেটআপ করা নেই।', variant: 'danger');
            return;
        }

        if ($record->status === IndexingStatus::Success) {
            Flux::toast('এই URL ইতিমধ্যে সফলভাবে ইনডেক্স করা আছে।', variant: 'info');
            return;
        }

        $record->update([
            'status' => IndexingStatus::Queued,
            'error_message' => null,
        ]);

        IndexUrlJob::dispatch($record->id);

        unset($this->urls);
        Flux::toast('রিট্রাই কিউতে যোগ হয়েছে।', variant: 'success');
    }
}; ?>

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Google Indexing ম্যানেজার</h1>
        <flux:badge :color="$this->remainingQuota > 20 ? 'lime' : 'amber'">
            আজকের বাকি কোটা: {{ $this->remainingQuota }} / {{ config('services.google.indexing_daily_quota', 200) }}
        </flux:badge>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <flux:card>
            <h2 class="font-bold mb-4">Quick Indexing</h2>
            <form wire:submit="submitForIndexing" class="flex gap-4">
                <flux:input wire:model="url" placeholder="https://..." />
                <flux:button type="submit" variant="primary" icon="bolt" wire:loading.attr="disabled"
                    wire:target="submitForIndexing" />
            </form>
            @error('url')
                <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
            @enderror
        </flux:card>

        <flux:card>
            <h2 class="font-bold mb-4">CSV Import</h2>
            <form wire:submit="importCsv" class="flex gap-4">
                <flux:input type="file" wire:model="csvFile" accept=".csv,.txt" />
                <flux:button type="submit" icon="document-arrow-up" wire:loading.attr="disabled"
                    wire:target="csvFile,importCsv" />
            </form>
            @error('csvFile')
                <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
            @enderror
            <flux:text class="text-xs text-zinc-500 mt-2">CSV-এর প্রথম কলামে URL থাকতে হবে। সর্বোচ্চ 5MB।</flux:text>
        </flux:card>
    </div>

    <div>
        <div class="flex gap-4 mb-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search URLs..." icon="magnifying-glass" />

            <flux:select wire:model.live="filterStatus" placeholder="সব স্ট্যাটাস">
                <flux:select.option value="">সবগুলো</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
                <flux:select.option value="queued">Queued</flux:select.option>
                <flux:select.option value="success">Success</flux:select.option>
                <flux:select.option value="failed">Failed</flux:select.option>
                <flux:select.option value="quota_exceeded">Quota Exceeded</flux:select.option>
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>URL</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Last Crawled</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($this->urls as $item)
                    <flux:table.row wire:key="url-{{ $item->id }}">
                        <flux:table.cell class="max-w-xs truncate" title="{{ $item->url }}">{{ $item->url }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$item->status->color()">{{ $item->status->label() }}
                            </flux:badge>
                            @if ($item->status === \App\Enums\IndexingStatus::Failed && $item->error_message)
                                <flux:tooltip content="{{ $item->error_message }}">
                                    <flux:icon.information-circle class="inline size-4 text-zinc-400" />
                                </flux:tooltip>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $item->last_crawled?->format('d M, Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell align="end" class="space-x-1">
                            @if (in_array($item->status, [
                                    \App\Enums\IndexingStatus::Failed,
                                    \App\Enums\IndexingStatus::QuotaExceeded,
                                    \App\Enums\IndexingStatus::Pending,
                                ]))
                                <flux:button wire:click="retry({{ $item->id }})" variant="subtle" icon="arrow-path"
                                    size="sm" title="Retry indexing" wire:loading.attr="disabled"
                                    wire:target="retry" />
                            @endif

                            <flux:button wire:click="edit({{ $item->id }})" variant="subtle" icon="pencil"
                                size="sm" />
                            <flux:button wire:click="delete({{ $item->id }})"
                                wire:confirm="আপনি কি নিশ্চিত এই URL টি মুছে ফেলতে চান?" variant="danger" icon="trash"
                                size="sm" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center text-zinc-500 py-8">
                            কোনো URL পাওয়া যায়নি।
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        <div class="mt-4">{{ $this->urls->links() }}</div>
    </div>

    <flux:modal wire:model="isModalOpen">
        <div class="p-6 space-y-4">
            <h3 class="font-bold">Edit URL</h3>
            <flux:input wire:model="editUrlValue" />
            @error('editUrlValue')
                <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
            @enderror
            <flux:button wire:click="update" variant="primary" class="w-full" wire:loading.attr="disabled"
                wire:target="update">
                Update
            </flux:button>
        </div>
    </flux:modal>
</div>
