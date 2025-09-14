<template>
  <header class="bg-black fixed top-0 left-0 w-full min-h-[50px] sm:min-h-[80px] shadow flex items-center justify-center z-[102] px-6 border-b border-gray-800">
    <!-- SVG with dark theme color -->
    <svg class="absolute top-0 left-0 w-full h-full opacity-10 z-0 fill-current text-gray-700" viewBox="0 0 1440 120" preserveAspectRatio="none">
      <path
        d="M0 0L60 10C120 20 240 40 360 45C480 50 600 40 720 30C840 20 960 10 1080 15C1200 20 1320 40 1380 50L1440 60V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0V0Z"
      />
    </svg>

    <!-- Logo -->
    <Link :href="auth ? route('dashboard') : route('welcome')" class="z-10">
      <ApplicationLogo class="h-6 sm:h-10 text-white" />
    </Link>
  </header>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue' // Import the ApplicationLogo component
import { ref } from 'vue'

defineProps({
  auth: Boolean,
})

const sidebarOpen = ref(false)
const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
  // emit an event or manage sidebar state via Pinia/global store if needed
}
</script>
