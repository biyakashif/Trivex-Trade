<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ChartBarIcon, ClipboardIcon } from '@heroicons/vue/24/solid';

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

const activeTab = ref('crypto'); // Default to crypto tab
const selectedCoinId = ref(props.coinTypes[0]?.id || null); // Default to the first coin type
const showHistoryModal = ref(false); // State for history modal

// Compute the available balance based on the selected coin
const availableBalance = () => {
  const selectedCoin = props.coinTypes.find(coin => coin.id === selectedCoinId.value);
  if (!selectedCoin) return 0;
  const balanceKey = `${selectedCoin.symbol}_balance`;
  return props.balances[balanceKey] || 0;
};

// Set active tab
const setActiveTab = (tab) => {
  activeTab.value = tab;
  if (tab === 'history') {
    showHistoryModal.value = true; // Trigger the history display
  } else {
    showHistoryModal.value = false;
  }
};

// Copy history to clipboard
const copyHistoryToClipboard = () => {
  const historyText = props.withdrawals.map(w => 
    `Type: ${w.coin_id ? 'Crypto' : 'Bank'}, Amount: ${w.amount_withdraw}, Status: ${w.status}, Date: ${new Date(w.created_at).toLocaleString()}`
  ).join('\n');
  navigator.clipboard.writeText(historyText).then(() => {
    alert('Withdrawal history copied to clipboard!');
  }).catch(err => {
    console.error('Failed to copy history:', err);
  });
};
</script>

