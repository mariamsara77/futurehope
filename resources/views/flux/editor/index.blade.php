@props(['label' => null, 'description' => null])

<div {{ $attributes->only('class') }}>
    @if ($label)
        <flux:label class="mb-1">{{ $label }}</flux:label>
    @endif

    @if ($description)
        <flux:description class="mb-3">{{ $description }}</flux:description>
    @endif

    <div x-data="{
        content: @entangle($attributes->wire('model')),
        async initEditor() {
            // ১. Quill লাইব্রেরিটি লোড হওয়ার জন্য অপেক্ষা করবে
            const Quill = await window.initQuill();

            // ২. এডিটর সেটআপ (আপনার আগের লজিক অনুযায়ী)
            const quill = new Quill($refs.quillEditor, {
                theme: 'snow',
                modules: {
                    toolbar: $refs.toolbar
                }
            });

            if (this.content) {
                quill.root.innerHTML = this.content;
            }

            let timeout;
quill.on('text-change', () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        const html = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML;
        if (this.content !== html) {
            this.content = html;
        }
    }, 300); // ৩০০ মিলিসেকেন্ড পর ডাটা আপডেট হবে
});

            this.$watch('content', value => {
                if (value !== quill.root.innerHTML) {
                    quill.root.innerHTML = value || '';
                }
            });
        }
    }" x-init="initEditor" class="relative group">
        <div
            class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-700/50 shadow-sm transition-all">

            {{-- Toolbar with Flux UI Consistency --}}
            <div x-ref="toolbar"
                class="flex items-center flex-wrap gap-1 px-2 py-1.5 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50">

                {{-- Text Heading Dropdown (Customized) --}}
                <span class="ql-formats !mr-2">
                    <select class="ql-header !w-24 !bg-transparent">
                        <option value="1">Heading 1</option>
                        <option value="2">Heading 2</option>
                        <option selected>Normal</option>
                    </select>
                </span>

                <div class="h-4 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                {{-- Formatting Tools (Underline Included) --}}
                <span class="ql-formats !mr-1">
                    <button class="ql-bold" title="Bold"></button>
                    <button class="ql-italic" title="Italic"></button>
                    <button class="ql-underline" title="Underline"></button>
                    {{-- <button class="ql-strike" title="Strikethrough"></button> --}}
                </span>

                <div class="h-4 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                {{-- Lists (Fixed Numbering) --}}
                <span class="ql-formats !mr-1">
                    <button class="ql-list" value="ordered" title="Numbered List"></button>
                    <button class="ql-list" value="bullet" title="Bullet List"></button>
                </span>

                <div class="h-4 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                {{-- Link & Clean --}}
                <span class="ql-formats !mr-0">
                    <button class="ql-link" title="Insert Link"></button>
                    <button class="ql-clean" title="Remove Formatting"></button>
                </span>
            </div>

            {{-- Quill Editor Area --}}
            <div x-ref="quillEditor" class="min-h-[150px] leading-relaxed text-black dark:text-white">
            </div>
        </div>
    </div>

    <flux:error :name="$attributes->wire('model')->value()" />
</div>

<style>
    /* Quill Snow Theme Overrides */
    .ql-toolbar.ql-snow,
    .ql-container.ql-snow {
        border: none !important;
    }

    /* Toolbar Icons & Dropdown Style */
    .ql-snow .ql-stroke {
        stroke: #71717a !important;
        stroke-width: 2px;
    }

    .ql-snow .ql-fill {
        fill: #71717a !important;
    }

    .dark .ql-snow .ql-stroke {
        stroke: #a1a1aa !important;
    }

    .dark .ql-snow .ql-fill {
        fill: #a1a1aa !important;
    }

    /* Active Tool State */
    .ql-snow.ql-toolbar button:hover .ql-stroke,
    .ql-snow.ql-toolbar button.ql-active .ql-stroke {
        stroke: #3b82f6 !important;
    }

    .ql-snow.ql-toolbar button:hover .ql-fill,
    .ql-snow.ql-toolbar button.ql-active .ql-fill {
        fill: #3b82f6 !important;
    }

    /* Floating Link Bar Fix & Customization */
    .ql-snow .ql-tooltip {
        @apply !rounded-lg !border-zinc-200 dark: !border-zinc-700 !bg-white dark: !bg-zinc-800 !shadow-xl !text-zinc-800 dark: !text-zinc-200;
        left: 20px !important;
        top: 10px !important;
        z-index: 50;
    }

    .ql-snow .ql-tooltip input[type=text] {
        @apply !rounded-md !border-zinc-200 dark: !border-zinc-600 dark: !bg-zinc-900 !px-2 !py-1;
    }

    .ql-snow .ql-tooltip a.ql-action::after {
        content: 'Apply';
        @apply !text-blue-600;
        font-weight: bold;
    }

    .ql-snow .ql-tooltip a.ql-remove::after {
        content: 'Remove';
        @apply !text-red-500;
    }

    /* Fix for Ordered List Numbering */
    .ql-editor ol {
        padding-left: 0 !important;
    }

    .ql-editor ol li {
        padding-left: 1.5rem !important;
    }

    /* Buttons Styling */
    .ql-formats button {
        @apply rounded-md transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-700/50 !important;
    }



    /* ১. শুধুমাত্র Ordered List (Numbered) ফিক্স */
    .ql-editor ol li[data-list="ordered"] {
        counter-increment: list-0;
        padding-left: 1.5rem !important;
        position: relative;
        list-style-type: none !important;
        /* ডিফল্ট ০ বা ডট সরানোর জন্য */
    }

    .ql-editor ol li[data-list="ordered"]::before {
        content: counter(list-0) ". ";
        position: absolute;
        left: 0;
        @apply text-zinc-500 font-medium;
    }

    /* ২. বুলেট লিস্টের জন্য ডিফল্ট স্টাইল (নম্বর আসা বন্ধ করবে) */
    .ql-editor ul li[data-list="bullet"] {
        list-style-type: disc !important;
        /* ডট দেখাবে */
        padding-left: 0.5rem !important;
    }

    .ql-editor ul li[data-list="bullet"]::before {
        content: "" !important;
        /* নম্বর বা অন্য কিছু আসা ব্লক করবে */
    }

    /* কাউন্টার রিসেট */
    .ql-editor ol {
        counter-reset: list-0;
    }

    /* Quill এর ভেতরের প্যারাগ্রাফ গ্যাপ কমানো */
    .ql-editor p {
        margin-bottom: 2px !important;
        /* আপনার পছন্দমতো কমিয়ে নিন */
        line-height: 1.5 !important;
    }

    /* ফোকাস অবস্থায় বর্ডার কালার পরিবর্তন (Flux Style) */
    .group:focus-within .overflow-hidden {
        @apply border-indigo-500 ring-1 ring-indigo-500/20 !important;
    }
</style>