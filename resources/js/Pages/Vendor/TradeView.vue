<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { useCryptoStore } from '@/Store/crypto';
import { ChartBarIcon, CalculatorIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  symbol: { type: String, required: false },
  trade: { type: Object, default: null },
  lossApplied: { type: Boolean, default: false },
});
const cryptoStore = useCryptoStore();
const user = usePage().props.auth.user;

// State for modals, form visibility, direction, and trade result
const showOrderForm = ref(false);
const direction = ref('up');
const showResultModal = ref(false);
const tradeResult = ref(null);
const timeLeft = ref(0);
const lossApplied = ref(props.lossApplied);
const errorMessage = ref(''); // Added for server-side error messages

// Selected symbol state (default to BTC)
const currentSymbol = ref((props.symbol && props.symbol.toUpperCase()) || 'BTC');

// List of available symbols (populated from store)
const availableCryptos = ref([]);
const dropdownOpen = ref(false);

// Timer state for animation
const initialTime = ref(0);

// Form state
const deliveryTime = ref(60);
const purchaseAmount = ref(0); // Changed default to 100
const availableAssets = ref(0);

// Profit percentages and minimum amounts
const profitPercentages = {
  30: 0.14,
  60: 0.30,
  90: 0.50,
  120: 0.70,
  300: 0.90,
};

const minimumAmounts = {
  30: 10,
  60: 3000,
  90: 10000,
  120: 30000,
  300: 50000,
};

// Computed properties for form
const priceRange = computed(() => {
  const range = profitPercentages[deliveryTime.value] * 100;
  return range.toFixed(2); // Format to 2 decimal places
});
const expectedReturn = computed(() => {
  const amount = parseFloat(purchaseAmount.value);
  const range = parseFloat(priceRange.value);
  return (amount * (range / 100)).toFixed(2);
});

// Computed properties for market data with fallback values
const marketCap = computed(() => {
  const cap = cryptoStore.getMarketCap((currentSymbol.value || 'BTC').toLowerCase());
  return cap ? `$${Number(cap).toLocaleString()}` : '$0';
});
const volume = computed(() => {
  const vol = cryptoStore.getVolume((currentSymbol.value || 'BTC').toLowerCase());
  return vol ? `$${Number(vol).toLocaleString()}` : '$0';
});
const symbolToTradingView = (symbol) => {
  const upperSymbol = (symbol || currentSymbol.value).toUpperCase();
  // Ensure proper symbol format for TradingView (e.g., BINANCE:BTCUSDT)
  const tradingPairs = {
    'BTC': 'BINANCE:BTCUSDT',
    'ETH': 'BINANCE:ETHUSDT',
    'BNB': 'BINANCE:BNBUSDT',
    'ADA': 'BINANCE:ADAUSDT',
    'XRP': 'BINANCE:XRPUSDT',
    // Add more mappings as needed
  };
  return tradingPairs[upperSymbol] || `BINANCE:${upperSymbol}USDT`;
};
const widgetUrl = computed(() => {
  const tradingViewSymbol = symbolToTradingView(currentSymbol.value);
  return `https://s.tradingview.com/widgetembed/?symbol=${encodeURIComponent(tradingViewSymbol)}&interval=5&theme=Dark&style=1&toolbarbg=f1f3f6&locale=en&utm_source=yourdomain.com&utm_medium=widget_new&utm_campaign=chart`;
});
const currentPrice = computed(() => {
  const price = cryptoStore.getPrice((currentSymbol.value || 'BTC').toLowerCase());
  return price ? parseFloat(price).toFixed(2) : '0.00';
});
const priceChange = computed(() => {
  const change = cryptoStore.getChange((currentSymbol.value || 'BTC').toLowerCase());
  const price = parseFloat(cryptoStore.getPrice((currentSymbol.value || 'BTC').toLowerCase()) || 0);
  if (!change) return '+0.00 (+0.00%)';
  const changeValue = parseFloat(change);
  const changeAmount = (price * changeValue) / 100;
  const changeSign = changeValue >= 0 ? '+' : '';
  return `${changeSign}${changeAmount.toFixed(2)} (${changeSign}${changeValue}%)`;
});

