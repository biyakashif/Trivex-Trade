<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useCryptoStore } from '@/Store/crypto';
import { formatBalance } from '@/utils/formatBalance';

// Access the crypto store
const cryptoStore = useCryptoStore();
const page = usePage();
const pageProps = page.props;

// Define props to receive balances from the backend
const props = defineProps({
  balances: {
    type: Object,
    default: () => ({
      usdt_balance: 0,
      btc_balance: 0,
      eth_balance: 0,
    }),
  },
});

// Make balances reactive for live updates
const liveBalances = ref({
  usdt_balance: props.balances.usdt_balance || 0,
  btc_balance: props.balances.btc_balance || 0,
  eth_balance: props.balances.eth_balance || 0,
});

// Echo listeners
let balanceEchoListener = null;

// Setup Echo listeners for real-time updates
const setupEchoListeners = () => {
  const userId = pageProps.auth?.user?.id;

  if (!userId || !window.Echo) {
    console.warn('User ID or Echo not available for real-time updates');
    return;
  }

  // Balance updates listener
  balanceEchoListener = window.Echo.private(`user.${userId}`)
    .listen('.balance.updated', (data) => {
      console.log('Swap - Balance updated via Echo:', data);
      if (data.balances) {
        liveBalances.value = {
          usdt_balance: data.balances.usdt_balance || 0,
          btc_balance: data.balances.btc_balance || 0,
          eth_balance: data.balances.eth_balance || 0,
        };
      }
    });
};

// List of cryptocurrencies
const cryptos = [
  { symbol: 'usdt', name: 'Tether', balanceKey: 'usdt_balance', decimals: 2 },
  { symbol: 'btc', name: 'Bitcoin', balanceKey: 'btc_balance', decimals: 8 },
  { symbol: 'eth', name: 'Ethereum', balanceKey: 'eth_balance', decimals: 4 },
];

// Reactive state for the swap form
const fromCrypto = ref('usdt');
const toCrypto = ref('btc');
const fromAmount = ref('');
const toAmount = ref('');

// Error and success messages
const errorMessage = ref('');
const successMessage = ref('');

// Ensure crypto data is fetched when the component mounts
onMounted(() => {
  cryptoStore.startAutoRefresh(); // Start fetching live prices
  
  // Set up Echo listeners for real-time updates with delay
  setTimeout(() => {
    setupEchoListeners();
  }, 100);
});

// Cleanup on unmount
onUnmounted(() => {
  // Clean up Echo listeners
  if (balanceEchoListener) {
    balanceEchoListener.stopListening('.balance.updated');
  }

  // Leave the private channel
  const userId = pageProps.auth?.user?.id;
  if (userId && window.Echo) {
    window.Echo.leave(`user.${userId}`);
  }
});

// Computed property to get the remaining balance after swapping
const remainingBalance = computed(() => {
  const crypto = cryptos.find(c => c.symbol === fromCrypto.value);
  if (!crypto) return '0.00';
  
  const balanceKey = crypto.balanceKey;
  const currentBalance = Number(liveBalances.value[balanceKey]) || 0;
  const amountToSwap = parseFloat(fromAmount.value) || 0;
  const remaining = currentBalance - amountToSwap;
  const decimals = crypto.decimals;
  return remaining >= 0 ? formatBalance(remaining, decimals) : formatBalance(0, decimals);
});

// Computed property to get the live rate
const liveRate = computed(() => {
  const fromSymbol = fromCrypto.value;
  const toSymbol = toCrypto.value;
  if (!fromSymbol || !toSymbol) return 0;
  
  const fromPrice = Number(cryptoStore.getPrice(fromSymbol)) || 0;
  const toPrice = Number(cryptoStore.getPrice(toSymbol)) || 0;
  if (fromPrice === 0 || toPrice === 0) return 0;
  return fromPrice / toPrice;
});

// Computed property to calculate the amount to receive
const calculatedToAmount = computed(() => {
  const amount = parseFloat(fromAmount.value) || 0;
  const rate = liveRate.value;
  const result = amount * rate;
  if (isNaN(result)) return '';
  
  const crypto = cryptos.find(c => c.symbol === toCrypto.value);
  const decimals = crypto ? crypto.decimals : 2;
  return result.toFixed(decimals);
});

