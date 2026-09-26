<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    @livewireStyles
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('home') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')" class="grid">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                @can('biodata-manage')
                <flux:sidebar.item icon="user-circle" :href="route('dashboard.biodata')"
                    :current="request()->routeIs('dashboard.biodata')" wire:navigate>
                    {{ __('My Biodata') }}
                </flux:sidebar.item>
                @endcan
            </flux:sidebar.group>

            @if(auth()->user()->can('user-manage') || auth()->user()->can('designation-manage') ||
            auth()->user()->can('role-manage') || auth()->user()->can('permission-manage') ||
            auth()->user()->can('work-manage') || auth()->user()->can('contact-manage'))
            <flux:sidebar.group :heading="__('Administration')" class="grid mt-4">

                @can('user-manage')
                <flux:sidebar.item icon="users" :href="route('dashboard.users')"
                    :current="request()->routeIs('dashboard.users')" wire:navigate>
                    {{ __('Manage Users') }}
                </flux:sidebar.item>
                @endcan

                @can('designation-manage')
                <flux:sidebar.item icon="identification" :href="route('dashboard.designations')"
                    :current="request()->routeIs('dashboard.designations')" wire:navigate>
                    {{ __('Designations') }}
                </flux:sidebar.item>
                @endcan

                @can('role-manage')
                <flux:sidebar.item icon="shield-check" :href="route('dashboard.roles')"
                    :current="request()->routeIs('dashboard.roles')" wire:navigate>
                    {{ __('Roles Manage') }}
                </flux:sidebar.item>
                @endcan

                @can('permission-manage')
                <flux:sidebar.item icon="key" :href="route('dashboard.permissions')"
                    :current="request()->routeIs('dashboard.permissions')" wire:navigate>
                    {{ __('Permissions Manage') }}
                </flux:sidebar.item>
                @endcan

                @can('contact-manage')
                <flux:sidebar.item icon="envelope" :href="route('dashboard.contact-messages')"
                    :current="request()->routeIs('dashboard.contact-messages')" wire:navigate>
                    {{ __('Contact Messages') }}
                </flux:sidebar.item>
                @endcan

                @can('work-manage')
                <flux:sidebar.item icon="folder-open" :href="route('dashboard.work-categories')"
                    :current="request()->routeIs('dashboard.work-categories')" wire:navigate>
                    {{ __('Work Categories') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="briefcase" :href="route('dashboard.works')"
                    :current="request()->routeIs('dashboard.works')" wire:navigate>
                    {{ __('Work Submissions') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="wrench-screwdriver" :href="route('dashboard.work-management')"
                    :current="request()->routeIs('dashboard.work-management')" wire:navigate>
                    {{ __('Work Management') }}
                </flux:sidebar.item>
                @endcan

                @can('activity-log-view')
                <flux:sidebar.item icon="chart-bar" :href="route('dashboard.visitor')"
                    :current="request()->routeIs('dashboard.visitor*')" wire:navigate>
                    {{ __('Visitor Analytics') }}
                </flux:sidebar.item>
                @endcan

                @can('biodata-manage')
                <flux:sidebar.item icon="document-text" :href="route('dashboard.all-biodata')"
                    :current="request()->routeIs('dashboard.all-biodata')" wire:navigate>
                    {{ __('All Biodata') }}
                </flux:sidebar.item>
                @endcan

            </flux:sidebar.group>
            @endif
        </flux:sidebar.nav>

        <flux:spacer />

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :avatar="auth()->user()->avatar_url" :name="auth()->user()->name"
                :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar src="{{ auth()->user()->avatar_url }}" :name="auth()->user()->name"
                                :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    <livewire:create-team-modal />

    @persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
    @endpersist

    @livewireScripts
    @fluxScripts
</body>

</html>