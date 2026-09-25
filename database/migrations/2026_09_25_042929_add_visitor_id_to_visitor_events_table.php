<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_events', function (Blueprint $table) {
            $table->foreignId('visitor_id')->nullable()->after('session_id')->constrained('visitors')->nullOnDelete();
            // নোট: 'visitors' টেবিলের সাথে ফরেন কি না থাকলে শুধু unsignedBigInteger ব্যবহার করুন:
            // $table->unsignedBigInteger('visitor_id')->nullable()->after('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('visitor_events', function (Blueprint $table) {
            $table->dropColumn('visitor_id');
        });
    }
};
