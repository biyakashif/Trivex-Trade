<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useCryptoStore } from '@/Store/crypto.js';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/solid';

// Crypto store for managing crypto data
const cryptoStore = useCryptoStore();
const showMore = ref(false);
const showHistory = ref(false);
const history = ref([]);
const historyError = ref(null);
const successMessage = ref(null);

// List of cryptocurrency symbols to display
const allSymbols = [
  'BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'XRP', 'ADA', 'DOGE',
  'TRX', 'AVAX', 'SHIB', 'LINK', 'DOT', 'BCH', 'NEAR',
  'LTC', 'MATIC', 'UNI', 'ICP', 'PEPE'
];

// Crypto data for the table
const cryptoData = ref([]);

function updateCryptoData() {
  cryptoData.value = allSymbols.map(symbol => ({
    symbol: symbol,
    name: symbol,
    lastPrice: cryptoStore.getPrice(symbol.toLowerCase()) || 0,
    change: cryptoStore.getChange(symbol.toLowerCase()) || 0,
    marketCap: cryptoStore.getMarketCap(symbol.toLowerCase()) || 0,
    volume: cryptoStore.getVolume(symbol.toLowerCase()) || 0,
  }));
}

// Computed property to limit the number of displayed items
const displayedCryptoData = computed(() => {
  return showMore.value ? cryptoData.value : cryptoData.value.slice(0, 5);
});

// Navigate to the trade view page for a specific symbol
function goToTradeView(symbol) {
  router.visit(route('vendor.trade.view', { symbol: symbol.toLowerCase() }));
}

// Fetch trade history
async function fetchTradeHistory() {
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
}

// Manage the update interval
let updateInterval = null;

// Lifecycle hooks to manage crypto data updates
onMounted(() => {
  updateCryptoData();
  updateInterval = setInterval(updateCryptoData, 1000);
});

onUnmounted(() => {
  if (updateInterval) {
    clearInterval(updateInterval);
    updateInterval = null;
  }
});
</script>

