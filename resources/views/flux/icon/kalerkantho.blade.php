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





<svg {{ $attributes->class($classes) }} data-flux-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82 67"
    fill="currentColor" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round"
    stroke-linejoin="round" aria-hidden="true" data-slot="icon">
    <path
        d="M14.4 16.1c-1.3 2.2 1.4 2.8 12.6 3.1l11.5.3-11 4.4c-6 2.4-11.6 4.8-12.2 5.3-1.7 1.3-1.7 8.3 0 9.6.6.5 5.7 2.9 11.2 5.2 11.9 5 14.8 6.7 14.2 8.4-1.1 2.9.7 3.4 7.3 2.2l6.5-1.1.5-15c.5-14.3.6-15 2.5-14.7 2.2.4 4.5 2.8 4.5 4.8 0 .6-.9 1.4-2 1.7-1.9.5-2.1 1.2-1.8 7.4l.3 6.8 3.4-.2c1.8 0 4.8-.4 6.7-.8 3.4-.6 3.4-.6 3.4-6.2 0-9.9-5.2-17.2-13.3-19-1.6-.3-3.1-1.2-3.4-1.9-.7-1.8-39.8-2.1-40.9-.3M41 32c0 6.3-.9 7.7-3.7 6-1-.5-4.5-2.1-7.7-3.5-3.3-1.4-5.7-2.7-5.5-2.9 1-1 15.5-6.3 16.2-5.9.4.2.7 3.1.7 6.3" />
</svg>
