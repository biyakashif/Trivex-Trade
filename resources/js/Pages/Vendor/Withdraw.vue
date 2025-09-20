<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ChartBarIcon, ClipboardIcon } from '@heroicons/vue/24/solid';
import { useCryptoStore } from '@/Store/crypto';
import { formatBalance } from '@/utils/formatBalance';

// Function to format amounts with proper decimals
const formatAmount = (amount, symbol) => {
  const crypto = props.coinTypes.find(c => c.symbol === symbol.toLowerCase());
  if (!crypto) return amount;
  const parsed = parseFloat(amount);
  return isNaN(parsed) ? amount : parsed.toFixed(crypto.symbol === 'usdt' ? 2 : (crypto.symbol === 'btc' ? 8 : 4));
};

const page = usePage();

const props = defineProps({
  balances: {
    type: Object,
    default: () => ({
      usdt_balance: 0,
      btc_balance: 0,
      eth_balance: 0,
    }),
  },
  withdrawals: {
    type: Array,
    default: () => [],
  },
  coinTypes: {
    type: Array,
    default: () => [],
  },
});

const cryptoStore = useCryptoStore();
const activeTab = ref('crypto'); // Default to crypto tab
const selectedCoinId = ref(props.coinTypes[0]?.id || null); // Default to the first coin type
const showHistoryModal = ref(false); // State for history modal
const withdrawalsData = ref(props.withdrawals); // Reactive withdrawal history
const serverMessage = ref(''); // Server response message for UI display
const messageType = ref(''); // 'success' or 'error' for styling

// Polling for withdrawal history updates
let withdrawalPollInterval = null;
let balancePollInterval = null;

// Echo listeners
let balanceEchoListener = null;
let withdrawalEchoListener = null;

// Make balances reactive for live updates
const liveBalances = ref({
  usdt_balance: props.balances.usdt_balance || 0,
  btc_balance: props.balances.btc_balance || 0,
  eth_balance: props.balances.eth_balance || 0,
});

// Get selected coin information
const selectedCoin = computed(() => {
  return props.coinTypes.find(coin => coin.id === selectedCoinId.value);
});

// Get selected coin balance
const selectedCoinBalance = computed(() => {
  if (!selectedCoin.value) return 0;
  const balanceKey = `${selectedCoin.value.symbol}_balance`;
  return liveBalances.value[balanceKey] || 0;
});

