
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';

const users = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);
const totalUsers = ref(0);
const errorMessage = ref('');

const blockUserForm = useForm({
    _method: 'POST',
});
const deleteUserForm = useForm({
    _method: 'POST',
});
const approveUserForm = useForm({
    _method: 'POST',
});
const restoreUserForm = useForm({
    _method: 'POST',
});

const showDeleteModal = ref(false);
const userToDelete = ref(null);
const showDeletedUsers = ref(false);
const deletedUsers = ref([]);
const deletedUsersCurrentPage = ref(1);
const deletedUsersLastPage = ref(1);
const deletedUsersTotal = ref(0);

const fetchUsers = async (page = 1) => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) {
      errorMessage.value = 'CSRF token not found. Please refresh the page.';
      return;
    }

    const response = await fetch(`/admin/users?page=${page}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': token,
      },
      credentials: 'include',
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch users: ${response.status} ${response.statusText}`);
    }

    const data = await response.json();
    users.value = data.data || [];
    currentPage.value = data.current_page || 1;
    lastPage.value = data.last_page || 1;
    totalUsers.value = data.total || 0;
    errorMessage.value = '';
  } catch (error) {
    console.error('Error fetching users:', error);
    errorMessage.value = `Failed to load users: ${error.message}. Please try again later.`;
    users.value = [];
  }
};

const blockUser = (id) => {
  blockUserForm.post(`/admin/users/block/${id}`, {
    preserveScroll: true,
    onSuccess: () => {
      blockUserForm.reset();
      fetchUsers(currentPage.value);
    },
    onError: () => {
      errorMessage.value = 'Failed to update user status. Please try again.';
    },
  });
};

const approveUser = (id) => {
  approveUserForm.post(`/admin/users/approve/${id}`, {
    preserveScroll: true,
    onSuccess: () => {
      approveUserForm.reset();
      fetchUsers(currentPage.value);
    },
    onError: () => {
      errorMessage.value = 'Failed to approve user. Please try again.';
    },
  });
};

const openDeleteModal = (id) => {
  userToDelete.value = id;
  showDeleteModal.value = true;
};

const confirmDelete = () => {
  if (userToDelete.value) {
    deleteUserForm.post(`/admin/users/delete/${userToDelete.value}`, {
      preserveScroll: true,
      onSuccess: () => {
        deleteUserForm.reset();
        showDeleteModal.value = false;
        fetchUsers(currentPage.value);
      },
      onError: () => {
        showDeleteModal.value = false;
        errorMessage.value = 'Failed to delete user. Please try again.';
      },
    });
  }
};

const cancelDelete = () => {
  showDeleteModal.value = false;
};

const changePage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    currentPage.value = page;
    fetchUsers(page);
  }
};

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
    deletedUsersCurrentPage.value = data.current_page || 1;
    deletedUsersLastPage.value = data.last_page || 1;
    deletedUsersTotal.value = data.total || 0;
  } catch (error) {
    console.error('Error fetching deleted users:', error);
    errorMessage.value = `Failed to load deleted users: ${error.message}. Please try again later.`;
    deletedUsers.value = [];
  }
};

const restoreUser = (id) => {
  restoreUserForm.post(`/admin/deleted-users/restore/${id}`, {
    preserveScroll: true,
    onSuccess: () => {
      restoreUserForm.reset();
      fetchDeletedUsers(deletedUsersCurrentPage.value);
      fetchUsers(currentPage.value); // Refresh main users list
    },
    onError: () => {
      errorMessage.value = 'Failed to restore user. Please try again.';
    },
  });
};

const changeDeletedUsersPage = (page) => {
  if (page >= 1 && page <= deletedUsersLastPage.value) {
    deletedUsersCurrentPage.value = page;
    fetchDeletedUsers(page);
  }
};

const closeDeletedUsersModal = () => {
  showDeletedUsers.value = false;
};

onMounted(() => {
  fetchUsers();
});

// Watch for showDeletedUsers changes to fetch data when modal opens
watch(showDeletedUsers, (newValue) => {
  if (newValue) {
    fetchDeletedUsers();
  }
});
</script>

