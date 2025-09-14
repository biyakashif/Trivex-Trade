<template>
    <div class="min-h-screen bg-black text-white">
        <div>
            <Header :auth="true" />
        </div>
        <Hamburger :sidebarOpen="sidebarOpen" @toggle-sidebar="sidebarOpen = !sidebarOpen" />
        <Sidebar :sidebarOpen="sidebarOpen" :user="$page.props.auth.user" />
        <main :class="['transition-all', sidebarOpen ? 'lg:ml-[250px]' : 'lg:ml-0', 'pt-[50px] sm:pt-[80px] pb-16 lg:pb-0']">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
        <FloatingChat :telegram-username="'TrivexSupport'" />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

import Header from '@/Components/Header.vue';
import Sidebar from '@/Components/Sidebar.vue';
import Hamburger from '@/Components/Hamburger.vue';
import FloatingChat from '@/Components/FloatingChat.vue';

const { props } = usePage();
const sidebarOpen = ref(false);
let activityInterval = null;

// Function to send last activity to backend silently
const sendLastActivity = () => {
    axios.post('/update-last-activity')
        .then(() => {
            console.log('Last activity updated');
        })
        .catch((error) => {
            console.error('Failed to update last activity:', error);
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

onUnmounted(() => {
    if (activityInterval) {
        clearInterval(activityInterval);
    }
});
</script>
