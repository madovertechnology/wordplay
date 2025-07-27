<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <h3 class="text-xl font-bold mb-4">Your Streak</h3>
      <div class="flex items-center space-x-8">
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600">{{ streak.current }}</div>
          <div class="text-sm text-gray-600">Current Streak</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600">{{ streak.longest }}</div>
          <div class="text-sm text-gray-600">Longest Streak</div>
        </div>
      </div>
      <div v-if="streak.willBreakTomorrow" class="mt-4 p-3 bg-yellow-100 text-yellow-800 rounded-md">
        <span class="font-bold">Warning:</span> Your streak will break tomorrow if you don't play!
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    streak: {
      type: Object,
      required: true,
    },
  },
};
</script>