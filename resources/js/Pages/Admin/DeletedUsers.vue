<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const deletedUsers = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const errorMessage = ref('');

const fetchDeletedUsers = async (page = 1) => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) {
      errorMessage.value = 'CSRF token not found. Please refresh the page.';
      return;
    }
    const response = await fetch(`/admin/deleted-users?page=${page}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': token,
      },
      credentials: 'include',
    });
    if (!response.ok) {
      throw new Error(`Failed to fetch deleted users: ${response.status} ${response.statusText}`);
    }
    const data = await response.json();
    deletedUsers.value = data.data || [];
    currentPage.value = data.current_page || 1;
    lastPage.value = data.last_page || 1;
    total.value = data.total || 0;
    errorMessage.value = '';
  } catch (error) {
    errorMessage.value = `Failed to load deleted users: ${error.message}. Please try again later.`;
    deletedUsers.value = [];
  }
};

const restoreUser = (id) => {
  // Implement restore logic if needed
};

const changePage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    currentPage.value = page;
    fetchDeletedUsers(page);
  }
};

onMounted(() => {
  fetchDeletedUsers();
});
</script>

<template>
  <Head title="Deleted Users" />
  <AdminLayout>
    <div class="py-6">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-4 flex items-center">Deleted Users</h2>
        <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
          {{ errorMessage }}
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-200">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deleted Date</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deleted By</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in deletedUsers" :key="user.id">
                <td class="px-4 py-2">{{ user.name }}</td>
                <td class="px-4 py-2">{{ user.email }}</td>
                <td class="px-4 py-2">{{ user.deleted_at ? new Date(user.deleted_at).toLocaleString() : 'Not Available' }}</td>
                <td class="px-4 py-2">{{ user.deleted_by_admin ? user.deleted_by_admin.name : 'Unknown' }}</td>
              </tr>
              <tr v-if="!deletedUsers.length">
                <td colspan="4" class="px-4 py-2 text-center">No deleted users found.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-4 flex justify-between items-center">
          <button
            @click="changePage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-4 py-2 rounded action-btn disabled:opacity-50"
          >
            Previous
          </button>
          <span>Page {{ currentPage }} of {{ lastPage }} ({{ total }} users)</span>
          <button
            @click="changePage(currentPage + 1)"
            :disabled="currentPage === lastPage"
            class="px-4 py-2 rounded action-btn disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
