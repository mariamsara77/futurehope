<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('profiles') || !Schema::hasTable('users')) {
            return;
        }

        $now = now();

        DB::table('users')
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->whereNull('profiles.id')
            ->select('users.id')
            ->orderBy('users.id')
            ->chunkById(500, function ($users) use ($now) {
                $rows = collect($users)->map(fn ($user) => [
                    'user_id' => $user->id,
                    'status' => 'active',
                    'priority' => 999,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($rows) {
                    DB::table('profiles')->insert($rows);
                }
            }, 'users.id');
    }

    public function down(): void
    {
        // Existing profiles must never be removed by this data backfill.
    }
};
