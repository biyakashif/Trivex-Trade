<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useCryptoStore } from '@/Store/crypto.js';
import Chart from 'chart.js/auto';
import Header from '@/Components/Header.vue';

// Crypto store for managing selected currency
const cryptoStore = useCryptoStore();
const showCryptoList = ref(false);
const showMore = ref(false);

// Fetch balance, admin messages, and flash messages from props
const { props } = usePage();
const balance = ref(props.balance || { usdt_balance: 0, btc_balance: 0, eth_balance: 0 });
const adminMessages = ref(props.adminMessages || []);
const flash = props.flash || {};

// Total balance in USD
const totalBalanceUSD = computed(() => {
  const usdt = Number(balance.value.usdt_balance) || 0;
  const btcPrice = Number(cryptoStore.getPrice('btc')) || 0;
  const ethPrice = Number(cryptoStore.getPrice('eth')) || 0;
  const btc = (Number(balance.value.btc_balance) || 0) * btcPrice;
  const eth = (Number(balance.value.eth_balance) || 0) * ethPrice;
  return usdt + btc + eth;
});

// Total balance in the selected currency
const totalBalance = computed(() => {
  const usdValue = totalBalanceUSD.value;
  const selectedCurrency = cryptoStore.currency || 'USD'; // Fallback to USD
  const btcPrice = Number(cryptoStore.getPrice('btc')) || 0;
  const ethPrice = Number(cryptoStore.getPrice('eth')) || 0;

  if (selectedCurrency === 'USD') {
    return usdValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  } else if (selectedCurrency === 'USDT') {
    return (Number(balance.value.usdt_balance) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  } else if (selectedCurrency === 'BTC') {
    return (Number(balance.value.btc_balance) || 0).toLocaleString('en-US', { minimumFractionDigits: 8, maximumFractionDigits: 8 });
  } else if (selectedCurrency === 'ETH') {
    return (Number(balance.value.eth_balance) || 0).toLocaleString('en-US', { minimumFractionDigits: 8, maximumFractionDigits: 8 });
  }
  return usdValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); // Fallback to USD
});

// Live balance update
let balanceInterval = null;
const balanceController = ref(null);

async function fetchLiveBalances() {
  if (balanceController.value) balanceController.value.abort(); // Abort previous request
  balanceController.value = new AbortController();
  try {
    const response = await fetch('/live-balances', {
      signal: balanceController.value.signal,
    });
    const data = await response.json();
    balance.value = {
      usdt_balance: data.usdt_balance,
      eth_balance: data.eth_balance,
      btc_balance: data.btc_balance,
    };
  } catch (error) {
    if (error.name !== 'AbortError') {
      console.error('Error fetching live balances:', error);
    }
  }
}

const allSymbols = [
  'BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'XRP', 'ADA', 'DOGE',
  'TRX', 'AVAX', 'SHIB', 'LINK', 'DOT', 'BCH', 'NEAR',
  'LTC', 'MATIC', 'UNI', 'ICP', 'PEPE'
];

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

const displayedCryptoData = computed(() => {
  return showMore.value ? cryptoData.value : cryptoData.value.slice(0, 5);
});

function goToTradeView(symbol) {
  router.visit(route('vendor.trade.view', { symbol: symbol.toLowerCase() }));
}

const newsData = ref([]);
const newsIndex = ref(0);
const currentNews = ref(null);
let newsInterval = null;
const newsController = ref(null);

async function fetchCryptoNews() {
  if (newsController.value) newsController.value.abort(); // Abort previous request
  newsController.value = new AbortController();
  try {
    const response = await fetch('https://min-api.cryptocompare.com/data/v2/news/?lang=EN', {
      signal: newsController.value.signal,
    });
    const data = await response.json();
    if (data.Data && data.Data.length > 0) {
      newsData.value = data.Data;
      displayNews();
    } else {
      console.error('No news data received.');
    }
  } catch (error) {
    if (error.name !== 'AbortError') {
      console.error('Error fetching news:', error);
    }
  }
}

function displayNews() {
  if (newsData.value.length === 0) return;
  const news = newsData.value[newsIndex.value];
  currentNews.value = news;
  newsIndex.value = (newsIndex.value + 1) % newsData.value.length;
}

