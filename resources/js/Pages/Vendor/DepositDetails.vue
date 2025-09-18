<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { useCryptoStore } from '@/Store/crypto';
import { ref, computed, onMounted } from 'vue';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/solid'; // Import for history icon
import { formatBalance } from '@/utils/formatBalance';

const props = defineProps({
  symbol: { type: String, required: true },
  depositDetails: { type: Object, required: true },
  balances: {
    type: Object,
    default: () => ({
      usdt_balance: 0,
      btc_balance: 0,
      eth_balance: 0,
    }),
  },
  coinTypes: {
    type: Array,
    default: () => [],
  },
});

const cryptoStore = useCryptoStore();
const isCopied = ref(false);
const copyError = ref(null); // State for copy error message
const showHistory = ref(false);
const history = ref([]);
const historyError = ref(null); // State for history fetch error
const withdrawHistory = ref([]);
const withdrawHistoryError = ref(null); // State for withdraw history fetch error
const successMessage = ref(null); // State for success message
const exchangeErrorMessage = ref(''); // State for exchange error message
const exchangeSuccessMessage = ref(''); // State for exchange success message
const sendErrorMessage = ref(''); // State for send error message
const sendSuccessMessage = ref(''); // State for send success message
const sendProcessing = ref(false); // State for send processing
const previewImage = ref(''); // State for deposit screenshot preview

// Define supported cryptocurrencies with full names
const cryptos = [
  { symbol: 'usdt', balanceKey: 'usdt_balance', decimals: 2, name: 'Tether' },
  { symbol: 'btc', balanceKey: 'btc_balance', decimals: 8, name: 'Bitcoin' },
  { symbol: 'eth', balanceKey: 'eth_balance', decimals: 4, name: 'Ethereum' }
];

// Tabs and Swap state
const activeTab = ref('receive');
const toCrypto = ref('usdt');
const fromAmount = ref('');
const toAmount = ref('');

onMounted(() => {
  if (cryptoStore && cryptoStore.startAutoRefresh) {
    cryptoStore.startAutoRefresh();
  }
  const symbols = ['usdt','btc','eth'].filter(s => s !== props.symbol);
  toCrypto.value = symbols[0] || props.symbol;
  fetchWithdrawHistory(); // Fetch withdrawal history on mount
});

// Computed property to get the remaining balance after swapping
const remainingBalance = computed(() => {
  const balanceKey = cryptos.find(c => c.symbol === props.symbol).balanceKey;
  const currentBalance = Number(props.balances[balanceKey]) || 0;
  const amountToSwap = parseFloat(fromAmount.value) || 0;
  const remaining = currentBalance - amountToSwap;
  const decimals = cryptos.find(c => c.symbol === props.symbol).decimals;
  return remaining >= 0 ? formatBalance(remaining, decimals) : formatBalance(0, decimals);
});

// Computed property to get the live rate (matching Swap.vue)
const liveRate = computed(() => {
  const fromPrice = Number(cryptoStore.getPrice(props.symbol)) || 0;
  const toPrice = Number(cryptoStore.getPrice(toCrypto.value)) || 0;
  if (fromPrice === 0 || toPrice === 0) return 0;
  return fromPrice / toPrice; // Correct: For USDT to BTC, if USDT = 1 USD, BTC = 60000 USD, rate = 1 / 60000 ≈ 0.00001667
});

// Computed property to calculate the amount to receive (matching Swap.vue)
const calculatedToAmount = computed(() => {
  const amount = parseFloat(fromAmount.value) || 0;
  const rate = liveRate.value;
  const result = amount * rate;
  const toDecimals = cryptos.find(c => c.symbol === toCrypto.value).decimals;
  return isNaN(result) ? '' : result.toFixed(toDecimals);
});

// Update toAmount when fromAmount changes
const updateToAmount = () => {
  toAmount.value = calculatedToAmount.value;
};

