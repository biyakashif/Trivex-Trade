<template>
  <!-- Desktop Sidebar -->
  <aside
    :class="[
      'hidden lg:flex fixed top-0 left-0 h-full bg-black transition-all z-[101] flex-col pt-20 shadow-lg',
      sidebarOpen ? 'w-[250px]' : 'w-0 overflow-x-hidden'
    ]"
  >
    <!-- User Info Box -->
    <div class="px-6 py-4 mb-6">
      <div class="bg-gray-900 text-white p-4 rounded-xl shadow-md text-center border border-gray-800">
        <h6 class="text-lg font-semibold tracking-wide">{{ user?.name }}</h6>
      </div>
    </div>

    <!-- Sidebar Menu -->
    <ul class="flex-grow space-y-1">
      <li
        v-for="item in menu"
        :key="item.label"
        class="px-4"
      >
        <Link
          :href="item.href"
          class="flex items-center px-4 py-3 rounded-lg text-white transition duration-200 ease-in-out hover:bg-blue-700 hover:pl-6"
          :class="{ 'bg-blue-700 font-semibold': $page.url === item.href }"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          <span>{{ item.label }}</span>
        </Link>
      </li>
    </ul>

    <!-- Logout Button -->
    <button
      class="m-6 px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-red-800 transition"
      @click="logout"
    >
      Logout
    </button>
  </aside>

  <!-- Mobile Bottom Navigation -->
  <nav class="lg:hidden fixed bottom-0 left-0 w-full bg-black z-[101] flex justify-around items-center py-2 shadow-t border-t border-gray-800">
    <Link
      v-for="item in menu"
      :key="item.label"
      :href="item.href"
      class="flex flex-col items-center text-white text-sm transition hover:text-blue-400"
      :class="{ 'font-bold text-blue-400': $page.url === item.href }"
    >
      <component :is="item.icon" class="w-6 h-6" />
      <span>{{ item.label }}</span>
    </Link>
  </nav>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { HomeIcon, ChartBarIcon, CurrencyDollarIcon, ArrowUpIcon, UserIcon } from '@heroicons/vue/24/solid';

defineProps({
  sidebarOpen: Boolean,
  user: Object,
});

const menu = [
  { label: 'Home', href: '/dashboard', icon: HomeIcon },
  { label: 'Assets', href: '/deposit', icon: ChartBarIcon },
  { label: 'Trade', href: '/trade/btc', icon: CurrencyDollarIcon },
  { label: 'Grow', href: '/investment', icon: ArrowUpIcon },
  { label: 'Account', href: '/account', icon: UserIcon },
];

function logout() {
  console.log('Initiating logout request to:', route('logout'));
  router.post(route('logout'), {}, {
    onSuccess: () => {
      console.log('Logout successful');
    },
    onError: (errors) => {
      console.error('Logout failed:', errors);
    },
  });
}
</script>