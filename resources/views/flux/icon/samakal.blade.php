{{-- Credit: Font Awesome (https://fontawesome.com) --}}

@props([
    'variant' => 'outline',
])

@php
    if ($variant === 'solid') {
        throw new \Exception('The "solid" variant is not supported in this icon.');
    }

    $classes = Flux::classes('shrink-0')->add(
        match ($variant) {
            'outline' => '',
        },
    );

    $strokeWidth = match ($variant) {
        'outline' => 0,
    };
@endphp





<svg {{ $attributes->class($classes) }} data-flux-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 125 125"
    fill="currentColor" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round"
    stroke-linejoin="round" aria-hidden="true" data-slot="icon">
    <path
        d="m84.4 39.5-.6 2.5H49.6c-18.9 0-34.6.3-34.9.7-1.1 1 1.4 3.3 3.6 3.3 1.1 0 5.4 1.6 9.5 3.6 13.5 6.5 22.4 18 20.8 26.9-.8 4.3-5.9 10.3-10.3 12.1-4.2 1.8-11.8 1.8-17.8-.1-2.7-.8-5.1-1.2-5.4-1-.2.3 1.4 2.2 3.5 4.2 4.7 4.5 12.7 8.6 16.4 8.6 4.2 0 14.9-5.5 21.7-11.1C64.2 82.9 66.4 82 74 82c9.9 0 18.4 4.2 31.9 15.8l5.1 4.4V46l-3.2-2c-3.5-2.1-12.8-5.3-19-6.4-3.4-.6-3.8-.5-4.4 1.9M84 59c0 11.1-.2 12.9-1.5 12.4-.8-.4-3.9-.9-6.8-1.3l-5.2-.6-.6-5.5c-.6-5.7-3.6-12.6-6.3-14.9-.9-.8-1.6-1.7-1.6-2.2s5-.9 11-.9h11z" />
</svg>