// Admin message handling
const messageIndex = ref(0);
const currentMessage = ref(null);
let messageInterval = null;
let messagePollInterval = null;
const messageController = ref(null);

async function fetchAdminMessages() {
  if (messageController.value) messageController.value.abort(); // Abort previous request
  messageController.value = new AbortController();
  try {
    const response = await fetch('/admin-messages', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      signal: messageController.value.signal,
    });
    const data = await response.json();
    adminMessages.value = data || [];
    // Reset the message index if the current index is out of bounds
    if (messageIndex.value >= adminMessages.value.length) {
      messageIndex.value = 0;
      displayMessage();
    }
  } catch (error) {
    if (error.name !== 'AbortError') {
      console.error('Error fetching admin messages:', error);
    }
  }
}

function displayMessage() {
  if (adminMessages.value.length === 0) return;
  currentMessage.value = adminMessages.value[messageIndex.value];
  messageIndex.value = (messageIndex.value + 1) % adminMessages.value.length;
}

// Chart Data Simulation
const selectedCrypto = ref('btc'); // Default to BTC
const chartData = computed(() => {
  const symbol = selectedCrypto.value.toLowerCase();
  const price = Number(cryptoStore.getPrice(symbol)) || 0;
  const change = Number(cryptoStore.getChange(symbol)) || 0;
  const basePrice = price - (change / 100) * price * 7; // Simulate 7 days back
  return {
    labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
    datasets: [{
      label: `${selectedCrypto.value.toUpperCase()} Price Trend`,
      data: Array.from({ length: 7 }, (_, i) => basePrice + (change / 100) * price * i),
      borderColor: '#22c55e',
      backgroundColor: 'rgba(34, 197, 94, 0.2)',
      fill: true,
      tension: 0.4,
    }],
  };
});

let chartInstance = null;
onMounted(() => {
  cryptoStore.startAutoRefresh();
  fetchCryptoNews();
  newsInterval = setInterval(fetchCryptoNews, 5000); // Update news every 5 seconds
  updateCryptoData();
  setInterval(updateCryptoData, 1000);
  fetchLiveBalances();
  balanceInterval = setInterval(fetchLiveBalances, 10000);
  displayMessage();
  messageInterval = setInterval(displayMessage, 5000);
  fetchAdminMessages();
  messagePollInterval = setInterval(fetchAdminMessages, 30000);

  // Initialize the chart
  const ctx = document.getElementById('cryptoChart')?.getContext('2d');
  if (ctx) {
    chartInstance = new Chart(ctx, {
      type: 'line',
      data: chartData.value,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: false,
            ticks: { color: '#ffffff', font: { size: 12 } },
            grid: { color: 'rgba(255, 255, 255, 0.1)' },
          },
          x: {
            ticks: { color: '#ffffff', font: { size: 12 } },
            grid: { color: 'rgba(255, 255, 255, 0.1)' },
          },
        },
        plugins: {
          legend: { labels: { color: '#ffffff' } },
        },
      },
    });
  } else {
    console.error('Canvas context for cryptoChart not found');
  }
});

// Watch chartData for live updates
watch(chartData, (newChartData) => {
  if (chartInstance && chartInstance.data) {
    chartInstance.data = newChartData;
    chartInstance.update(); // Update the chart with new data
  }
}, { deep: true });

