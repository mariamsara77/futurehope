@props([
    'name' => null,
    'icon' => null,
    'action' => false,
])

@php
$classes = \Illuminate\Support\Arr::toCssClasses([
    'flex items-center justify-center gap-2 whitespace-nowrap px-3 py-1.5 text-sm font-medium transition-all cursor-pointer',
    // Default style
    'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white border-b-2 border-transparent' => ! $action,
    // Segmented/Pills active state logic handle via Alpine or Livewire
    'rounded-md' => ! $action,
]);
@endphp

<button
    type="button"
    {{ $attributes->class($classes) }}
    @if($name) x-on:click="$wire.set('tab', '{{ $name }}')" @endif
>
    @if ($icon)
        <flux:icon :icon="$icon" class="size-4 opacity-70" />
    @endif

    {{ $slot }}
</button>
