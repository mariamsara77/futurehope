@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'placeholder' => null,
    'invalid' => null,
    'size' => null,
])

@php
    $invalid ??= $name && $errors->has($name);

    $classes = Flux::classes()
        ->add('w-full ps-3 pe-10 text-left transition-all duration-100 focus:outline-none')
        ->add(
            match ($size) {
                'sm' => 'h-8 py-1 text-sm rounded-md',
                'xs' => 'h-6 py-0.5 text-xs rounded-md',
                default => 'h-10 py-2 text-base sm:text-sm rounded-lg',
            },
        )
        ->add('bg-white dark:bg-white/10 text-zinc-800 dark:text-zinc-200 shadow-sm border')
        ->add(
            $invalid
                ? 'border-red-500 ring-1 ring-red-500'
                : 'border-zinc-300 dark:border-white/15 border-b-zinc-300/80 focus:ring-2 focus:ring-zinc-500/20 focus:border-zinc-500',
        );
@endphp

<div x-data="{
    open: false,
    value: @entangle($attributes->wire('model')),
    search: '',
    filter: true,

    init() {
        this.syncSearchFromValue();
        this.$watch('value', () => this.syncSearchFromValue());
    },

    syncSearchFromValue() {
        this.$nextTick(() => {
            const option = this.$refs.optionsContainer?.querySelector(`[data-value='${String(this.value)}']`);
            if (option) this.search = option.getAttribute('data-label') || option.innerText.trim();
        });
    },

    select(val, lab) {
        this.value = val;
        this.search = lab;
        this.open = false;
    }
}" @click.outside="open = false" class="relative w-full">

    <div class="relative group">
        <input type="text" x-model="search" x-ref="input" @focus="open = true" @input="open = true"
            @keydown.escape="open = false" placeholder="{{ $placeholder }}" {{ $attributes->class($classes) }} />

        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
            <svg class="size-4 text-zinc-400 opacity-50" fill="none" viewBox="0 0 20 20" stroke="currentColor"
                stroke-width="1.5">
                <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="open" x-ref="optionsContainer" x-anchor.bottom-start.offset.4="$refs.input"
            x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-y-1"
            class="fixed z-[9999] max-h-64 overflow-auto rounded-xl border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-900 shadow-xl p-1"
            :style="{ minWidth: $refs.input.offsetWidth + 'px' }" style="display: none;">

            <div class="space-y-0.5">
                {{ $slot }}

                {{-- The No-Data/Empty State logic --}}
                <div x-show="search && $el.parentElement.querySelectorAll('[role=option]:not([style*=\'display: none\'])').length === 0"
                    class="p-2 text-center flex flex-col items-center">
                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">No industries found</span>
                    {{-- <span class="text-xs text-zinc-500 mt-1">Try searching for something else.</span> --}}
                </div>
            </div>
        </div>
    </template>
</div>
