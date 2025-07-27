<template>
  <AppLayout title="Word Scramble">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Word Scramble
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Game Container -->
            <div class="flex flex-col items-center">
              <!-- Game Stats -->
              <div class="w-full flex justify-between mb-6">
                <div class="text-lg">
                  <span class="font-bold">Found:</span> {{ foundWords.length }} / {{ puzzle.possible_words_count }}
                </div>
                <div class="text-lg">
                  <span class="font-bold">Score:</span> {{ totalScore }}
                </div>
              </div>

              <!-- Letters Display -->
              <div class="flex justify-center mb-8">
                <div 
                  v-for="(letter, index) in letters" 
                  :key="index"
                  class="w-12 h-12 md:w-16 md:h-16 flex items-center justify-center text-2xl md:text-3xl font-bold bg-blue-100 rounded-lg shadow-md m-1 cursor-pointer transition-transform hover:scale-110"
                  :class="{ 'bg-blue-300': selectedLetters.includes(index) }"
                  @click="toggleLetter(index)"
                >
                  {{ letter }}
                </div>
              </div>

              <!-- Word Input -->
              <div class="mb-6 text-center">
                <div class="text-2xl font-bold mb-2">{{ currentWord }}</div>
                <div class="flex space-x-2">
                  <button 
                    @click="shuffleLetters"
                    class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300"
                  >
                    Shuffle
                  </button>
                  <button 
                    @click="clearWord"
                    class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300"
                  >
                    Clear
                  </button>
                  <button 
                    @click="submitWord"
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                    :disabled="currentWord.length < 3"
                    :class="{ 'opacity-50 cursor-not-allowed': currentWord.length < 3 }"
                  >
                    Submit
                  </button>
                </div>
              </div>

              <!-- Message Display -->
              <div v-if="message" class="mb-6 p-3 rounded-md" :class="messageClass">
                {{ message }}
              </div>

              <!-- Found Words -->
              <div class="w-full">
                <h3 class="text-xl font-bold mb-2">Found Words</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                  <div 
                    v-for="(word, index) in foundWords" 
                    :key="index"
                    class="p-2 bg-green-100 rounded-md"
                  >
                    <span class="font-medium">{{ word.word }}</span>
                    <span class="text-sm text-gray-600 ml-2">({{ word.score }})</span>
                  </div>
                  <div v-if="foundWords.length === 0" class="col-span-full text-gray-500 italic">
                    No words found yet. Start playing!
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Leaderboard -->
        <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h3 class="text-xl font-bold mb-4">Daily Leaderboard</h3>
            <div v-if="leaderboard.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Player</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(entry, index) in leaderboard" :key="index">
                    <td class="px-6 py-4 whitespace-nowrap">{{ index + 1 }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ entry.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ entry.score }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="text-gray-500 italic">
              No entries in the leaderboard yet.
            </div>
          </div>
        </div>

        <!-- Streak Display -->
        <div v-if="streak" class="mt-6">
          <StreakDisplay :streak="streak" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StreakDisplay from '@/Components/Game/StreakDisplay.vue';