<template>
  <Head title="Users" />
  <AdminLayout>
    <div class="py-6">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-4 flex items-center">
          New Registered Users
          <button
            @click="showDeletedUsers = true"
            class="ml-4 px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm flex items-center"
            title="View Deleted Users"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            Deleted Users
          </button>
        </h2>

        <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
          {{ errorMessage }}
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-200">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered At</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verification</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in users" :key="user.id">
                <td class="px-4 py-2">{{ user.id }}</td>
                <td class="px-4 py-2">{{ user.name }}</td>
                <td class="px-4 py-2">{{ user.email }}</td>
                <td class="px-4 py-2">
                  {{ user.created_at ? new Date(user.created_at).toLocaleString() : 'Not Available' }}
                </td>
                <td class="px-4 py-2">
                  <span class="px-2 py-1 rounded" :class="user.is_blocked ? 'bg-red-500 text-white' : 'bg-green-500 text-white'">
                    {{ user.is_blocked ? 'Blocked' : 'Active' }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <span class="px-2 py-1 rounded" :class="user.email_verified_at !== null ? 'bg-blue-500 text-white' : 'bg-yellow-500 text-white'">
                    {{ user.email_verified_at !== null ? 'Verified' : 'Unverified' }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <button
                    v-if="user.email_verified_at === null"
                    @click="approveUser(user.id)"
                    class="px-2 py-1 rounded bg-blue-500 text-white hover:bg-blue-600 mr-2"
                  >
                    Approve
                  </button>
                  <button
                    @click="blockUser(user.id)"
                    class="px-2 py-1 rounded text-white mr-2"
                    :class="user.is_blocked ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600'"
                  >
                    {{ user.is_blocked ? 'Unblock' : 'Block' }}
                  </button>
                  <button
                    @click="openDeleteModal(user.id)"
                    class="px-2 py-2 rounded bg-red-700 text-white hover:bg-red-800"
                  >
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="!users.length && !errorMessage">
                <td colspan="7" class="px-4 py-2 text-center">No users found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-between items-center">
          <button
            @click="changePage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50 hover:bg-gray-300"
          >
            Previous
          </button>
          <span>Page {{ currentPage }} of {{ lastPage }} ({{ totalUsers }} users)</span>
          <button
            @click="changePage(currentPage + 1)"
            :disabled="currentPage === lastPage"
            class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50 hover:bg-gray-300"
          >
            Next
          </button>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Confirm Deletion</h3>
            <p class="mb-4">Are you sure you want to delete this user? This action cannot be undone.</p>
            <div class="flex justify-end space-x-4">
              <button @click="cancelDelete" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">Cancel</button>
              <button @click="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete</button>
            </div>
          </div>
        </div>

        <!-- Deleted Users Modal -->
        <div v-if="showDeletedUsers" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-6xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-xl font-bold">Deleted Users</h3>
              <button @click="closeDeletedUsersModal" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deleted Date</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deleted By</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="user in deletedUsers" :key="user.id">
                    <td class="px-4 py-2">{{ user.name }}</td>
                    <td class="px-4 py-2">{{ user.email }}</td>
                    <td class="px-4 py-2">
                      {{ user.deleted_at ? new Date(user.deleted_at).toLocaleString() : 'Not Available' }}
                    </td>
                    <td class="px-4 py-2">
                      {{ user.deleted_by_admin ? user.deleted_by_admin.name : 'Unknown' }}
                    </td>
                    <td class="px-4 py-2">
                      <button
                        @click="restoreUser(user.id)"
                        class="px-3 py-1 rounded bg-green-500 text-white hover:bg-green-600 text-sm"
                      >
                        Restore
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!deletedUsers.length">
                    <td colspan="5" class="px-4 py-2 text-center">No deleted users found.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-4 flex justify-between items-center">
              <button
                @click="changeDeletedUsersPage(deletedUsersCurrentPage - 1)"
                :disabled="deletedUsersCurrentPage === 1"
                class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50 hover:bg-gray-300"
              >
                Previous
              </button>
              <span>Page {{ deletedUsersCurrentPage }} of {{ deletedUsersLastPage }} ({{ deletedUsersTotal }} users)</span>
              <button
                @click="changeDeletedUsersPage(deletedUsersCurrentPage + 1)"
                :disabled="deletedUsersCurrentPage === deletedUsersLastPage"
                class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50 hover:bg-gray-300"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>