<?php

use Livewire\Component;

new class extends Component {}; ?>
<div class="space-y-4">
    <flux:heading class="" size="xl" level="1">Admin Dashboard</flux:heading>
    <flux:text class="">Monitor your site analytics in real-time</flux:text>
    <livewire:pages::admin.dashboard.real-time-visitors lazy />
    <livewire:pages::admin.dashboard.analytics-chart lazy />
    <livewire:pages::admin.dashboard.visitor-dashboard lazy />
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @endpush
</div>