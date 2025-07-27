<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    dashboardData: Object,
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- User Profile Summary -->
                <div class="mb-8 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-800">Your Profile</h3>
                        <div class="flex items-center">
                            <div v-if="dashboardData.user.avatar" class="mr-4">
                                <img :src="dashboardData.user.avatar" alt="Avatar" class="h-16 w-16 rounded-full" />
                            </div>
                            <div v-else class="mr-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-xl font-semibold text-indigo-600">
                                    {{ dashboardData.user.name.charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            <div>
                                <h4 class="text-xl font-semibold">{{ dashboardData.user.name }}</h4>
                                <p class="text-gray-600">{{ dashboardData.user.email }}</p>
                                <div v-if="dashboardData.user.rank" class="mt-2">
                                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-sm font-semibold text-indigo-800">
                                        {{ dashboardData.user.rank.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Badges Section -->
                        <div v-if="dashboardData.user.badges && dashboardData.user.badges.length > 0" class="mt-6">
                            <h4 class="mb-2 text-md font-semibold text-gray-700">Your Badges</h4>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="badge in dashboardData.user.badges" :key="badge.name" class="flex items-center rounded-full bg-gray-100 px-3 py-1">
                                    <span class="mr-1">{{ badge.icon }}</span>
                                    <span class="text-sm font-medium">{{ badge.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Games Section -->
                <h3 class="mb-4 text-xl font-semibold text-gray-800">Daily Games</h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="game in dashboardData.games" :key="game.id" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="mb-2 text-lg font-semibold">{{ game.name }}</h4>
                            <p class="mb-4 text-sm text-gray-600">{{ game.description }}</p>
                            
                            <div v-if="game.user_stats" class="mb-4">
                                <div class="mb-2 flex justify-between">
                                    <span class="text-sm text-gray-600">Total Score:</span>
                                    <span class="font-semibold">{{ game.user_stats.total_score }}</span>
                                </div>
                                <div class="mb-2 flex justify-between">
                                    <span class="text-sm text-gray-600">Plays:</span>
                                    <span class="font-semibold">{{ game.user_stats.plays_count }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Current Streak:</span>
                                    <span class="font-semibold">{{ game.user_stats.current_streak }}</span>
                                </div>
                            </div>
                            
                            <Link :href="`/games/${game.slug}`" class="mt-4 inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Play Now
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
