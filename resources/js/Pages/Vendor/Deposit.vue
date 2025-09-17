<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useCryptoStore } from '@/Store/crypto';
import { formatBalance } from '@/utils/formatBalance';
import { ArrowsRightLeftIcon, ArrowDownCircleIcon } from '@heroicons/vue/24/solid';

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

// Function to navigate to the swap page
const goToSwap = () => {
  router.visit(route('swap.index'));
};

// Function to navigate to the withdraw page
const goToWithdraw = () => {
  router.visit(route('withdraw'));
};
</script>

<template>
  <Head title="Deposit" />
  <AuthenticatedLayout>
    <template #header>
      <!-- <h1 class="text-2xl font-bold text-white">Deposit</h1> -->
    </template>

    <div class="py-4 bg-black min-h-screen">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Crypto Deposit Section -->
        <div class="mb-6">
          <h2 class="text-lg font-semibold text-white mb-4">Crypto Deposit</h2>
          <div class="space-y-4">
            <div
              v-for="crypto in cryptos"
              :key="crypto.symbol"
              @click="goToDetails(crypto.symbol)"
              class="relative bg-black rounded-xl shadow-md p-4 cursor-pointer hover:scale-105 transition-transform duration-200 border border-gray-800"
            >
              <img
                :src="cryptoStore.getIcon(crypto.symbol)"
                alt="Crypto icon"
                class="w-6 h-6 mr-3"
              />
              <div class="flex justify-between items-center">
                <div>
                  <p class="font-semibold text-white">{{ crypto.symbol.toUpperCase() }}</p>
                  <p class="text-sm text-gray-400">{{ crypto.name }}</p>
                </div>
                <div class="text-sm text-white">
                  <p>Balance: <span :class="crypto.symbol === 'usdt' ? 'text-green-400' : crypto.symbol === 'btc' ? 'text-orange-400' : 'text-blue-400'">{{ formatBalance(props.balances[crypto.balanceKey], crypto.decimals) }}</span> {{ crypto.symbol.toUpperCase() }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions Section -->
        <div class="bg-black shadow-lg rounded-xl p-6 border border-gray-800">
          <h2 class="text-lg font-semibold text-white mb-4">Actions</h2>
          <div class="flex flex-col sm:flex-row sm:justify-center sm:space-x-4 space-y-4 sm:space-y-0">
            <!-- Swap Card -->
            <div
              @click="goToSwap"
              class="relative bg-black rounded-xl shadow-md p-4 cursor-pointer hover:bg-gray-900 transition-transform duration-200 min-w-[200px] flex-1 flex items-center justify-between border border-gray-700 swap-card"
            >
              <ArrowsRightLeftIcon class="w-6 h-6 text-white mr-3" />
              <div class="text-white font-bold">Swap</div>
            </div>

            <!-- Withdraw Card -->
            <div
              @click="goToWithdraw"
              class="relative bg-black rounded-xl shadow-md p-4 cursor-pointer hover:bg-gray-900 transition-transform duration-200 min-w-[200px] flex-1 flex items-center justify-between border border-gray-700 withdraw-card"
            >
              <ArrowDownCircleIcon class="w-6 h-6 text-white mr-3" />
              <div class="text-white font-bold">Withdraw</div>
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
</style>