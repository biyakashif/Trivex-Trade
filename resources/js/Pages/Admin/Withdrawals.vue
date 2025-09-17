<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  withdrawals: {
    type: Array,
    default: () => [],
  },
});

const activeTab = ref('crypto');

const setActiveTab = (tab) => {
  activeTab.value = tab;
};

const approveWithdrawal = (id) => {
  router.post(route('admin.withdrawals.approve', id), {}, {
    onSuccess: () => {
      // Optionally, you can refresh the page or update the UI
    },
    onError: (errors) => {
      console.error('Error approving withdrawal:', errors);
    },
  });
};

const rejectWithdrawal = (id) => {
  router.post(route('admin.withdrawals.reject', id), {}, {
    onSuccess: () => {
      // Optionally, you can refresh the page or update the UI
    },
    onError: (errors) => {
      console.error('Error rejecting withdrawal:', errors);
    },
  });
};

const editWithdrawal = (id) => {
  router.visit(route('admin.withdrawals.edit', id));
};
</script>

<template>
  <Head title="Withdrawals" />
  <AdminLayout>
    <template #header>
      <h1 class="text-2xl font-bold text-white">Withdrawals</h1>
    </template>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
      <!-- Tabs Navigation -->
      <div class="mb-6">
        <div class="flex space-x-2 bg-transparent p-1 rounded-full">
          <button
            @click="setActiveTab('crypto')"
            :class="[
              'flex-1 py-2 px-4 rounded-full text-sm font-semibold transition-all duration-200',
              activeTab === 'crypto'
                ? 'bg-indigo-600 text-white shadow-md'
                : 'bg-transparent text-gray-300 hover:bg-indigo-600/10'
            ]"
          >
            Crypto Withdrawals
          </button>
          <button
            @click="setActiveTab('bank')"
            :class="[
              'flex-1 py-2 px-4 rounded-full text-sm font-semibold transition-all duration-200',
              activeTab === 'bank'
                ? 'bg-indigo-600 text-white shadow-md'
                : 'bg-transparent text-gray-300 hover:bg-indigo-600/10'
            ]"
          >
            Bank Withdrawals
          </button>
        </div>
      </div>

      <!-- Crypto Withdrawals -->
      <div v-if="activeTab === 'crypto'">
        <h2 class="text-xl font-semibold text-white mb-4">Crypto Withdrawals</h2>
        <div v-for="user in props.withdrawals" :key="user.id" class="space-y-3">
          <div v-if="user.withdraws.some(w => w.coin_id)">
            <h3 class="text-base font-medium text-white">{{ user.name }} ({{ user.email }})</h3>
            <div class="space-y-3">
              <div
                v-for="withdraw in user.withdraws.filter(w => w.coin_id)"
                :key="withdraw.id"
                class="withdraw-card p-4 rounded-xl shadow-sm hover:shadow-lg transform hover:scale-105 transition-all duration-200"
              >
                <div class="flex justify-between items-center mb-2">
                  <span class="text-sm font-semibold text-indigo-700">
                    {{ withdraw.coin_type?.coin_name }} ({{ withdraw.coin_type?.symbol.toUpperCase() }})
                  </span>
                  <span
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-medium',
                      withdraw.status === 'pending' ? 'bg-yellow-200 text-yellow-800' :
                      withdraw.status === 'approved' ? 'bg-green-200 text-green-800' :
                      'bg-red-200 text-red-800'
                    ]"
                  >
                    {{ withdraw.status.charAt(0).toUpperCase() + withdraw.status.slice(1) }}
                  </span>
                </div>
                <div class="text-xs text-gray-600 space-y-1">
                  <p><span class="font-medium">Amount:</span> {{ withdraw.amount_withdraw }}</p>
                  <p><span class="font-medium">Wallet:</span> {{ withdraw.crypto_wallet }}</p>
                  <p><span class="font-medium">Date:</span> {{ new Date(withdraw.created_at).toLocaleString() }}</p>
                </div>
                <div class="mt-3 flex space-x-2">
                  <button
                    v-if="withdraw.status === 'pending'"
                    @click="approveWithdrawal(withdraw.id)"
                    class="flex-1 bg-green-500 text-white text-xs py-2 rounded-full hover:bg-green-600 transition-all duration-200"
                  >
                    Approve
                  </button>
                  <button
                    v-if="withdraw.status === 'pending'"
                    @click="rejectWithdrawal(withdraw.id)"
                    class="flex-1 bg-red-500 text-white text-xs py-2 rounded-full hover:bg-red-600 transition-all duration-200"
                  >
                    Reject
                  </button>
                  <button
                    @click="editWithdrawal(withdraw.id)"
                    class="flex-1 action-btn text-xs py-2 rounded-full"
                  >
                    Edit
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bank Withdrawals -->
      <div v-if="activeTab === 'bank'">
        <h2 class="text-xl font-semibold text-white mb-4">Bank Withdrawals</h2>
        <div v-for="user in props.withdrawals" :key="user.id" class="space-y-3">
          <div v-if="user.withdraws.some(w => !w.coin_id)">
            <h3 class="text-base font-medium text-white">{{ user.name }} ({{ user.email }})</h3>
            <div class="space-y-3">
              <div
                v-for="withdraw in user.withdraws.filter(w => !w.coin_id)"
                :key="withdraw.id"
                class="withdraw-card p-4 rounded-xl shadow-sm hover:shadow-lg transform hover:scale-105 transition-all duration-200"
              >
                <div class="flex justify-between items-center mb-2">
                  <span class="text-sm font-semibold text-teal-700">
                    Bank Transfer
                  </span>
                  <span
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-medium',
                      withdraw.status === 'pending' ? 'bg-yellow-200 text-yellow-800' :
                      withdraw.status === 'approved' ? 'bg-green-200 text-green-800' :
                      'bg-red-200 text-red-800'
                    ]"
                  >
                    {{ withdraw.status.charAt(0).toUpperCase() + withdraw.status.slice(1) }}
                  </span>
                </div>
                <div class="text-xs text-gray-600 space-y-1">
                  <p><span class="font-medium">Amount:</span> {{ withdraw.amount_withdraw }}</p>
                  <p><span class="font-medium">Account Holder:</span> {{ withdraw.account_holder_name }}</p>
                  <p><span class="font-medium">Bank:</span> {{ withdraw.bank_name }}</p>
                  <p><span class="font-medium">Account Number:</span> {{ withdraw.bank_account_number }}</p>
                  <p><span class="font-medium">Date:</span> {{ new Date(withdraw.created_at).toLocaleString() }}</p>
                </div>
                <div class="mt-3 flex space-x-2">
                  <button
                    v-if="withdraw.status === 'pending'"
                    @click="approveWithdrawal(withdraw.id)"
                    class="flex-1 bg-green-500 text-white text-xs py-2 rounded-full hover:bg-green-600 transition-all duration-200"
                  >
                    Approve
                  </button>
                  <button
                    v-if="withdraw.status === 'pending'"
                    @click="rejectWithdrawal(withdraw.id)"
                    class="flex-1 bg-red-500 text-white text-xs py-2 rounded-full hover:bg-red-600 transition-all duration-200"
                  >
                    Reject
                  </button>
                  <button
                    @click="editWithdrawal(withdraw.id)"
                    class="flex-1 action-btn text-xs py-2 rounded-full"
                  >
                    Edit
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>