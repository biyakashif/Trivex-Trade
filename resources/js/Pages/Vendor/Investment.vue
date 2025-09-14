<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { ArrowUpIcon } from '@heroicons/vue/24/solid';

const { props } = usePage();
const usdtBalance = ref(props.usdt_balance || 0);
const activeInvestments = ref(props.active_investments || []);
const selectedPlan = ref(null);
const investmentAmount = ref('');
const errorMessage = ref('');
const showForm = ref(false);

// Investment plans
const plans = {
  '7_days': { days: 7, profit: 0.10, min: 1000 },
  '15_days': { days: 15, profit: 0.25, min: 10000 },
  '30_days': { days: 30, profit: 0.50, min: 30000 },
  '60_days': { days: 60, profit: 0.90, min: 50000 },
};

// Computed property for expected profit
const expectedProfit = computed(() => {
  if (!selectedPlan.value || !investmentAmount.value) return 0;
  const plan = plans[selectedPlan.value];
  const amount = parseFloat(investmentAmount.value);
  return (amount * plan.profit).toFixed(2);
});

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

// Start an investment
const startInvestment = async () => {
  errorMessage.value = '';

  try {
    const response = await fetch('/investment/store', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        plan: selectedPlan.value,
        amount: parseFloat(investmentAmount.value),
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || 'Failed to start investment');
    }

    // Update balance and active investments
    usdtBalance.value -= parseFloat(investmentAmount.value);
    activeInvestments.value.unshift(data.investment);
    showForm.value = false;
    selectedPlan.value = null;
    investmentAmount.value = '';
  } catch (error) {
    errorMessage.value = error.message;
  }
};

// Update active investments on mount and every minute
const updateActiveInvestments = () => {
  activeInvestments.value = activeInvestments.value.map((investment) => {
    if (investment.status === 'active' && new Date(investment.ends_at) <= new Date()) {
      // Fetch updated investment data from the server
      fetch('/investment')
        .then((res) => res.json())
        .then((data) => {
          usdtBalance.value = data.usdt_balance;
          activeInvestments.value = data.active_investments;
        });
    }
    return investment;
  });
};

onMounted(() => {
  updateActiveInvestments();
  setInterval(updateActiveInvestments, 60000); // Check every minute
});
</script>

<template>
  <Head title="Grow Your Investment" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center space-x-2">
        <ArrowUpIcon class="h-6 w-6 text-green-400" />
        <h2 class="font-semibold text-xl text-white leading-tight sm:text-2xl">
          Grow Your Investment
        </h2>
      </div>
    </template>

    <div class="max-w-2xl mx-auto py-6 px-4 sm:max-w-full sm:px-6 lg:px-8">
      <!-- Balance Section -->
      <div class="bg-black shadow-md rounded-lg p-6 mb-6 sm:p-4 border border-gray-800">
        <p class="text-sm text-gray-300 sm:text-xs">Current USDT Balance</p>
        <p class="text-2xl font-bold text-white sm:text-xl">
          {{ usdtBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }} USDT
        </p>
      </div>

      <!-- Active Investments -->
      <div v-if="activeInvestments.length" class="mb-6">
        <h3 class="text-lg font-semibold text-white mb-2 sm:text-base">Active Investments</h3>
        <div
          v-for="investment in activeInvestments"
          :key="investment.id"
          class="bg-black shadow-md rounded-lg p-4 mb-2 sm:p-3 border border-gray-800"
        >
          <p class="font-medium text-white sm:text-sm">
            {{ formatPlanName(investment.plan) }} Plan
          </p>
          <p class="text-sm text-gray-300 sm:text-xs">
            Amount: {{ investment.amount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }} USDT
          </p>
          <p class="text-sm text-green-400 sm:text-xs">
            Profit: {{ investment.profit.toLocaleString('en-US', { minimumFractionDigits: 2 }) }} USDT
          </p>
          <p class="text-sm text-gray-300 sm:text-xs">
            Time Remaining: {{ getTimeRemaining(investment.ends_at) }}
          </p>
        </div>
      </div>

      <!-- Investment Plans -->
      <!-- <div class="bg-gradient-to-r from-blue-600 to-purple-600 shadow-md rounded-lg p-6 sm:p-4"> -->

