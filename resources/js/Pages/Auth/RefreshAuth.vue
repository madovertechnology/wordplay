<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">
                    Refreshing Authentication
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    {{ message }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

defineProps({
    message: String,
})

onMounted(() => {
    // Force redirect to home page after a short delay
    setTimeout(() => {
        window.location.href = '/'
    }, 1000)
})
</script>