export default {
  components: {
    AppLayout,
    StreakDisplay,
  },
  
  props: {
    puzzle: Object,
  },
  
  setup(props) {
    // Game state
    const letters = ref([]);
    const selectedLetters = ref([]);
    const foundWords = ref([]);
    const totalScore = ref(0);
    const message = ref('');
    const messageClass = ref('');
    const leaderboard = ref([]);
    const streak = ref(null);
    
    // Computed properties
    const currentWord = computed(() => {
      return selectedLetters.value.map(index => letters.value[index]).join('');
    });
    
    // Initialize the game
    onMounted(() => {
      initializeGame();
      loadSubmissions();
      loadLeaderboard();
      
      // Load streak for authenticated users
      const page = usePage();
      if (page.props.auth?.user) {
        loadStreak();
      }
    });
    
    // Methods
    const initializeGame = () => {
      letters.value = props.puzzle.letters.split('');
      shuffleLetters();
    };
    
    const shuffleLetters = () => {
      // Fisher-Yates shuffle algorithm
      for (let i = letters.value.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [letters.value[i], letters.value[j]] = [letters.value[j], letters.value[i]];
      }
      selectedLetters.value = [];
    };
    
    const toggleLetter = (index) => {
      const position = selectedLetters.value.indexOf(index);
      if (position === -1) {
        selectedLetters.value.push(index);
      } else {
        selectedLetters.value.splice(position, 1);
      }
    };
    
    const clearWord = () => {
      selectedLetters.value = [];
    };
    
    const submitWord = async () => {
      if (currentWord.value.length < 3) {
        showMessage('Word must be at least 3 letters long', 'error');
        return;
      }
      
      try {
        // Get CSRF token from meta tag
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
        
        if (!csrfToken) {
          showMessage('Security token missing. Please refresh the page.', 'error');
          return;
        }
        
        const response = await fetch(route('games.word-scramble.api.submit'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
          body: JSON.stringify({
            word: currentWord.value,
          }),
        });
        
        if (!response.ok) {
          if (response.status === 419) {
            showMessage('Session expired. Please refresh the page.', 'error');
            // Automatically refresh after a short delay
            setTimeout(() => {
              window.location.reload();
            }, 2000);
            return;
          }
          if (response.status === 401) {
            showMessage('Starting guest session...', 'info');
            // The controller should handle guest token creation, so try again after a short delay
            setTimeout(() => {
              submitWord();
            }, 1000);
            return;
          }
          if (response.status === 403) {
            showMessage('Access denied. Please try refreshing the page.', 'error');
            return;
          }
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
          showMessage(`Found "${data.word}" for ${data.score} points!`, 'success');
          foundWords.value.push({
            word: data.word,
            score: data.score,
          });
          totalScore.value = data.total_score;
          clearWord();
          
          // Update streak if available
          if (data.streak) {
            streak.value = {
              current: data.streak.current,
              longest: data.streak.longest,
            };
          }
          
          // Reload leaderboard
          loadLeaderboard();
        } else {
          showMessage(data.message, 'error');
        }
      } catch (error) {
        console.error('Error submitting word:', error);
        showMessage('An error occurred while submitting the word', 'error');
      }
    };
    
    const loadSubmissions = async () => {
      try {
        const response = await fetch(route('games.word-scramble.api.submissions'), {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });
        
        if (!response.ok) {
          if (response.status === 401) {
            // User not authenticated, skip loading submissions
            return;
          }
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
          foundWords.value = data.submissions.map(submission => ({
            word: submission.word,
            score: submission.score,
          }));
          totalScore.value = data.total_score;
        }
      } catch (error) {
        console.error('Error loading submissions:', error);
      }
    };
    
    const loadLeaderboard = async () => {
      try {
        const response = await fetch(route('games.word-scramble.api.leaderboard.daily'), {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        leaderboard.value = data;
      } catch (error) {
        console.error('Error loading leaderboard:', error);
      }
    };
    
    const loadStreak = async () => {
      try {
        const response = await fetch(route('api.streaks.get', { game: 'word-scramble' }), {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });
        
        if (!response.ok) {
          if (response.status === 401) {
            // User not authenticated, skip loading streak
            return;
          }
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
          streak.value = {
            current: data.current_streak,
            longest: data.longest_streak,
            willBreakTomorrow: data.will_break_tomorrow,
          };
        }
      } catch (error) {
        console.error('Error loading streak:', error);
      }
    };
    
    const showMessage = (text, type) => {
      message.value = text;
      if (type === 'success') {
        messageClass.value = 'bg-green-100 text-green-800';
      } else if (type === 'info') {
        messageClass.value = 'bg-blue-100 text-blue-800';
      } else {
        messageClass.value = 'bg-red-100 text-red-800';
      }
      
      // Clear message after 3 seconds
      setTimeout(() => {
        message.value = '';
      }, 3000);
    };
    
    return {
      letters,
      selectedLetters,
      currentWord,
      foundWords,
      totalScore,
      message,
      messageClass,
      leaderboard,
      streak,
      shuffleLetters,
      toggleLetter,
      clearWord,
      submitWord,
    };
  },
};
</script>