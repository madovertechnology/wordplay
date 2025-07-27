<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);
const page = usePage();
const user = computed(() => page.props.auth?.user);

// Simple logout that forces complete page reload
const logout = () => {
    // Clear everything immediately
    localStorage.clear();
    sessionStorage.clear();

    // Clear all cookies
    document.cookie.split(";").forEach(function(c) {
        document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
    });

    // Go to force auth refresh
    window.location.href = route('force.auth.refresh')
}

// Nuclear logout as emergency option
const nuclearLogout = () => {
    window.location.href = route('nuclear.logout')
}

// Super simple logout - just clear everything and reload
const superSimpleLogout = () => {
    // Clear everything
    localStorage.clear();
    sessionStorage.clear();

    // Clear all cookies
    document.cookie.split(";").forEach(function(c) {
        document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
    });

    // Force complete page reload
    window.location.reload(true);
}
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-gray-100">
            <nav class="border-b border-gray-100 bg-white">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')" v-if="user">
                                    Dashboard
                                </NavLink>
                                <NavLink href="/games/word-scramble" :active="route().current('games.word-scramble.*')">
                                    Word Scramble
                                </NavLink>
                                <NavLink href="/leaderboards/word-scramble" :active="route().current('leaderboards.*')">
                                    Leaderboard
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ml-6 sm:flex sm:items-center" v-if="user">
                            <!-- Settings Dropdown -->
                            <div class="relative ml-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{ user.name }}

                                                <svg
                                                    class="-mr-0.5 ml-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                        <button
                                            @click="logout"
                                            class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                                        >
                                            Log Out
                                        </button>
                                        <button
                                            @click="nuclearLogout"
                                            class="block w-full px-4 py-2 text-left text-sm leading-5 text-red-600 hover:bg-red-50 focus:outline-none focus:bg-red-50 transition duration-150 ease-in-out"
                                        >
                                            Force Logout (Emergency)
                                        </button>
                                        <button
                                            @click="superSimpleLogout"
                                            class="block w-full px-4 py-2 text-left text-sm leading-5 text-green-600 hover:bg-green-50 focus:outline-none focus:bg-green-50 transition duration-150 ease-in-out"
                                        >
                                            Super Simple Logout
                                        </button>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Guest Login/Register Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-4" v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]"
                            >
                                Log in
                            </Link>
                            <Link
                                :href="route('register')"
                                class="rounded-md bg-black px-3 py-2 text-white ring-1 ring-transparent transition hover:bg-black/90 focus:outline-none focus-visible:ring-[#FF2D20]"
                            >
                                Register
                            </Link>
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')" v-if="user">
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink href="/games/word-scramble" :active="route().current('games.word-scramble.*')">
                            Word Scramble
                        </ResponsiveNavLink>
                        <ResponsiveNavLink href="/leaderboards/word-scramble" :active="route().current('leaderboards.*')">
                            Leaderboard
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-gray-200 pb-1 pt-4" v-if="user">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">
                                {{ user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">{{ user.email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                            <button
                                @click="logout"
                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                            >
                                Log Out
                            </button>
                            <button
                                @click="nuclearLogout"
                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-red-600 hover:bg-red-50 focus:outline-none focus:bg-red-50 transition duration-150 ease-in-out"
                            >
                                Force Logout (Emergency)
                            </button>
                            <button
                                @click="superSimpleLogout"
                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-green-600 hover:bg-green-50 focus:outline-none focus:bg-green-50 transition duration-150 ease-in-out"
                            >
                                Super Simple Logout
                            </button>
                        </div>
                    </div>

                    <!-- Responsive Guest Options -->
                    <div class="border-t border-gray-200 pb-1 pt-4" v-else>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('login')">Log in</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('register')">Register</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
