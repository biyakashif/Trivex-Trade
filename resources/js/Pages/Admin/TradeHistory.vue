<script setup>
import { ref } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3'; // Add Link import
import AdminLayout from '@/Layouts/AdminLayout.vue';

const { props } = usePage();
const users = ref(props.users.data);
const links = ref(props.users.links);
const showModal = ref(false);
const selectedUserTrades = ref([]);
const historyError = ref(null);

const viewHistory = async (userId) => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const response = await fetch(`/admin/trade-history/${userId}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': token,
      },
      credentials: 'include', // Include session cookies
    });
    const data = await response.json();
    if (response.ok) {
      selectedUserTrades.value = data.history;
      historyError.value = null;
      showModal.value = true;
    } else {
      historyError.value = data.error || 'Failed to fetch trade history';
      selectedUserTrades.value = [];
      showModal.value = true;
    }
  } catch (error) {
    console.error('Error fetching trade history:', error);
    historyError.value = 'Server error while fetching trade history';
    selectedUserTrades.value = [];
    showModal.value = true;
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedUserTrades.value = [];
  historyError.value = null;
};
</script>

<template>
  <Head title="Trade History" />
  <AdminLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-4">Trade History</h2>

            <!-- Users Table -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Action
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="user in users" :key="user.id">
                    <td class="px-6 py-4 whitespace-nowrap">{{ user.email }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <button
                        @click="viewHistory(user.id)"
                        class="px-3 py-1 rounded-md text-sm action-btn"
                      >
                        View History
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
              <div class="flex justify-between items-center">
                <div>
                  Showing {{ users.length }} of {{ props.users.total }} users
                </div>
                <div class="flex space-x-2">
                  <template v-for="link in links" :key="link.label">
                    <Link
                      v-if="link.url"
                      :href="link.url"
                      class="px-3 py-1 border rounded-md"
                      :class="{ 'bg-blue-600 text-white': link.active, 'bg-white text-gray-700': !link.active }"
                      v-html="link.label"
                    />
                    <span
                      v-else
                      class="px-3 py-1 border rounded-md text-gray-400"
                      v-html="link.label"
                    />
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- History Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-3 w-11/12 max-w-sm max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-base font-semibold text-gray-800">Trade History</h2>
          <button @click="closeModal" class="text-gray-600 hover:text-gray-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div v-if="historyError" class="mb-2 p-1 bg-red-100 text-red-800 rounded-md text-[10px] text-center">
          {{ historyError === 'Unauthorized' ? 'You are not authorized to view this user\'s trade history' : historyError }}
        </div>
        <div v-else-if="selectedUserTrades.length === 0" class="text-gray-600 text-[10px] text-center">
          No trade history available.
        </div>
        <div v-else class="space-y-2">
          <div v-for="trade in selectedUserTrades" :key="trade.id" class="border border-gray-200 rounded-md p-2 text-[10px]">
            <div class="flex justify-between">
              <span class="text-gray-800 font-medium">{{ trade.symbol.toUpperCase() }}</span>
              <span :class="{
                'text-yellow-600': trade.status === 'pending',
                'text-green-600': trade.status === 'completed',
                'text-red-600': trade.status === 'loss' || trade.status === 'rejected'
              }">
                {{ trade.status.charAt(0).toUpperCase() + trade.status.slice(1) }}
              </span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-600">Amount</span>
              <span class="text-gray-800">{{ trade.trade_amount }} {{ trade.symbol.toUpperCase() }}</span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-600">Profit</span>
              <span class="text-gray-800">{{ trade.profit_earned ?? 0 }} {{ trade.symbol.toUpperCase() }}</span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-600">Date</span>
              <span class="text-gray-800">{{ new Date(trade.created_at).toLocaleString() }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
th:hover {
  background-color: #f3f4f6;
}
</style>