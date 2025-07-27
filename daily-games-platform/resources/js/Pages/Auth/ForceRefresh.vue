<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">
                    Forcing Authentication Refresh
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    {{ message }}
                </p>
                <div class="mt-4">
                    <p class="text-sm text-gray-500">
                        Backend authentication state has been cleared.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'

defineProps({
    message: String,
})

onMounted(() => {
    // Clear all frontend state
    localStorage.clear();
    sessionStorage.clear();

    // Clear all cookies
    document.cookie.split(";").forEach(function(c) {
        document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
    });

    // Force redirect to home page after a short delay
    setTimeout(() => {
        window.location.href = '/'
    }, 2000)
})
</script>