// Set maximum amount for exchange
const setMaxAmount = () => {
  const balanceKey = cryptos.find(c => c.symbol === props.symbol).balanceKey;
  const maxAmount = Number(props.balances[balanceKey]) || 0;
  fromAmount.value = maxAmount.toString();
  updateToAmount();
};

// Function to navigate back to Deposit page
const goBack = () => {
  router.visit(route('deposit'));
};

// Get current crypto balance
const currentBalance = computed(() => {
  const balanceKey = `${props.symbol}_balance`;
  return props.balances[balanceKey] || 0;
});

// Get current crypto info
const currentCrypto = computed(() => {
  return cryptos.find(c => c.symbol === props.symbol) || cryptos[0];
});

// Get full wallet name
const getWalletName = (symbol) => {
  const crypto = cryptos.find(c => c.symbol === symbol);
  return crypto ? `${crypto.name} Wallet` : `${symbol.toUpperCase()} Wallet`;
};

// Display amounts with live USD conversion
const displayAmounts = (symbol) => {
  const raw = currentBalance.value;
  const decimals = symbol === 'usdt' ? 2 : (symbol === 'btc' ? 8 : 4);
  const formattedAmount = formatBalance(raw, decimals);
  
  // Calculate live USD value
  let usdValue = 0;
  if (symbol === 'usdt') {
    usdValue = raw; // USDT is already in USD
  } else {
    const price = Number(cryptoStore.getPrice(symbol)) || 0;
    usdValue = raw * price;
  }
  
  const primary = `US$ ${formatBalance(usdValue, 2)}`;
  const secondary = `${formattedAmount} ${symbol.toUpperCase()}`;
  const icon = cryptoStore.getIcon(symbol);
  
  return { primary, secondary, icon, usdValue, formattedAmount };
};

// Form setup for deposit submission
const form = useForm({
  symbol: props.symbol,
  amount: '',
  slip: null,
});

// Handle file change for deposit screenshot preview
const handleFileChange = (event) => {
  const file = event.target.files[0];
  form.slip = file;
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    previewImage.value = '';
  }
};

// Copy address to clipboard with fallback
const copyAddress = async () => {
  const address = props.depositDetails.address;
  copyError.value = null; // Reset any previous error

  // Modern Clipboard API
  if (navigator.clipboard && navigator.clipboard.writeText) {
    try {
      await navigator.clipboard.writeText(address);
      isCopied.value = true;
      setTimeout(() => {
        isCopied.value = false;
      }, 2000);
      return;
    } catch (err) {
      console.error('Clipboard API failed:', err);
      copyError.value = 'Failed to copy address. Please copy manually.';
    }
  }

  // Fallback for older browsers or non-secure contexts
  try {
    const textarea = document.createElement('textarea');
    textarea.value = address;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    isCopied.value = true;
    setTimeout(() => {
      isCopied.value = false;
    }, 2000);
  } catch (err) {
    console.error('Fallback copy failed:', err);
    copyError.value = 'Failed to copy address. Please copy manually.';
  }
};

// Fetch deposit history for the current symbol
const fetchHistory = async () => {
  try {
    const response = await fetch(`${route('deposit.history')}?symbol=${props.symbol}`);
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }
    const data = await response.json();
    history.value = data.deposits;
    historyError.value = null;
    showHistory.value = true;
  } catch (error) {
    console.error('Error fetching deposit history:', error);
    historyError.value = 'Failed to load deposit history. Please try again later.';
    history.value = [];
    showHistory.value = true;
  }
};

// Fetch withdrawal history for the current symbol
const fetchWithdrawHistory = async () => {
  try {
    const response = await fetch(`${route('withdraw.history')}?symbol=${props.symbol}`);
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }
    const data = await response.json();
    withdrawHistory.value = data.withdrawals;
    withdrawHistoryError.value = null;
  } catch (error) {
    console.error('Error fetching withdrawal history:', error);
    withdrawHistoryError.value = 'Failed to load withdrawal history. Please try again later.';
    withdrawHistory.value = [];
  }
};

