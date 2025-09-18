<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useCryptoStore } from '@/Store/crypto';
import { formatBalance } from '@/utils/formatBalance';
import { ArrowLeftIcon } from '@heroicons/vue/24/solid';

// Access the crypto store
const cryptoStore = useCryptoStore();

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
});

// Computed property to get the remaining balance after swapping
const remainingBalance = computed(() => {
  const balanceKey = cryptos.find(c => c.symbol === fromCrypto.value).balanceKey;
  const currentBalance = Number(props.balances[balanceKey]) || 0;
  const amountToSwap = parseFloat(fromAmount.value) || 0;
  const remaining = currentBalance - amountToSwap;
  const decimals = cryptos.find(c => c.symbol === fromCrypto.value).decimals;
  return remaining >= 0 ? formatBalance(remaining, decimals) : formatBalance(0, decimals);
});

// Computed property to get the live rate
const liveRate = computed(() => {
  const fromPrice = Number(cryptoStore.getPrice(fromCrypto.value)) || 0;
  const toPrice = Number(cryptoStore.getPrice(toCrypto.value)) || 0;
  if (fromPrice === 0 || toPrice === 0) return 0;
  return fromPrice / toPrice; // Correct: For USDT to BTC, if USDT = 1 USD, BTC = 60000 USD, rate = 1 / 60000 ≈ 0.00001667
});

// Computed property to calculate the amount to receive
const calculatedToAmount = computed(() => {
  const amount = parseFloat(fromAmount.value) || 0;
  const rate = liveRate.value;
  const result = amount * rate;
  return isNaN(result) ? '' : result.toFixed(cryptos.find(c => c.symbol === toCrypto.value).decimals);
});

// Update toAmount when fromAmount changes
const updateToAmount = () => {
  toAmount.value = calculatedToAmount.value;
};

// Set the input amount to the maximum available balance
const setMaxAmount = () => {
  const balanceKey = cryptos.find(c => c.symbol === fromCrypto.value).balanceKey;
  const maxBalance = Number(props.balances[balanceKey]) || 0;
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

  const amount = parseFloat(fromAmount.value);
  const fromBalanceKey = cryptos.find(c => c.symbol === fromCrypto.value).balanceKey;
  const availableBalance = Number(props.balances[fromBalanceKey]) || 0;

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

// Go back in history
const goBack = () => {
  window.history.back();
};
</script>

<template>
  <Head title="Swap" />
  <AuthenticatedLayout>
    <button @click="goBack" class="flex items-center space-x-2 text-white mb-4 hover:text-gray-300">
      <ArrowLeftIcon class="h-5 w-5" />
      <span>Back</span>
    </button>

    <div class="pt-4 py-6 bg-black min-h-screen">
      <div class="max-w-lg lg:max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="bg-black overflow-hidden rounded-xl shadow-lg p-6 border border-gray-800">
          <!-- Swap Form -->
          <div class="space-y-4">
             <h1 class="text-2xl font-bold text-white">Swap Cryptocurrencies</h1>
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
                Remaining Balance: {{ remainingBalance }} {{ fromCrypto.toUpperCase() }}
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
                    :disabled="crypto.symbol === fromCrypto"
                    :class="{ 'text-gray-400': crypto.symbol === fromCrypto }"
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
                Balance: {{ formatBalance(props.balances[cryptos.find(c => c.symbol === toCrypto).balanceKey], cryptos.find(c => c.symbol === toCrypto).decimals) }} {{ toCrypto.toUpperCase() }}
              </p>
            </div>

            <!-- Live Rate -->
            <div class="text-sm text-gray-300">
              <p>Live Rate: 1 {{ fromCrypto.toUpperCase() }} = {{ liveRate.toFixed(8) }} {{ toCrypto.toUpperCase() }}</p>
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
.bg-black {
  background-color: #181A20 !important;
}

.text-white {
  color: #fff !important;
}

button,
button[type="submit"],
button[type="button"] {
  background: #23262F !important;
  color: #fff !important;
  transition: background 0.2s, color 0.2s;
}
button:hover,
button[type="submit"]:hover,
button[type="button"]:hover {
  background: #f3f4f6 !important;
  color: #181A20 !important;
}
</style>