<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

// Define props to receive grouped wallet data, selected user ID, and balances
const props = defineProps({
  groupedWallets: {
    type: Object,
    default: () => ({
      usdt: [],
      btc: [],
      eth: [],
    }),
  },
  selectedUserId: {
    type: [String, Number],
    default: null,
  },
  selectedUser: {
    type: Object,
    default: null,
  },
  balances: {
    type: Object,
    default: () => ({
      usdt_balance: 0,
      btc_balance: 0,
      eth_balance: 0,
    }),
  },
});

// Get the page instance for flash messages
const $page = usePage();

// Safely parse balance values to numbers
const parseBalance = (value, decimals) => {
  const parsed = parseFloat(value || 0);
  return isNaN(parsed) ? 0 : parseFloat(parsed.toFixed(decimals));
};

// State to hold the updated balances
const liveBalances = ref({
  usdt_balance: parseBalance(props.balances.usdt_balance, 2),
  btc_balance: parseBalance(props.balances.btc_balance, 8),
  eth_balance: parseBalance(props.balances.eth_balance, 8),
});

// Make groupedWallets reactive so we can update the status locally
const localGroupedWallets = ref({
  usdt: [...props.groupedWallets.usdt],
  btc: [...props.groupedWallets.btc],
  eth: [...props.groupedWallets.eth],
});

// State for input amounts
const amounts = ref({
  usdt: '',
  btc: '',
  eth: '',
});

// Polling interval ID
let pollingInterval = null;
let isUpdatingBalance = false; // Flag to prevent polling during updates

