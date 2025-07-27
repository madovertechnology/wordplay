<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    game: Object,
    period: String,
    leaderboard: Array,
    userRank: Object,
});

const activePeriod = ref(props.period || 'daily');

const periodOptions = [
    { value: 'daily', label: 'Daily' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'all-time', label: 'All Time' },
];

const formattedPeriod = computed(() => {
    switch (activePeriod.value) {
        case 'daily':
            return 'Today';
        case 'monthly':
            return 'This Month';
        case 'all-time':
            return 'All Time';
        default:
            return 'Today';
    }
});

const userRankDisplay = computed(() => {
    if (!props.userRank) {
        return 'Not ranked';
    }
    
    return `#${props.userRank.rank} (${props.userRank.score} points)`;
});
</script>

<template>
    <Head :title="`${game.name} Leaderboard`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ game.name }} Leaderboard
                </h2>
                <Link :href="`/games/${game.slug}`" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Play Game
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">{{ formattedPeriod }} Leaderboard</h3>
                            <div class="flex space-x-2">
                                <Link 
                                    v-for="option in periodOptions" 
                                    :key="option.value"
                                    :href="`/leaderboards/${game.slug}/${option.value}`"
                                    :class="[
                                        'rounded-md px-3 py-2 text-sm font-medium',
                                        activePeriod === option.value 
                                            ? 'bg-indigo-600 text-white' 
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    ]"
                                >
                                    {{ option.label }}
                                </Link>
                            </div>
                        </div>
                        
                        <div v-if="userRank" class="mb-6 rounded-md bg-indigo-50 p-4">
                            <p class="text-sm text-gray-700">
                                Your Rank: <span class="font-semibold">{{ userRankDisplay }}</span>
                            </p>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Rank
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Player
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Score
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="(entry, index) in leaderboard" :key="index" :class="{ 'bg-indigo-50': entry.user_id === $page.props.auth.user?.id }">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            #{{ index + 1 }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <div v-if="entry.avatar" class="mr-3 h-8 w-8">
                                                    <img :src="entry.avatar" alt="Avatar" class="h-8 w-8 rounded-full" />
                                                </div>
                                                <div v-else class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-600">
                                                    {{ entry.name.charAt(0).toUpperCase() }}
                                                </div>
                                                <span>{{ entry.name }}</span>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {{ entry.score }}
                                        </td>
                                    </tr>
                                    <tr v-if="leaderboard.length === 0">
                                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No entries found for this leaderboard.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>