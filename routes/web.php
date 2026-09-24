<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    // === Dashboard & Analytics Routes ===
    Route::livewire('/dashboard', 'pages::admin.dashboard.dashboard')->name('dashboard.root');
    Route::livewire('/{current_team?}/dashboard', 'pages::admin.dashboard.dashboard')->name('dashboard')->middleware(\App\Http\Middleware\EnsureTeamMembership::class);
    Route::livewire('/dashboard/session-manage', 'pages::admin.dashboard.session-manage')->name('dashboard.session');
    
    // Visitor & Analytics (Activity Log View)
    Route::middleware(['can:activity-log-view'])->group(function () {
        Route::livewire('/dashboard/visitor-dashboard', 'pages::admin.dashboard.visitor-dashboard')->name('dashboard.visitor');
        Route::livewire('/dashboard/visitor-analytics/{visitorId}', 'pages::admin.dashboard.visitor-details')->name('dashboard.visitor.details');
    });

    Route::livewire('/dashboard/universal-ai', 'pages::admin.dashboard.universal-ai-create')->name('dashboard.universal-ai');
    Route::livewire('/dashboard/missing-data', 'pages::admin.dashboard.missing-data-manager')->name('dashboard.missing-data');

    // === System Manager Routes ===
    Route::middleware(['can:system-manager'])->group(function () {
        Route::livewire('/dashboard/system-manager/terminal-command', 'pages::admin.dashboard.terminal-command-manager')->name('dashboard.system-manager.terminal-command');
        Route::livewire('/dashboard/system-manager/database-monitor', 'pages::admin.dashboard.database-monitor')->name('dashboard.system-manager.database-monitor');
    });

    Route::middleware(['can:google indexing request'])->group(function () {
        Route::livewire('/dashboard/google-indexing', 'pages::admin.dashboard.google-indexing-request')->name('dashboard.google-indexing');
    });

    // ==========================================
    // NEW ROUTES: Foundation Management System
    // ==========================================

    // Profile (Biodata Manage)
    Route::middleware(['can:biodata-manage'])->group(function () {
        Route::livewire('/dashboard/my-biodata', 'pages::profile.biodata-manager')->name('dashboard.biodata');
    });

    // === User & Role Management Routes ===
    Route::middleware(['can:user-manage'])->group(function () {
        Route::livewire('/dashboard/users', 'pages::user.user-manager')->name('dashboard.users');
    });

    Route::middleware(['can:designation-manage'])->group(function () {
        Route::livewire('/dashboard/designations', 'pages::designation.designation-manager')->name('dashboard.designations');
    });

    Route::middleware(['can:role-manage'])->group(function () {
        Route::livewire('/dashboard/roles', 'pages::role.role-manager')->name('dashboard.roles');
    });

    Route::middleware(['can:permission-manage'])->group(function () {
        Route::livewire('/dashboard/permissions', 'pages::role.manage-permissions')->name('dashboard.permissions');
    });

    Route::middleware(['can:contact-manage'])->group(function () {
        Route::livewire('/dashboard/contact-messages', 'pages::contact.contact-manager')->name('dashboard.contact-messages');
    });

    Route::middleware(['can:work-manage'])->group(function () {
        Route::livewire('/dashboard/works', 'pages::work.work-manager')->name('dashboard.works');
        Route::livewire('/dashboard/work-management', 'pages::work.work-management')->name('dashboard.work-management');
        Route::livewire('/dashboard/work-categories', 'pages::work.category-manager')->name('dashboard.work-categories');
    });

    Route::middleware(['can:biodata-manage'])->group(function () {
        Route::livewire('/dashboard/all-biodata', 'pages::profile.manage-biodata')->name('dashboard.all-biodata');
    });
});

require __DIR__.'/settings.php';