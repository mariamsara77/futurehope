<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. VISITORS
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->char('hash', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('browser_family', 50)->nullable()->index();
            $table->string('os_family', 50)->nullable()->index();
            $table->string('device_type', 20)->nullable()->index();
            $table->string('device_model', 100)->nullable();
            $table->char('country_code', 2)->nullable()->index();
            $table->string('city_name', 100)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->boolean('is_pwa')->default(false)->index();
            $table->boolean('has_installed_pwa')->default(false)->index();
            $table->string('app_version', 20)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();

            $table->index(['country_code', 'device_type', 'is_bot'], 'idx_visitor_analytics');
            $table->index(['last_seen_at', 'is_bot']);
        });

        // 2. VISITOR SESSIONS
        Schema::create('visitor_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->string('origin_type', 20)->default('direct')->index();
            $table->string('origin_source')->nullable()->index();
            $table->text('entry_url')->nullable();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable()->index();
            $table->string('utm_campaign')->nullable()->index();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_active_at')->nullable();
            $table->unsignedInteger('hits_count')->default(0);
            $table->unsignedInteger('seconds_spent')->default(0);
            $table->timestamps();

            $table->index(['visitor_id', 'last_active_at'], 'idx_session_recovery');
            $table->index(['started_at', 'origin_type'], 'idx_traffic_stats');
        });

        // 3. PAGE VIEWS
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->string('title', 255)->nullable();
            $table->text('url');
            $table->char('url_hash', 40)->index();
            $table->string('route_name', 100)->nullable()->index();
            $table->unsignedInteger('load_time_ms')->nullable();
            $table->unsignedInteger('view_duration')->default(0);
            $table->timestamp('created_at')->useCurrent();

            // Session foreign key
            $table->foreign('session_id')->references('id')->on('visitor_sessions')->cascadeOnDelete();

            $table->index(['visitor_id', 'created_at'], 'idx_user_journey');
            $table->index(['created_at', 'route_name'], 'idx_popular_pages');
        });

        // 4. VISITOR EVENTS
        Schema::create('visitor_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->nullable();
            $table->string('event_category', 50)->index();
            $table->string('event_action', 50)->index();
            $table->string('event_label', 100)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign Key Definition
            $table->foreign('session_id')
                ->references('id')
                ->on('visitor_sessions')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index(['event_category', 'event_action', 'created_at'], 'idx_event_stats');
        });
    }

    public function down(): void
    {
        // Drop tables in REVERSE order of creation to avoid FK constraints
        Schema::dropIfExists('visitor_events');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('visitor_sessions');
        Schema::dropIfExists('visitors');
    }
};