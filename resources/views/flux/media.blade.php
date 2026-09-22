@props([
    'media' => [],
])

@php
    $mediaArray = is_array($media) || $media instanceof \Illuminate\Support\Collection ? $media : [$media];

    if (empty($mediaArray) || count($mediaArray) === 0 || !$mediaArray[0]) {
        return;
    }

    $items = collect($mediaArray)->map(function ($m) {
        if (is_object($m) && method_exists($m, 'getUrl')) {
            return [
                'url' => $m->getUrl(),
                'thumb' => $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl(),
                'type' => str_contains($m->mime_type ?? '', 'video') ? 'video' : 'image',
                'caption' => $m->name ?? ''
            ];
        }

        return [
            'url' => (string) $m,
            'thumb' => (string) $m,
            'type' => 'image',
            'caption' => ''
        ];
    })->values()->all();

    $count = count($items);
    
    // ডিফল্ট গ্রিড লেআউট ক্লাস
    $defaultGridClass = $count > 1 ? 'grid grid-cols-2 gap-2 rounded-2xl' : 'grid grid-cols-1 gap-2 rounded-2xl';
@endphp

<div x-data="{ galleryItems: @js($items) }" class="w-full">
    {{-- $attributes->class() ব্যবহারের ফলে বাইরে থেকে পাঠানো rounded, gap বা grid ক্লাস স্বয়ংক্রিয়ভাবে মার্জ হবে --}}
    <div {{ $attributes->class([$defaultGridClass, 'overflow-hidden']) }}>

        @foreach(collect($items)->take(3) as $index => $item)
            <div class="{{ $count > 2 && $index === 0 ? 'row-span-2' : '' }} relative group cursor-pointer overflow-hidden bg-zinc-100 dark:bg-zinc-900 border border-black/5 dark:border-white/5"
                @click="$dispatch('open-lightbox', { index: {{ $index }}, items: galleryItems })">

                <img src="{{ $item['thumb'] }}"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    loading="lazy">

                {{-- ভিডিও প্লে আইকন --}}
                @if($item['type'] === 'video')
                    <div class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/40">
                        <div class="bg-white/20 backdrop-blur-md p-2 rounded-full text-white border border-white/30">
                            <flux:icon name="play" variant="solid" class="size-6" />
                        </div>
                    </div>
                @endif

                {{-- অতিরিক্ত ছবি সংখ্যা --}}
                @if($count > 3 && $index === 2)
                    <div class="absolute inset-0 bg-black/60 flex items-center justify-center text-white font-bold">
                        <span class="text-lg">+ {{ bn_num($count - 3) }} টি</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @once
        <x-global-lightbox />
    @endonce
</div>