// Submit deposit
const submitDeposit = () => {
  form.post(route('deposit.store'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      successMessage.value = 'Deposit submitted successfully! Awaiting approval.';
      setTimeout(() => {
        successMessage.value = null;
      }, 3000); // Hide after 3 seconds
      form.reset();
      previewImage.value = '';
      fetchHistory(); // Refresh history after submission
    },
    onError: (errors) => {
      successMessage.value = null;
      alert('Error submitting deposit: ' + JSON.stringify(errors));
    },
  });
};

// Send (USDT only) — reuse withdraw page submit route
const sendForm = useForm({
  wallet_address: '',
  amount_withdraw: '',
  coin_id: null,
});

// Get current coin for withdrawal
const currentCoin = computed(() => {
  return props.coinTypes.find(coin => coin.symbol === props.symbol);
});

// Submit Send form using fetch to stay on current page (no redirect)
const submitSend = async () => {
  // Clear previous messages
  sendErrorMessage.value = '';
  sendSuccessMessage.value = '';

  // Validate wallet address
  if (!sendForm.wallet_address || sendForm.wallet_address.trim() === '') {
    sendErrorMessage.value = 'Please enter a wallet address.';
    return;
  }

  // Validate amount
  const amount = parseFloat(sendForm.amount_withdraw);
  if (!amount || amount <= 0) {
    sendErrorMessage.value = 'Please enter a valid amount.';
    return;
  }

  // Check balance
  const currentBalanceValue = Number(currentBalance.value) || 0;
  if (amount > currentBalanceValue) {
    sendErrorMessage.value = 'Insufficient balance. Please check your available balance.';
    return;
  }

  // Get current coin
  if (!currentCoin.value) {
    sendErrorMessage.value = `${props.symbol.toUpperCase()} coin not found.`;
    return;
  }

  sendProcessing.value = true;

  try {
    // Create FormData for the request
    const formData = new FormData();
    formData.append('wallet_address', sendForm.wallet_address);
    formData.append('amount_withdraw', sendForm.amount_withdraw);
    formData.append('coin_id', currentCoin.value.id);

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
      formData.append('_token', csrfToken.getAttribute('content'));
    }

    const response = await fetch(route('withdraw.store'), {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
    });

    const data = await response.json();

    if (response.ok) {
      sendSuccessMessage.value = data.message || 'Withdrawal request submitted successfully! Awaiting approval.';
      sendForm.reset();
      fetchWithdrawHistory(); // Refresh withdrawal history

      // Clear success message after 5 seconds
      setTimeout(() => {
        sendSuccessMessage.value = '';
      }, 5000);
    } else {
      // Handle validation errors
      if (data.errors) {
        const errorMessages = Object.values(data.errors).flat();
        sendErrorMessage.value = errorMessages[0] || 'Validation error occurred.';
      } else if (data.message) {
        sendErrorMessage.value = data.message;
      } else if (data.error) {
        sendErrorMessage.value = data.error;
      } else {
        sendErrorMessage.value = 'An error occurred while processing your withdrawal request.';
      }
    }
  } catch (error) {
    console.error('Network error:', error);
    sendErrorMessage.value = 'Network error. Please try again later.';
  } finally {
    sendProcessing.value = false;
  }
};

// Try to read USDT coin id from globally provided props (same as Withdraw.vue)
const page = usePage();
const usdtCoinId = computed(() => {
  const list = page?.props?.coinTypes || [];
  const found = Array.isArray(list) ? list.find((c) => (c.symbol || '').toLowerCase() === 'usdt') : null;
  return found?.id || '';
});

