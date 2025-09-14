<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

// Access the authenticated user
const page = usePage();
const user = page.props.auth.user;

// Trade history reactive variables
const showHistory = ref(false);
const history = ref([]);
const historyError = ref(null);
const successMessage = ref(null);

// Function to handle logout
const logout = () => {
  router.post(route('logout'));
};

// Functions to handle profile actions
const editProfile = () => {
  router.get(route('profile.edit'));
};

// Fetch trade history
const fetchTradeHistory = async () => {
  try {
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
      historyError.value = 'CSRF token not found. Please refresh the page.';
      showHistory.value = true;
      return;
    }

    const response = await fetch('/trade/history', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      },
      credentials: 'include', // Ensure session cookies are sent
    });

    console.log('Trade history response:', {
      status: response.status,
      headers: Object.fromEntries(response.headers.entries()),
    });

    if (!response.ok) {
      const responseText = await response.text();
      console.error('Fetch error:', response.status, responseText);
      if (response.status === 401) {
        historyError.value = 'Please log in to view trade history.';
        router.visit(route('login'));
        return;
      } else if (response.status === 403) {
        historyError.value = 'Please verify your email to view trade history.';
        router.visit(route('verification.notice'));
        return;
      } else {
        historyError.value = `Failed to load trade history: ${response.statusText}`;
        showHistory.value = true;
        return;
      }
    }

    // Check if the response is JSON
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      const responseText = await response.text();
      console.error('Non-JSON response:', responseText);
      historyError.value = 'Server returned invalid data. Please try again.';
      showHistory.value = true;
      return;
    }

    const data = await response.json();
    if (data.history) {
      history.value = data.history;
      historyError.value = null;
      successMessage.value = 'Trade history loaded successfully.';
      showHistory.value = true;
    } else {
      historyError.value = 'No trade history found.';
      history.value = [];
      showHistory.value = true;
    }
  } catch (error) {
    console.error('Error fetching trade history:', error);
    historyError.value = 'Failed to load trade history. Please try again.';
    showHistory.value = true;
  }
};
</script>

<template>
  <Head title="Account" />
  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-xl font-bold text-white text-center pt-4">Account</h1>
    </template>

    <div class="py-6 bg-black min-h-screen">
      <div class="px-4 sm:max-w-2xl md:max-w-3xl lg:max-w-4xl mx-auto">
        <!-- User Information Card -->
        <div class="bg-black rounded-xl shadow-lg p-4 mb-4 border border-gray-800">
          <h2 class="text-base font-semibold mb-4 text-white">User Information</h2>
          <div class="space-y-4">
            <div class="flex items-center bg-gray-900 rounded-lg p-3">
              <div class="w-10 h-10 bg-yellow-400 rounded-full flex items-center justify-center mr-3">
                <span class="text-white font-bold">{{ user.name.charAt(0) }}</span>
              </div>
              <div class="flex-1">
                <div class="text-sm font-medium text-white">{{ user.name }}</div>
                <div class="text-xs text-gray-400">{{ user.email }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Profile Actions Cards -->
        <div class="space-y-4">
          <div class="bg-black rounded-xl shadow-lg p-4 border border-gray-800">
            <button
              @click="editProfile"
              class="w-full px-4 py-2 bg-black text-white border border-gray-700 rounded-md text-sm font-medium hover:bg-gray-900 transition-colors duration-200"
            >
              Edit Profile
            </button>
          </div>
          <div class="bg-black rounded-xl shadow-lg p-4 border border-gray-800">
            <button
              @click="fetchTradeHistory"
              class="w-full px-4 py-2 bg-black text-white border border-gray-700 rounded-md text-sm font-medium hover:bg-gray-900 transition-colors duration-200"
            >
              Trade History
            </button>
          </div>
          <div class="bg-black rounded-xl shadow-lg p-4 border border-gray-800">
            <button
              @click="logout"
              class="w-full px-4 py-2 bg-black text-white border border-gray-700 rounded-md text-sm font-medium hover:bg-gray-900 transition-colors duration-200"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- History Modal -->
    <div v-if="showHistory" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50">
      <div class="bg-black rounded-lg p-3 w-11/12 max-w-sm max-h-[80vh] overflow-y-auto border border-gray-800 text-white">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-base font-semibold text-white">Trade History</h2>
          <button @click="showHistory = false" class="text-blue-400 hover:text-blue-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <!-- Show success message in history modal if open -->
        <div v-if="successMessage" class="mb-2 p-1 bg-green-900 text-green-400 rounded-md text-[10px] text-center">
          {{ successMessage }}
        </div>
        <div v-if="historyError" class="mb-2 p-1 bg-red-900 text-red-400 rounded-md text-[10px] text-center">
          {{ historyError }}
        </div>
        <div v-else-if="history.length === 0" class="text-gray-400 text-[10px] text-center">
          No trade history available.
        </div>
        <div v-else class="space-y-2">
          <div v-for="trade in history" :key="trade.id" class="border border-gray-700 rounded-md p-2 text-[10px] bg-gray-900">
            <div class="flex justify-between">
              <span class="text-white font-medium">{{ trade.symbol.toUpperCase() }}</span>
              <span :class="{
                'text-yellow-400': trade.status === 'pending',
                'text-green-400': trade.status === 'completed',
                'text-red-400': trade.status === 'loss' || trade.status === 'rejected'
              }">
                {{ trade.status.charAt(0).toUpperCase() + trade.status.slice(1) }}
              </span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-400">Amount</span>
              <span class="text-white">{{ trade.trade_amount }} {{ trade.symbol.toUpperCase() }}</span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-400">Profit</span>
              <span class="text-white">{{ trade.profit_earned ?? 0 }} {{ trade.symbol.toUpperCase() }}</span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-400">Date</span>
              <span class="text-white">{{ new Date(trade.created_at).toLocaleString() }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>