// Function to fetch updated balances
const fetchBalances = async () => {
  if (!props.selectedUserId || isUpdatingBalance) return;
  try {
    const response = await fetch(`/admin/update-wallet?user_id=${props.selectedUserId}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    // Check if the response is OK
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }

    // Check the content type of the response
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      // If the response is not JSON, it might be a redirect to the login page
      if (response.status === 401 || response.status === 403) {
        // Redirect to login page
        window.location.href = '/login';
        return;
      }
      throw new Error('Response is not JSON');
    }

    const data = await response.json();
    liveBalances.value = {
      usdt_balance: parseBalance(data.props.balances.usdt_balance, 2),
      btc_balance: parseBalance(data.props.balances.btc_balance, 8),
      eth_balance: parseBalance(data.props.balances.eth_balance, 8),
    };
    // Update localGroupedWallets with fresh data
    localGroupedWallets.value = {
      usdt: [...data.props.groupedWallets.usdt],
      btc: [...data.props.groupedWallets.btc],
      eth: [...data.props.groupedWallets.eth],
    };
  } catch (error) {
    console.error('Error fetching updated balances:', error);
    // If the error indicates an authentication issue, redirect to login
    if (error.message.includes('401') || error.message.includes('403')) {
      window.location.href = '/login';
    }
  }
};

// Start polling every 5 seconds as a fallback
onMounted(() => {
  pollingInterval = setInterval(fetchBalances, 5000); // Poll every 5 seconds
});

// Stop polling when the component is unmounted
onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
  isUpdatingBalance = false; // Reset flag on unmount
});

// Computed property to get the selected user's name and email (if available)
const selectedUserName = () => {
  // First try to get from selectedUser prop
  if (props.selectedUser) {
    return props.selectedUser.name || 'Unknown';
  }

  // Fallback to wallet data
  if (props.selectedUserId && localGroupedWallets.value.usdt.length > 0) {
    return localGroupedWallets.value.usdt[0].user?.name || 'Unknown';
  }
  if (props.selectedUserId && localGroupedWallets.value.btc.length > 0) {
    return localGroupedWallets.value.btc[0].user?.name || 'Unknown';
  }
  if (props.selectedUserId && localGroupedWallets.value.eth.length > 0) {
    return localGroupedWallets.value.eth[0].user?.name || 'Unknown';
  }
  return null;
};

const selectedUserEmail = () => {
  // First try to get from selectedUser prop
  if (props.selectedUser) {
    return props.selectedUser.email || null;
  }

  // Fallback to wallet data
  if (props.selectedUserId && localGroupedWallets.value.usdt.length > 0) {
    return localGroupedWallets.value.usdt[0].user?.email || null;
  }
  if (props.selectedUserId && localGroupedWallets.value.btc.length > 0) {
    return localGroupedWallets.value.btc[0].user?.email || null;
  }
  if (props.selectedUserId && localGroupedWallets.value.eth.length > 0) {
    return localGroupedWallets.value.eth[0].user?.email || null;
  }
  return null;
};

// Function to handle approve/reject actions for wallet deposits
const updateWalletStatus = (walletId, action) => {
  // Find the wallet being updated
  const wallet = [
    ...localGroupedWallets.value.usdt,
    ...localGroupedWallets.value.btc,
    ...localGroupedWallets.value.eth,
  ].find(w => w.id === walletId);

  if (!wallet) {
    console.error('Wallet not found:', walletId);
    return;
  }

  const symbol = wallet.symbol;
  const amount = parseFloat(wallet.amount);
  const balanceKey = `${symbol}_balance`;
  const decimals = symbol === 'usdt' ? 2 : 8;

  router.post(route('admin.update-wallet.update', { walletId }), { action }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Update the wallet status in the localGroupedWallets
      const walletIndex = localGroupedWallets.value[symbol].findIndex(w => w.id === walletId);
      if (walletIndex !== -1) {
        localGroupedWallets.value[symbol][walletIndex].status = action === 'approve' ? 'approved' : 'rejected';
      }

      // If the action is 'approve', update the balance immediately
      if (action === 'approve') {
        liveBalances.value[balanceKey] = parseFloat((liveBalances.value[balanceKey] + amount).toFixed(decimals));
      }
    },
    onError: (errors) => {
      console.error('Error updating wallet status:', errors);
      fetchBalances(); // Fetch the latest balances in case of an error
    },
  });
};

// Function to handle balance updates (add or subtract)
const updateBalance = (crypto, action) => {
  const amount = parseFloat(amounts.value[crypto]);
  if (isNaN(amount) || amount === 0) {
    return;
  }

  const balanceKey = `${crypto}_balance`;
  const decimals = crypto === 'usdt' ? 2 : 8;

  // Set flag to prevent polling during update
  isUpdatingBalance = true;

  router.post(route('admin.update-wallet.balance', { userId: props.selectedUserId }), {
    crypto,
    amount,
    action,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Immediately update the balance on the frontend
      if (action === 'add') {
        liveBalances.value[balanceKey] = parseFloat((liveBalances.value[balanceKey] + amount).toFixed(decimals));
      } else if (action === 'subtract') {
        liveBalances.value[balanceKey] = parseFloat((liveBalances.value[balanceKey] - amount).toFixed(decimals));
      }
      amounts.value[crypto] = ''; // Reset the input field

      // Resume polling after a short delay to allow server to process
      setTimeout(() => {
        isUpdatingBalance = false;
      }, 2000); // Wait 2 seconds before resuming polling
    },
    onError: (errors) => {
      console.error('Error updating balance:', errors);
      fetchBalances(); // Fetch the latest balances in case of an error
      isUpdatingBalance = false; // Reset flag on error
    },
  });
};
</script>

<template>
  <Head title="Update Wallet" />
  <AdminLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <h1 class="text-2xl font-bold mb-4">
            Update Wallet
            <span v-if="selectedUserId && selectedUserName()" class="text-lg font-semibold text-gray-600 block mt-1">
              for {{ selectedUserName() }}
              <span v-if="selectedUserEmail()" class="text-sm text-gray-500">({{ selectedUserEmail() }})</span>
            </span>
          </h1>

          <!-- Balances Section -->
          <div v-if="selectedUserId" class="mb-6">
            <h2 class="text-lg font-semibold mb-2">Client Balances</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <!-- USDT Balance -->
              <div class="p-3 border border-gray-200 rounded-md">
                <p class="text-sm font-medium">USDT Balance</p>
                <p class="text-lg font-semibold">{{ liveBalances.usdt_balance.toFixed(2) }} USDT</p>
                <input
                  v-model="amounts.usdt"
                  type="number"
                  step="any"
                  placeholder="Enter amount"
                  class="mt-2 block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                />
                <div class="mt-2 flex space-x-2">
                  <button
                    @click="updateBalance('usdt', 'add')"
                    class="px-3 py-1 rounded text-sm action-btn"
                  >
                    Update
                  </button>
                  <button
                    @click="updateBalance('usdt', 'subtract')"
                    class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600"
                  >
                    Remove
                  </button>
                </div>
              </div>

              <!-- BTC Balance -->
              <div class="p-3 border border-gray-200 rounded-md">
                <p class="text-sm font-medium">BTC Balance</p>
                <p class="text-lg font-semibold">{{ liveBalances.btc_balance.toFixed(8) }} BTC</p>
                <input
                  v-model="amounts.btc"
                  type="number"
                  step="any"
                  placeholder="Enter amount"
                  class="mt-2 block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                />
                <div class="mt-2 flex space-x-2">
                  <button
                    @click="updateBalance('btc', 'add')"
                    class="px-3 py-1 rounded text-sm action-btn"
                  >
                    Update
                  </button>
                  <button
                    @click="updateBalance('btc', 'subtract')"
                    class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600"
                  >
                    Remove
                  </button>
                </div>
              </div>

              <!-- ETH Balance -->
              <div class="p-3 border border-gray-200 rounded-md">
                <p class="text-sm font-medium">ETH Balance</p>
                <p class="text-lg font-semibold">{{ liveBalances.eth_balance.toFixed(8) }} ETH</p>
                <input
                  v-model="amounts.eth"
                  type="number"
                  step="any"
                  placeholder="Enter amount"
                  class="mt-2 block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                />
                <div class="mt-2 flex space-x-2">
                  <button
                    @click="updateBalance('eth', 'add')"
                    class="px-3 py-1 rounded text-sm action-btn"
                  >
                    Update
                  </button>
                  <button
                    @click="updateBalance('eth', 'subtract')"
                    class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Wallet Information -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">USDT</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BTC</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ETH</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                  <!-- USDT Column -->
                  <td class="px-4 py-2 align-top">
                    <div v-if="localGroupedWallets.usdt.length === 0" class="text-gray-500 text-sm">
                      No USDT deposits
                    </div>
                    <div v-else class="space-y-2">
                      <div v-for="wallet in localGroupedWallets.usdt" :key="wallet.id" class="border border-gray-200 rounded-md p-2">
                        <div class="flex items-start">
                          <!-- Deposit Slip Image -->
                          <div v-if="wallet.slip_path" class="mr-3">
                            <a :href="'/storage/' + wallet.slip_path" target="_blank">
                              <img :src="'/storage/' + wallet.slip_path" alt="Deposit Slip" class="w-12 h-12 object-cover rounded">
                            </a>
                          </div>
                          <div v-else class="mr-3 text-gray-500 text-xs">
                            No Image
                          </div>
                          <!-- Wallet Details -->
                          <div class="flex-1">
                            <div class="text-sm">
                              <strong>User:</strong> {{ wallet.user?.name || 'Unknown' }}
                            </div>
                            <div class="text-sm">
                              <strong>Amount:</strong> {{ wallet.amount }} USDT
                            </div>
                            <div class="text-sm">
                              <strong>Date:</strong> {{ new Date(wallet.created_at).toLocaleString() }}
                            </div>
                            <!-- Actions -->
                            <div class="mt-2 flex justify-end space-x-2">
                              <span v-if="wallet.status === 'approved'" class="text-green-600 text-xs font-semibold">
                                Approved
                              </span>
                              <span v-else-if="wallet.status === 'rejected'" class="text-red-600 text-xs font-semibold">
                                Rejected
                              </span>
                              <template v-else>
                                <button
                                  @click="updateWalletStatus(wallet.id, 'approve')"
                                  class="px-2 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600"
                                >
                                  Approve
                                </button>
                                <button
                                  @click="updateWalletStatus(wallet.id, 'reject')"
                                  class="px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600"
                                >
                                  Reject
                                </button>
                              </template>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- BTC Column -->
                  <td class="px-4 py-2 align-top">
                    <div v-if="localGroupedWallets.btc.length === 0" class="text-gray-500 text-sm">
                      No BTC deposits
                    </div>
                    <div v-else class="space-y-2">
                      <div v-for="wallet in localGroupedWallets.btc" :key="wallet.id" class="border border-gray-200 rounded-md p-2">
                        <div class="flex items-start">
                          <!-- Deposit Slip Image -->
                          <div v-if="wallet.slip_path" class="mr-3">
                            <a :href="'/storage/' + wallet.slip_path" target="_blank">
                              <img :src="'/storage/' + wallet.slip_path" alt="Deposit Slip" class="w-12 h-12 object-cover rounded">
                            </a>
                          </div>
                          <div v-else class="mr-3 text-gray-500 text-xs">
                            No Image
                          </div>
                          <!-- Wallet Details -->
                          <div class="flex-1">
                            <div class="text-sm">
                              <strong>User:</strong> {{ wallet.user?.name || 'Unknown' }}
                            </div>
                            <div class="text-sm">
                              <strong>Amount:</strong> {{ wallet.amount }} BTC
                            </div>
                            <div class="text-sm">
                              <strong>Date:</strong> {{ new Date(wallet.created_at).toLocaleString() }}
                            </div>
                            <!-- Actions -->
                            <div class="mt-2 flex justify-end space-x-2">
                              <span v-if="wallet.status === 'approved'" class="text-green-600 text-xs font-semibold">
                                Approved
                              </span>
                              <span v-else-if="wallet.status === 'rejected'" class="text-red-600 text-xs font-semibold">
                                Rejected
                              </span>
                              <template v-else>
                                <button
                                  @click="updateWalletStatus(wallet.id, 'approve')"
                                  class="px-2 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600"
                                >
                                  Approve
                                </button>
                                <button
                                  @click="updateWalletStatus(wallet.id, 'reject')"
                                  class="px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600"
                                >
                                  Reject
                                </button>
                              </template>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- ETH Column -->
                  <td class="px-4 py-2 align-top">
                    <div v-if="localGroupedWallets.eth.length === 0" class="text-gray-500 text-sm">
                      No ETH deposits
                    </div>
                    <div v-else class="space-y-2">
                      <div v-for="wallet in localGroupedWallets.eth" :key="wallet.id" class="border border-gray-200 rounded-md p-2">
                        <div class="flex items-start">
                          <!-- Deposit Slip Image -->
                          <div v-if="wallet.slip_path" class="mr-3">
                            <a :href="'/storage/' + wallet.slip_path" target="_blank">
                              <img :src="'/storage/' + wallet.slip_path" alt="Deposit Slip" class="w-12 h-12 object-cover rounded">
                            </a>
                          </div>
                          <div v-else class="mr-3 text-gray-500 text-xs">
                            No Image
                          </div>
                          <!-- Wallet Details -->
                          <div class="flex-1">
                            <div class="text-sm">
                              <strong>User:</strong> {{ wallet.user?.name || 'Unknown' }}
                            </div>
                            <div class="text-sm">
                              <strong>Amount:</strong> {{ wallet.amount }} ETH
                            </div>
                            <div class="text-sm">
                              <strong>Date:</strong> {{ new Date(wallet.created_at).toLocaleString() }}
                            </div>
                            <!-- Actions -->
                            <div class="mt-2 flex justify-end space-x-2">
                              <span v-if="wallet.status === 'approved'" class="text-green-600 text-xs font-semibold">
                                Approved
                              </span>
                              <span v-else-if="wallet.status === 'rejected'" class="text-red-600 text-xs font-semibold">
                                Rejected
                              </span>
                              <template v-else>
                                <button
                                  @click="updateWalletStatus(wallet.id, 'approve')"
                                  class="px-2 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600"
                                >
                                  Approve
                                </button>
                                <button
                                  @click="updateWalletStatus(wallet.id, 'reject')"
                                  class="px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600"
                                >
                                  Reject
                                </button>
                              </template>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
/* Custom button styles */
.action-btn {
  background-color: #3b82f6;
  color: white;
}

.action-btn:hover {
  background-color: #2563eb;
}
</style>