<template>
  <Head title="Withdraw" />
  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-2xl py-3 font-bold text-white text-center">Withdraw</h1>
    </template>

    <div class="bg-black flex flex-col sm:min-h-screen h-[calc(100vh-4rem)] sm:h-auto">
      <!-- Fixed Tabs Navigation -->
      <div class="bg-black shadow-md sticky top-0 z-10 border-b border-gray-800">
        <ul class="flex justify-around items-center p-2">
          <li>
            <button
              @click="setActiveTab('crypto')"
              :class="[
                'py-2 px-4 rounded-lg font-semibold transition-all duration-200',
                activeTab === 'crypto'
                  ? 'bg-gray-800 text-white shadow-lg'
                  : 'text-gray-300 hover:bg-gray-900'
              ]"
            >
              Crypto
            </button>
          </li>
          <li>
            <button
              @click="setActiveTab('bank')"
              :class="[
                'py-2 px-4 rounded-lg font-semibold transition-all duration-200',
                activeTab === 'bank'
                  ? 'bg-gray-800 text-white shadow-lg'
                  : 'text-gray-300 hover:bg-gray-900'
              ]"
            >
              Bank
            </button>
          </li>
          <li>
            <button
              @click="setActiveTab('history')"
              :class="[
                'py-2 px-4 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-1',
                activeTab === 'history'
                  ? 'bg-gray-800 text-white shadow-lg'
                  : 'text-gray-300 hover:bg-gray-900'
              ]"
            >
              <ChartBarIcon class="h-5 w-5" />
              <span>History</span>
            </button>
          </li>
        </ul>
      </div>

      <!-- Main Content -->
      <div class="flex-1 flex justify-center items-start p-2 sm:p-4">
        <!-- Crypto Withdrawal Form -->
        <div
          v-if="activeTab === 'crypto'"
          class="bg-black rounded-xl shadow-md p-6 w-full max-w-md lg:max-w-4xl border border-gray-800"
        >
          <h2 class="text-xl font-bold text-white mb-6 text-center">Crypto Withdrawal</h2>
          <form @submit.prevent="router.post(route('withdraw.store'), $event.target, { preserveState: true })">
            <div class="space-y-6">
              <!-- Select Crypto -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">Select Cryptocurrency</label>
                <select
                  name="coin_id"
                  v-model="selectedCoinId"
                  class="w-full bg-black border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-white"
                >
                  <option v-for="coin in coinTypes" :key="coin.id" :value="coin.id" class="bg-black text-white">
                    {{ coin.coin_name }} ({{ coin.symbol.toUpperCase() }})
                  </option>
                </select>
              </div>

              <!-- Wallet Address -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">Wallet Address</label>
                <input
                  type="text"
                  name="wallet_address"
                  class="w-full bg-black border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-white placeholder-gray-400"
                  placeholder="Enter your wallet address"
                />
              </div>

              <!-- Amount -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">Withdraw Amount</label>
                <div class="flex items-center space-x-2">
                  <input
                    type="number"
                    name="amount_withdraw"
                    step="any"
                    class="w-full bg-black border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-white placeholder-gray-400"
                    placeholder="Enter amount"
                  />
                  <span class="text-gray-300 text-sm">Available: {{ availableBalance() }}</span>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="text-center">
                <button
                  type="submit"
                  class="w-full bg-black text-white font-semibold py-3 rounded-xl shadow-md hover:bg-gray-900 transition-all duration-200 border border-gray-700"
                >
                  Submit
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Bank Withdrawal Form -->
        <div
          v-if="activeTab === 'bank'"
          class="bg-black rounded-xl shadow-md p-6 w-full max-w-md lg:max-w-4xl border border-gray-800"
        >
          <h2 class="text-xl font-bold text-white mb-6 text-center">Bank Withdrawal</h2>
          <form @submit.prevent="router.post(route('withdraw.store'), $event.target, { preserveState: true })">
            <div class="space-y-6">
              <!-- Account Holder Name -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">Account Holder Name</label>
                <input
                  type="text"
                  name="account_holder_name"
                  class="w-full bg-black border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-white placeholder-gray-400"
                  placeholder="Enter account holder name"
                />
              </div>

              <!-- Bank Name -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">Bank Name</label>
                <input
                  type="text"
                  name="bank_name"
                  class="w-full bg-black border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-white placeholder-gray-400"
                  placeholder="Enter bank name"
                />
              </div>

              <!-- Account Number -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">Account Number</label>
                <input
                  type="text"
                  name="bank_account_number"
                  class="w-full bg-black border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-white placeholder-gray-400"
                  placeholder="Enter account number"
                />
              </div>

              <!-- Amount -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">Withdraw Amount (USDT)</label>
                <div class="flex items-center space-x-2">
                  <input
                    type="number"
                    name="bank_withdraw_amount"
                    step="any"
                    class="w-full bg-black border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-white placeholder-gray-400"
                    placeholder="Enter amount"
                  />
                  <span class="text-gray-300 text-sm">Available: {{ balances.usdt_balance }}</span>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="text-center">
                <button
                  type="submit"
                  class="w-full bg-black text-white font-semibold py-3 rounded-xl shadow-md hover:bg-gray-900 transition-all duration-200 border border-gray-700"
                >
                  Submit
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- History Display -->
<div v-if="activeTab === 'history'" class="w-full max-w-md lg:max-w-4xl p-4 space-y-4 pb-20 md:pb-4">
  <div v-if="withdrawals.length === 0" class="text-gray-300 text-sm text-center py-4">
    No withdrawal history available.
  </div>
  <div v-else class="space-y-4">
    <div
      v-for="withdrawal in withdrawals"
      :key="withdrawal.id"
      class="p-4 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 bg-black border border-gray-800"
    >
      <div class="flex justify-between items-center mb-2">
        <span class="text-sm font-semibold text-white">
          {{ withdrawal.coin_id ? 'Crypto' : 'Bank' }}
        </span>
        <span :class="[
          'px-2 py-1 rounded-full text-xs font-medium',
          withdrawal.status === 'pending' ? 'bg-yellow-900 text-yellow-200' :
          withdrawal.status === 'approved' ? 'bg-green-900 text-green-200' :
          'bg-red-900 text-red-200'
        ]">
          {{ withdrawal.status.charAt(0).toUpperCase() + withdrawal.status.slice(1) }}
        </span>
      </div>
      <div class="text-xs text-gray-300 space-y-1">
        <div class="flex justify-between">
          <span class="font-medium">Amount:</span>
          <span>{{ withdrawal.amount_withdraw }}</span>
        </div>
        <div v-if="withdrawal.coin_id" class="space-y-1">
          <div class="flex justify-between">
            <span class="font-medium">Wallet:</span>
            <span>{{ withdrawal.crypto_wallet }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium">Crypto:</span>
            <span>{{ withdrawal.coin_type?.coin_name }} ({{ withdrawal.coin_type?.symbol.toUpperCase() }})</span>
          </div>
        </div>
        <div v-else class="space-y-1">
          <div class="flex justify-between">
            <span class="font-medium">Holder:</span>
            <span>{{ withdrawal.account_holder_name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium">Bank:</span>
            <span>{{ withdrawal.bank_name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium">Account:</span>
            <span>{{ withdrawal.bank_account_number }}</span>
          </div>
        </div>
        <div class="flex justify-between">
          <span class="font-medium">Date:</span>
          <span>{{ new Date(withdrawal.created_at).toLocaleString() }}</span>
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
/* Ensure the body takes up the full height and prevents unwanted scrolling */
body {
  margin: 0;
  padding: 0;
  overflow: hidden;
}

/* Fix for mobile scrolling */
/* @media (max-width: 640px) {
  .bg-gray-100 {
    overflow-y: hidden;
  }
} */

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