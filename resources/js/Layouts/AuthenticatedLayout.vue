<template>
    <div class="min-h-screen bg-[#181A20] text-white">
        <div>
            <slot name="header" />
        </div>
        <Hamburger :sidebarOpen="sidebarOpen" @toggle-sidebar="sidebarOpen = !sidebarOpen" />
    <Sidebar :sidebarOpen="sidebarOpen" />
    <main :class="['transition-all', sidebarOpen ? 'lg:ml-[250px]' : 'lg:ml-0', isFullScreenPage ? 'pt-0' : 'pt-2 lg:pt-[50px]', 'pb-16 lg:pb-0']">
            <div :class="['mx-auto max-w-7xl px-4 sm:px-6 lg:px-8', isFullScreenPage ? 'py-0' : 'py-6']">
                <slot />
            </div>
        </main>
        <FloatingChat :telegram-username="'TrivexSupport'" />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';


import Sidebar from '@/Components/Sidebar.vue';
import Hamburger from '@/Components/Hamburger.vue';
import FloatingChat from '@/Components/FloatingChat.vue';

const page = usePage();
const { props } = page;
const sidebarOpen = ref(false);
let activityInterval = null;

// Check if current page is TradeView or DepositDetails
const isFullScreenPage = computed(() => ['Vendor/TradeView', 'Vendor/DepositDetails', 'Vendor/Withdraw'].includes(page.component));

// Function to send last activity to backend silently
const sendLastActivity = () => {
    // Check if user is still authenticated before sending request
    const user = props.auth?.user;
    if (!user || !user.id) {
        // User is not authenticated, stop the activity tracking
        if (activityInterval) {
            clearInterval(activityInterval);
            activityInterval = null;
        }
        return;
    }

    axios.post('/update-last-activity')
        .then((response) => {
            // Only log success if we actually got a successful response
            if (response.status === 200) {
                console.log('Last activity updated');
            }
        })
        .catch((error) => {
            // If we get 401 or 419 (CSRF token expired), user is logged out, stop tracking
            if (error.response?.status === 401 || error.response?.status === 419) {
                if (activityInterval) {
                    clearInterval(activityInterval);
                    activityInterval = null;
                }
                // Force a page reload to clear any cached state
                window.location.reload();
            }
            // For other errors, also stop tracking to prevent infinite retries
            else if (error.response?.status >= 400) {
                if (activityInterval) {
                    clearInterval(activityInterval);
                    activityInterval = null;
                }
            }
            // Silent error handling - no console error for other errors
        });
};

onMounted(() => {
    const user = props.auth?.user;
    if (user && user.id) {
        // Send immediately when layout loads
        sendLastActivity();

        // Then repeat every 60 seconds if needed
        activityInterval = setInterval(sendLastActivity, 60000);
    }
});

// Watch for authentication state changes
watch(() => props.auth?.user, (newUser, oldUser) => {
    // If user was logged in but now is not (logout scenario)
    if (oldUser && oldUser.id && (!newUser || !newUser.id)) {
        if (activityInterval) {
            clearInterval(activityInterval);
            activityInterval = null;
        }
    }
    // If user was not logged in but now is (login scenario)
    else if (!oldUser && newUser && newUser.id) {
        // Start activity tracking
        sendLastActivity();
        activityInterval = setInterval(sendLastActivity, 60000);
    }
}, { immediate: false });

onUnmounted(() => {
    if (activityInterval) {
        clearInterval(activityInterval);
    }
});
</script>
