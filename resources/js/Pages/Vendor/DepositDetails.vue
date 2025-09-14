<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useCryptoStore } from '@/Store/crypto';
import { ref } from 'vue';
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
</script>

<template>
  <Head :title="`${symbol.toUpperCase()}`" />
  <AuthenticatedLayout>
    <div class="py-4 bg-black min-h-screen">
      <div class="max-w-xs mx-auto px-2">
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

          <!-- Success Message -->
          <div v-if="successMessage" class="mb-2 p-1 bg-green-900 text-green-200 rounded-md text-[10px] text-center border border-green-800">
            {{ successMessage }}
          </div>

          <!-- Network -->
          <div>
            <label class="block text-[10px] font-medium text-gray-300 mb-1">Network</label>
            <div class="relative">
              <select
                disabled
                class="block w-full px-1 py-0.5 border border-gray-700 rounded-md bg-black text-[10px] text-white focus:outline-none"
              >
                <option>{{ depositDetails.network }}</option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center pr-1 pointer-events-none">
                <svg class="w-2 h-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>

          <!-- QR Code -->
          <div class="flex justify-center">
            <img
              :src="depositDetails.qr_code ? '/storage/' + depositDetails.qr_code : 'https://via.placeholder.com/100?text=No+QR+Code'"
              alt="QR Code"
              class="w-24 h-24"
            />
          </div>

          <!-- Address -->
          <div>
            <label class="block text-[10px] font-medium text-gray-300 mb-1">Address</label>
            <div class="flex items-center border border-gray-700 p-0.5 rounded-md bg-black">
              <span class="text-[10px] text-white flex-1 break-all">{{ depositDetails.address }}</span>
              <button
                @click="copyAddress"
                :class="['ml-1 px-1 py-0.5 text-white rounded-md text-[10px]', isCopied ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700']"
              >
                {{ isCopied ? 'Copied!' : 'Copy' }}
              </button>
            </div>
            <div v-if="copyError" class="mt-1 text-[10px] text-red-400">
              {{ copyError }}
            </div>
          </div>

          <!-- Warning -->
          <!-- <div v-if="depositDetails.warning" class="mb-2 p-1 bg-yellow-100 text-yellow-800 rounded-md text-[10px]">
            <span class="text-yellow-600 mr-1">⚠️</span>
            {{ depositDetails.warning }}
          </div> -->

          <!-- Additional Details -->
          <div class="text-[10px] space-y-1">
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

          <!-- Amount Input -->
          <div>
            <label class="block text-[10px] font-medium text-gray-300 mb-1">Amount</label>
            <input
              v-model="form.amount"
              type="number"
              step="any"
              placeholder="Enter deposit amount"
              class="block w-full px-1 py-0.5 border border-gray-700 rounded-md text-[10px] bg-black text-white placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <!-- Upload Slip -->
          <div>
            <label class="block text-[10px] font-medium text-gray-300 mb-1">Upload Deposit Slip</label>
            <input
              type="file"
              @change="form.slip = $event.target.files[0]"
              accept="image/*"
              class="block w-full text-[10px] text-white border border-gray-700 rounded-md px-1 py-0.5 bg-black file:mr-2 file:py-0.5 file:px-1 file:rounded-md file:border-0 file:bg-gray-700 file:text-white file:text-[10px] hover:file:bg-gray-600"
            />
          </div>

          <!-- Deposit Button -->
          <button
            @click="submitDeposit"
            :disabled="form.processing"
            class="w-full px-2 py-1 bg-black text-white rounded-md text-[10px] hover:bg-gray-900 border border-gray-700"
          >
            Deposit
          </button>
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