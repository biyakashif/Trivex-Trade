<template>
  <!-- Desktop Sidebar -->
  <aside
    :class=" [
      'hidden lg:flex fixed top-0 left-0 h-full bg-[#181A20] transition-all z-[101] flex-col pt-20 shadow-lg',
      sidebarOpen ? 'w-[250px]' : 'w-0 overflow-x-hidden'
    ]"
  >
    <!-- User Info Box -->
    <div class="px-6 py-4 mb-6">
      <div class="bg-gray-900 text-white p-4 rounded-xl shadow-md text-center border border-gray-800 flex flex-col items-center">
        <img
          v-if="user && user.avatar"
          :src="getAvatarUrl(user.avatar)"
          alt="User Avatar"
          class="w-16 h-16 rounded-full mb-3 object-cover"
        />
        <div
          v-else
          class="w-16 h-16 rounded-full mb-3 flex items-center justify-center bg-gray-800 text-gray-400"
        >
          <UserIcon class="w-10 h-10" />
        </div>
        <h6 class="text-lg font-semibold tracking-wide">{{ user?.name }}</h6>
        <div class="text-xs text-gray-400 mt-1 break-words max-w-full">{{ user?.email }}</div>
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
          class="flex items-center px-4 py-3 rounded-lg text-white transition duration-200 ease-in-out hover:bg-[#23262F] hover:pl-6"
          :class="{ 'bg-[#23262F] font-semibold': $page.url === item.href }"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          <span>{{ item.label }}</span>
        </Link>
      </li>
    </ul>

    <!-- Logout Button -->
    <button
      class="m-6 px-4 py-2 bg-[#23262F] text-white rounded-full w-full font-semibold text-base min-h-[2.2rem] transition hover:bg-[#f3f4f6] hover:text-[#181A20] focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
      @click="logout"
    >
      Logout
    </button>
  </aside>

  <!-- Mobile Bottom Navigation -->
  <nav class="lg:hidden fixed bottom-0 left-0 w-full bg-[#181A20] z-[101] flex justify-around items-center py-2 shadow-t border-t border-gray-800">
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
import { Link, router, usePage } from '@inertiajs/vue3';
import { HomeIcon, ChartBarIcon, CurrencyDollarIcon, ArrowUpIcon, UserIcon } from '@heroicons/vue/24/solid';

defineProps({
  sidebarOpen: Boolean,
});

// Read the authenticated user directly from Inertia page props so the sidebar
// always shows the latest values saved in the database (auth.user).
const page = usePage();
const user = page.props.auth.user;


function getAvatarUrl(avatar) {
  if (!avatar) return '/assets/avatar/boys/1.jpg';
  // If avatar is already a full URL (http/https), return as is
  if (/^https?:\/\//.test(avatar)) return avatar;
  // If avatar is already an absolute path (starts with /storage or /assets), return as is
  if (avatar.startsWith('/storage') || avatar.startsWith('/assets')) return avatar;
  // Otherwise, assume it's a filename and prefix with /storage/avatars/
  return `/storage/avatars/${avatar}`;
}

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