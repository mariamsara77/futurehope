@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'placeholder' => 'Select...',
    'invalid' => null,
    'size' => null,
    'searchable' => false,
])

@php
$invalid ??= $name && $errors->has($name);

$classes = Flux::classes()
    ->add('relative flex items-center justify-between w-full ps-3 pe-2 text-left cursor-default transition-all duration-200 focus:outline-none border')
    ->add($invalid 
        ? 'bg-red-50 border-red-500 text-red-900' 
        : 'bg-zinc-400/10 border-transparent focus:border-zinc-500')
    ->add(match ($size) {
        'sm' => 'h-8 py-1 text-sm rounded-md',
        'xs' => 'h-6 py-0.5 text-xs rounded-md',
        default => 'h-10 py-2 text-base sm:text-sm rounded-lg',
    });
@endphp

<div x-data="{
    open: false,
    value: @if(isset($__livewire)) @entangle($attributes->wire('model')) @else {{ $attributes->get('x-model') ?? 'null' }} @endif,
    label: '',
    search: '',
    buttonWidth: 0,

    init() {
        this.syncLabel();
        this.$watch('value', () => this.syncLabel());
        
        // বাটন এবং ড্রপডাউনকে সমান রাখার জন্য উইডথ ক্যালকুলেশন
        this.$nextTick(() => { this.refreshWidth() });
        window.addEventListener('resize', () => this.refreshWidth());
    },

    refreshWidth() {
        // বাটনের উইডথ আপডেট করা
        this.buttonWidth = this.$el.offsetWidth;
    },

    syncLabel() {
        this.$nextTick(() => {
            const option = this.$refs.optionsContainer?.querySelector(`[data-value='${String(this.value)}']`);
            this.label = option ? (option.getAttribute('data-label') || option.innerText.trim()) : '';
        });
    },

    select(val, lab) {
        this.value = val;
        this.label = lab;
        this.open = false;
        this.search = '';
    }
}" 
@click.outside="open = false" 
@keydown.escape="open = false" 
{{-- w-fit নিশ্চিত করে যে এটি কন্টেন্টের সমান চওড়া হবে, কিন্তু খুব ছোট হবে না --}}
class="relative inline-block w-full min-w-[150px]">

    {{-- বাটন বা ট্রিগার --}}
    <button type="button" x-ref="trigger"
        @click="open = !open; refreshWidth(); if(open) { $nextTick(() => $refs.searchInput?.focus()) }"
        {{ $attributes->class($classes) }} 
        role="combobox" 
        :aria-expanded="open">

        <div class="flex items-center w-full truncate">
            {{-- Invisible Ghost Text: এটি সব অপশনের মধ্যে সবচেয়ে চওড়া জায়গা দখল করে রাখে --}}
            <div class="invisible w-0 h-0 overflow-hidden pr-6" aria-hidden="true">
                {{-- এখানে একটি বড় স্যাম্পল টেক্সট দিন অথবা আপনার লুপের সবচেয়ে বড় ডাটাটি --}}
                <slot name="ghost">Longest Option Text Placeholder</slot>
            </div>
            
            <span class="block truncate" x-text="label || '{{ $placeholder }}'"
                :class="!label ? 'text-zinc-500' : 'text-zinc-800 dark:text-zinc-200'"></span>
        </div>
        
        <flux:icon.chevron-up-down class="size-4 text-zinc-400 shrink-0 ml-2" />
    </button>

    {{-- ড্রপডাউন মেনু --}}
    <template x-teleport="body">
        <div x-show="open" 
            x-ref="optionsContainer" 
            x-anchor.bottom-start.offset.4="$refs.trigger"
            x-transition:enter="transition ease-out duration-100" 
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="fixed z-[9999] overflow-hidden rounded-xl border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-800 shadow-2xl"
            {{-- বাটন এবং ড্রপডাউনকে হুবহু সমান উইডথ দিচ্ছে --}}
            :style="{ width: buttonWidth + 'px' }" 
            x-cloak>

            @if ($searchable)
                <div class="p-2 border-b border-zinc-100 dark:border-white/5 bg-white dark:bg-zinc-800 sticky top-0">
                    <input x-model="search" x-ref="searchInput" @click.stop type="text" placeholder="Search..."
                        class="w-full px-3 py-1.5 text-sm bg-zinc-50 dark:bg-white/5 border border-zinc-200 dark:border-white/10 rounded-md focus:ring-1 focus:ring-zinc-400 focus:outline-none text-zinc-800 dark:text-zinc-200">
                </div>
            @endif

            <div class="max-h-[250px] p-1 overflow-y-auto space-y-0.5 custom-scrollbar">
                {{ $slot }}

                {{-- No Results --}}
                <div x-show="search && $el.querySelectorAll('[role=option]:not([style*=\'display: none\'])').length === 0"
                    class="px-3 py-4 text-center text-sm text-zinc-500">
                    No results found
                </div>
            </div>
        </div>
    </template>
</div>