<template>
  <Head title="Trade" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Trade
      </h2>
    </template>

    <div class="text-gray-900">
      <!-- Crypto Table: Mobile (<lg) -->
      <div class="lg:hidden px-4 pt-4">
        <div class="space-y-6">
          <!-- Professional Header Section -->
          <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-gray-800 pb-4">
            <div class="mb-2 sm:mb-0">
              <h4 class="text-2xl font-bold text-gray-900 mb-1">Recent Trading Activities</h4>
              <p class="text-gray-600 text-sm">Live updates for all cryptocurrencies.</p>
            </div>
            <button
              @click="fetchTradeHistory()"
              class="mt-2 sm:mt-0 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors duration-200 flex items-center space-x-2"
            >
              <ClipboardDocumentListIcon class="h-5 w-5" />
              <span class="text-sm font-medium">View History</span>
            </button>
          </header>

          <!-- Table Headers -->
          <div class="flex justify-between text-gray-500 text-sm font-medium">
            <span>Name</span>
            <span class="text-center w-24">Last price</span>
            <span class="text-center w-16">Change</span>
          </div>

          <!-- Crypto Data Rows -->
          <div
            v-for="item in displayedCryptoData"
            :key="item.symbol"
            @click="goToTradeView(item.symbol)"
            class="flex justify-between items-center py-2 cursor-pointer hover:bg-gray-100 hover:shadow-md hover:scale-[1.01] transition-all duration-200"
          >
            <div class="flex items-center">
              <img
                v-if="cryptoStore.getIcon(item.symbol.toLowerCase())"
                :src="cryptoStore.getIcon(item.symbol.toLowerCase())"
                :alt="`${item.symbol} icon`"
                class="w-5 h-5 mr-2"
              />
              <span>{{ item.name }}</span>
            </div>
            <span class="text-center w-24">
              {{ item.lastPrice ? Number(item.lastPrice).toLocaleString('en-US', { minimumFractionDigits: 2 }) : 'N/A' }}
            </span>
            <span
              class="text-center w-16 px-2 py-1 rounded text-sm"
              :class="item.change >= 0 ? 'bg-green-500 text-white' : 'bg-red-500 text-white'"
            >
              {{ item.change >= 0 ? '+' : '' }}{{ item.change }}%
            </span>
          </div>

          <!-- Show More/Less Button -->
          <div class="text-center mt-4">
            <button
              @click="showMore = !showMore"
              class="text-blue-600 hover:underline font-medium"
            >
              {{ showMore ? 'Show Less' : 'Show More' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Crypto Table: Large Screen (lg and above) -->
      <section class="hidden lg:block py-10 bg-gray-100 px-4 md:px-8">
        <div class="container mx-auto">
          <div class="w-full">
            <div class="bg-transparent border-0">
              <div class="flex flex-wrap justify-between items-center border-b border-gray-800 pb-4">
                <div>
                  <h4 class="text-2xl font-bold text-gray-900 mb-2">Recent Trading Activities</h4>
                  <p class="text-gray-600 mb-0">Live updates for all cryptocurrencies.</p>
                </div>
                <button @click="fetchTradeHistory()">
                  <ClipboardDocumentListIcon class="h-6 w-6 text-gray-600 hover:text-gray-800" />
                </button>
              </div>
              <div class="pt-4">
                <div class="overflow-x-auto">
                  <table class="w-full text-left text-gray-900">
                    <thead class="bg-gray-200">
                      <tr>
                        <th class="p-3">Name</th>
                        <th class="p-3">Price</th>
                        <th class="p-3">24h %</th>
                        <th class="p-3">Market Cap</th>
                        <th class="p-3">Volume(24h)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="item in displayedCryptoData"
                        :key="item.symbol"
                        @click="goToTradeView(item.symbol)"
                        class="border-b cursor-pointer hover:bg-gray-100 hover:shadow-md hover:scale-[1.01] transition-all duration-200"
                      >
                        <td class="p-3 flex items-center">
                          <img
                            v-if="cryptoStore.getIcon(item.symbol.toLowerCase())"
                            :src="cryptoStore.getIcon(item.symbol.toLowerCase())"
                            :alt="`${item.symbol} icon`"
                            class="w-6 h-6 mr-2"
                          />
                          {{ item.name }}
                        </td>
                        <td class="p-3">${{ item.lastPrice ? Number(item.lastPrice).toLocaleString('en-US', { minimumFractionDigits: 2 }) : 'N/A' }}</td>
                        <td class="p-3" :class="item.change >= 0 ? 'text-green-600' : 'text-red-600'">
                          {{ item.change >= 0 ? '+' : '' }}{{ item.change }}%
                        </td>
                        <td class="p-3">${{ item.marketCap ? Number(item.marketCap).toLocaleString() : 'N/A' }}</td>
                        <td class="p-3">${{ item.volume ? Number(item.volume).toLocaleString() : 'N/A' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="text-center mt-4">
                  <button
                    @click="showMore = !showMore"
                    class="text-blue-600 hover:underline font-medium"
                  >
                    {{ showMore ? 'Show Less' : 'Show More' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- History Modal -->
      <div v-if="showHistory" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-3 w-11/12 max-w-sm max-h-[80vh] overflow-y-auto">
          <div class="flex justify-between items-center mb-3">
            <h2 class="text-base font-semibold text-gray-800">Trade History</h2>
            <button @click="showHistory = false" class="text-gray-600 hover:text-gray-800">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <!-- Show success message in history modal if open -->
          <div v-if="successMessage" class="mb-2 p-1 bg-green-100 text-green-800 rounded-md text-[10px] text-center">
            {{ successMessage }}
          </div>
          <div v-if="historyError" class="mb-2 p-1 bg-red-100 text-red-800 rounded-md text-[10px] text-center">
            {{ historyError }}
          </div>
          <div v-else-if="history.length === 0" class="text-gray-600 text-[10px] text-center">
            No trade history available.
          </div>
          <div v-else class="space-y-2">
            <div v-for="trade in history" :key="trade.id" class="border border-gray-200 rounded-md p-2 text-[10px]">
              <div class="flex justify-between">
                <span class="text-gray-800 font-medium">{{ trade.symbol.toUpperCase() }}</span>
                <span :class="{
                  'text-yellow-600': trade.status === 'pending',
                  'text-green-600': trade.status === 'completed',
                  'text-red-600': trade.status === 'loss' || trade.status === 'rejected'
                }">
                  {{ trade.status.charAt(0).toUpperCase() + trade.status.slice(1) }}
                </span>
              </div>
              <div class="flex justify-between mt-1">
                <span class="text-gray-600">Amount</span>
                <span class="text-gray-800">{{ trade.trade_amount }} {{ trade.symbol.toUpperCase() }}</span>
              </div>
              <div class="flex justify-between mt-1">
                <span class="text-gray-600">Profit</span>
                <span class="text-gray-800">{{ trade.profit_earned ?? 0 }} {{ trade.symbol.toUpperCase() }}</span>
              </div>
              <div class="flex justify-between mt-1">
                <span class="text-gray-600">Date</span>
                <span class="text-gray-800">{{ new Date(trade.created_at).toLocaleString() }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>