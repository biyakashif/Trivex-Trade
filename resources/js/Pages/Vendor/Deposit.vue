<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useCryptoStore } from '@/Store/crypto';
import { formatBalance } from '@/utils/formatBalance';
import { ChevronLeftIcon } from '@heroicons/vue/24/solid';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Access the crypto store
const cryptoStore = useCryptoStore();

// Get the current page props
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

// Echo listener
let balanceEchoListener = null;

// Function to fetch updated balances (removed - using Echo for real-time updates)
// const fetchBalances = async () => {
//   try {
//     const response = await fetch(route('deposit'), {
//       headers: {
//         'Accept': 'application/json',
//         'X-Requested-With': 'XMLHttpRequest',
//       },
//     });
//     if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
//     const data = await response.json();
    
//     // Update live balances if data is available
//     if (data.props && data.props.balances) {
//       liveBalances.value = {
//         usdt_balance: data.props.balances.usdt_balance || 0,
//         btc_balance: data.props.balances.btc_balance || 0,
//         eth_balance: data.props.balances.eth_balance || 0,
//       };
//     }
//   } catch (error) {
//     console.error('Error fetching balances:', error);
//   }
// };

// Setup Echo listeners for real-time updates
const setupEchoListeners = () => {
  const userId = pageProps.auth?.user?.id;
  
  console.log('Setting up Echo listeners...');
  console.log('User ID:', userId);
  console.log('Window.Echo:', window.Echo);
  console.log('Page props auth:', pageProps.auth);
  
  if (!userId || !window.Echo) {
    console.warn('User ID or Echo not available for real-time updates');
    return;
  }

  // Check if listener is already set up
  if (balanceEchoListener) {
    console.log('Echo listener already exists, skipping setup');
    return;
  }

  console.log('Setting up Echo listener for user:', userId);

  // Balance updates listener
  try {
    balanceEchoListener = window.Echo.private(`user.${userId}`)
      .listen('.balance.updated', (data) => {
        console.log('Balance updated via Echo:', data);
        if (data.balances) {
          liveBalances.value = {
            usdt_balance: data.balances.usdt_balance || 0,
            btc_balance: data.balances.btc_balance || 0,
            eth_balance: data.balances.eth_balance || 0,
          };
        }
      });
    console.log('Echo listener set up successfully for user:', userId);
  } catch (error) {
    console.error('Failed to set up Echo listener:', error);
  }
};

// List of cryptocurrencies with names, balance keys, and decimal places
const cryptos = [
  { symbol: 'usdt', name: 'Tether', balanceKey: 'usdt_balance', decimals: 2 },
  { symbol: 'btc', name: 'Bitcoin', balanceKey: 'btc_balance', decimals: 8 },
  { symbol: 'eth', name: 'Ethereum', balanceKey: 'eth_balance', decimals: 4 },
];

// Function to navigate to the details page
const goToDetails = (symbol) => {
  router.visit(route('deposit.details', { symbol }));
};

// Function to navigate back
const goBack = () => {
  // most reliable: browser history
  history.back();
};

// Setup Echo listeners on mount
onMounted(() => {
  // Try to set up Echo listeners immediately
  setupEchoListeners();
  
  // Also watch for changes in authentication state
  watch(() => pageProps.auth?.user?.id, (newUserId) => {
    if (newUserId && window.Echo) {
      console.log('User authentication state changed, setting up Echo listeners');
      setupEchoListeners();
    }
  });
});

// Cleanup Echo listeners on unmount
onUnmounted(() => {
  if (balanceEchoListener) {
    try {
      balanceEchoListener.stopListening('.balance.updated');
      console.log('Echo listener cleaned up successfully');
    } catch (error) {
      console.error('Failed to clean up Echo listener:', error);
    }
  }
  
  // Leave the private channel
  const userId = pageProps.auth?.user?.id;
  if (userId && window.Echo) {
    try {
      window.Echo.leave(`user.${userId}`);
      console.log('Left Echo channel successfully');
    } catch (error) {
      console.error('Failed to leave Echo channel:', error);
    }
  }
});

// Helper to produce the amounts shown on the right side (large fiat-like line + small crypto line)
const displayAmounts = (crypto) => {
  const raw = liveBalances.value[crypto.balanceKey] ?? 0;
  
  // Calculate live USD value
  let usdValue = 0;
  if (crypto.symbol === 'usdt') {
    usdValue = raw; // USDT is already in USD
  } else {
    const price = Number(cryptoStore.getPrice(crypto.symbol)) || 0;
    usdValue = raw * price;
  }
  
  const primary = `US$ ${formatBalance(usdValue, 2)}`;
  const secondary = `${formatBalance(raw, crypto.decimals)} ${crypto.symbol.toUpperCase()}`;
  return { primary, secondary };
};
</script>

<template>
  <Head title="Deposit" />
  <AuthenticatedLayout>
    <template #header>
      <!-- blank - custom header inside page -->
    </template>

    <div class="py-6 bg-black min-h-screen">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Top header with back button and illustration -->
        <div class="flex items-start justify-between mb-4 sm:mb-6">
          <div class="flex items-center space-x-4">
            <button @click="goBack" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-blue-500 flex items-center justify-center shadow-md">
              <ChevronLeftIcon class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
            </button>
            <div>
              <h1 class="text-lg sm:text-2xl font-bold text-white">Sending encrypted currency immediately</h1>
              <p class="text-xs sm:text-sm text-gray-400 mt-1">Select a wallet to send encrypted currency</p>
            </div>
          </div>

          <!-- placeholder illustration: keep same dark look but allow image slot -->
          <div class="hidden sm:block w-28 h-20 sm:w-36 sm:h-24 bg-gradient-to-br from-blue-400 to-blue-200 rounded-lg opacity-90"></div>
        </div>

        <!-- Wallet list (large rows) -->
        <div class="bg-black rounded-xl border border-gray-800 overflow-hidden">
          <div v-for="crypto in cryptos" :key="crypto.symbol" @click="goToDetails(crypto.symbol)" class="wallet-row flex items-center justify-between px-3 py-3 sm:px-4 sm:py-5 border-b border-gray-800 cursor-pointer hover:bg-gray-900 transition-colors">
            <div class="flex items-center space-x-3 sm:space-x-4">
              <img :src="cryptoStore.getIcon(crypto.symbol)" alt="icon" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full" />
              <div>
                <div class="text-white text-sm sm:text-lg font-semibold">{{ crypto.name }} Wallet</div>
                <div class="text-xs sm:text-sm text-gray-400">{{ crypto.name }} Coin</div>
              </div>
            </div>

            <div class="text-right">
              <div class="text-base sm:text-xl text-white font-semibold">
                {{ displayAmounts(crypto).primary }}
              </div>
              <div class="text-xs sm:text-sm text-gray-400 mt-1">{{ displayAmounts(crypto).secondary }}</div>
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

/* Only update the background color for action cards/buttons, keep original shape/size */
.actions-card,
.swap-card,
.withdraw-card {
  background: #23262F !important;
  color: #fff !important;
}
.actions-card:hover,
.swap-card:hover,
.withdraw-card:hover {
  background: #f3f4f6 !important;
  color: #181A20 !important;
}

/* Ensure text inside the card also changes color on hover */
.actions-card:hover *,
.swap-card:hover *,
.withdraw-card:hover * {
  color: #181A20 !important;
}

/* New styles for wallet list rows */
.wallet-row {
  background: transparent;
}
.wallet-row:hover {
  background: rgba(255,255,255,0.03);
}

/* Back button focus */
button:focus {
  outline: 2px solid rgba(59,130,246,0.6);
}
</style>