<!-- Investment Plans -->
      <div class="bg-black shadow-lg rounded-xl p-8 sm:p-5 border border-gray-800">
        <h3 class="text-xl font-bold text-white mb-6 sm:text-lg">Investment Plans</h3>

        <!-- Error Message -->
        <div v-if="errorMessage" class="bg-red-900 border-l-4 border-red-500 text-red-200 p-4 mb-4 rounded-r-lg sm:p-3 sm:text-sm">
          <p>{{ errorMessage }}</p>
        </div>

        <!-- Start Investment Button -->
        <button
          @click="showForm = true"
          class="w-full bg-black text-white font-semibold py-3 rounded-lg shadow-md hover:bg-gray-900 transition duration-300 mb-6 sm:py-2 sm:text-sm border border-gray-700"
        >
          Start New Investment
        </button>

        <!-- Investment Form -->
        <div v-if="showForm" class="space-y-6 mb-6 sm:space-y-4">
          <div>
            <label class="block text-sm font-medium text-white mb-2 sm:text-xs">Select Plan</label>
            <select
              v-model="selectedPlan"
              class="w-full p-4 border rounded-lg bg-black text-white border-gray-700 appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 sm:p-3 sm:text-sm"
              style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2024%2024%22%20stroke%3D%22%23ffffff%22%3E%3Cpath%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%222%22%20d%3D%22M19%209l-7%207-7-7%22%20/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1.25rem;"
            >
              <option value="" disabled class="bg-black text-white">Select a plan</option>
              <option v-for="(plan, key) in plans" :key="key" :value="key" class="bg-black text-white">
                {{ formatPlanName(key) }} - {{ plan.profit * 100 }}% Profit
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-white mb-2 sm:text-xs">Investment Amount (USDT)</label>
            <input
              v-model="investmentAmount"
              type="number"
              min="0"
              step="0.01"
              placeholder="Enter amount"
              class="w-full p-4 border rounded-lg bg-black text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:p-3 sm:text-sm border-gray-700"
            />
            <p v-if="selectedPlan" class="text-xs text-gray-400 mt-2 sm:text-xs">
              Minimum: {{ plans[selectedPlan].min }} USDT
            </p>
          </div>

          <div v-if="selectedPlan" class="flex justify-between text-sm sm:text-xs">
            <span class="text-gray-300">Expected Profit:</span>
            <span class="font-medium text-green-400">{{ expectedProfit }} USDT</span>
          </div>

          <div class="flex space-x-3 sm:space-x-2">
            <button
              @click="startInvestment"
              :disabled="!selectedPlan || !investmentAmount"
              class="w-full bg-black text-white font-semibold py-2 sm:py-3 rounded-lg shadow-md hover:bg-gray-900 transition duration-300 disabled:bg-gray-600 disabled:cursor-not-allowed text-sm border border-gray-700"
            >
              Confirm
            </button>
            <button
              @click="showForm = false; selectedPlan = null; investmentAmount = '';"
              class="w-full bg-black text-white font-semibold py-2 sm:py-3 rounded-lg shadow-md hover:bg-gray-900 transition duration-300 text-sm border border-gray-700"
            >
              Cancel
            </button>
          </div>
        </div>

        <!-- Plans List -->
        <div class="space-y-4 sm:space-y-3">
          <div
            v-for="(plan, key) in plans"
            :key="key"
            class="bg-black rounded-lg p-4 hover:bg-gray-900 transition duration-300 sm:p-3 border border-gray-800"
          >
            <h4 class="text-md font-semibold text-white sm:text-sm">
              {{ formatPlanName(key) }} Plan
            </h4>
            <p class="text-sm text-green-400 sm:text-xs">
              Profit: {{ (plan.profit * 100).toFixed(0) }}%
            </p>
            <p class="text-sm text-gray-300 sm:text-xs">
              Minimum: {{ plan.min.toLocaleString() }} USDT
            </p>
            <p class="text-sm text-gray-300 sm:text-xs">
              Duration: {{ plan.days }} Days
            </p>
          </div>
        </div>
      </div>
      <!-- </div> -->
    </div>
  </AuthenticatedLayout>
</template>