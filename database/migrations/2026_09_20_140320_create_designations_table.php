<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // সভাপতি, সাধারণ সম্পাদক, কোষাধ্যক্ষ...
            $table->string('slug')->unique();
            $table->integer('order')->default(0);
            $table->boolean('is_committee')->default(false); // Committee member কিনা
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};