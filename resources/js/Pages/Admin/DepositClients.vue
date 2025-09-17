<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

// Define props for initial data from Inertia
const props = defineProps({
  initialUsers: {
    type: Array,
    default: () => [],
  },
  initialPage: {
    type: Number,
    default: 1,
  },
  initialLastPage: {
    type: Number,
    default: 1,
  },
});

// State for users and pagination
const users = ref(props.initialUsers);
const currentPage = ref(props.initialPage);
const lastPage = ref(props.initialLastPage);
const errorMessage = ref(''); // Add state for error messages

// Function to fetch users with pagination
const fetchUsers = async (page = 1) => {
  try {
    const response = await fetch(`/admin/deposit-clients?page=${page}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    if (!response.ok) throw new Error(`Failed to fetch users: ${response.statusText}`);
    const data = await response.json();
    console.log('Fetched users:', data); // Log the response for debugging
    users.value = data.data || [];
    currentPage.value = data.current_page || 1;
    lastPage.value = data.last_page || 1;
    errorMessage.value = ''; // Clear any previous error
  } catch (error) {
    console.error('Error fetching users:', error);
    errorMessage.value = 'Failed to load users. Please try again later.';
    users.value = [];
  }
};

// Polling interval ID
let pollingInterval = null;

// Start polling every 10 seconds
onMounted(() => {
  fetchUsers(currentPage.value); // Initial fetch
  pollingInterval = setInterval(() => fetchUsers(currentPage.value), 10000); // Poll every 10 seconds
});

// Stop polling when the component is unmounted
onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
});
</script>

<template>
  <Head title="Deposit Clients" />
  <AdminLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <h1 class="text-2xl font-bold mb-4">Deposit Clients List</h1>



          <!-- User List Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="user in users" :key="user.id">
                  <td class="px-4 py-2 whitespace-nowrap text-sm">{{ user.name }}</td>
                  <td class="px-4 py-2 whitespace-nowrap text-sm">{{ user.email }}</td>
                  <td class="px-4 py-2 whitespace-nowrap">
                    <Link
                      :href="`/admin/update-wallet?user_id=${user.id}`"
                      class="px-3 py-1 rounded text-xs action-btn"
                    >
                      Update Wallet
                    </Link>
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
              class="px-4 py-2 rounded action-btn disabled:opacity-50"
            >
              Previous
            </button>
            <span>Page {{ currentPage }} of {{ lastPage }}</span>
            <button
              @click="fetchUsers(currentPage + 1)"
              :disabled="currentPage === lastPage"
              class="px-4 py-2 rounded action-btn disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>