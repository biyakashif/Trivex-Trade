<script setup>
import { ref } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const { props } = usePage();
const users = ref(props.users.data);
const links = ref(props.users.links);
const showModal = ref(false);
const selectedUserInvestments = ref([]);
const investmentError = ref(null);

const viewInvestments = async (userId) => {
  try {
    console.log('Fetching investments for user ID:', userId);
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const response = await fetch(`/admin/investment-history/${userId}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': token,
      },
      credentials: 'include',
    });
    const data = await response.json();
    if (response.ok) {
      selectedUserInvestments.value = data.investments;
      investmentError.value = null;
      showModal.value = true;
    } else {
      console.error('Server responded with:', response.status, data);
      investmentError.value = data.error || 'Failed to fetch investment history';
      selectedUserInvestments.value = [];
      showModal.value = true;
    }
  } catch (error) {
    console.error('Error fetching investment history:', error.message, error.stack);
    investmentError.value = 'Server error while fetching investment history';
    selectedUserInvestments.value = [];
    showModal.value = true;
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedUserInvestments.value = [];
  investmentError.value = null;
};

// Format plan name for display
const formatPlanName = (plan) => {
  return plan.replace('_', ' ').replace('days', 'Days');
};

// Calculate time remaining for active investments
const getTimeRemaining = (endsAt) => {
  const now = new Date();
  const end = new Date(endsAt);
  const diff = end - now;

  if (diff <= 0) return 'Completed';

  const days = Math.floor(diff / (1000 * 60 * 60 * 24));
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  return `${days}d ${hours}h ${minutes}m`;
};
</script>

<template>
  <Head title="Investment History" />
  <AdminLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-4">Investment History</h2>

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
                        @click="viewInvestments(user.id)"
                        class="px-3 py-1 rounded-md text-sm action-btn"
                      >
                        View Investments
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

    <!-- Investment Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-3 w-11/12 max-w-sm max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-base font-semibold text-gray-800">Investment History</h2>
          <button @click="closeModal" class="text-gray-600 hover:text-gray-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div v-if="investmentError" class="mb-2 p-1 bg-red-100 text-red-800 rounded-md text-[10px] text-center">
          {{ investmentError === 'Unauthorized' ? 'You are not authorized to view this user\'s investment history' : investmentError }}
        </div>
        <div v-else-if="selectedUserInvestments.length === 0" class="text-gray-600 text-[10px] text-center">
          No investment history available.
        </div>
        <div v-else class="space-y-2">
          <div v-for="investment in selectedUserInvestments" :key="investment.id" class="border border-gray-200 rounded-md p-2 text-[10px]">
            <div class="flex justify-between">
              <span class="text-gray-800 font-medium">{{ formatPlanName(investment.plan) }} Plan</span>
              <span :class="{
                'text-green-600': investment.status === 'active',
                'text-gray-600': investment.status === 'completed'
              }">
                {{ investment.status.charAt(0).toUpperCase() + investment.status.slice(1) }}
              </span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-600">Amount</span>
              <span class="text-gray-800">{{ investment.amount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }} USDT</span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-600">Profit</span>
              <span class="text-gray-800">{{ investment.profit.toLocaleString('en-US', { minimumFractionDigits: 2 }) }} USDT</span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-600">Time Remaining</span>
              <span class="text-gray-800">{{ getTimeRemaining(investment.ends_at) }}</span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-600">Start Date</span>
              <span class="text-gray-800">{{ new Date(investment.starts_at).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between mt-1">
              <span class="text-gray-600">End Date</span>
              <span class="text-gray-800">{{ new Date(investment.ends_at).toLocaleString() }}</span>
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