async function fetchWithdrawalHistory() {
  try {
    const response = await fetch(route('withdraw.history'), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    const data = await response.json();
    withdrawalsData.value = data.withdrawals || [];
  } catch (error) {
    console.error('Error fetching withdrawal history:', error);
  }
}

// Setup Echo listeners for real-time updates
const setupEchoListeners = () => {
  const userId = page?.props?.auth?.user?.id;
  
  console.log('Withdraw - Setting up Echo listeners...');
  console.log('Withdraw - User ID:', userId);
  console.log('Withdraw - Window.Echo:', window.Echo);
  console.log('Withdraw - Page props auth:', page?.props?.auth);
  
  if (!userId || !window.Echo) {
    console.warn('Withdraw - User ID or Echo not available for real-time updates');
    return;
  }

  console.log('Withdraw - Setting up Echo listener for user:', userId);

  // Balance updates listener
  balanceEchoListener = window.Echo.private(`user.${userId}`)
    .listen('.balance.updated', (data) => {
      console.log('Withdraw - Balance updated via Echo:', data);
      if (data.balances) {
        liveBalances.value = {
          usdt_balance: data.balances.usdt_balance || 0,
          btc_balance: data.balances.btc_balance || 0,
          eth_balance: data.balances.eth_balance || 0,
        };
      }
    });

  // Withdrawal history updates
  withdrawalEchoListener = window.Echo.private(`user.${userId}`)
    .listen('.withdrawal.updated', (data) => {
      console.log('Withdraw - Withdrawal updated via Echo:', data);
      fetchWithdrawalHistory(); // Refresh withdrawal history
    });
};

// Submit withdrawal form using Inertia router (better for CSRF handling)
const submitWithdrawal = async () => {
  // Get the form element
  const form = document.querySelector('form');
  if (!form) return;

  // Get form data
  const formData = new FormData(form);
  const amount = parseFloat(formData.get(activeTab.value === 'crypto' ? 'amount_withdraw' : 'bank_withdraw_amount'));

  // Client-side balance validation
  let availableBalance = 0;
  if (activeTab.value === 'crypto') {
    availableBalance = selectedCoinBalance.value || 0;
  } else {
    availableBalance = liveBalances.value.usdt_balance || 0;
  }

  if (amount > availableBalance) {
    serverMessage.value = `Insufficient balance. Available: ${formatAmount(availableBalance, activeTab.value === 'crypto' ? selectedCoin.value.symbol : 'usdt')} ${activeTab.value === 'crypto' ? selectedCoin.value.symbol.toUpperCase() : 'USDT'}`;
    messageType.value = 'error';
    setTimeout(() => {
      serverMessage.value = '';
      messageType.value = '';
    }, 5000);
    return;
  }

  if (amount <= 0) {
    serverMessage.value = 'Please enter a valid amount greater than 0';
    messageType.value = 'error';
    setTimeout(() => {
      serverMessage.value = '';
      messageType.value = '';
    }, 5000);
    return;
  }

  // Create data object for Inertia
  const data = {};
  for (let [key, value] of formData.entries()) {
    data[key] = value;
  }

  // Use Inertia router for better CSRF handling
  router.post(route('withdraw.store'), data, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      // Success - show server message
      const serverMessageText = 'Withdrawal request submitted successfully';
      
      // Display message in UI instead of alert
      serverMessage.value = serverMessageText;
      messageType.value = 'success';
      
      // Clear message after 5 seconds
      setTimeout(() => {
        serverMessage.value = '';
        messageType.value = '';
      }, 5000);

      // Immediately update local balance for real-time display
      if (activeTab.value === 'crypto') {
        const balanceKey = `${selectedCoin.value.symbol}_balance`;
        const currentAmount = Number(liveBalances.value[balanceKey]) || 0;
        liveBalances.value = {
          ...liveBalances.value,
          [balanceKey]: currentAmount - amount,
        };
      } else if (activeTab.value === 'bank') {
        const currentAmount = Number(liveBalances.value.usdt_balance) || 0;
        liveBalances.value = {
          ...liveBalances.value,
          usdt_balance: currentAmount - amount,
        };
      }
    },
    onError: (errors) => {
      // Handle validation errors
      let errorMessage = 'An error occurred while processing your withdrawal request.';
      
      if (errors) {
        const errorMessages = Object.values(errors).flat();
        errorMessage = errorMessages[0];
      }
      
      // Display error message in UI
      serverMessage.value = errorMessage;
      messageType.value = 'error';
      
      // Clear error message after 5 seconds
      setTimeout(() => {
        serverMessage.value = '';
        messageType.value = '';
      }, 5000);
    },
  });
};

// Set maximum amount for selected cryptocurrency or USDT for bank
const setMaxAmount = () => {
  if (activeTab.value === 'crypto') {
    // Crypto withdrawal
    const maxAmount = selectedCoinBalance.value;
    const form = document.querySelector('form');
    if (form) {
      const amountInput = form.querySelector('input[name="amount_withdraw"]');
      if (amountInput) {
        amountInput.value = formatAmount(maxAmount, selectedCoin.value.symbol);
      }
    }
  } else if (activeTab.value === 'bank') {
    // Bank withdrawal - use USDT balance
    const maxAmount = liveBalances.value.usdt_balance || 0;
    const form = document.querySelector('form');
    if (form) {
      const amountInput = form.querySelector('input[name="bank_withdraw_amount"]');
      if (amountInput) {
        amountInput.value = formatAmount(maxAmount, 'usdt');
      }
    }
  }
};

