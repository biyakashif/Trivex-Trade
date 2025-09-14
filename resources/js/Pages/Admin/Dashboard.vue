
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Access the authenticated user
const user = usePage().props.auth.user;

// State for online users
const onlineUsers = ref([]);

// Pusher setup
window.Pusher = Pusher;

const pusherConfig = {
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
};

if (pusherConfig.key && pusherConfig.cluster) {
    try {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherConfig.key,
            cluster: pusherConfig.cluster,
            forceTLS: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            },
        });
    } catch (error) {
        console.error('Failed to initialize Laravel Echo in Dashboard:', error);
    }
}

// Fetch online users via Axios
const fetchOnlineUsers = async () => {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) {
            console.error('CSRF token not found');
            return;
        }

        const response = await axios.get('/admin/online-users', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
        });
        onlineUsers.value = response.data.map(user => ({
            id: user.id,
            name: user.name,
            email: user.email,
        }));
        console.log('Fetched online users:', onlineUsers.value); // Debug log
    } catch (error) {
        console.error('Error fetching online users:', error);
    }
};

// Listen for real-time updates via Pusher
const subscribeToOnlineUsers = () => {
    if (window.Echo) {
        const channel = window.Echo.private('online-users');
        
        channel.listen('.user.online.status', (event) => {
            if (event.user_id && typeof event.is_online === 'boolean' && event.user_id !== 1) { // Exclude admin (user_id 1)
                const userIndex = onlineUsers.value.findIndex(u => u.id === event.user_id);
                if (event.is_online && userIndex === -1) {
                    onlineUsers.value.push({
                        id: event.user_id,
                        name: event.name || `User ${event.user_id}`,
                        email: event.email || `user${event.user_id}@example.com`,
                    });
                } else if (!event.is_online && userIndex !== -1) {
                    onlineUsers.value.splice(userIndex, 1);
                }
                console.log('Pusher update - Online users:', onlineUsers.value); // Debug log
            }
        })
        .error((error) => {
            console.error('Pusher channel error in Dashboard:', error);
        });
    }
};

// Lifecycle hooks
onMounted(() => {
    fetchOnlineUsers();
    subscribeToOnlineUsers();
    // Poll for online users periodically as a fallback
    setInterval(fetchOnlineUsers, 15000); // Reduced to 15 seconds for faster updates
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave('online-users');
    }
});
</script>

<template>
  <Head title="Admin Dashboard" />
  <AdminLayout>
    <div class="py-6">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-4">Online Users</h1>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-200">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in onlineUsers" :key="user.id">
                <td class="px-4 py-2 text-green-600">{{ user.name }}</td>
                <td class="px-4 py-2">{{ user.email }}</td>
              </tr>
              <tr v-if="!onlineUsers.length">
                <td colspan="2" class="px-4 py-2 text-center text-gray-500">No online users.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>