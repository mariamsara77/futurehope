<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UpdateLastActive
{
    private const UPDATE_INTERVAL_MINUTES = 5;

    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $cacheKey = 'user-active-' . $user->id;

            // যদি ৫ মিনিটের মধ্যে ক্যাশ না থাকে তবেই আপডেট হবে
            if (!Cache::has($cacheKey)) {
                // সরাসরি কুয়েরি বিল্ডার দিয়ে আপডেট করলে মডেল ইভেন্ট ট্রিগার হয় না, যা আরও ফাস্ট
                $user->newQuery()->where('id', $user->id)->update([
                    'last_active_at' => now()
                ]);

                Cache::put($cacheKey, true, now()->addMinutes(self::UPDATE_INTERVAL_MINUTES));
            }
        }

        return $next($request);
    }
}