const fetchAvailableAssets = async () => {
  try {
    const response = await fetch('/current-balance', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
    });
    if (!response.ok) throw new Error('Failed to fetch balance');
    const data = await response.json();
    availableAssets.value = parseFloat(data.current_balance).toFixed(2);
  } catch (error) {
    errorMessage.value = 'Failed to fetch balance. Please try again.';
    availableAssets.value = '0.00';
  }
};

const btcPrice = ref('0.00');
const fetchBitcoinPrice = async () => {
  try {
    const response = await fetch('https://api.coinbase.com/v2/exchange-rates?currency=BTC');
    if (!response.ok) throw new Error('Failed to fetch Bitcoin price');
    const data = await response.json();
    btcPrice.value = parseFloat(data.data.rates.USDT).toFixed(2);
  } catch (error) {
    errorMessage.value = 'Failed to fetch Bitcoin price. Using last known value.';
    btcPrice.value = btcPrice.value || '0.00';
  }
};

// Fetch loss status
const fetchLossStatus = async () => {
  try {
    const response = await fetch('/check-loss-status', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
    });
    if (!response.ok) throw new Error('Failed to fetch loss status');
    const data = await response.json();
    lossApplied.value = data.lossApplied;
  } catch (error) {
    errorMessage.value = 'Failed to fetch loss status. Assuming no loss applied.';
    lossApplied.value = false;
  }
};

// Poll for loss status updates during an active trade
let pollingInterval = null;
const startPolling = () => {
  pollingInterval = setInterval(async () => {
    await fetchLossStatus();
    if (tradeResult.value) {
      tradeResult.value.lossApplied = lossApplied.value;
    }
  }, 3000);
};
const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
};

const toggleOrderForm = () => {
  showOrderForm.value = !showOrderForm.value;
  errorMessage.value = ''; // Clear error message when toggling the form
};

const selectCoin = (symbol) => {
  currentSymbol.value = String(symbol).toUpperCase();
  dropdownOpen.value = false;
};

const setDirection = (value) => {
  direction.value = value;
  errorMessage.value = ''; // Clear error message when changing direction
};

const submitOrder = async () => {
  errorMessage.value = ''; // Clear previous error message

  const amount = parseFloat(purchaseAmount.value);
  const minAmount = minimumAmounts[deliveryTime.value];

  if (isNaN(amount) || amount < minAmount) {
    errorMessage.value = `Trade amount must be at least ${minAmount} USDT`;
    return;
  }

  if (amount > parseFloat(availableAssets.value)) {
    errorMessage.value = 'Insufficient balance to start the trade';
    return;
  }

  router.post('/trade/store', {
    symbol: currentSymbol.value,
    direction: direction.value,
    delivery_time: deliveryTime.value,
    trade_amount: amount,
    trade_profit: parseFloat(expectedReturn.value),
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      tradeResult.value = page.props.trade;
      tradeResult.value.lossApplied = page.props.lossApplied;
      showOrderForm.value = false;
      startTimer();
    },
    onError: (errors) => {
      errorMessage.value = errors.error || 'Failed to submit trade';
    },
  });
};

const startTimer = () => {
  timeLeft.value = deliveryTime.value;
  initialTime.value = deliveryTime.value; // Store initial time for animation
  showResultModal.value = true;
  startPolling();

  const interval = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value--;
    } else {
      clearInterval(interval);
      stopPolling();
      fetchAvailableAssets(); // Refresh balance after trade
    }
  }, 1000);
};

const closeResultModal = () => {
  showResultModal.value = false;
  stopPolling();
};

