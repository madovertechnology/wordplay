<template>
    <AuthenticatedLayout>
        <Head title="Analytics Dashboard" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-3xl font-bold">Analytics Dashboard</h1>
                            
                            <!-- Period Selector -->
                            <div class="flex space-x-2">
                                <button
                                    v-for="period in periods"
                                    :key="period.value"
                                    @click="changePeriod(period.value)"
                                    :class="[
                                        'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                        selectedPeriod === period.value
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                    ]"
                                >
                                    {{ period.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Overview Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                            <div class="bg-blue-50 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-2 bg-blue-500 rounded-md">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-blue-600">Page Views</p>
                                        <p class="text-2xl font-bold text-blue-900">{{ formatNumber(analytics.overview.total_page_views) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-green-50 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-2 bg-green-500 rounded-md">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-green-600">Unique Visitors</p>
                                        <p class="text-2xl font-bold text-green-900">{{ formatNumber(analytics.overview.unique_visitors) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-purple-50 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-2 bg-purple-500 rounded-md">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-purple-600">Total Sessions</p>
                                        <p class="text-2xl font-bold text-purple-900">{{ formatNumber(analytics.overview.total_sessions) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-orange-50 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-2 bg-orange-500 rounded-md">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-orange-600">Bounce Rate</p>
                                        <p class="text-2xl font-bold text-orange-900">{{ formatPercentage(analytics.overview.bounce_rate) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts and Tables -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Popular Pages -->
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold mb-4">Popular Pages</h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="(views, page) in analytics.page_views.popular_pages"
                                        :key="page"
                                        class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0"
                                    >
                                        <span class="text-sm text-gray-600">{{ formatPageName(page) }}</span>
                                        <span class="text-sm font-medium text-gray-900">{{ formatNumber(views) }} views</span>
                                    </div>
                                    <div v-if="Object.keys(analytics.page_views.popular_pages).length === 0" class="text-center py-4 text-gray-500">
                                        No data available
                                    </div>
                                </div>
                            </div>

                            <!-- Game Events -->
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold mb-4">Game Activity</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-sm text-gray-600">Total Game Events</span>
                                        <span class="text-sm font-medium text-gray-900">{{ formatNumber(analytics.game_events.total_game_events) }}</span>
                                    </div>
                                    <div
                                        v-for="(events, game) in analytics.game_events.games_by_popularity"
                                        :key="game"
                                        class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0"
                                    >
                                        <span class="text-sm text-gray-600">{{ formatGameName(game) }}</span>
                                        <span class="text-sm font-medium text-gray-900">{{ formatNumber(events) }} events</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Engagement Stats -->
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold mb-4">User Engagement</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Average Session Duration</span>
                                        <span class="text-sm font-medium text-gray-900">{{ formatDuration(analytics.engagement.average_session_duration) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Total Engagement Time</span>
                                        <span class="text-sm font-medium text-gray-900">{{ formatDuration(analytics.engagement.total_engagement_time) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Retention Stats -->
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold mb-4">User Retention</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Daily Retention</span>
                                        <span class="text-sm font-medium text-gray-900">{{ formatPercentage(analytics.retention.daily_retention) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Weekly Retention</span>
                                        <span class="text-sm font-medium text-gray-900">{{ formatPercentage(analytics.retention.weekly_retention) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Monthly Retention</span>
                                        <span class="text-sm font-medium text-gray-900">{{ formatPercentage(analytics.retention.monthly_retention) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Last Updated -->
                        <div class="mt-8 text-center text-sm text-gray-500">
                            Last updated: {{ formatDateTime(analytics.last_updated) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    analytics: Object,
    selectedPeriod: Number,
})

const periods = [
    { value: 1, label: '1 Day' },
    { value: 7, label: '7 Days' },
    { value: 30, label: '30 Days' },
    { value: 90, label: '90 Days' },
]

const changePeriod = (days) => {
    router.get(route('analytics.dashboard'), { days }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const formatNumber = (number) => {
    return new Intl.NumberFormat().format(number || 0)
}

const formatPercentage = (number) => {
    return `${(number || 0).toFixed(1)}%`
}

const formatDuration = (seconds) => {
    if (!seconds) return '0s'
    
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = seconds % 60
    
    if (hours > 0) {
        return `${hours}h ${minutes}m`
    } else if (minutes > 0) {
        return `${minutes}m ${secs}s`
    } else {
        return `${secs}s`
    }
}

const formatPageName = (page) => {
    return page.charAt(0).toUpperCase() + page.slice(1).replace(/[-_]/g, ' ')
}

const formatGameName = (game) => {
    return game.split('-').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ')
}

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString()
}
</script>