// Set active tab
const setActiveTab = (tab) => {
  activeTab.value = tab;
  if (tab === 'history') {
    showHistoryModal.value = true; // Trigger the history display
  } else {
    showHistoryModal.value = false;
  }
  
  // Clear any server messages when switching tabs
  clearServerMessage();
};

// Copy history to clipboard
const copyHistoryToClipboard = async () => {
  try {
    const historyText = withdrawalsData.value.map(w =>
      `Type: ${w.coin_id ? 'Crypto' : 'Bank'}, Amount: ${w.amount_withdraw}, Status: ${w.status}, Date: ${new Date(w.created_at).toLocaleString()}`
    ).join('\n');
    
    await navigator.clipboard.writeText(historyText);
    
    // Show success message in UI
    serverMessage.value = 'Withdrawal history copied to clipboard successfully!';
    messageType.value = 'success';
    
    // Clear message after 3 seconds
    setTimeout(() => {
      serverMessage.value = '';
      messageType.value = '';
    }, 3000);
  } catch (error) {
    console.error('Failed to copy history:', error);
    
    // Show error message in UI
    serverMessage.value = 'Failed to copy withdrawal history. Please try again.';
    messageType.value = 'error';
    
    // Clear error message after 3 seconds
    setTimeout(() => {
      serverMessage.value = '';
      messageType.value = '';
    }, 3000);
  }
};

// Clear server message
const clearServerMessage = () => {
  serverMessage.value = '';
  messageType.value = '';
};

// Setup polling on mount
onMounted(() => {
  // Start crypto store auto refresh for live prices
  if (cryptoStore && cryptoStore.startAutoRefresh) {
    cryptoStore.startAutoRefresh();
  }

  // Start polling for updates
  fetchWithdrawalHistory();

  // Set up Echo listeners for real-time updates with delay
  setTimeout(() => {
    setupEchoListeners();
  }, 100);
});

onUnmounted(() => {
  // Stop crypto store auto refresh
  if (cryptoStore && cryptoStore.stopAutoRefresh) {
    cryptoStore.stopAutoRefresh();
  }

  // Clean up Echo listeners
  if (balanceEchoListener) {
    balanceEchoListener.stopListening('.balance.updated');
  }
  if (withdrawalEchoListener) {
    withdrawalEchoListener.stopListening('.withdrawal.updated');
  }

  // Leave the private channel
  const userId = page?.props?.auth?.user?.id;
  if (userId && window.Echo) {
    window.Echo.leave(`user.${userId}`);
  }
});
</script>

