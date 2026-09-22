@props([
'sidebar' => false,
])

@if($sidebar)
<flux:sidebar.brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
    <x-slot name="logo" class="flex aspect-square size-12 items-center justify-center rounded-md">
        <img src="/500.png" class="size-12 fill-current text-white dark:text-black" />
    </x-slot>
</flux:sidebar.brand>
@else
<flux:brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
    <x-slot name="logo" class="flex aspect-square size-12 items-center justify-center rounded-md">
        <img src="/500.png" class="size-12 fill-current text-white dark:text-black" />
    </x-slot>
</flux:brand>
@endif