const performExchange = async () => {
  exchangeErrorMessage.value = '';
  exchangeSuccessMessage.value = '';

  const amount = parseFloat(fromAmount.value);
  const fromBalanceKey = cryptos.find(c => c.symbol === props.symbol).balanceKey;
  const availableBalance = Number(props.balances[fromBalanceKey]) || 0;

  if (!amount || amount <= 0) {
    exchangeErrorMessage.value = 'Please enter a valid amount.';
    return;
  }

  if (amount > availableBalance) {
    exchangeErrorMessage.value = 'Insufficient balance for this swap.';
    return;
  }

  if (props.symbol === toCrypto.value) {
    exchangeErrorMessage.value = 'Cannot swap the same cryptocurrency.';
    return;
  }

  try {
    const convertedAmount = parseFloat(calculatedToAmount.value);
    const response = await router.post(route('swap.perform'), {
      from_crypto: props.symbol,
      to_crypto: toCrypto.value,
      from_amount: amount,
      to_amount: convertedAmount,
    }, {
      preserveState: true,
      onSuccess: () => {
        exchangeSuccessMessage.value = 'Swap completed successfully!';
        fromAmount.value = '';
        toAmount.value = '';
      },
      onError: (errors) => {
        exchangeErrorMessage.value = errors.error || 'An error occurred during the swap.';
      },
    });
  } catch (error) {
    exchangeErrorMessage.value = 'An error occurred during the swap.';
  }
};
</script>