// Computed property for "To" balance (safe like DepositDetails.vue)
const exchangeToBalance = computed(() => {
  const crypto = cryptos.find(c => c.symbol === toCrypto.value);
  if (!crypto) return '0.00';
  const balance = Number(liveBalances.value[crypto.balanceKey]) || 0;
  return formatBalance(balance, crypto.decimals);
});

// Computed property for "From" balance (safe like DepositDetails.vue)
const exchangeFromBalance = computed(() => {
  const crypto = cryptos.find(c => c.symbol === fromCrypto.value);
  if (!crypto) return '0.00';
  const balance = Number(liveBalances.value[crypto.balanceKey]) || 0;
  return formatBalance(balance, crypto.decimals);
});

// Update toAmount when fromAmount changes
const updateToAmount = () => {
  toAmount.value = calculatedToAmount.value;
};

// Set the input amount to the maximum available balance
const setMaxAmount = () => {
  const crypto = cryptos.find(c => c.symbol === fromCrypto.value);
  if (!crypto) return;
  
  const balanceKey = crypto.balanceKey;
  const maxBalance = Number(liveBalances.value[balanceKey]) || 0;
  fromAmount.value = maxBalance.toString();
  updateToAmount();
};

// Swap the from and to cryptocurrencies
const swapCurrencies = () => {
  const temp = fromCrypto.value;
  fromCrypto.value = toCrypto.value;
  toCrypto.value = temp;
  // Ensure "To" crypto is not the same as "From" after swapping
  if (fromCrypto.value === toCrypto.value) {
    const availableCryptos = cryptos.filter(c => c.symbol !== fromCrypto.value);
    toCrypto.value = availableCryptos.length > 0 ? availableCryptos[0].symbol : fromCrypto.value;
  }
  updateToAmount();
};

// Validate and perform the swap
const performSwap = async () => {
  errorMessage.value = '';
  successMessage.value = '';

  const fromCryptoObj = cryptos.find(c => c.symbol === fromCrypto.value);
  const toCryptoObj = cryptos.find(c => c.symbol === toCrypto.value);
  
  if (!fromCryptoObj) {
    errorMessage.value = 'Invalid "From" cryptocurrency selected.';
    return;
  }
  
  if (!toCryptoObj) {
    errorMessage.value = 'Invalid "To" cryptocurrency selected.';
    return;
  }

  const amount = parseFloat(fromAmount.value);
  const fromBalanceKey = fromCryptoObj.balanceKey;
  const availableBalance = Number(liveBalances.value[fromBalanceKey]) || 0;

  if (!amount || amount <= 0) {
    errorMessage.value = 'Please enter a valid amount.';
    return;
  }

  if (amount > availableBalance) {
    errorMessage.value = 'Insufficient balance for this swap.';
    return;
  }

  if (fromCrypto.value === toCrypto.value) {
    errorMessage.value = 'Cannot swap the same cryptocurrency.';
    return;
  }

  try {
    const convertedAmount = parseFloat(calculatedToAmount.value);
    const response = await router.post(route('swap.perform'), {
      from_crypto: fromCrypto.value,
      to_crypto: toCrypto.value,
      from_amount: amount,
      to_amount: convertedAmount,
    }, {
      preserveState: true,
      onSuccess: () => {
        successMessage.value = 'Swap completed successfully!';
        
        // Immediately update local balances for real-time display
        const fromBalanceKey = fromCryptoObj.balanceKey;
        const toBalanceKey = toCryptoObj.balanceKey;
        
        const currentFromBalance = Number(liveBalances.value[fromBalanceKey]) || 0;
        const currentToBalance = Number(liveBalances.value[toBalanceKey]) || 0;
        
        liveBalances.value = {
          ...liveBalances.value,
          [fromBalanceKey]: Math.max(0, currentFromBalance - amount),
          [toBalanceKey]: currentToBalance + convertedAmount
        };
        
        fromAmount.value = '';
        toAmount.value = '';
      },
      onError: (errors) => {
        errorMessage.value = errors.error || 'An error occurred during the swap.';
      },
    });
  } catch (error) {
    errorMessage.value = 'An error occurred during the swap.';
  }
};
</script>

