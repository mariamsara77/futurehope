@props([
    'modal' => null,
    'minLength' => 1,
])

<div role="option"
    x-show="search && search.length >= {{ $minLength }} && ! Array.from($el.parentElement.querySelectorAll('[data-label]')).some(el => el.getAttribute('data-label').toLowerCase() === search.toLowerCase())"
    @click="{{ $modal ? "\$flux.modal('$modal').show()" : '$el.dispatchEvent(new CustomEvent(\'click\'))' }}"
    class="flex items-center gap-3 px-3 py-2 rounded-md cursor-default text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-colors">
    <div class="flex-none flex items-center justify-center size-5 rounded-full bg-indigo-100 dark:bg-indigo-500/20">
        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </div>

    <span class="block truncate sm:text-sm font-medium">
        {{ $slot->isEmpty() ? 'Create new' : $slot }}
        <span x-show="search" x-text="'&quot;' + search + '&quot;'"></span>
    </span>
</div>
