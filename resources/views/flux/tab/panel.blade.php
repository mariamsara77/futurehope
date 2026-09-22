@props(['name'])

<div
    x-show="$wire.tab === '{{ $name }}'"
    {{ $attributes->class('py-4') }}
>
    {{ $slot }}
</div>
