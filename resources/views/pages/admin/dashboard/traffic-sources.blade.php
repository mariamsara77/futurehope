<?php

use Livewire\Component;
use App\Models\VisitorSession;
use Illuminate\Support\Facades\DB;

new class extends Component {
    // কুইক স্ট্যাট কার্ড ডাটা (Single Query Optimized)
    public function getSourceStatsProperty()
    {
        $stats = VisitorSession::query()
            ->select(
                DB::raw("
                COUNT(CASE WHEN origin_type = 'direct' THEN 1 END) as direct,
                COUNT(CASE WHEN origin_type = 'search' THEN 1 END) as search,
                COUNT(CASE WHEN origin_type = 'social' THEN 1 END) as social,
                COUNT(CASE WHEN origin_type = 'referral' THEN 1 END) as referral
            "),
            )
            ->first();

        return [
            'direct' => $stats->direct ?? 0,
            'search' => $stats->search ?? 0,
            'social' => $stats->social ?? 0,
            'referral' => $stats->referral ?? 0,
        ];
    }

    // ইউনিক ডোমেইন অনুযায়ী রেফারাল সাইট গ্রুপিং (High Performance)
    public function getTopReferralDomainsProperty()
    {
        // প্রথমে SQL-এ Group By করে রো সংখ্যা কমিয়ে আনা হয়েছে
        $rawReferrals = VisitorSession::query()->select('origin_source', DB::raw('count(*) as count'))->where('origin_type', 'referral')->whereNotNull('origin_source')->where('origin_source', '!=', '')->groupBy('origin_source')->get();

        return $rawReferrals
            ->map(function ($session) {
                $source = trim($session->origin_source);

                if (!str_starts_with($source, 'http://') && !str_starts_with($source, 'https://')) {
                    $source = 'https://' . $source;
                }

                $host = parse_url($source, PHP_URL_HOST);

                if ($host) {
                    // l.facebook.com, lm.facebook.com, m.facebook.com, www. google.com ইত্যাদি ক্লিন করা হচ্ছে
                    $host = preg_replace('/^(www|l|lm|m|web|touch|mobile)\./i', '', $host);
                }

                return [
                    'domain' => $host ?: 'Direct / Unknown',
                    'count' => $session->count,
                ];
            })
            ->groupBy('domain')
            ->map(function ($items, $domain) {
                return [
                    'domain' => $domain,
                    'count' => $items->sum('count'),
                ];
            })
            ->sortByDesc('count')
            ->take(10) // শীর্ষ ১০টি রেফারাল সাইট
            ->values()
            ->toArray();
    }

    // শীর্ষ ১০টি ট্রাফিক সোর্স
    public function getTopSourcesProperty()
    {
        return VisitorSession::query()->select('origin_type', 'origin_source', DB::raw('count(*) as count'))->whereNotNull('origin_type')->groupBy('origin_type', 'origin_source')->orderByDesc('count')->take(10)->get();
    }

    // শীর্ষ ১০টি একটিভ UTM ক্যাম্পেইন
    public function getUtmCampaignsProperty()
    {
        return VisitorSession::query()->select('utm_campaign', DB::raw('count(*) as count'))->whereNotNull('utm_campaign')->where('utm_campaign', '!=', '')->groupBy('utm_campaign')->orderByDesc('count')->take(10)->get();
    }
}; ?>

<div class="space-y-6">
    <!-- Section Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight">Traffic Acquisition Dashboard</flux:heading>
            <flux:subheading>আপনার ওয়েবসাইটের ভিজিটর সোর্স এবং রেফারাল ট্রাফিকের রিয়েল-টাইম বিশ্লেষণ</flux:subheading>
        </div>
        <flux:button variant="subtle" size="sm" icon="arrow-path" wire:click="$refresh">রিফ্রেশ করুন</flux:button>
    </div>

    <!-- Quick Overview Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Direct -->
        <flux:card class="relative overflow-hidden group hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <flux:label class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Direct Traffic
                    </flux:label>
                    <flux:heading size="2xl" class="font-extrabold mt-1">
                        {{ number_format($this->sourceStats['direct']) }}
                    </flux:heading>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl text-blue-600 dark:text-blue-400">
                    <flux:icon name="arrow-right-start-on-rectangle" class="w-6 h-6" />
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-blue-500"></div>
        </flux:card>

        <!-- Organic Search -->
        <flux:card class="relative overflow-hidden group hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <flux:label class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Search Engines
                    </flux:label>
                    <flux:heading size="2xl" class="font-extrabold mt-1">
                        {{ number_format($this->sourceStats['search']) }}
                    </flux:heading>
                </div>
                <div class="p-3 bg-zinc-400/10 rounded-xl text-emerald-600 dark:text-emerald-400">
                    <flux:icon name="magnifying-glass" class="w-6 h-6" />
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-zinc-400/10"></div>
        </flux:card>

        <!-- Social Media -->
        <flux:card class="relative overflow-hidden group hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <flux:label class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Social Networks
                    </flux:label>
                    <flux:heading size="2xl" class="font-extrabold mt-1">
                        {{ number_format($this->sourceStats['social']) }}
                    </flux:heading>
                </div>
                <div class="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl text-indigo-600 dark:text-indigo-400">
                    <flux:icon name="share" class="w-6 h-6" />
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-indigo-500"></div>
        </flux:card>

        <!-- Referral Sites -->
        <flux:card class="relative overflow-hidden group hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <flux:label class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Referral Sites
                    </flux:label>
                    <flux:heading size="2xl" class="font-extrabold mt-1">
                        {{ number_format($this->sourceStats['referral']) }}
                    </flux:heading>
                </div>
                <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl text-amber-600 dark:text-amber-400">
                    <flux:icon name="link" class="w-6 h-6" />
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-amber-500"></div>
        </flux:card>
    </div>

    <!-- Main Referral Domain Section (Full Width for Clarity) -->
    <flux:card class="border border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center justify-between mb-6 border-b border-zinc-400/25 pb-4">
            <div>
                <flux:heading size="lg" class="font-bold">Top Referral Domains</flux:heading>
                <flux:subheading>ইউনিক বা রুট ডোমেন ফিল্টারিং অনুযায়ী প্রধান রেফারাল সোর্সসমূহ</flux:subheading>
            </div>
            <flux:badge color="amber" size="sm" class="font-semibold">Unique Cleaned Domains</flux:badge>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-2/3">Referrer Domain</flux:table.column>
                <flux:table.column align="end" class="w-1/3">Active Sessions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->topReferralDomains as $referral)
                    <flux:table.row class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                        <flux:table.cell class="font-medium">
                            <div class="flex items-center gap-4">
                                <div class="p-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-md">
                                    <flux:icon name="globe-alt" class="w-4  h-4 text-zinc-500 dark:text-zinc-400" />
                                </div>
                                <span
                                    class="text-zinc-800 dark:text-zinc-200 font-semibold">{{ $referral['domain'] }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end"
                            class="font-bold text-fuchsia-600 dark:text-fuchsia-400 text-sm">
                            {{ number_format($referral['count']) }}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="2" class="text-center text-zinc-400 py-8">
                            <div class="flex flex-col items-center justify-center gap-4">
                                <flux:icon name="link" class="w-8 h-8 text-zinc-300" />
                                <span>কোনো রেফারাল ডোমেন পাওয়া যায়নি।</span>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Detailed Analytics Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Traffic Sources -->
        <flux:card class="border border-zinc-200 dark:border-zinc-800">
            <div class="mb-4">
                <flux:heading size="lg" class="font-bold">Top Traffic Sources</flux:heading>
                <flux:subheading>ভিজিটরদের আগমনের মূল ক্যাটাগরি ও সোর্স</flux:subheading>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Domain / Host</flux:table.column>
                    <flux:table.column align="end">Sessions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->topSources as $source)
                        <flux:table.row class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                            <flux:table.cell>
                                <flux:badge size="sm" variant="subtle" color="zinc"
                                    class="capitalize font-medium">
                                    {{ $source->origin_type }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="font-medium max-w-[200px] truncate">
                                {{ $source->origin_source ?: 'Direct / Bookmarked' }}
                            </flux:table.cell>
                            <flux:table.cell align="end" class="font-bold text-zinc-700 dark:text-zinc-300">
                                {{ number_format($source->count) }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center text-zinc-400 py-6">
                                কোনো ট্রাফিক সোর্স পাওয়া যায়নি।
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <!-- UTM Campaigns -->
        <flux:card class="border border-zinc-200 dark:border-zinc-800">
            <div class="mb-4">
                <flux:heading size="lg" class="font-bold">Active Campaigns (UTM)</flux:heading>
                <flux:subheading>চলতি মার্কেটিং ক্যাম্পেইন এবং প্রমোশনের পারফরম্যান্স</flux:subheading>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Campaign Name</flux:table.column>
                    <flux:table.column align="end">Total Hits</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->utmCampaigns as $campaign)
                        <flux:table.row class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                            <flux:table.cell class="font-semibold text-zinc-800 dark:text-zinc-200">
                                <div class="flex items-center gap-4">
                                    <flux:icon name="megaphone" class="w-4  h-4 text-emerald-500" />
                                    <span>{{ $campaign->utm_campaign }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="end"
                                class="font-extrabold text-emerald-600 dark:text-emerald-400">
                                {{ number_format($campaign->count) }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="2" class="text-center text-zinc-400 py-8">
                                <div class="flex flex-col items-center justify-center gap-4">
                                    <flux:icon name="megaphone" class="w-6 h-6 text-zinc-300" />
                                    <span>কোনো একটিভ ক্যাম্পেইন পাওয়া যায়নি।</span>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
