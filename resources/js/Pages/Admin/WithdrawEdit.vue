<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
  withdraw: {
    type: Object,
    required: true,
  },
});

const submitForm = (event) => {
  const formData = new FormData(event.target);
  const data = Object.fromEntries(formData.entries());

  // Rename fields for consistency with controller
  if (props.withdraw.coin_id) {
    data.wallet_address = data.account_number;
  }

  router.post(route('admin.withdrawals.update', props.withdraw.id), data, {
    preserveState: true,
  });
};
</script>

<template>
  <Head title="Edit Withdrawal" />
  <AdminLayout>
    <template #header>
      <h1 class="text-2xl font-bold text-gray-900">Edit Withdrawal</h1>
    </template>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
      <div class="max-w-2xl mx-auto">
        <div class="bg-white p-6 rounded-xl shadow-sm">
          <!-- Withdrawal Details -->
          <div class="mb-6 space-y-2">
            <h2 class="text-lg font-semibold text-gray-800">
              {{ withdraw.coin_id ? 'Crypto Withdrawal' : 'Bank Withdrawal' }}
            </h2>
            <p class="text-sm text-gray-600"><span class="font-medium">Amount:</span> {{ withdraw.amount_withdraw }}</p>
            <p class="text-sm text-gray-600">
              <span class="font-medium">Status:</span>
              <span
                :class="[
                  'inline-block px-2 py-1 rounded-full text-xs font-medium',
                  withdraw.status === 'pending' ? 'bg-yellow-200 text-yellow-800' :
                  withdraw.status === 'approved' ? 'bg-green-200 text-green-800' :
                  'bg-red-200 text-red-800'
                ]"
              >
                {{ withdraw.status.charAt(0).toUpperCase() + withdraw.status.slice(1) }}
              </span>
            </p>
            <p class="text-sm text-gray-600"><span class="font-medium">Created At:</span> {{ new Date(withdraw.created_at).toLocaleString() }}</p>
          </div>

          <!-- Edit Form -->
          <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Withdraw Amount -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Withdraw Amount</label>
              <input
                type="number"
                name="amount_withdraw"
                :value="withdraw.amount_withdraw"
                step="any"
                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2 px-3"
                required
              />
            </div>

            <!-- Crypto Withdrawal Fields -->
            <div v-if="withdraw.coin_id">
              <label class="block text-sm font-medium text-gray-700 mb-1">Crypto Wallet</label>
              <input
                type="text"
                name="account_number"
                :value="withdraw.bank_account_number"
                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2 px-3"
                required
              />
            </div>

            <!-- Bank Withdrawal Fields -->
            <div v-else>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Holder</label>
                <input
                  type="text"
                  name="account_holder_name"
                  :value="withdraw.account_holder_name"
                  class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2 px-3"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                <input
                  type="text"
                  name="bank_name"
                  :value="withdraw.bank_name"
                  class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2 px-3"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                <input
                  type="text"
                  name="bank_account_number"
                  :value="withdraw.bank_account_number"
                  class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2 px-3"
                  required
                />
              </div>
            </div>

            <!-- Submit Button -->
            <div class="text-right">
              <button
                type="submit"
                class="bg-indigo-600 text-white font-semibold text-sm py-2 px-6 rounded-full hover:bg-indigo-700 transition-all duration-200"
              >
                Update
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>