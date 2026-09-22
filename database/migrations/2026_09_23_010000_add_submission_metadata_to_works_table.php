<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('profiles', function (Blueprint $table) {
                $table->unsignedInteger('priority')->default(999)->after('designation_id');
            });
        } catch (\Throwable $e) {
            // Priority column already exists
        }

        try {
            Schema::table('works', function (Blueprint $table) {
                $table->string('submitted_name')->nullable()->after('category_id');
                $table->string('submitted_email')->nullable()->after('submitted_name');
            });
        } catch (\Throwable $e) {
            // Columns already exist
        }
    }

    public function down(): void
    {
        //
    }
};