<template>
  <Head :title="`${getWalletName(symbol)}`" />
  <AuthenticatedLayout>
  <div class="bg-black min-h-screen pt-2 sm:pt-4">
      <!-- Header with back button and title -->
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800">
        <button @click="goBack" class="w-8 h-8 flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h1 class="text-lg font-semibold text-white">{{ getWalletName(symbol) }}</h1>
        <button @click="fetchHistory" class="w-8 h-8 flex items-center justify-center">
          <ClipboardDocumentListIcon class="w-5 h-5 text-white" />
        </button>
      </div>

      <!-- Balance Display -->
      <div class="px-4 py-2 text-center">
        <div class="text-lg font-medium text-gray-300 mb-2">{{ displayAmounts(symbol).primary }}</div>
        <div class="flex items-center justify-center text-white">
          <img v-if="displayAmounts(symbol).icon" :src="displayAmounts(symbol).icon" :alt="symbol.toUpperCase()" class="w-6 h-6 mr-2 rounded-full" />
          <span class="text-2xl font-bold">{{ displayAmounts(symbol).secondary }}</span>
        </div>
      </div>

      <div class="px-2 pb-4 sm:px-4 sm:pb-6 mt-0">
        <div class="bg-black rounded-xl border border-gray-800 overflow-hidden mt-0" style="margin-top:0!important;">

          <!-- Tabs (clean like image) -->
          <div class="flex justify-center gap-12 px-4 pt-6">
            <button @click="activeTab='receive'; sendErrorMessage=''; sendSuccessMessage=''" :class="[
              'tab-button text-base font-medium text-white cursor-pointer',
              activeTab==='receive' 
                ? 'border-b-2 border-blue-400 pb-2' 
                : ''
            ]">Receive</button>
            <button @click="activeTab='send'; sendErrorMessage=''; sendSuccessMessage=''" :class="[
              'tab-button text-base font-medium text-white cursor-pointer',
              activeTab==='send' 
                ? 'border-b-2 border-blue-400 pb-2' 
                : ''
            ]">Send</button>
            <button @click="activeTab='exchange'; exchangeErrorMessage=''; exchangeSuccessMessage=''" :class="[
              'tab-button text-base font-medium text-white cursor-pointer',
              activeTab==='exchange' 
                ? 'border-b-2 border-blue-400 pb-2' 
                : ''
            ]">Exchange</button>
          </div>

          <!-- Tab Content -->
          <div class="p-4 space-y-2">
            <!-- Success Message -->
            <div v-if="successMessage" class="mb-2 p-2 bg-green-900 text-green-200 rounded-md text-xs text-center border border-green-800">
              {{ successMessage }}
            </div>

          <!-- RECEIVE: Network selector (pills) -->
          <div v-if="activeTab==='receive' && symbol === 'usdt'" class="space-y-2">
            <div class="text-sm font-semibold text-white">Receiving assets</div>
            <div class="flex space-x-3">
              <button class="px-3 py-2 rounded-xl border text-sm"
                :class="depositDetails.network?.toLowerCase().includes('erc') ? 'border-blue-400 text-white' : 'border-gray-700 text-gray-400'">
                ERC20—{{ symbol.toUpperCase() }}
              </button>
              <button class="px-3 py-2 rounded-xl border text-sm"
                :class="depositDetails.network?.toLowerCase().includes('trc') ? 'border-blue-400 text-white' : 'border-gray-700 text-gray-400'">
                TRC20—{{ symbol.toUpperCase() }}
              </button>
            </div>
          </div>

          <!-- RECEIVE: QR Code -->
          <div v-if="activeTab==='receive'" class="flex justify-center py-1">
            <img
              :src="depositDetails.qr_code ? '/storage/' + depositDetails.qr_code : 'https://via.placeholder.com/100?text=No+QR+Code'"
              alt="QR Code"
              class="w-32 h-32"
            />
          </div>

          <!-- RECEIVE: Address + Copy link -->
          <div v-if="activeTab==='receive'" class="text-center">
            <div class="text-xs text-white break-all">{{ depositDetails.address }}</div>
            <button @click="copyAddress" class="text-blue-400 text-xs mt-1 hover:text-blue-300">{{ isCopied ? 'Copied!' : 'Copy address' }}</button>
            <div v-if="copyError" class="mt-1 text-[10px] text-red-400">
              {{ copyError }}
            </div>
          </div>

          <!-- RECEIVE: Amount Input (rounded with suffix) -->
          <div v-if="activeTab==='receive'" class="space-y-1">
            <div class="text-sm font-semibold text-white">Deposit amount</div>
            <div class="flex items-center border border-gray-700 rounded-2xl px-3 py-2 bg-black">
              <span class="mr-2 text-xs bg-gray-800 text-white rounded-full px-2 py-1">{{ symbol.toUpperCase() }}</span>
              <input v-model="form.amount" type="number" step="any" placeholder="0" class="flex-1 bg-transparent text-white text-sm focus:outline-none placeholder-gray-500" />
              <span class="ml-2 text-gray-400 text-sm uppercase">{{ symbol }}</span>
            </div>
          </div>

          <!-- RECEIVE: Upload Slip (tile) -->
          <div v-if="activeTab==='receive'" class="space-y-1">
            <div class="text-sm font-semibold text-white">Deposit screenshot</div>
            <label class="w-28 h-28 border border-gray-700 rounded-lg flex items-center justify-center cursor-pointer bg-black hover:bg-gray-900 overflow-hidden">
              <input type="file" class="hidden" accept="image/*" @change="handleFileChange" />
              <img v-if="previewImage" :src="previewImage" class="w-full h-full object-cover" />
              <svg v-else class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </label>
          </div>

          <!-- RECEIVE: Submit -->
          <button v-if="activeTab==='receive'"
            @click="submitDeposit"
            :disabled="form.processing"
            class="w-full px-4 py-3 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-500"
          >
            Submit
          </button>

          <!-- SEND -->
          <div v-if="activeTab==='send'" class="space-y-4">
            <!-- Success Message for Send -->
            <div v-if="sendSuccessMessage" class="mb-2 p-2 bg-green-900 text-green-200 rounded-md text-xs text-center border border-green-800">
              {{ sendSuccessMessage }}
            </div>

            <!-- Error Message for Send -->
            <div v-if="sendErrorMessage" class="mb-2 p-2 bg-red-900 text-red-200 rounded-md text-xs text-center border border-red-800">
              {{ sendErrorMessage }}
            </div>

            <!-- Mirror Withdraw.vue structure (crypto tab) so backend receives identical fields -->
            <div class="space-y-4">
              <!-- Wallet Address -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">Wallet Address</label>
                <input 
                  v-model="sendForm.wallet_address" 
                  @input="sendErrorMessage = ''"
                  type="text" 
                  placeholder="Enter your wallet address" 
                  class="w-full bg-black border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-white placeholder-gray-400" 
                />
              </div>
              
              <!-- Amount -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">Withdraw Amount</label>
                <div class="space-y-2">
                  <input 
                    v-model="sendForm.amount_withdraw" 
                    @input="sendErrorMessage = ''"
                    type="number" 
                    step="any" 
                    placeholder="Enter amount" 
                    class="w-full bg-black border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-white placeholder-gray-400" 
                  />
                  <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-300 flex items-center">
                      <img v-if="displayAmounts(symbol).icon" :src="displayAmounts(symbol).icon" :alt="symbol.toUpperCase()" class="w-3 h-3 mr-1 rounded-full" />
                      Available: {{ displayAmounts(symbol).formattedAmount }} {{ props.symbol.toUpperCase() }}
                    </span>
                    <button 
                      @click="sendForm.amount_withdraw = currentBalance.toString()"
                      class="text-blue-400 hover:text-blue-300 text-xs"
                    >
                      Use Max
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Submit Button -->
              <div class="text-center">
                <button 
                  @click="submitSend" 
                  :disabled="sendProcessing"
                  class="w-full bg-green-600 text-white font-semibold py-3 rounded-xl shadow-md hover:bg-green-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ sendProcessing ? 'Sending...' : 'Send' }}
                </button>
              </div>
            </div>

            <!-- Withdrawal History -->
            <div class="mt-6">
              <h3 class="text-sm font-semibold text-white mb-2">Sending History</h3>
              <div v-if="withdrawHistoryError" class="text-red-400 text-xs mb-2">
                {{ withdrawHistoryError }}
              </div>
              <div v-else-if="withdrawHistory.length === 0" class="text-gray-400 text-xs">
                No withdrawal history available.
              </div>
              <div v-else class="space-y-2">
                <div v-for="withdrawal in withdrawHistory" :key="withdrawal.id" class="border border-gray-800 rounded-md p-2 text-xs bg-black">
                  <div class="flex justify-between">
                    <span class="text-white font-medium">{{ withdrawal.symbol.toUpperCase() }}</span>
                    <span :class="{
                      'text-yellow-400': withdrawal.status === 'pending',
                      'text-green-400': withdrawal.status === 'approved',
                      'text-red-400': withdrawal.status === 'rejected'
                    }">
                      {{ withdrawal.status.charAt(0).toUpperCase() + withdrawal.status.slice(1) }}
                    </span>
                  </div>
                  <div class="flex justify-between mt-1">
                    <span class="text-gray-400">Amount</span>
                    <span class="text-white">{{ withdrawal.amount }} {{ withdrawal.symbol.toUpperCase() }}</span>
                  </div>
                  <div class="flex justify-between mt-1">
                    <span class="text-gray-400">Address</span>
                    <span class="text-white text-xs break-all">{{ withdrawal.wallet_address }}</span>
                  </div>
                  <div class="flex justify-between mt-1">
                    <span class="text-gray-400">Date</span>
                    <span class="text-white">{{ new Date(withdrawal.created_at).toLocaleString() }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- EXCHANGE -->
          <div v-if="activeTab==='exchange'" class="space-y-4">
            <!-- From Crypto -->
            <div>
              <label class="block text-sm font-medium text-white mb-1">From</label>
              <div class="flex flex-col lg:flex-row lg:items-center lg:space-x-2 space-y-2 lg:space-y-0">
                <div class="flex w-full lg:w-auto space-x-2">
                  <div class="px-3 py-2 border border-gray-700 rounded-md bg-black text-white text-sm">
                    {{ props.symbol.toUpperCase() }}
                  </div>
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
                    class="px-3 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition duration-200 border border-gray-600"
                  >
                    Max
                  </button>
                </div>
              </div>
              <p class="text-sm text-gray-300 mt-1">
                Remaining Balance: {{ remainingBalance }} {{ props.symbol.toUpperCase() }}
              </p>
            </div>

            <!-- Swap Icon -->
            <div class="flex justify-center">
              <button class="p-2 bg-gray-700 rounded-full hover:bg-gray-600 transition duration-200 border border-gray-600">
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
                    :disabled="crypto.symbol === props.symbol"
                    :class="{ 'text-gray-400': crypto.symbol === props.symbol }"
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
                Balance: <img v-if="displayAmounts(toCrypto).icon" :src="displayAmounts(toCrypto).icon" :alt="toCrypto.toUpperCase()" class="w-3 h-3 inline mr-1 rounded-full" /> {{ formatBalance(props.balances[cryptos.find(c => c.symbol === toCrypto).balanceKey], cryptos.find(c => c.symbol === toCrypto).decimals) }} {{ toCrypto.toUpperCase() }}
              </p>
            </div>

            <!-- Live Rate -->
            <div class="text-sm text-gray-300">
              <p>Live Rate: 1 {{ props.symbol.toUpperCase() }} = {{ liveRate.toFixed(8) }} {{ toCrypto.toUpperCase() }}</p>
            </div>

            <!-- Error/Success Messages -->
            <div v-if="exchangeErrorMessage" class="text-red-400 text-sm">{{ exchangeErrorMessage }}</div>
            <div v-if="exchangeSuccessMessage" class="text-green-400 text-sm">{{ exchangeSuccessMessage }}</div>

            <!-- Exchange Button -->
            <button
              @click="performExchange"
              class="w-full bg-green-600 text-white font-semibold py-3 rounded-xl shadow-md hover:bg-green-500 transition duration-200"
            >
              Exchange
            </button>
          </div>
          </div>
        </div>
      </div>
    </div>

    <!-- History Modal -->
    <div v-if="showHistory" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-black rounded-lg p-3 w-11/12 max-w-sm max-h-[80vh] overflow-y-auto border border-gray-800">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-base font-semibold text-white">Deposit History</h2>
          <button @click="showHistory = false" class="text-gray-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L5 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <!-- Show success message in history modal if open -->
        <div v-if="successMessage" class="mb-2 p-1 bg-green-900 text-green-200 rounded-md text-[10px] text-center border border-green-800">
          {{ successMessage }}
        </div>
        <div v-if="historyError" class="text-red-400 text-[10px] mb-2">
          {{ historyError }}
        </div>
        <div v-else-if="history.length === 0" class="text-gray-400 text-[10px]">
          No deposit history available.
        </div>
        <div v-else class="space-y-2">
          <div v-for="deposit in history" :key="deposit.id" class="border border-gray-800 rounded-md p-2 text-[10px] bg-black">
            <div class="flex justify-between">
              <span class="text-white font-medium">{{ deposit.symbol.toUpperCase() }}</span>
              <span :class="{
                'text-yellow-400': deposit.status === 'pending',
                'text-green-400': deposit.status === 'approved',
                'text-red-400': deposit.status === 'rejected'
              }">
                {{ deposit.status.charAt(0).toUpperCase() + deposit.status.slice(1) }}
              </span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-400">Amount</span>
              <span class="text-white">{{ deposit.amount }} {{ deposit.symbol.toUpperCase() }}</span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-400">Date</span>
              <span class="text-white">{{ new Date(deposit.created_at).toLocaleString() }}</span>
            </div>
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

/* Tab buttons - clean text style */
.tab-button {
  background: transparent !important;
  color: #fff !important;
  border: none !important;
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
button:not(.tab-button),
button[type="submit"]:not(.tab-button),
button[type="button"]:not(.tab-button),
.copy-btn,
.deposit-btn {
  background: #23262F !important;
  color: #fff !important;
  transition: background 0.2s, color 0.2s;
}
button:not(.tab-button):hover,
button[type="submit"]:not(.tab-button):hover,
button[type="button"]:not(.tab-button):hover,
.copy-btn:hover,
.deposit-btn:hover {
  background: #f3f4f6 !important;
  color: #181A20 !important;
}
</style>