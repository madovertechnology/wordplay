<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
      <h3 class="text-xl font-bold mb-4">Your Badges</h3>
      <div v-if="badges.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div 
          v-for="badge in badges" 
          :key="badge.id"
          class="p-4 bg-gray-100 rounded-lg text-center"
        >
          <div class="text-3xl mb-2">{{ badge.icon }}</div>
          <div class="font-bold">{{ badge.name }}</div>
          <div class="text-sm text-gray-600">{{ badge.description }}</div>
          <div class="text-xs text-gray-500 mt-1">
            Earned {{ formatDate(badge.awarded_at) }}
          </div>
        </div>
      </div>
      <div v-else class="text-gray-500 italic">
        You haven't earned any badges yet. Keep playing to earn badges!
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    badges: {
      type: Array,
      required: true,
    },
  },
  
  methods: {
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString();
    },
  },
};
</script>