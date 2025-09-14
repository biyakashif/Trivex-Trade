<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

// State for users
const users = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);
const errorMessage = ref('');
const successMessage = ref(''); // Add state for success messages

// Fetch users
const fetchUsers = async (page = 1) => {
  try {
    const response = await fetch(`/admin/trade-loss-data?page=${page}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    if (!response.ok) throw new Error(`Failed to fetch users: ${response.statusText}`);
    const data = await response.json();
    console.log('Fetched users:', data);
    users.value = data.data || [];
    currentPage.value = data.current_page || 1;
    lastPage.value = data.last_page || 1;
    errorMessage.value = '';
  } catch (error) {
    console.error('Error fetching users:', error);
    errorMessage.value = 'Failed to load users. Please try again later.';
    users.value = [];
  }
};

// Toggle loss status for a user
const toggleLoss = async (userId, lossApplied) => {
  try {
    // Optimistically update the UI
    const user = users.value.find(u => u.id === userId);
    if (user) {
      user.loss_applied = lossApplied; // Update local state
    }

    const response = await axios.post(`/admin/trade-loss/update-loss/${userId}`, {
      loss_applied: lossApplied,
    });

    successMessage.value = response.data.message;
    errorMessage.value = '';
    console.log(response.data.message);

    // Optionally, refetch users to ensure consistency with backend
    // await fetchUsers(currentPage.value);
  } catch (error) {
    // Revert optimistic update on failure
    const user = users.value.find(u => u.id === userId);
    if (user) {
      user.loss_applied = !lossApplied; // Revert to original state
    }
    console.error('Error updating loss status:', error.response?.data || error.message);
    errorMessage.value = error.response?.data?.message || 'Failed to update loss status';
    successMessage.value = '';
  }
};

// Load users on component mount
onMounted(() => {
  fetchUsers();
});
</script>

<template>
  <Head title="Trade Loss" />
  <AdminLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <h4 class="text-xl font-bold mb-4">Trade Loss</h4>
          <!-- Display success message -->
          <div v-if="successMessage" class="text-green-600 mb-4">
            {{ successMessage }}
          </div>
          <!-- Display error message -->
          <div v-if="errorMessage" class="text-red-600 mb-4">
            {{ errorMessage }}
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Apply Loss</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="user in users" :key="user.id">
                  <td class="px-4 py-2">{{ user.name }}</td>
                  <td class="px-4 py-2">{{ user.email }}</td>
                  <td class="px-4 py-2">
                    <input
                      type="checkbox"
                      :id="'loss_' + user.id"
                      :checked="user.loss_applied"
                      @change="toggleLoss(user.id, $event.target.checked)"
                      :disabled="errorMessage !== ''"
                    />
                    <label :for="'loss_' + user.id" class="ml-2">Apply Loss</label>
                  </td>
                </tr>
                <tr v-if="!users.length && !errorMessage">
                  <td colspan="3" class="text-center py-4">No users found.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Pagination -->
          <div class="mt-4 flex justify-between">
            <button
              @click="fetchUsers(currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
            >
              Previous
            </button>
            <span>Page {{ currentPage }} of {{ lastPage }}</span>
            <button
              @click="fetchUsers(currentPage + 1)"
              :disabled="currentPage === lastPage"
              class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>