<template>
  <Head title="Swap" />
  <AuthenticatedLayout>

    <div class="bg-black min-h-screen pt-2 pb-4">
      <div class="max-w-lg lg:max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="bg-black rounded-xl lg:rounded-3xl p-2 lg:p-8 border border-gray-800 shadow-lg">
          <!-- Swap Form -->
          <div class="space-y-3 lg:space-y-4">
             <h1 class="text-xl lg:text-3xl font-bold text-white text-center mb-4 lg:mb-6">Swap Cryptocurrencies</h1>
            <!-- From Crypto -->
            <div>
              <label class="block text-sm font-medium text-white mb-1">From</label>
              
              <div class="flex flex-col lg:flex-row lg:items-center lg:space-x-2 space-y-2 lg:space-y-0">
                <select
                  v-model="fromCrypto"
                  @change="updateToAmount"
                  class="w-full border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-black text-white border-gray-700"
                >
                  <option v-for="crypto in cryptos" :key="crypto.symbol" :value="crypto.symbol" class="bg-black text-white">
                    {{ crypto.symbol.toUpperCase() }}
                  </option>
                </select>
                <div class="flex w-full lg:w-auto space-x-2">
                  <input
                    v-model="fromAmount"
                    @input="updateToAmount"
                    type="number"
                    step="any"
                    placeholder="Amount"
                    class="w-full border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-black text-white placeholder-gray-400 border-gray-700"
                  />
                  <button
                    @click="setMaxAmount"
                    class="px-3 py-1 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition duration-200 border border-gray-600"
                  >
                    Max
                  </button>
                </div>
              </div>
              <p class="text-sm text-gray-300 mt-1">
                Remaining Balance: {{ remainingBalance }} {{ fromCrypto.value?.toUpperCase() || '' }}
              </p>
            </div>

            <!-- Swap Icon -->
            <div class="flex justify-center">
              <button
                @click="swapCurrencies"
                class="p-2 bg-gray-700 rounded-full hover:bg-gray-600 transition duration-200 border border-gray-600"
              >
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m-12 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
              </button>
            </div>

            <!-- To Crypto -->
            <div>
              <label class="block text-sm font-medium text-white mb-1">To</label>
              <div class="flex flex-col lg:flex-row lg:items-center lg:space-x-2 space-y-2 lg:space-y-0">
                <select
                  v-model="toCrypto"
                  @change="updateToAmount"
                  class="w-full border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-black text-white border-gray-700"
                >
                  <option
                    v-for="crypto in cryptos"
                    :key="crypto.symbol"
                    :value="crypto.symbol"
                    :disabled="crypto.symbol === fromCrypto.value"
                    :class="{ 'text-gray-400': crypto.symbol === fromCrypto.value }"
                    class="bg-black text-white"
                  >
                    {{ crypto.symbol.toUpperCase() }}
                  </option>
                </select>
                <input
                  v-model="toAmount"
                  type="text"
                  readonly
                  placeholder="Amount"
                  class="w-full border rounded-md shadow-sm bg-black text-white placeholder-gray-400 border-gray-700"
                />
              </div>
              <p class="text-sm text-gray-300 mt-1">
                Balance: {{ exchangeToBalance }} {{ toCrypto.value?.toUpperCase() || '' }}
              </p>
            </div>

            <!-- Live Rate -->
            <div class="text-sm text-gray-300">
              <p>Live Rate: 1 {{ fromCrypto.value?.toUpperCase() || '' }} = {{ (liveRate.value || 0).toFixed(8) }} {{ toCrypto.value?.toUpperCase() || '' }}</p>
            </div>

            <!-- Error/Success Messages -->
            <div v-if="errorMessage" class="text-red-400 text-sm">{{ errorMessage }}</div>
            <div v-if="successMessage" class="text-green-400 text-sm">{{ successMessage }}</div>

            <!-- Swap Button -->
            <button
              @click="performSwap"
              class="w-full bg-black text-white font-semibold py-2 px-4 rounded-md hover:bg-gray-900 transition duration-200 border border-gray-700"
            >
              Swap Now
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Professional styling for both mobile and desktop */
.bg-black {
  background-color: #181A20 !important;
}

.text-white {
  color: #fff !important;
}

.min-h-screen {
  min-height: 100vh;
}

/* Smooth scrolling and better body styling */
body {
  margin: 0;
  padding: 0;
  overflow-x: hidden;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Custom scrollbar for webkit browsers */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f9fafb;
}

::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Enhanced focus states */
input:focus,
select:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Professional button hover effects */
button:hover {
  transform: translateY(-1px);
}

