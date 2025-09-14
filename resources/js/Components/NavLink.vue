<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useCryptoStore } from '@/Store/crypto.js';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

// Props
const props = defineProps({
  isRegistrationDisabled: {
    type: Boolean,
    default: false,
  },
});

// State for mobile navbar toggle
const navbarOpen = ref(false);

// Access user and crypto store
const { props: pageProps } = usePage();
const user = pageProps.auth.user;
const cryptoStore = useCryptoStore();

// Navigation links
const navItems = [
  { name: 'Home', href: '/' },
  { name: 'About', href: '/about#about-us' },
  { name: 'Contact', href: '/about#contact' },
];
</script>

<template>
  <nav class="fixed top-0 w-full bg-gray-200 text-gray-900 z-50">
    <div class="container mx-auto px-4 py-4 flex items-center justify-between flex-wrap">
      <!-- Logo -->
      <Link :href="route('welcome')" class="block py-2 px-4">
        <ApplicationLogo class="h-8 w-auto" />
      </Link>

      <!-- Crypto Stats (Desktop) -->
      <ul class="hidden lg:flex space-x-6">
        <li class="text-center">
          <span class="block text-xs">Last Price</span>
          <span class="text-sm">${{ cryptoStore.getPrice('btc') || 'Loading...' }}</span>
        </li>
        <li class="text-center">
          <span class="block text-xs">24H Change</span>
          <span class="text-sm">{{ cryptoStore.getChange('btc') || 'Loading...' }}%</span>
        </li>
        <li class="text-center">
          <span class="block text-xs">24H Vol</span>
          <span class="text-sm">${{ cryptoStore.getVolume('btc') || 'Loading...' }}</span>
        </li>
        <li class="text-center">
          <span class="block text-xs">Live Price</span>
          <span class="text-sm">${{ cryptoStore.getPrice('btc') || 'Loading...' }}</span>
        </li>
      </ul>

      <!-- Mobile Menu Button -->
      <button
        class="lg:hidden p-2 focus:outline-none"
        @click="navbarOpen = !navbarOpen"
        aria-label="Toggle navigation"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="!navbarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Navigation Links and Auth Links -->
      <div :class="['lg:flex lg:items-center', navbarOpen ? 'block' : 'hidden']" class="w-full lg:w-auto">
        <ul class="lg:flex lg:space-x-6 mt-4 lg:mt-0">
          <li v-for="item in navItems" :key="item.name">
            <a :href="item.href" class="block py-2 px-4 hover:text-blue-400">{{ item.name }}</a>
          </li>
        </ul>
        <div class="flex space-x-4 mt-4 lg:mt-0 lg:ml-6">
          <Link
            v-if="!user"
            :href="route('login')"
            class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 flex items-center"
          >
            <i class="fas fa-user mr-1"></i> Sign In
          </Link>
          <Link
            v-if="!user && !isRegistrationDisabled"
            :href="route('register')"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center"
          >
            <i class="fas fa-user-plus mr-1"></i> Register
          </Link>
          <Link
            v-if="user"
            :href="route('dashboard')"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center"
          >
            Dashboard
          </Link>
        </div>
      </div>
    </div>
  </nav>
</template>