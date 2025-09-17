<template>
  <div
    :class="[
      'fixed top-0 left-0 h-full admin-sidebar text-white shadow-lg z-50 transition-transform duration-300',
      sidebarOpen ? 'translate-x-0' : '-translate-x-full',
      'w-64'
    ]"
  >
    <div class="flex flex-col h-full p-4">
      <!-- Sidebar Header -->
      <div class="mb-6 flex items-center justify-between">
        <span class="text-lg font-semibold">Admin Menu</span>
        <button
          class="lg:hidden text-white focus:outline-none"
          @click="$emit('toggle-sidebar')"
        >
          <svg
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Navigation Links -->
      <nav class="flex flex-col space-y-2">
        <Link
          :href="safeRoute('admin.dashboard')"
          class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 w-full"
          :class="{ 'active': route().current('admin.dashboard') }"
          @click="mobileClose"
        >
          Online Users
        </Link>
        <Link
          :href="safeRoute('admin.qr-address-upload')"
          class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 w-full"
          :class="{ 'active': route().current('admin.qr-address-upload') }"
          @click="mobileClose"
        >
          QR & Address
        </Link>
        <Link
          :href="safeRoute('admin.deposit-clients')"
          class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 w-full"
          :class="{ 'active': route().current('admin.deposit-clients') }"
          @click="mobileClose"
        >
          Manage Deposit
        </Link>
        <Link
          :href="safeRoute('admin.trade-loss')"
          class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 w-full"
          :class="{ 'active': route().current('admin.trade-loss') }"
          @click="mobileClose"
        >
          Apply Trade Loss
        </Link>
        <Link
          :href="safeRoute('admin.withdrawals')"
          class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 w-full"
          :class="{ 'active': route().current('admin.withdrawals') }"
          @click="mobileClose"
        >
          Manage Withdrawals
        </Link>
        <!-- Announcement link removed per request (route commented out) -->
        <!--
        <Link
          :href="safeRoute('admin.manage.messages')"
          class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 w-full"
          :class="{ 'active': route().current('admin.manage.messages') }"
          @click="mobileClose"
        >
          Announcement
        </Link>
        -->
        <Link
          :href="safeRoute('admin.users.index')"
          class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 w-full"
          :class="{ 'active': route().current('admin.users.index') }"
          @click="mobileClose"
        >
          Manage Users
        </Link>
        <Link
          :href="safeRoute('admin.trade-history')"
          class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 w-full"
          :class="{ 'active': route().current('admin.trade-history') }"
          @click="mobileClose"
        >
          Trade History
        </Link>
                <Link
  :href="route('admin.investment-history')"
          class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 w-full"
  :class="{ 'active': route().current('admin.investment-history') }"
>
  Investment History
</Link>
        <Link
          :href="safeRoute('admin.user-ip-location')"
          class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 w-full"
          :class="{ 'active': route().current('admin.user-ip-location') }"
          @click="mobileClose"
        >
          User IP & Location
        </Link>

      </nav>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({
  sidebarOpen: Boolean,
  safeRoute: Function,
});
const emit = defineEmits(['toggle-sidebar']);

// Close sidebar on mobile after clicking a link
const mobileClose = () => {
  if (window.innerWidth < 1024) { // lg breakpoint
    emit('toggle-sidebar');
  }
};
</script>

<style scoped>
/* Smooth transition for sidebar */
.transition-transform {
  transition-property: transform;
  transition-duration: 300ms;
  transition-timing-function: ease-in-out;
}
</style>