<template>
  <Head title="Withdraw" />
  <AuthenticatedLayout>


    <div class="bg-black min-h-screen">
      <!-- Navigation Tabs - Moved to Top -->
      <div class="sticky top-0 z-20 bg-gray-900/95 backdrop-blur-xl border-b border-gray-700/50 shadow-lg">
        <div class="max-w-6xl mx-auto px-4 lg:px-8">
          <nav class="flex justify-center space-x-1 lg:space-x-2 py-1 lg:py-2">
            <button
              @click="setActiveTab('crypto')"
              :class="[
                'flex-1 lg:flex-none px-4 lg:px-8 py-3 lg:py-4 rounded-xl font-semibold transition-all duration-300 text-sm lg:text-base relative overflow-hidden',
                activeTab === 'crypto'
                  ? 'bg-blue-600 text-white shadow-md'
                  : 'text-gray-300 hover:text-white hover:bg-gray-800/50'
              ]"
            >
              <div class="relative z-10 flex items-center justify-center space-x-2">
                <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                <span>Crypto</span>
              </div>
            </button>

            <button
              @click="setActiveTab('bank')"
              :class="[
                'flex-1 lg:flex-none px-4 lg:px-8 py-3 lg:py-4 rounded-xl font-semibold transition-all duration-300 text-sm lg:text-base relative overflow-hidden',
                activeTab === 'bank'
                  ? 'bg-purple-600 text-white shadow-md'
                  : 'text-gray-300 hover:text-white hover:bg-gray-800/50'
              ]"
            >
              <div class="relative z-10 flex items-center justify-center space-x-2">
                <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span>Bank</span>
              </div>
            </button>

            <button
              @click="setActiveTab('history')"
              :class="[
                'flex-1 lg:flex-none px-4 lg:px-8 py-3 lg:py-4 rounded-xl font-semibold transition-all duration-300 text-sm lg:text-base relative overflow-hidden',
                activeTab === 'history'
                  ? 'bg-gray-600 text-white shadow-md'
                  : 'text-gray-300 hover:text-white hover:bg-gray-800/50'
              ]"
            >
              <div class="relative z-10 flex items-center justify-center space-x-2">
                <ChartBarIcon class="h-5 w-5 lg:h-6 lg:w-6" />
                <span>History</span>
              </div>
            </button>
          </nav>
        </div>
      </div>

      <!-- Main Content -->
      <div class="flex-1 px-3 py-1 lg:px-8 lg:py-2">
        <div class="max-w-5xl mx-auto">
        <!-- Crypto Withdrawal Form -->
        <div
          v-if="activeTab === 'crypto'"
          class="bg-black rounded-xl lg:rounded-2xl p-2 lg:p-4 border border-gray-800 h-[calc(100vh-130px)] lg:h-[calc(100vh-150px)]"
        >
          <div class="text-center mb-2 lg:mb-4">
            <h2 class="text-lg lg:text-3xl font-bold text-white mb-1 lg:mb-2">Crypto Withdrawal</h2>
            <p class="text-gray-400 text-xs lg:text-base">Send cryptocurrency to your external wallet</p>
          </div>

          <!-- Crypto Server Response Message -->
          <div v-if="serverMessage && activeTab === 'crypto'" class="mb-2 lg:mb-4">
            <div
              :class="[
                'text-center py-1.5 px-3 lg:py-3 lg:px-6 rounded-md lg:rounded-xl font-medium text-xs lg:text-sm',
                messageType === 'success'
                  ? 'bg-green-900 text-green-200 border border-green-800'
                  : 'bg-red-900 text-red-200 border border-red-800'
              ]"
            >
              {{ serverMessage }}
            </div>
          </div>

          <form @submit.prevent="submitWithdrawal" class="space-y-2 lg:space-y-4">
            <!-- Select Crypto -->
            <div class="space-y-1 lg:space-y-2">
              <label class="block text-xs lg:text-base font-semibold text-white">Select Cryptocurrency</label>
              <div class="relative">
                <select
                  name="coin_id"
                  v-model="selectedCoinId"
                  class="w-full bg-black border border-gray-700 rounded-md lg:rounded-2xl p-2.5 lg:p-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 text-white text-xs lg:text-base appearance-none"
                >
                  <option v-for="coin in coinTypes" :key="coin.id" :value="coin.id" class="bg-black text-white">
                    {{ coin.coin_name }} ({{ coin.symbol.toUpperCase() }})
                  </option>
                </select>
                <div class="absolute right-2.5 lg:right-4 top-1/2 transform -translate-y-1/2 pointer-events-none">
                  <svg class="w-3.5 h-3.5 lg:w-5 lg:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Wallet Address -->
            <div class="space-y-1 lg:space-y-2">
              <label class="block text-xs lg:text-base font-semibold text-white">Wallet Address</label>
              <div class="relative">
                <input
                  type="text"
                  name="wallet_address"
                  class="w-full bg-black border border-gray-700 rounded-md lg:rounded-2xl p-2.5 lg:p-4 pl-8 lg:pl-12 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 text-white placeholder-gray-400 text-xs lg:text-base"
                  placeholder="Enter your wallet address"
                />
                <div class="absolute left-2.5 lg:left-4 top-1/2 transform -translate-y-1/2">
                  <svg class="w-3.5 h-3.5 lg:w-5 lg:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Amount -->
            <div class="space-y-1 lg:space-y-2">
              <label class="block text-xs lg:text-base font-semibold text-white">Withdraw Amount</label>
              <div class="relative">
                <input
                  type="number"
                  name="amount_withdraw"
                  step="any"
                  class="w-full bg-black border border-gray-700 rounded-md lg:rounded-2xl p-2.5 lg:p-4 pl-8 lg:pl-12 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 text-white placeholder-gray-400 text-xs lg:text-base"
                  placeholder="Enter amount"
                />
                <div class="absolute left-2.5 lg:left-4 top-1/2 transform -translate-y-1/2">
                  <svg class="w-3.5 h-3.5 lg:w-5 lg:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                  </svg>
                </div>
              </div>

              <!-- Balance Info -->
              <div class="flex justify-between items-center bg-gray-800 rounded-md lg:rounded-lg p-1.5 lg:p-4">
                <div class="flex items-center space-x-1.5 lg:space-x-2">
                  <img v-if="selectedCoin && cryptoStore.getIcon(selectedCoin.symbol)" :src="cryptoStore.getIcon(selectedCoin.symbol)" :alt="selectedCoin.symbol.toUpperCase()" class="w-3.5 h-3.5 lg:w-6 lg:h-6 rounded-full" />
                  <span class="text-xs lg:text-base text-gray-300">Available: {{ selectedCoinBalance ? formatAmount(selectedCoinBalance, selectedCoin.symbol) : '0.00' }} {{ selectedCoin ? selectedCoin.symbol.toUpperCase() : 'CRYPTO' }}</span>
                </div>
                <button
                  @click="setMaxAmount"
                  class="px-2 lg:px-4 py-1 lg:py-2 bg-gray-700 hover:bg-gray-600 text-white text-xs lg:text-sm font-medium rounded-md lg:rounded-lg transition-all duration-300"
                >
                  Use Max
                </button>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2 lg:pt-4">
              <button
                type="submit"
                class="w-full bg-white hover:bg-gray-100 text-black font-bold py-2 lg:py-3 rounded-md lg:rounded-2xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-[1.02] text-xs lg:text-base"
              >
                <div class="flex items-center justify-center space-x-1.5 lg:space-x-2">
                  <svg class="w-3.5 h-3.5 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                  </svg>
                  <span>Send Withdrawal Request</span>
                </div>
              </button>
            </div>
          </form>
        </div>

        <!-- Bank Withdrawal Form -->
        <div
          v-if="activeTab === 'bank'"
          class="bg-black rounded-xl lg:rounded-2xl p-1.5 lg:p-3 border border-gray-800 h-[calc(100vh-130px)] lg:h-[calc(100vh-150px)]"
        >
          <div class="text-center mb-1 lg:mb-3">
            <h2 class="text-lg lg:text-3xl font-bold text-white mb-0.5 lg:mb-1">Bank Withdrawal</h2>
            <p class="text-gray-400 text-xs lg:text-base">Transfer funds to your bank account</p>
          </div>

          <!-- Bank Server Response Message -->
          <div v-if="serverMessage && activeTab === 'bank'" class="mb-1 lg:mb-2">
            <div
              :class="[
                'text-center py-1.5 px-3 lg:py-3 lg:px-6 rounded-md lg:rounded-xl font-medium text-xs lg:text-sm',
                messageType === 'success'
                  ? 'bg-green-900 text-green-200 border border-green-800'
                  : 'bg-red-900 text-red-200 border border-red-800'
              ]"
            >
              {{ serverMessage }}
            </div>
          </div>

          <form @submit.prevent="submitWithdrawal" class="space-y-1.5 lg:space-y-3">
            <!-- Account Holder Name -->
            <div class="space-y-0.5 lg:space-y-1">
              <label class="block text-xs lg:text-base font-semibold text-white">Account Holder Name</label>
              <div class="relative">
                <input
                  type="text"
                  name="account_holder_name"
                  class="w-full bg-black border border-gray-700 rounded-md lg:rounded-2xl p-2 lg:p-3 pl-8 lg:pl-12 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-300 text-white placeholder-gray-400 text-xs lg:text-base"
                  placeholder="Enter account holder name"
                />
                <div class="absolute left-2.5 lg:left-4 top-1/2 transform -translate-y-1/2">
                  <svg class="w-3.5 h-3.5 lg:w-5 lg:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Bank Name -->
            <div class="space-y-0.5 lg:space-y-1">
              <label class="block text-xs lg:text-base font-semibold text-white">Bank Name</label>
              <div class="relative">
                <input
                  type="text"
                  name="bank_name"
                  class="w-full bg-black border border-gray-700 rounded-md lg:rounded-2xl p-2 lg:p-3 pl-8 lg:pl-12 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-300 text-white placeholder-gray-400 text-xs lg:text-base"
                  placeholder="Enter bank name"
                />
                <div class="absolute left-2.5 lg:left-4 top-1/2 transform -translate-y-1/2">
                  <svg class="w-3.5 h-3.5 lg:w-5 lg:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Account Number -->
            <div class="space-y-0.5 lg:space-y-1">
              <label class="block text-xs lg:text-base font-semibold text-white">Account Number</label>
              <div class="relative">
                <input
                  type="text"
                  name="bank_account_number"
                  class="w-full bg-black border border-gray-700 rounded-md lg:rounded-2xl p-2 lg:p-3 pl-8 lg:pl-12 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-300 text-white placeholder-gray-400 text-xs lg:text-base"
                  placeholder="Enter account number"
                />
                <div class="absolute left-2.5 lg:left-4 top-1/2 transform -translate-y-1/2">
                  <svg class="w-3.5 h-3.5 lg:w-5 lg:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Amount -->
            <div class="space-y-0.5 lg:space-y-1">
              <label class="block text-xs lg:text-base font-semibold text-white">Withdraw Amount (USDT)</label>
              <div class="relative">
                <input
                  type="number"
                  name="bank_withdraw_amount"
                  step="any"
                  class="w-full bg-black border border-gray-700 rounded-md lg:rounded-2xl p-2 lg:p-3 pl-8 lg:pl-12 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-300 text-white placeholder-gray-400 text-xs lg:text-base"
                  placeholder="Enter amount"
                />
                <div class="absolute left-2.5 lg:left-4 top-1/2 transform -translate-y-1/2">
                  <svg class="w-3.5 h-3.5 lg:w-5 lg:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                  </svg>
                </div>
              </div>

              <!-- Balance Info -->
              <div class="flex justify-between items-center bg-gray-800 rounded-md lg:rounded-lg p-1 lg:p-2">
                <div class="flex items-center space-x-1.5 lg:space-x-2">
                  <img v-if="cryptoStore.getIcon('usdt')" :src="cryptoStore.getIcon('usdt')" alt="USDT" class="w-3.5 h-3.5 lg:w-6 lg:h-6 rounded-full" />
                  <span class="text-xs lg:text-base text-gray-300">Available: {{ liveBalances.usdt_balance ? formatAmount(liveBalances.usdt_balance, 'usdt') : '0.00' }} USDT</span>
                </div>
                <button
                  @click="setMaxAmount"
                  class="px-2 lg:px-4 py-1 lg:py-2 bg-gray-700 hover:bg-gray-600 text-white text-xs lg:text-sm font-medium rounded-md lg:rounded-lg transition-all duration-300"
                >
                  Use Max
                </button>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-1 lg:pt-2">
              <button
                type="submit"
                class="w-full bg-white hover:bg-gray-100 text-black font-bold py-2 lg:py-2.5 rounded-md lg:rounded-2xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-[1.02] text-xs lg:text-base"
              >
                <div class="flex items-center justify-center space-x-1.5 lg:space-x-2">
                  <svg class="w-3.5 h-3.5 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                  </svg>
                  <span>Send Withdrawal Request</span>
                </div>
              </button>
            </div>
          </form>
        </div>

        <!-- History Display -->
        <div v-if="activeTab === 'history'" class="bg-black history-section">
          <div class="p-2 lg:p-4">
            <div class="text-center mb-2 lg:mb-4">
              <h2 class="text-sm lg:text-xl font-bold text-white mb-0.5 lg:mb-1">Withdrawal History</h2>
              <p class="text-gray-400 text-xs">Track all your withdrawal requests</p>
            </div>

            <!-- History Server Response Message -->
            <div v-if="serverMessage && activeTab === 'history'" class="mb-2 lg:mb-4">
              <div
                :class="[
                  'text-center py-1 px-2 lg:py-2 lg:px-4 rounded-md font-medium text-xs',
                  messageType === 'success'
                    ? 'bg-green-900 text-green-200 border border-green-800'
                    : 'bg-red-900 text-red-200 border border-red-800'
                ]"
              >
                {{ serverMessage }}
              </div>
            </div>

            <div v-if="withdrawalsData.length === 0" class="text-center py-8 lg:py-16">
              <div class="bg-gray-800 rounded-lg p-4 lg:p-6 border border-gray-700">
                <svg class="w-8 h-8 lg:w-12 lg:h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-sm font-semibold text-gray-300 mb-1">No withdrawal history</h3>
                <p class="text-gray-500 text-xs">Your withdrawal requests will appear here</p>
              </div>
            </div>

            <div v-else class="space-y-2 lg:space-y-3">
              <div
                v-for="withdrawal in withdrawalsData"
                :key="withdrawal.id"
                class="bg-gray-800 rounded-md p-3 lg:p-4 border border-gray-700 shadow-sm hover:shadow-md transition-all duration-300"
              >
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-1 lg:mb-2">
                  <div class="flex items-center space-x-1 lg:space-x-2 mb-1 lg:mb-0">
                    <div :class="[
                      'flex-shrink-0 w-4 h-4 lg:w-8 lg:h-8 rounded-full flex items-center justify-center',
                      withdrawal.coin_id ? 'bg-blue-900' : 'bg-purple-900'
                    ]">
                      <svg v-if="withdrawal.coin_id" class="w-2 h-2 lg:w-4 lg:h-4 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                      </svg>
                      <svg v-else class="w-2 h-2 lg:w-4 lg:h-4 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                      </svg>
                    </div>
                    <div>
                      <h3 class="text-xs lg:text-sm font-bold text-white">{{ withdrawal.coin_id ? 'Crypto' : 'Bank' }} Withdrawal</h3>
                      <p class="text-xs text-gray-400">{{ new Date(withdrawal.created_at).toLocaleDateString() }}</p>
                    </div>
                  </div>

                  <div :class="[
                    'inline-flex items-center px-1 lg:px-2 py-0.5 rounded-full text-xs font-semibold self-start lg:self-auto',
                    withdrawal.status === 'pending' || withdrawal.status === 'Under Review' ? 'bg-yellow-900 text-yellow-200 border border-yellow-800' :
                    withdrawal.status === 'approved' ? 'bg-green-900 text-green-200 border border-green-800' :
                    'bg-red-900 text-red-200 border border-red-800'
                  ]">
                    <div :class="[
                      'w-1 h-1 rounded-full mr-1',
                      withdrawal.status === 'pending' || withdrawal.status === 'Under Review' ? 'bg-yellow-500' :
                      withdrawal.status === 'approved' ? 'bg-green-500' :
                      'bg-red-500'
                    ]"></div>
                    {{ withdrawal.status.charAt(0).toUpperCase() + withdrawal.status.slice(1) }}
                  </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-1 lg:gap-3">
                  <div class="space-y-1">
                    <div class="flex justify-between items-center">
                      <span class="text-xs font-medium text-gray-400">Amount:</span>
                      <span class="text-xs font-bold text-white">{{ formatAmount(withdrawal.amount_withdraw, withdrawal.coin_type?.symbol || 'usdt') }}</span>
                    </div>

                    <div v-if="withdrawal.coin_id" class="space-y-1">
                      <div class="flex justify-between items-center">
                        <span class="text-xs font-medium text-gray-400">Wallet:</span>
                        <span class="text-xs text-white font-mono truncate">{{ withdrawal.crypto_wallet }}</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-xs font-medium text-gray-400">Crypto:</span>
                        <span class="text-xs text-white">{{ withdrawal.coin_type?.coin_name }} ({{ withdrawal.coin_type?.symbol.toUpperCase() }})</span>
                      </div>
                    </div>

                    <div v-else class="space-y-1">
                      <div class="flex justify-between items-center">
                        <span class="text-xs font-medium text-gray-400">Holder:</span>
                        <span class="text-xs text-white truncate">{{ withdrawal.account_holder_name }}</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-xs font-medium text-gray-400">Bank:</span>
                        <span class="text-xs text-white truncate">{{ withdrawal.bank_name }}</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-xs font-medium text-gray-400">Account:</span>
                        <span class="text-xs text-white font-mono">{{ withdrawal.bank_account_number }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="flex items-end justify-end">
                    <button
                      @click="copyHistoryToClipboard"
                      class="inline-flex items-center space-x-1 px-2 py-1.5 lg:px-3 lg:py-2 bg-gray-700 hover:bg-gray-600 text-white rounded transition-all duration-300 text-xs font-medium border border-gray-600"
                    >
                      <ClipboardIcon class="h-3 w-3 lg:h-4 lg:w-4" />
                      <span>Copy All</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
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

/* Large screen optimizations */
@media (min-width: 1024px) {
  .min-h-screen {
    background: #181A20 !important;
  }

  /* Enhanced spacing for large screens */
  .space-y-8 > * + * {
    margin-top: 2rem;
  }

  .space-y-6 > * + * {
    margin-top: 1.5rem;
  }

  /* Better form layouts on desktop */
  .grid-cols-1 {
    grid-template-columns: 1fr;
  }

  /* Professional hover effects */
  .hover\:scale-\[1\.02\]:hover {
    transform: scale(1.02);
  }

  /* Ensure cards fit within viewport on large screens */
  .bg-black.rounded-xl {
    height: calc(100vh - 150px);
  }

  /* Compact form elements on large screens */
  .space-y-6 > * + * {
    margin-top: 1rem !important;
  }

  /* Reduce excessive padding on large screens */
  .p-6 {
    padding: 1rem !important;
  }

  .p-4 {
    padding: 0.75rem !important;
  }

  .p-3 {
    padding: 0.5rem !important;
  }
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .min-h-screen {
    background: #181A20 !important;
  }

  /* Compact spacing for mobile */
  .space-y-6 > * + * {
    margin-top: 1.5rem;
  }

  .space-y-4 > * + * {
    margin-top: 1rem;
  }

  /* Mobile-friendly shadows */
  .shadow-sm {
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  }

  .shadow-md {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  /* Ensure cards fit within mobile viewport */
  .bg-black.rounded-xl {
    height: calc(100vh - 130px);
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

/* Ensure history section background stays black */
.history-section {
  background-color: #181A20 !important;
}

.history-section .bg-black {
  background-color: #181A20 !important;
}

/* Ensure no white background appears anywhere in history */
.history-section,
.history-section *,
.history-section .space-y-2 > * + *,
.history-section .space-y-3 > * + * {
  background-color: #181A20 !important;
}

/* Force black background for all history elements */
.history-section .bg-gray-800 {
  background-color: #1f2937 !important;
}

.history-section .bg-gray-700 {
  background-color: #374151 !important;
}

.history-section .bg-gray-600 {
  background-color: #4b5563 !important;
}

/* Ensure the scrollable area has consistent background */
.history-section.overflow-y-auto {
  background-color: #181A20 !important;
}

/* Remove any default spacing that might cause white gaps */
.history-section .space-y-2 > * + * {
  margin-top: 0.5rem !important;
  background-color: #181A20 !important;
}

.history-section .space-y-3 > * + * {
  margin-top: 0.75rem !important;
  background-color: #181A20 !important;
}
</style>