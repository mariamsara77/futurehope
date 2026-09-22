@props([
    'value' => null,
    'label' => null,
])

@php
    // label না থাকলে slot-কে label হিসেবে ধরা হবে
    $displayLabel = $label ?? $slot;
    $stringValue = (string) $value;

    $classes = Flux::classes()
        ->add('group relative flex items-center gap-2 px-2 py-1.5 rounded-md cursor-pointer select-none transition-colors')
        ->add('text-zinc-700 dark:text-zinc-300 text-sm')
        // হোভার এবং সিলেক্টেড স্টেট
        ->add('hover:bg-zinc-100 dark:hover:bg-white/5')
        ->add('data-[selected=true]:bg-zinc-50 dark:data-[selected=true]:bg-white/10 data-[selected=true]:font-medium');
@endphp

<div 
    role="option" 
    x-show="!search || $el.innerText.toLowerCase().includes(search.toLowerCase())"
    @click="select('{{ $stringValue }}', '{{ $displayLabel }}')"
    data-value="{{ $stringValue }}" 
    data-label="{{ $displayLabel }}"
    :data-selected="String(value) === '{{ $stringValue }}'"
    {{ $attributes->class($classes) }}
>
    {{-- Check Icon --}}
    <div class="flex items-center justify-center size-4 shrink-0">
        <span x-show="String(value) === '{{ $stringValue }}'" x-cloak>
            <flux:icon icon="check" variant="micro" class="size-4 text-zinc-800 dark:text-white" />
        </span>
    </div>

    {{-- Label with Truncate --}}
    <span class="block truncate flex-1">
        {{ $displayLabel }}
    </span>
</div>