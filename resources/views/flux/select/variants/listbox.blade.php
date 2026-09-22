@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'placeholder' => null,
    'invalid' => null,
    'size' => null,
    'searchable' => false,
])

@php
$invalid ??= $name && $errors->has($name);
// প্রতিটি সিলেক্টের জন্য একটি ইউনিক আইডি জেনারেট করা হচ্ছে
$id = 'flux-select-' . uniqid();

$classes = Flux::classes()
    ->add(
        'relative flex items-center justify-between w-full ps-3 pe-2 text-left cursor-default transition duration-75 focus:outline-none',
    )
    ->add('bg-zinc-400/10')
    ->add('data-invalid:outline-2 data-invalid:outline-red-600')
    ->add(
        match ($size) {
            'sm' => 'h-8 py-1 text-sm rounded-md',
            'xs' => 'h-6 py-0.5 text-xs rounded-md',
            default => 'h-10 py-2 text-base sm:text-sm rounded-lg',
        },
    );
// ->add('bg-white dark:bg-white/10 text-zinc-800 dark:text-zinc-200 shadow-sm ');
// ->add(
//     $invalid
//         ? 'border border-red-500 ring-1 ring-red-500'
//         : 'border border-zinc-300 dark:border-white/15 border-b-zinc-300/80 focus:ring-2 focus:ring-zinc-500/20 focus:border-zinc-500',
// );
@endphp

<div x-data="{
    open: false,
    {{-- যদি Livewire থাকে তবে entangle করবে, নাহলে x-model এর value নিবে --}}
    value: @if(isset($__livewire)) @entangle($attributes->wire('model')) @else {{ $attributes->get('x-model') ?? 'null' }} @endif,
    label: '',
    search: '',
    buttonWidth: 0,

    init() {
        {{-- সাধারণ ব্লেড ফাইলের x-model এর সাথে সিঙ্ক করার জন্য --}}
        @if(!isset($__livewire) && $attributes->has('x-model'))
            this.$watch('currentLang', (val) => { this.value = val; });
        @endif

        this.syncLabel();
        this.$watch('value', () => this.syncLabel());
        this.$nextTick(() => { this.buttonWidth = this.$refs.trigger.offsetWidth });
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
}" @click.outside="open = false" @keydown.escape="open = false" class="relative min-w-40">

    {{-- বাটন বা ট্রিগার --}}
    <button type="button" x-ref="trigger"
        @click="open = !open; if(open) { buttonWidth = $el.offsetWidth; $nextTick(() => $refs.searchInput?.focus()) }"
        {{ $attributes->class($classes) }} role="combobox" :aria-expanded="open">

        <span class="block truncate" x-text="label || '{{ $placeholder }}'"
            :class="!label ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-800 dark:text-zinc-200'"></span>
        <flux:icon.chevron-up-down class="size-4 text-zinc-400 dark:text-zinc-500 ml-2" />
    </button>

    {{-- ড্রপডাউন মেনু --}}
    <template x-teleport="body">
        <div x-show="open" x-ref="optionsContainer" x-anchor.bottom-start.offset.4="$refs.trigger"
            x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="fixed z-9999 max-h-80 overflow-y-auto rounded-xl border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-700 shadow-xl"
           :style="{ width: buttonWidth + 'px' }">
            <div class="space-y-0.5 p-1.5">
                 @if ($searchable)           
                    <flux:input x-model="search" x-ref="searchInput" @click.stop size="sm" icon="magnifying-glass" placeholder="Filter..." />
                @endif
                {{ $slot }}

                {{-- No Results --}}
                <div x-show="search && $el.parentElement.querySelectorAll('[role=option]:not([style*=\'display: none\'])').length === 0"
                    class="px-3 py-4 text-center text-sm text-zinc-500">
                    No results found
                </div>
            </div>
        </div>
    </template>
</div>
