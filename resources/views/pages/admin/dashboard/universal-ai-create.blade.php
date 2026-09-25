<?php

use Livewire\Component;
use App\Ai\Agents\UniversalDbAgent;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Facades\Ai;
use Livewire\Attributes\Layout;

new  class extends Component {
    public string $tableName = '';
    public string $userInput = '';
    public array $availableTables = [];
    public bool $loading = false;

    public function mount()
    {
        // অপ্রয়োজনীয় টেবিল বাদ দিয়ে লিস্ট তৈরি
        $exclude = ['migrations', 'failed_jobs', 'sessions', 'cache', 'job_batches', 'notifications', 'personal_access_tokens', 'telescope_entries', 'pulse_entries', 'activity_log', 'media'];

        $this->availableTables = collect(Schema::getTables())->pluck('name')->reject(fn($name) => in_array($name, $exclude))->values()->toArray();

        if (!empty($this->availableTables)) {
            $this->tableName = $this->availableTables[0];
        }
    }

    public function executeAiAction()
    {
        if (empty($this->userInput)) {
            $this->js("Flux.toast({ variant: 'warning', text: 'অনুগ্রহ করে কিছু লিখুন।' })");
            return;
        }

        $this->loading = true;

        try {
            $agent = new UniversalDbAgent($this->tableName);

            // AI Prompt
            $response = Ai::provider('gemini')->prompt($agent, $this->userInput);

            // JSON ক্লিনআপ (জেমিনি অনেক সময় ব্যাকটিকস দেয়)
            $content = (string) $response;
            $cleanJson = preg_replace('/^```json|```$/m', '', $content);
            $result = json_decode(trim($cleanJson), true);

            if (isset($result['data'])) {
                $this->saveData($result['data']);

                $this->js("Flux.toast({ variant: 'success', heading: 'সফল!', text: 'ডেটা সেভ করা হয়েছে।' })");
                $this->userInput = '';
            } else {
                throw new \Exception('AI could not parse the data correctly.');
            }
        } catch (\Throwable $e) {
            \Log::error('AI Agent Error: ' . $e->getMessage());
            $this->js("Flux.toast({ variant: 'danger', heading: 'ব্যর্থ!', text: 'AI ডেটা প্রসেস করতে পারেনি।' })");
        }

        $this->loading = false;
    }

    protected function saveData(array $data)
    {
        $modelClass = 'App\\Models\\' . Str::studly(Str::singular($this->tableName));

        DB::transaction(function () use ($modelClass, $data) {
            if (class_exists($modelClass)) {
                // মডেলে ফিল্ডগুলো অবশ্যই $fillable থাকতে হবে
                $modelClass::create($data);
            } else {
                DB::table($this->tableName)->insert(
                    array_merge($data, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]),
                );
            }
        });
    }
}; ?>

<div class="max-w-3xl mx-auto p-8">
    <flux:card variant="subtle" class="space-y-6 shadow-xl border-t-4 border-primary">
        <header class="flex items-center gap-4">
            <div class="p-2 bg-primary/10 rounded-lg">
                <flux:icon.sparkles class="size-6" />
            </div>
            <div>
                <flux:heading size="xl">Universal AI Data Entry</flux:heading>
                <flux:subheading>আপনার ন্যাচারাল ল্যাঙ্গুয়েজ কমান্ডকে সরাসরি ডাটাবেস এন্ট্রিতে রূপান্তর করুন।
                </flux:subheading>
            </div>
        </header>

        <flux:separator variant="faint" />

        <div class="grid gap-6">
            <flux:select wire:model="tableName" label="টার্গেট টেবিল সিলেক্ট করুন" variant="listbox" searchable>
                @foreach ($availableTables as $table)
                    <flux:select.option :value="$table">{{ Str::headline($table) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="userInput" label="ডেটা এন্ট্রি কমান্ড (বাংলা বা ইংলিশ)"
                placeholder="উদা: 'নতুন ইউজার মারিয়াম, ইমেইল mariam@example.com, সে একজন ডেভেলপার'..." rows="6"
                class="bg-zinc-50 dark:bg-zinc-900" />

            <div class="flex items-center justify-between gap-4">
                <p class="text-xs text-zinc-500 italic">নির্দেশনা: AI স্বয়ংক্রিয়ভাবে কলামগুলো চিনে নেবে।</p>
                <flux:button wire:click="executeAiAction" variant="primary" icon="sparkles" class="px-8">
                    AI দিয়ে ইনসার্ট করুন
                </flux:button>
            </div>
        </div>
    </flux:card>
</div>
