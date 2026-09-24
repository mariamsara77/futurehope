<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_votes')) {
            Schema::table('work_votes', function (Blueprint $table) {
                $table->unique(['work_id', 'user_id'], 'work_votes_work_user_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('work_votes')) {
            Schema::table('work_votes', function (Blueprint $table) {
                $table->dropUnique('work_votes_work_user_unique');
            });
        }
    }
};