onMounted(async () => {
  await fetchAvailableAssets();
  await fetchBitcoinPrice();
  await fetchLossStatus();
  cryptoStore.fetchTop10CryptoData();
  cryptoStore.startAutoRefresh(); // This will keep the data updated every 60 seconds
  setInterval(fetchBitcoinPrice, 30000);

  // Populate available cryptos from store
  const populateCryptos = () => {
    const keys = Object.keys(cryptoStore.prices);
    if (keys.length) {
      availableCryptos.value = keys.map(k => ({ symbol: k.toUpperCase(), icon: cryptoStore.getIcon(k) }));
    } else {
      // fallback list
      availableCryptos.value = ['BTC','ETH','BNB','ADA','XRP'].map(s => ({ symbol: s, icon: cryptoStore.getIcon(s.toLowerCase()) }));
    }
  };

  populateCryptos();
  // watch store changes and repopulate
  watch(() => cryptoStore.prices, populateCryptos, { deep: true });

  // keep BTC as default selection but do not auto-open the order form

  // If trade prop is provided (e.g., after successful trade), show result modal
  if (props.trade) {
    tradeResult.value = props.trade;
    tradeResult.value.lossApplied = props.lossApplied;
    showOrderForm.value = false;
    startTimer();
  }
});

onUnmounted(() => {
  stopPolling();
});

// Computed property for the stroke-dashoffset
const strokeDashoffset = computed(() => {
  const circumference = 2 * Math.PI * 90; // Radius is 90
  const progress = timeLeft.value / initialTime.value;
  return circumference * (1 - progress);
});

const tradePriceRange = computed(() => {
  if (!tradeResult.value || !tradeResult.value.delivery_time) return '0.00';
  const perc = profitPercentages[tradeResult.value.delivery_time];
  if (!perc) return '0.00';
  const range = perc * 100;
  return range.toFixed(2);
});

// Computed property to detect large screens
const $screenIsLarge = computed(() => window.innerWidth >= 1024);
</script>