onUnmounted(() => {
  if (newsInterval) clearInterval(newsInterval);
  if (balanceInterval) clearInterval(balanceInterval);
  if (messageInterval) clearInterval(messageInterval);
  if (messagePollInterval) clearInterval(messagePollInterval);
  if (balanceController.value) balanceController.value.abort();
  if (newsController.value) newsController.value.abort();
  if (messageController.value) messageController.value.abort();
  if (chartInstance) chartInstance.destroy();
});
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <Header :auth="true" />
    </template>

    <div class="bg-black text-white min-h-screen">
      <!-- Flash Messages -->
      <div class="px-4 pt-4" v-if="flash.success || flash.error">
        <div
          v-if="flash.success"
          class="bg-green-900 border border-green-600 text-white px-4 py-3 rounded mb-4"
          role="alert"
        >
          {{ flash.success }}
        </div>
        <div
          v-if="flash.error"
          class="bg-red-900 border border-red-600 text-white px-4 py-3 rounded mb-4"
          role="alert"
        >
          {{ flash.error }}
        </div>
      </div>

      <!-- Balance Section -->
      <div class="px-4 py-2 relative" style="position: relative; z-index: 10;">
        <div class="bg-black rounded-xl shadow-md p-5 text-white border border-gray-800">
          <p class="text-xs opacity-75">Est total value</p>
          <div class="flex items-center justify-between">
            <p class="text-2xl font-bold">{{ totalBalance }}</p>
            <button
              @click.stop="showCryptoList = !showCryptoList; console.log('Toggle:', showCryptoList)"
              class="flex items-center bg-black border border-gray-700 hover:bg-gray-900 rounded-md px-2 py-1 text-sm font-semibold text-white transition-colors pointer-events-auto"
            >
              {{ cryptoStore.currency || 'USD' }}
              <svg
                class="w-4 h-4 ml-1"
                :class="{ 'rotate-180': showCryptoList }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </button>
          </div>
          <!-- Crypto List Toggle -->
          <div
            v-if="showCryptoList"
            class="absolute right-0 top-full mt-1 bg-black shadow-lg rounded-md p-2 z-1000 w-32 transition-all duration-200 transform border border-gray-700"
            :class="{ 'opacity-100 scale-100': showCryptoList, 'opacity-0 scale-95': !showCryptoList }"
          >
            <button
              v-for="crypto in ['USD', 'USDT', 'ETH', 'BTC']"
              :key="crypto"
              @click.stop="() => { console.log('Selected:', crypto); cryptoStore.setCurrency(crypto); showCryptoList = false; }"
              class="block px-4 py-2 bg-black text-white hover:bg-gray-900 rounded w-full text-left text-sm pointer-events-auto border border-gray-700"
            >
              {{ crypto }}
            </button>
          </div>
        </div>
      </div>

      <!-- Combined Messages and News Section -->
      <section class="px-4 pt-4">
        <div class="space-y-4">

          <!-- <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-md p-4 text-white h-32 overflow-hidden relative">
            <h2 class="text-lg font-bold mb-2">Announcements</h2>
            <div class="relative w-full h-16">
              <transition-group
                name="fade"
                tag="div"
                class="absolute inset-0 flex items-center"
              >
                <div
                  v-if="currentMessage"
                  :key="messageIndex"
                  class="absolute inset-0 flex items-center"
                >
                  <p class="text-sm line-clamp-2">{{ currentMessage.message }}</p>
                </div>
              </transition-group>
            </div>
          </div> -->
          <!-- News -->
          <div class="bg-black rounded-xl shadow-md p-4 text-white h-32 overflow-hidden relative border border-gray-800">
            <h2 class="text-lg font-bold mb-2">Crypto News</h2>
            <div class="relative w-full h-16">
              <transition-group
                name="fade"
                tag="div"
                class="absolute inset-0"
              >
                <div
                  v-if="currentNews"
                  :key="newsIndex"
                  class="absolute inset-0"
                >
                  <h4 class="text-sm font-semibold truncate text-blue-400">{{ currentNews.title }}</h4>
                  <p class="text-xs line-clamp-2 text-gray-400">{{ currentNews.body.substring(0, 150) }}...</p>
                </div>
              </transition-group>
            </div>
          </div>
          <!-- Chart Card -->
          <div class="bg-black rounded-xl shadow-md p-4 text-white h-64 overflow-hidden relative border border-gray-800">
            <h2 class="text-lg font-bold mb-2">Crypto Price Chart</h2>
            <div class="flex space-x-2 mb-2">
              <select
                v-model="selectedCrypto"
                class="p-2 rounded-md bg-black text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-400"
              >
                <option v-for="symbol in allSymbols" :key="symbol" :value="symbol.toLowerCase()" class="bg-black text-white">
                  {{ symbol }}
                </option>
              </select>
            </div>
            <div class="relative w-full h-40">
              <canvas id="cryptoChart" class="w-full h-full"></canvas>
            </div>
          </div>
        </div>
      </section>

      <!-- Crypto Table: Mobile (<lg) -->
      <div class="lg:hidden px-4 pt-2">
        <div class="bg-black border border-gray-800 shadow-sm rounded-lg p-4">
          <h4 class="text-2xl font-bold text-white mb-2">Recent Trading Activities</h4>
          <p class="text-gray-400 mb-4">Live updates for all cryptocurrencies.</p>
          <div class="space-y-2">
            <div class="flex justify-between text-gray-400 text-sm font-medium">
              <span>Name</span>
              <span class="text-center w-24">Last price</span>
              <span class="text-center w-16">Change</span>
            </div>
            <div
              v-for="item in displayedCryptoData"
              :key="item.symbol"
              @click="goToTradeView(item.symbol)"
              class="flex justify-between items-center py-1 cursor-pointer hover:bg-gray-900 transition-all duration-200"
            >
              <div class="flex items-center">
                <img
                  v-if="cryptoStore.getIcon(item.symbol.toLowerCase())"
                  :src="cryptoStore.getIcon(item.symbol.toLowerCase())"
                  :alt="`${item.symbol} icon`"
                  class="w-5 h-5 mr-2"
                />
                <span class="text-white">{{ item.name }}</span>
              </div>
              <span class="text-center w-24 text-white">
                {{ item.lastPrice ? Number(item.lastPrice).toLocaleString('en-US', { minimumFractionDigits: 2 }) : 'N/A' }}
              </span>
              <span
                class="text-center w-16 px-1 text-sm"
                :class="item.change >= 0 ? 'text-green-400' : 'text-red-500'"
              >
                {{ item.change >= 0 ? '+' : '' }}{{ item.change }}%
              </span>
            </div>
            <div class="text-center mt-4">
              <button
                @click="showMore = !showMore"
                class="text-blue-400 hover:underline font-medium"
              >
                {{ showMore ? 'Show Less' : 'Show More' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Crypto Table: Large Screen (lg and above) -->
  <section class="hidden lg:block mt-6 py-6 px-4 md:px-8 rounded-lg shadow-sm bg-black border border-gray-800">
        <div class="container mx-auto">
          <div class="w-full">
            <div class="bg-transparent border-0">
              <div class="flex flex-wrap justify-between items-center border-b border-gray-200 pb-4">
                <div>
                  <h4 class="text-2xl font-bold text-white mb-2">Recent Trading Activities</h4>
                  <p class="text-gray-400 mb-0">Live updates for all cryptocurrencies.</p>
                </div>
              </div>
              <div class="pt-4">
                <div class="overflow-x-auto">
                  <table class="w-full text-left text-white">
                    <thead class="border-b border-gray-200">
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
                        v-for="(item, index) in displayedCryptoData"
                        :key="item.symbol"
                        @click="goToTradeView(item.symbol)"
                        class="border-b border-gray-700 cursor-pointer hover:bg-gray-900 transition-all duration-200"
                        :class="{ 'bg-gray-900': index % 2 === 1 }"
                      >
                        <td class="p-3 flex items-center">
                          <img
                            v-if="cryptoStore.getIcon(item.symbol.toLowerCase())"
                            :src="cryptoStore.getIcon(item.symbol.toLowerCase())"
                            :alt="`${item.symbol} icon`"
                            class="w-6 h-6 mr-2"
                          />
                          <span class="text-white">{{ item.name }}</span>
                        </td>
                        <td class="p-3 text-white">${{ item.lastPrice ? Number(item.lastPrice).toLocaleString('en-US', { minimumFractionDigits: 2 }) : 'N/A' }}</td>
                        <td class="p-3" :class="item.change >= 0 ? 'text-green-400' : 'text-red-500'">
                          {{ item.change >= 0 ? '+' : '' }}{{ item.change }}%
                        </td>
                        <td class="p-3 text-white">${{ item.marketCap ? Number(item.marketCap).toLocaleString() : 'N/A' }}</td>
                        <td class="p-3 text-white">${{ item.volume ? Number(item.volume).toLocaleString() : 'N/A' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="text-center mt-4">
                  <button
                    @click="showMore = !showMore"
                    class="text-blue-400 hover:underline font-medium"
                  >
                    {{ showMore ? 'Show Less' : 'Show More' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.bg-black,
.bg-black.bg-opacity-95,
.bg-transparent,
section.bg-black,
main,
.container,
.lg\:block.bg-black {
  background-color: #181A20 !important;
}

.text-white {
  color: #fff !important;
}

/* Fade Transition for Messages and News */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 1s ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>