button:active {
  transform: translateY(0);
}

/* Enhanced form styling */
input::placeholder,
select {
  transition: all 0.3s ease;
}

input:focus::placeholder {
  opacity: 0.7;
  transform: translateX(4px);
}

/* Professional card shadows */
.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.shadow-md {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.shadow-lg {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Large screen optimizations */
@media (min-width: 1024px) {
  .min-h-screen {
    background: #181A20 !important;
  }

  /* Enhanced spacing for large screens */
  .space-y-8 > * + * {
    margin-top: 2.5rem;
  }

  .space-y-6 > * + * {
    margin-top: 2rem;
  }

  /* Better form layouts on desktop */
  .grid-cols-1 {
    grid-template-columns: 1fr;
  }

  /* Professional hover effects */
  .hover\:scale-\[1\.02\]:hover {
    transform: scale(1.02);
  }
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .min-h-screen {
    min-height: auto !important;
    height: 100vh;
    padding-top: 0.5rem !important;
    padding-bottom: 1rem !important;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
  }
  
  /* Compact spacing for mobile */
  .space-y-6 > * + * {
    margin-top: 1rem;
  }

  .space-y-4 > * + * {
    margin-top: 0.75rem;
  }

  .space-y-3 > * + * {
    margin-top: 0.5rem;
  }

  /* Mobile-friendly shadows */
  .shadow-sm {
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  }

  .shadow-md {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  }

  /* Reduce main container padding on mobile */
  .bg-black.min-h-screen {
    padding-top: 0.5rem !important;
    padding-bottom: 1rem !important;
  }

  /* Make form more compact on mobile */
  .space-y-4 {
    gap: 0.75rem !important;
  }

  .space-y-3 {
    gap: 0.5rem !important;
  }

  /* Reduce heading size on mobile */
  h1 {
    font-size: 1.25rem !important;
    margin-bottom: 1rem !important;
  }

  /* Make form elements more compact */
  .p-2 {
    padding: 0.5rem !important;
  }

  /* Reduce input padding */
  input, select {
    padding: 0.5rem 0.75rem !important;
    font-size: 0.875rem !important;
  }

  /* Reduce button padding */
  button {
    padding: 0.5rem 1rem !important;
    font-size: 0.875rem !important;
  }

  /* Make the main container take full height but allow scrolling */
  .max-w-lg {
    max-width: 100% !important;
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  /* Ensure the form container takes available space */
  .bg-black.rounded-xl {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }

  /* Make the form content scrollable if needed */
  .space-y-3 {
    flex: 1;
    overflow-y: auto;
    padding-bottom: 1rem;
  }
}

/* Professional color scheme */
.text-gray-900 {
  color: #111827;
}

.text-gray-700 {
  color: #374151;
}

.text-gray-600 {
  color: #4b5563;
}

.text-gray-500 {
  color: #6b7280;
}

/* Enhanced button styling */
button {
  font-family: inherit;
  font-weight: 600;
  letter-spacing: 0.025em;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Professional form elements */
input,
select {
  font-family: inherit;
  font-weight: 500;
  letter-spacing: 0.025em;
}

/* Loading states */
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

/* Professional animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in {
  animation: fadeIn 0.3s ease-out;
}

/* Enhanced responsive utilities */
@media (min-width: 768px) {
  .md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .lg\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* Tab buttons - clean text style */
.tab-button {
  background: transparent !important;
  color: #fff !important;
  outline: none !important;
  padding: 0 !important;
  margin: 0 !important;
  box-shadow: none !important;
  transition: none !important;
}
.tab-button:hover {
  background: transparent !important;
  color: #fff !important;
}

/* Other buttons - keep original styling */
button:not(.tab-button):not(.copy-address-btn),
button[type="submit"]:not(.tab-button),
button[type="button"]:not(.tab-button),
.copy-btn,
.deposit-btn {
  background: #23262F !important;
  color: #fff !important;
  transition: background 0.2s, color 0.2s;
}
button:not(.tab-button):not(.copy-address-btn):hover,
button[type="submit"]:not(.tab-button):hover,
button[type="button"]:not(.tab-button):hover,
.copy-btn:hover,
.deposit-btn:hover {
  background: #f3f4f6 !important;
  color: #181A20 !important;
}
</style>