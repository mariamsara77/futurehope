<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->unsignedInteger('priority')->default(999)->after('designation_id');
            $table->index(['status', 'priority']);
        });

        Schema::table('works', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('submitted_name')->nullable()->after('category_id');
            $table->string('submitted_email')->nullable()->after('submitted_name');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn(['submitted_name', 'submitted_email']);
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['status', 'priority']);
            $table->dropColumn('priority');
        });
    }
};
