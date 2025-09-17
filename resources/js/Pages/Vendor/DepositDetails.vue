<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { useCryptoStore } from '@/Store/crypto';
import { ref, computed, onMounted } from 'vue';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/solid'; // Import for history icon

const props = defineProps({
  symbol: { type: String, required: true },
  depositDetails: { type: Object, required: true },
});

const cryptoStore = useCryptoStore();
const isCopied = ref(false);
const copyError = ref(null); // State for copy error message
const showHistory = ref(false);
const history = ref([]);
const historyError = ref(null); // State for history fetch error
const successMessage = ref(null); // State for success message

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
});

const liveRate = computed(() => {
  const fromPrice = Number(cryptoStore.getPrice(props.symbol)) || 0;
  const toPrice = Number(cryptoStore.getPrice(toCrypto.value)) || 0;
  if (fromPrice === 0 || toPrice === 0) return 0;
  return fromPrice / toPrice;
});

const calculatedToAmount = computed(() => {
  const amount = parseFloat(fromAmount.value) || 0;
  const rate = liveRate.value;
  const result = amount * rate;
  const decimals = props.symbol === 'usdt' ? 2 : (props.symbol === 'btc' ? 8 : 4);
  return isNaN(result) ? '' : result.toFixed(decimals);
});

const updateToAmount = () => {
  toAmount.value = calculatedToAmount.value;
};

// Form setup for deposit submission
const form = useForm({
  symbol: props.symbol,
  amount: '',
  slip: null,
});

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
});

// Try to read USDT coin id from globally provided props (same as Withdraw.vue)
const page = usePage();
const usdtCoinId = computed(() => {
  const list = page?.props?.coinTypes || [];
  const found = Array.isArray(list) ? list.find((c) => (c.symbol || '').toLowerCase() === 'usdt') : null;
  return found?.id || '';
});

const performExchange = async () => {
  const amount = parseFloat(fromAmount.value);
  if (!amount || amount <= 0) return;
  const convertedAmount = parseFloat(calculatedToAmount.value);
  try {
    await router.post(route('swap.perform'), {
      from_crypto: props.symbol,
      to_crypto: toCrypto.value,
      from_amount: amount,
      to_amount: convertedAmount,
    }, { preserveState: true });
    successMessage.value = 'Swap completed successfully!';
    fromAmount.value = '';
    toAmount.value = '';
    setTimeout(() => successMessage.value = null, 2500);
  } catch (e) {
    // keep UI minimal per requirement
  }
};
</script>