<template>
  <Head :title="`${currentSymbol.toUpperCase()} Trade`" />
  <AuthenticatedLayout>
    <template #header></template>
    <div class="h-screen flex flex-col bg-[#181A20]">
      <!-- Responsive wrapper: full screen for large screens, original for small screens -->
      <div
        class="flex flex-col flex-1 w-full px-2 sm:px-4 lg:px-6"
        :class="{'max-w-4xl mx-auto': !$screenIsLarge}"
        style="height: 100vh;"
      >
        <div
          class="bg-[#181A20] rounded-lg shadow flex flex-col flex-1 overflow-hidden border border-gray-800"
          style="height: 100%;"
        >
          <div class="p-2 sm:p-4 flex flex-col flex-1 text-white">
            <div class="flex justify-between items-center mb-1 sm:mb-2">
              <div>
                <!-- Custom listbox dropdown with icons and /USDT suffix -->
                <div class="relative mt-1">
                  <button
                    @click="dropdownOpen = !dropdownOpen"
                    type="button"
                    class="w-full flex items-center justify-between px-3 py-2 border rounded-md bg-[#23262F] text-white border-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-haspopup="listbox"
                    :aria-expanded="dropdownOpen ? 'true' : 'false'"
                  >
                    <div class="flex items-center space-x-2">
                      <img :src="cryptoStore.getIcon((currentSymbol.value||'BTC').toLowerCase()) || 'https://via.placeholder.com/24'" :alt="currentSymbol" class="h-5 w-5 sm:h-6 sm:w-6 rounded-full" />
                      <span class="font-semibold text-sm sm:text-base">{{ currentSymbol.toUpperCase() }}</span>
                      <span class="text-gray-400 text-xs sm:text-sm">/USDT</span>
                    </div>
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>

                  <ul
                    v-if="dropdownOpen"
                    tabindex="-1"
                    role="listbox"
                    class="absolute z-20 mt-1 w-full bg-[#23262F] border-none rounded-md shadow-lg max-h-48 overflow-auto"
                  >
                    <li
                      v-for="coin in availableCryptos"
                      :key="coin.symbol"
                      role="option"
                      @click="selectCoin(coin.symbol)"
                      class="flex items-center px-2 sm:px-3 py-1 sm:py-2 hover:bg-[#f3f4f6] hover:text-[#181A20] cursor-pointer text-white transition-colors"
                    >
                      <img :src="coin.icon || 'https://via.placeholder.com/24'" class="h-4 w-4 sm:h-5 sm:w-5 rounded-full mr-2 sm:mr-3" :alt="coin.symbol" />
                      <span class="flex-1 text-sm sm:text-base">{{ coin.symbol }}</span>
                      <span class="text-gray-400 text-xs sm:text-sm">/USDT</span>
                    </li>
                  </ul>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="text-sm sm:text-base font-bold text-white">
                    USTD {{ currentPrice }}
                  </span>
                  <span :class="priceChange.includes('-') ? 'text-red-500' : 'text-green-400'" class="text-[10px] sm:text-xs">
                    {{ priceChange }}
                  </span>
                </div>
              </div>
            </div>
            <div class="w-full mb-3 sm:mb-6 flex flex-col">
              <div class="h-80 sm:h-96 md:h-[28rem] lg:h-[28rem] flex">
                <iframe
                  id="tradingview_chart"
                  :src="widgetUrl"
                  class="w-full h-full rounded-md"
                  allowtransparency="true"
                  scrolling="no"
                  allowfullscreen="true"
                  style="min-height:10px;"
                ></iframe>
              </div>
            </div>
            <div class="bg-[#23262F] rounded-lg p-2 sm:p-4 mb-2 sm:mb-4 shadow-sm border border-gray-800">
              <h4 class="text-xs sm:text-sm font-semibold text-white mb-1 sm:mb-2">Market Statistics</h4>
              <div class="flex items-center justify-between mb-1 sm:mb-2">
                <div class="flex items-center space-x-1 sm:space-x-2 text-white">
                  <span class="text-[10px] sm:text-xs">Market Outlook</span>
                </div>
                <div class="flex space-x-1 sm:space-x-3">
                  <span class="text-green-400 font-semibold text-[10px] sm:text-sm">+{{ priceChange.replace('-', '') }}</span>
                  <span class="text-red-500 font-semibold text-[10px] sm:text-sm">-{{ priceChange.replace('+', '') }}</span>
                </div>
              </div>
              <div class="flex items-center justify-between mb-1 sm:mb-2">
                <div class="flex items-center space-x-1 sm:space-x-2 text-white">
                  <ChartBarIcon class="h-3 w-3 sm:h-4 sm:w-4 text-blue-400" />
                  <span class="text-[10px] sm:text-xs">24 hours</span>
                </div>
                <div class="text-white font-medium text-[10px] sm:text-sm">
                  USTD {{ volume }}
                </div>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-1 sm:space-x-2 text-white">
                  <CalculatorIcon class="h-3 w-3 sm:h-4 sm:w-4 text-blue-400" />
                  <span class="text-[10px] sm:text-xs">24-hour transaction</span>
                </div>
                <div class="text-white font-medium text-[10px] sm:text-sm">
                  USTD {{ currentPrice }}
                </div>
              </div>
            </div>
            <div>
              <button
                @click="toggleOrderForm"
                class="w-full py-2 sm:py-3 text-white font-semibold rounded-full bg-[#23262F] hover:bg-[#f3f4f6] hover:text-[#181A20] focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 text-xs sm:text-sm"
              >
                Order
              </button>
            </div>
          </div>
        </div>
      </div>
  </div>

    <!-- Order Form Modal -->
    <div v-if="showOrderForm" class="fixed inset-0 bg-[#181A20] bg-opacity-95 flex items-center justify-center z-50">
      <div class="bg-[#23262F] rounded-lg w-full max-w-md p-6 relative border border-gray-800">
          <button @click="toggleOrderForm" class="absolute top-2 right-2 text-gray-400 hover:text-white">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <h3 class="text-lg font-semibold mb-4 text-white">
          {{ currentSymbol.toUpperCase() }}/USDT Delivery
        </h3>
        <!-- Error Message -->
        <div v-if="errorMessage" class="bg-red-900 border-l-4 border-red-500 text-red-200 p-4 mb-4 rounded-r-lg">
          <p>{{ errorMessage }}</p>
        </div>
  <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <img :src="cryptoStore.getIcon((currentSymbol.value||'BTC').toLowerCase()) || 'https://assets.coingecko.com/coins/images/1/large/bitcoin.png'" :alt="currentSymbol" class="h-6 w-6" />
              <span class="font-medium text-white">
                {{ currentSymbol.toUpperCase() }} Coin
                <span :class="direction === 'up' ? 'text-green-400' : 'text-red-500'">
                  Buy {{ direction === 'up' ? 'Up' : 'Fall' }}
                </span>
              </span>
            </div>
            <div class="text-right">
              <span class="text-gray-400">{{ purchaseAmount }} USDT</span>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-white mb-1">Delivery time</label>
            <div class="flex items-center space-x-2">
              <div class="relative">
                <select
                  v-model="deliveryTime"
                  class="appearance-none w-24 pl-8 pr-4 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-black text-white border-gray-700"
                >
                  <option value="30" class="bg-black text-white">30S</option>
                  <option value="60" class="bg-black text-white">60S</option>
                  <option value="90" class="bg-black text-white">90S</option>
                  <option value="120" class="bg-black text-white">120S</option>
                  <option value="300" class="bg-black text-white">300S</option>
                </select>
                <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                  <div class="h-5 w-5 bg-blue-500 rounded-full"></div>
                </div>
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                  <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
              <button
                @click="setDirection('up')"
                :class="direction === 'up' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300'"
                class="px-4 py-2 rounded-l-md text-sm font-medium"
              >
                Up
              </button>
              <button
                @click="setDirection('fall')"
                :class="direction === 'fall' ? 'bg-red-500 text-white' : 'bg-gray-700 text-gray-300'"
                class="px-4 py-2 rounded-r-md text-sm font-medium"
              >
                Fall
              </button>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-white mb-1">Price range</label>
            <div class="relative">
              <select
                class="appearance-none w-full pl-8 pr-4 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-black text-white border-gray-700"
                :value="priceRange"
                disabled
              >
                <option :value="priceRange" class="bg-black text-white">
                  {{ direction === 'up' ? 'Up' : 'Fall' }} (* {{ priceRange }}%)
                </option>
              </select>
              <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                <svg
                  :class="direction === 'up' ? 'text-green-400' : 'text-red-500'"
                  class="h-5 w-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    :d="direction === 'up' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'"
                  />
                </svg>
              </div>
              <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-white mb-1">Purchase amount</label>
            <div class="relative">
              <input
                v-model="purchaseAmount"
                type="number"
                class="w-full pl-8 pr-4 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-black text-white border-gray-700"
              />
              <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
              </div>
              <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16M12 4v16" />
                </svg>
              </div>
            </div>
            <div class="flex justify-between text-xs text-gray-400 mt-1">
              <span>Minimum: {{ minimumAmounts[deliveryTime] }}</span>
              <span>Available assets: {{ availableAssets }}</span>
            </div>
          </div>
          <button
            @click="submitOrder"
            :class="direction === 'up' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600'"
            class="w-full py-3 text-white font-semibold rounded-lg transition duration-300"
          >
            Order
          </button>
        </div>
      </div>
    </div>

    <!-- Trade Result Modal -->
    <div v-if="showResultModal" class="fixed inset-0 bg-[#181A20] bg-opacity-95 flex items-center justify-center z-50 px-2">
      <div class="bg-[#23262F] rounded-xl shadow-md w-full max-w-sm sm:max-w-md p-4 sm:p-6 relative border border-gray-800 overflow-hidden">
        <!-- Subtle dark gradient from bottom-right to center -->
        <svg class="absolute -bottom-16 -right-16 w-[48rem] h-[48rem] pointer-events-none select-none z-0" viewBox="0 0 800 800" fill="none">
          <defs>
            <radialGradient id="modalDarkGrad" cx="50%" cy="50%" r="80%" fx="50%" fy="50%">
              <stop offset="0%" stop-color="#000000" stop-opacity="1" />
              <stop offset="60%" stop-color="#181A20" stop-opacity="0.98" />
              <stop offset="90%" stop-color="#181A20" stop-opacity="0.0" />
            </radialGradient>
          </defs>
          <ellipse cx="400" cy="400" rx="400" ry="400" fill="url(#modalDarkGrad)" />
        </svg>
        <button @click="closeResultModal" class="absolute top-2 right-2 text-gray-400 hover:text-white z-10">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
  <h3 class="text-lg sm:text-xl font-bold text-white mb-4 sm:mb-6 text-center z-10 relative">
          {{ timeLeft > 0 ? 'Waiting for settlement' : 'Trade Details' }}
        </h3>
  <div v-if="timeLeft > 0" class="text-center z-10 relative">
          <div class="relative w-28 h-28 sm:w-32 sm:h-32 mx-auto">
            <svg class="w-full h-full" viewBox="0 0 200 200">
              <!-- Background circle -->
              <circle
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#374151"
                stroke-width="10"
              />
              <!-- Secondary animated pulse circle -->
              <circle
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#22c55e"
                stroke-width="10"
                class="pulse-circle"
                style="stroke-opacity: 0.2;"
              />
              <!-- Animated wave circle (outer layer) -->
              <circle
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#22c55e"
                stroke-width="10"
                class="wave-circle"
                style="stroke-opacity: 0.5;"
              />
              <!-- Progress circle -->
              <circle
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#22c55e"
                stroke-width="10"
                stroke-dasharray="565.48"
                :stroke-dashoffset="strokeDashoffset"
                class="transform -rotate-90 origin-center transition-all duration-1000"
              />
            </svg>
            <!-- Timer text in the center -->
            <div class="absolute inset-0 flex items-center justify-center">
              <span class="text-xl sm:text-2xl font-semibold text-white">{{ timeLeft }} S</span>
            </div>
          </div>
          <p class="mt-4 text-white italic text-sm sm:text-base">Waiting...</p>
        </div>
  <div v-else class="text-center z-10 relative">
          <div class="relative w-28 h-28 sm:w-32 sm:h-32 mx-auto">
            <svg class="w-full h-full" viewBox="0 0 200 200">
              <!-- Background circle -->
              <circle
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#374151"
                stroke-width="10"
              />
              <!-- Secondary animated pulse circle -->
              <circle
                v-if="tradeResult && !tradeResult.lossApplied"
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#22c55e"
                stroke-width="10"
                class="pulse-circle"
                style="stroke-opacity: 0.2;"
              />
              <circle
                v-else
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#ef4444"
                stroke-width="10"
                class="pulse-circle"
                style="stroke-opacity: 0.2;"
              />
              <!-- Static circle (similar to timer) -->
              <circle
                v-if="tradeResult && !tradeResult.lossApplied"
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#22c55e"
                stroke-width="10"
                class="wave-circle"
                style="stroke-opacity: 0.5;"
              />
              <circle
                v-else
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#ef4444"
                stroke-width="10"
                class="wave-circle"
                style="stroke-opacity: 0.5;"
              />
              <!-- Full circle to show result -->
              <circle
                v-if="tradeResult && !tradeResult.lossApplied"
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#22c55e"
                stroke-width="10"
              />
              <circle
                v-else
                cx="100"
                cy="100"
                r="90"
                fill="none"
                stroke="#ef4444"
                stroke-width="10"
              />
            </svg>
            <!-- Profit or Loss text in the center -->
            <div class="absolute inset-0 flex items-center justify-center">
              <span v-if="tradeResult && !tradeResult.lossApplied" class="text-lg sm:text-xl font-bold text-green-400">USDT {{ tradeResult.profit_earned.toFixed(2) }} ({{ tradePriceRange }}%)</span>
              <span v-else class="text-lg sm:text-xl font-bold text-red-500">-USDT {{ tradeResult.trade_amount.toFixed(2) }} ({{ tradePriceRange }}%)</span>
            </div>
          </div>
          <div class="mt-4 bent-card relative bg-gradient-to-br from-[#23262F] via-[#23262F] to-[#181A20] shadow-lg rounded-2xl px-6 py-4 border border-gray-700/70">
            <!-- SVG mask for curved bent effect (modern, more pronounced) -->
            <svg class="absolute bottom-0 right-0 w-20 h-20 pointer-events-none select-none z-10" viewBox="0 0 80 80">
              <path d="M80,80 Q80,40 40,80 Z" fill="#181A20" />
            </svg>
            <ul class="divide-y divide-gray-200/20 text-sm text-white pl-0 sm:pl-1 text-left">
              <li class="flex justify-between items-center py-1 first:pt-0 last:pb-0">
                <span class="text-xs font-normal text-gray-300">Hosted Amount</span>
                <span class="font-medium">${{ tradeResult ? tradeResult.trade_amount.toFixed(2) : '0.00' }}</span>
              </li>
              <li v-if="tradeResult && !tradeResult.lossApplied" class="flex justify-between items-center py-1 first:pt-0 last:pb-0">
                <span class="text-xs font-normal text-gray-300">Total Amount</span>
                <span class="font-medium">${{ tradeResult ? (tradeResult.trade_amount + tradeResult.profit_earned).toFixed(2) : '0.00' }}</span>
              </li>
              <li v-if="tradeResult && !tradeResult.lossApplied" class="flex justify-between items-center py-1 first:pt-0 last:pb-0">
                <span class="text-xs font-normal text-gray-300">Buy Price</span>
                <span class="font-medium">${{ btcPrice }}</span>
              </li>
              <li class="flex justify-between items-center py-1 first:pt-0 last:pb-0">
                <span class="text-xs font-normal text-gray-300">Trade Direction</span>
                <span class="font-medium">{{ tradeResult ? (tradeResult.direction === 'up' ? 'Rise' : 'Fall') : 'N/A' }}</span>
              </li>
              <li class="flex justify-between items-center py-1 first:pt-0 last:pb-0">
                <span class="text-xs font-normal text-gray-300">Price Range</span>
                <span class="font-medium">{{ tradePriceRange }}%</span>
              </li>
              <li v-if="tradeResult && tradeResult.lossApplied" class="flex justify-between items-center py-1 first:pt-0 last:pb-0">
                <span class="text-xs font-normal text-gray-300">Loss Amount</span>
                <span class="font-medium">${{ tradeResult ? tradeResult.trade_amount.toFixed(2) : '0.00' }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.wave-circle {
  animation: wave 2s infinite ease-in-out;
}

.pulse-circle {
  animation: pulse 3s infinite ease-in-out;
}

@keyframes wave {
  0%, 100% {
    stroke-opacity: 0.5;
    transform: scale(1);
  }
  50% {
    stroke-opacity: 0.8;
    transform: scale(1.03);
  }
}

@keyframes pulse {
  0%, 100% {
    stroke-opacity: 0.2;
    transform: scale(1);
  }
  50% {
    stroke-opacity: 0.4;
    transform: scale(1.05);
  }
}

/* Custom bent/cut bottom-right corner for trade details card */
.bent-card {
  position: relative;
  overflow: hidden;
}


</style>
