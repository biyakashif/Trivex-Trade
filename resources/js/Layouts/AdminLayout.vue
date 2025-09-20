<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';

const user = usePage().props.auth.user;
const isRegistrationDisabled = ref(false);
const sidebarOpen = ref(false);
let activityInterval = null;

// Flash message state for auto-disappear
const flashMessage = ref({
  type: null, // 'success' or 'error'
  message: '',
  show: false,
});


// --- ADMIN CONSOLE LOGS ---
// All admin-side logs will appear in the browser console for debugging
function adminLog(...args) {
    console.log('[ADMIN]', ...args);
}
function adminError(...args) {
    console.error('[ADMIN]', ...args);
}

// Function to show flash messages with auto-disappear
const showFlashMessage = (type, message) => {
  flashMessage.value = {
    type,
    message,
    show: true,
  };

  // Auto-hide after 5 seconds
  setTimeout(() => {
    flashMessage.value.show = false;
  }, 5000);
};

// Watch for page flash messages and display them
watch(() => usePage().props.flash, (newFlash) => {
  if (newFlash?.success) {
    showFlashMessage('success', newFlash.success);
  } else if (newFlash?.error) {
    showFlashMessage('error', newFlash.error);
  }
}, { immediate: true });

const safeRoute = (routeName) => {
    try {
        return route(routeName);
    } catch (error) {
        adminError(`Route ${routeName} not found:`, error);
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
        adminLog('Fetched registration status:', isRegistrationDisabled.value);
    } catch (error) {
        adminError('Error fetching registration status:', error);
    }
};

const updateRegistrationStatus = () => {
    adminLog('Updating registration status to:', isRegistrationDisabled.value);
    router.post(
        '/admin/settings/registration-status',
        { registrationDisabled: isRegistrationDisabled.value },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                adminLog('Registration status updated:', isRegistrationDisabled.value);
            },
            onError: (errors) => {
                adminError('Error updating registration status:', errors);
                isRegistrationDisabled.value = !isRegistrationDisabled.value; // Revert on error
            },
        }
    );
};

const logout = () => {
    adminLog('Logging out...');
    router.post('/logout', {}, {
        preserveState: false,
        onSuccess: () => {
            adminLog('Logout successful');
            router.visit('/', { method: 'get' });
        },
        onError: (errors) => {
            adminError('Logout failed:', errors);
        },
    });
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

// Function to send last activity to backend silently (like AuthenticatedLayout)
const sendLastActivity = () => {
    if (!user || !user.id) {
        if (activityInterval) {
            clearInterval(activityInterval);
            activityInterval = null;
        }
        return;
    }
    fetch('/update-last-activity', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        credentials: 'same-origin',
    })
    .then((response) => {
        if (response.status === 200) {
            adminLog('Last activity updated');
        }
        if (response.status === 401 || response.status === 419) {
            if (activityInterval) {
                clearInterval(activityInterval);
                activityInterval = null;
            }
            adminError('Session expired or unauthorized. Reloading...');
            window.location.reload();
        } else if (response.status >= 400) {
            if (activityInterval) {
                clearInterval(activityInterval);
                activityInterval = null;
            }
            adminError('Failed to update last_activity. Stopping interval.');
        }
    })
    .catch((err) => {
        if (activityInterval) {
            clearInterval(activityInterval);
            activityInterval = null;
        }
        adminError('Error sending last activity:', err);
    });
};

onMounted(() => {
    fetchRegistrationStatus();
    if (user && user.id) {
        sendLastActivity();
        activityInterval = setInterval(sendLastActivity, 60000);
    }
});

onUnmounted(() => {
    if (activityInterval) {
        clearInterval(activityInterval);
    }
});
</script>

<template>
    <div class="min-h-screen admin-dark">
        <AdminSidebar :sidebarOpen="sidebarOpen" :safeRoute="safeRoute" @toggle-sidebar="toggleSidebar" />
        <nav class="admin-topnav text-white shadow-lg sticky top-0 z-50">
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
                                class="h-4 w-4 text-blue-400 focus:ring-blue-300 border-gray-600 rounded bg-black"
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
                            <div class="absolute right-0 mt-2 w-48 admin-topnav group-menu rounded-md shadow-lg py-1 z-10 opacity-0 group-hover:opacity-100 group-hover:translate-y-0 transform -translate-y-2 transition-all duration-200">
                                <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-200 hover:bg-gray-700 hover:text-white">
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <main :class="{ 'lg:ml-64': sidebarOpen }">
            <!-- Auto-disappearing Flash Messages -->
            <div
              v-if="flashMessage.show"
              :class="[
                'mb-4 p-4 rounded-md text-sm font-medium transition-all duration-300 ease-in-out transform mx-6 lg:mx-8',
                flashMessage.type === 'success'
                  ? 'bg-green-100 text-green-800 border-l-4 border-green-500'
                  : 'bg-red-100 text-red-800 border-l-4 border-red-500'
              ]"
              style="animation: slideIn 0.3s ease-out;"
            >
              <div class="flex items-center justify-between">
                <span>{{ flashMessage.message }}</span>
                <button
                  @click="flashMessage.show = false"
                  class="ml-4 text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
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

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes shrink {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}
</style>