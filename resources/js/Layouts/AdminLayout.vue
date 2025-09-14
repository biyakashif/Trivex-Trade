<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const user = usePage().props.auth.user;
const isRegistrationDisabled = ref(false);
const sidebarOpen = ref(false);
let activityInterval = null;

const safeRoute = (routeName) => {
    try {
        return route(routeName);
    } catch (error) {
        console.error(`Route ${routeName} not found:`, error);
        return '#';
    }
};

const fetchRegistrationStatus = async () => {
    try {
        const response = await fetch('/admin/settings/registration-status', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        isRegistrationDisabled.value = data.registrationDisabled;
        console.log('Fetched registration status:', isRegistrationDisabled.value);
    } catch (error) {
        console.error('Error fetching registration status:', error);
    }
};

const updateRegistrationStatus = () => {
    console.log('Updating registration status to:', isRegistrationDisabled.value);
    router.post(
        '/admin/settings/registration-status',
        { registrationDisabled: isRegistrationDisabled.value },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                console.log('Registration status updated:', isRegistrationDisabled.value);
            },
            onError: (errors) => {
                console.error('Error updating registration status:', errors);
                isRegistrationDisabled.value = !isRegistrationDisabled.value; // Revert on error
            },
        }
    );
};

const logout = () => {
    router.post('/logout', {}, {
        preserveState: false,
        onSuccess: () => {
            router.visit('/', { method: 'get' });
        },
        onError: (errors) => {
            console.error('Logout failed:', errors);
        },
    });
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

onMounted(() => {
    fetchRegistrationStatus();
    if (user && user.id) {
        activityInterval = setInterval(() => {
            router.post('/update-last-activity', {}, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    console.log('Last activity updated');
                },
                onError: (errors) => {
                    console.error('Failed to update last_activity:', errors);
                },
            });
        }, 30000);
    }
});

onUnmounted(() => {
    if (activityInterval) {
        clearInterval(activityInterval);
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <AdminSidebar :sidebarOpen="sidebarOpen" :safeRoute="safeRoute" @toggle-sidebar="toggleSidebar" />
        <nav class="bg-blue-800 text-white shadow-lg sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center h-16">
                    <div class="flex items-center">
                        <button class="p-2 focus:outline-none" @click="toggleSidebar">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path v-if="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center space-x-4 ml-auto">
                        <label class="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                v-model="isRegistrationDisabled"
                                @change="updateRegistrationStatus"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <span class="text-sm">Disable Registration</span>
                        </label>
                        <div class="relative group">
                            <button class="flex items-center text-sm font-medium text-white hover:text-gray-200 focus:outline-none">
                                {{ user.name }}
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 opacity-0 group-hover:opacity-100 group-hover:translate-y-0 transform -translate-y-2 transition-all duration-200">
                                <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <main :class="{ 'lg:ml-64': sidebarOpen }">
            <div v-if="$page.props.flash?.success" class="mb-4 p-2 bg-green-100 text-green-800 rounded-md text-sm">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="mb-4 p-2 bg-red-100 text-red-800 rounded-md text-sm">
                {{ $page.props.flash.error }}
            </div>
            <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>

<style scoped>
.group:hover .group-hover\:opacity-100 {
    opacity: 1;
}
.group:hover .group-hover\:translate-y-0 {
    transform: translateY(0);
}
@media (min-width: 640px) {
    .sm\:hidden {
        display: none;
    }
}
main {
    transition: margin-left 300ms ease-in-out;
}
</style>