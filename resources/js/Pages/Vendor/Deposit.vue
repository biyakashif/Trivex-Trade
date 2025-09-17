<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useCryptoStore } from '@/Store/crypto';
import { formatBalance } from '@/utils/formatBalance';
import { ChevronLeftIcon } from '@heroicons/vue/24/solid';

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

// Helper to produce the amounts shown on the right side (large fiat-like line + small crypto line)
const displayAmounts = (crypto) => {
  const raw = props.balances[crypto.balanceKey] ?? 0;
  const primary = `US$ ${formatBalance(raw, crypto.decimals)}`;
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
        <div class="flex items-start justify-between mb-6">
          <div class="flex items-center space-x-4">
            <button @click="goBack" class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center shadow-md">
              <ChevronLeftIcon class="w-5 h-5 text-white" />
            </button>
            <div>
              <h1 class="text-2xl font-bold text-white">Sending encrypted currency immediately</h1>
              <p class="text-sm text-gray-400 mt-1">Select a wallet to send encrypted currency</p>
            </div>
          </div>

          <!-- placeholder illustration: keep same dark look but allow image slot -->
          <div class="hidden sm:block w-36 h-24 bg-gradient-to-br from-blue-400 to-blue-200 rounded-lg opacity-90"></div>
        </div>

        <!-- Wallet list (large rows) -->
        <div class="bg-black rounded-xl border border-gray-800 overflow-hidden">
          <div v-for="crypto in cryptos" :key="crypto.symbol" @click="goToDetails(crypto.symbol)" class="wallet-row flex items-center justify-between px-4 py-5 border-b border-gray-800 cursor-pointer hover:bg-gray-900 transition-colors">
            <div class="flex items-center space-x-4">
              <img :src="cryptoStore.getIcon(crypto.symbol)" alt="icon" class="w-10 h-10 rounded-full" />
              <div>
                <div class="text-white text-lg font-semibold">{{ crypto.name }} Wallet</div>
                <div class="text-sm text-gray-400">{{ crypto.name }} Coin</div>
              </div>
            </div>

            <div class="text-right">
              <div class="text-xl text-white font-semibold">
                {{ displayAmounts(crypto).primary }}
              </div>
              <div class="text-sm text-gray-400 mt-1">{{ displayAmounts(crypto).secondary }}</div>
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