<template>
  <Head :title="`${symbol.toUpperCase()}`" />
  <AuthenticatedLayout>
    <div class="py-4 bg-black min-h-screen">
      <div class="max-w-md lg:max-w-4xl mx-auto px-4 lg:px-6">
        <div class="bg-black overflow-hidden shadow-lg rounded-lg p-3 space-y-3 border border-gray-800">
          <div class="flex justify-between items-center">
            <div class="flex items-center">
              <img
                :src="cryptoStore.getIcon(symbol) || 'https://via.placeholder.com/20?text=' + symbol.toUpperCase()"
                alt="Crypto icon"
                class="w-5 h-5 mr-1"
              />
              <h1 class="text-lg font-bold text-white">{{ symbol.toUpperCase() }}</h1>
            </div>
            <button
              @click="fetchHistory"
              class="flex items-center text-xs text-blue-400 hover:text-blue-300"
            >
              <ClipboardDocumentListIcon class="w-4 h-4 mr-1" />
              History
            </button>
          </div>

          <!-- Tabs (pill underline like screenshot) -->
          <div class="flex space-x-6 text-sm border-b border-gray-800">
            <button @click="activeTab='receive'" :class="['pb-2', activeTab==='receive' ? 'text-white border-b-2 border-blue-400' : 'text-gray-400']">Receive</button>
            <button @click="activeTab='send'" :disabled="symbol!=='usdt'" :class="['pb-2', activeTab==='send' ? 'text-white border-b-2 border-blue-400' : 'text-gray-400', symbol!=='usdt' ? 'opacity-50 cursor-not-allowed' : '']">Send</button>
            <button @click="activeTab='exchange'" :class="['pb-2', activeTab==='exchange' ? 'text-white border-b-2 border-blue-400' : 'text-gray-400']">Exchange</button>
          </div>

          <!-- Success Message -->
          <div v-if="successMessage" class="mb-2 p-1 bg-green-900 text-green-200 rounded-md text-[10px] text-center border border-green-800">
            {{ successMessage }}
          </div>

          <!-- RECEIVE: Network selector (pills) -->
          <div v-if="activeTab==='receive'" class="space-y-3">
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
          <div v-if="activeTab==='receive'" class="flex justify-center py-2">
            <img
              :src="depositDetails.qr_code ? '/storage/' + depositDetails.qr_code : 'https://via.placeholder.com/100?text=No+QR+Code'"
              alt="QR Code"
              class="w-36 h-36"
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

          <!-- Warning -->
          <!-- <div v-if="depositDetails.warning" class="mb-2 p-1 bg-yellow-100 text-yellow-800 rounded-md text-[10px]">
            <span class="text-yellow-600 mr-1">⚠️</span>
            {{ depositDetails.warning }}
          </div> -->

          <!-- RECEIVE: Additional Details -->
          <div v-if="activeTab==='receive'" class="text-[10px] space-y-1">
            <div class="flex justify-between">
              <label class="text-gray-400">Minimum deposit</label>
              <span class="text-white">{{ depositDetails.min_deposit }}</span>
            </div>
            <div class="flex justify-between">
              <label class="text-gray-400">Deposit account</label>
              <span class="text-white">{{ depositDetails.deposit_account }}</span>
            </div>
            <div class="flex justify-between">
              <label class="text-gray-400">Deposit arrival time</label>
              <span class="text-white">{{ depositDetails.deposit_arrival_time }}</span>
            </div>
            <div class="flex justify-between">
              <label class="text-gray-400">Withdraw enabled time</label>
              <span class="text-white">{{ depositDetails.withdraw_enabled_time }}</span>
            </div>
            <!-- <div v-if="depositDetails.contract_address" class="flex justify-between">
              <label class="text-gray-600">Contract address</label>
              <span>{{ depositDetails.contract_address }}</span>
            </div> -->
          </div>

          <!-- RECEIVE: Amount Input (rounded with suffix) -->
          <div v-if="activeTab==='receive'" class="space-y-2">
            <div class="text-sm font-semibold text-white">Deposit amount</div>
            <div class="flex items-center border border-gray-700 rounded-2xl px-3 py-2 bg-black">
              <span class="mr-2 text-xs bg-gray-800 text-white rounded-full px-2 py-1">{{ symbol.toUpperCase() }}</span>
              <input v-model="form.amount" type="number" step="any" placeholder="0" class="flex-1 bg-transparent text-white text-sm focus:outline-none placeholder-gray-500" />
              <span class="ml-2 text-gray-400 text-sm uppercase">{{ symbol }}</span>
            </div>
          </div>

          <!-- RECEIVE: Upload Slip (tile) -->
          <div v-if="activeTab==='receive'" class="space-y-2">
            <div class="text-sm font-semibold text-white">Deposit screenshot</div>
            <label class="block w-28 h-28 border border-gray-700 rounded-lg flex items-center justify-center cursor-pointer bg-black hover:bg-gray-900">
              <input type="file" class="hidden" accept="image/*" @change="form.slip = $event.target.files[0]" />
              <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
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

          <!-- SEND (USDT only) -->
          <div v-if="activeTab==='send'" class="space-y-2">
            <div v-if="symbol !== 'usdt'" class="text-[10px] text-gray-400">Send is available for USDT only.</div>
            <!-- Mirror Withdraw.vue structure (crypto tab) so backend receives identical fields -->
            <form v-else @submit.prevent="router.post(route('withdraw.store'), $event.target, { preserveState: true, preserveScroll: true })" class="space-y-2">
              <!-- coin_id for USDT so backend identifies crypto -->
              <input type="hidden" name="coin_id" :value="usdtCoinId" />
              <input type="hidden" name="symbol" value="usdt" />
              <div>
                <label class="block text-[10px] font-medium text-gray-300 mb-1">Wallet Address</label>
                <input name="wallet_address" v-model="sendForm.wallet_address" type="text" placeholder="Enter your wallet address" class="block w-full px-1 py-0.5 border border-gray-700 rounded-md text-[10px] bg-black text-white placeholder-gray-400 focus:outline-none" />
              </div>
              <div>
                <label class="block text-[10px] font-medium text-gray-300 mb-1">Amount (USDT)</label>
                <input name="amount_withdraw" v-model="sendForm.amount_withdraw" type="number" step="any" placeholder="Enter amount" class="block w-full px-1 py-0.5 border border-gray-700 rounded-md text-[10px] bg-black text-white placeholder-gray-400 focus:outline-none" />
              </div>
              <button type="submit" class="w-full px-4 py-3 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-500">Send</button>
            </form>
          </div>

          <!-- EXCHANGE -->
          <div v-if="activeTab==='exchange'" class="space-y-2">
            <div>
              <label class="block text-[10px] font-medium text-gray-300 mb-1">From</label>
              <div class="flex items-center space-x-2">
                <div class="text-[10px] text-white px-2 py-1 border border-gray-700 rounded-md uppercase">{{ symbol }}</div>
                <input v-model="fromAmount" @input="updateToAmount" type="number" step="any" placeholder="Amount" class="w-full px-1 py-0.5 border border-gray-700 rounded-md text-[10px] bg-black text-white placeholder-gray-400 focus:outline-none" />
              </div>
            </div>
            <div>
              <label class="block text-[10px] font-medium text-gray-300 mb-1">To</label>
              <div class="flex items-center space-x-2">
                <select v-model="toCrypto" @change="updateToAmount" class="px-2 py-1 border border-gray-700 rounded-md bg-black text-white text-[10px]">
                  <option value="usdt" :disabled="symbol==='usdt'">USDT</option>
                  <option value="btc" :disabled="symbol==='btc'">BTC</option>
                  <option value="eth" :disabled="symbol==='eth'">ETH</option>
                </select>
                <input v-model="toAmount" readonly placeholder="Amount" class="w-full px-1 py-0.5 border border-gray-700 rounded-md text-[10px] bg-black text-white placeholder-gray-400 focus:outline-none" />
              </div>
            </div>
            <div class="text-[10px] text-gray-300">Live Rate: 1 {{ symbol.toUpperCase() }} = {{ liveRate.toFixed(8) }} {{ toCrypto.toUpperCase() }}</div>
            <button @click="performExchange" class="w-full px-4 py-3 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-500">Exchange</button>
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
          <div v-for="deposit in history" :key="deposit.id" class="border border-gray-700 rounded-md p-2 text-[10px] bg-gray-900">
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

/* Only update button and clickable backgrounds/colors, do not change size or border-radius */
button,
button[type="submit"],
button[type="button"],
.copy-btn,
.deposit-btn {
  background: #23262F !important;
  color: #fff !important;
  transition: background 0.2s, color 0.2s;
}
button:hover,
button[type="submit"]:hover,
button[type="button"]:hover,
.copy-btn:hover,
.deposit-btn:hover {
  background: #f3f4f6 !important;
  color: #181A20 !important;
}
</style>