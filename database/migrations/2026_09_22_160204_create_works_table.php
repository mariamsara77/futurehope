<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('work_categories')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('status', [
                'suggested',   // user সাজেস্ট করেছে
                'voting',      // vote চলছে
                'approved',    // ১০ vote হয়েছে
                'running',     // চলমান
                'upcoming',    // শীঘ্রই
                'completed',   // শেষ
                'rejected'     // বাতিল
            ])->default('suggested');

            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('votes_count')->default(0);
            $table->unsignedInteger('required_votes')->default(10);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};