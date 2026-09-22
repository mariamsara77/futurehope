@props([
    'variant' => 'default', // default, segmented, pills
    'size' => 'md',        // md, sm
    'scrollable' => false,
    'fade' => false,
])

@php
$classes = \Illuminate\Support\Arr::toCssClasses([
    'flex items-center',
    'gap-1 p-1' => $variant === 'segmented' || $variant === 'pills',
    'bg-zinc-100/50 dark:bg-zinc-800/50' => $variant === 'segmented' || $variant === 'pills',
    'rounded-lg' => $variant === 'segmented' && $size === 'md',
    'rounded-md' => $variant === 'segmented' && $size === 'sm',
    'rounded-full' => $variant === 'pills',
    'overflow-x-auto no-scrollbar' => $scrollable,
    'relative' => $fade,
]);

// Fade effect logic
$fadeClasses = $fade ? 'after:absolute after:right-0 after:top-0 after:bottom-0 after:w-12 after:bg-gradient-to-l after:from-white dark:after:from-zinc-900 after:pointer-events-none' : '';
@endphp

<div {{ $attributes->class([$classes, $fadeClasses]) }} @if($scrollable) style="scrollbar-width: none;" @endif>
    {{ $slot }}
</div>
