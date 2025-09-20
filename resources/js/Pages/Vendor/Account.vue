<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowRightOnRectangleIcon, ChartBarIcon, WalletIcon, UserIcon, ArrowsRightLeftIcon } from '@heroicons/vue/24/solid';

// Access the authenticated user
const page = usePage();
const user = page.props.auth.user;

// Modal state
const showAvatarModal = ref(false);

// Avatar options
const avatars = [
  ...Array.from({ length: 6 }, (_, i) => ({ path: `/assets/avatar/boys/${i + 1}.jpg`, name: `Boy ${i + 1}` })),
  ...Array.from({ length: 6 }, (_, i) => ({ path: `/assets/avatar/girls/${i + 1}.jpg`, name: `Girl ${i + 1}` })),
];

// Function to handle logout
const logout = () => {
  router.post(route('logout'));
};

// Functions to handle profile actions
const editProfile = () => {
  router.visit('/profile');
};

// Function to select avatar
const selectAvatar = async (avatarPath) => {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    if (!csrfToken) {
      console.error('CSRF token not found');
      return;
    }

    const response = await fetch(route('profile.avatar'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ avatar: avatarPath }),
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ message: 'Unknown error' }));
      console.error('Avatar update failed:', errorData);
      return;
    }

    const data = await response.json();
    user.avatar = data.avatar || avatarPath;
    showAvatarModal.value = false;
  } catch (error) {
    console.error('Error updating avatar:', error);
  }
};
</script>

<template>
  <Head title="Account" />
  <AuthenticatedLayout>
    <template #header>
      <div class="pt-6 pb-3 px-4">
        <h1 class="text-xl font-bold text-white text-center">Account</h1>
      </div>
    </template>

    <div class="pt-0 pb-1 bg-black min-h-screen sm:py-4">
      <div class="px-3 sm:max-w-2xl md:max-w-3xl lg:max-w-4xl mx-auto">
        <!-- User Information Card -->
        <div class="bg-black rounded-xl shadow-lg p-3 mb-2 border border-gray-800 sm:p-4 sm:mb-3">
          <h2 class="text-base font-semibold mb-4 text-white">User Information</h2>
          <div class="space-y-4">
            <div class="flex items-center bg-gray-900 rounded-lg p-3">
              <img
                v-if="user.avatar"
                :src="user.avatar"
                alt="User Avatar"
                class="w-10 h-10 rounded-full mr-3 cursor-pointer"
                @click="showAvatarModal = true"
              />
              <button
                v-else
                @click="showAvatarModal = true"
                class="w-10 h-10 rounded-full mr-3 flex items-center justify-center bg-gray-800 text-gray-400 hover:bg-gray-700 focus:outline-none"
                aria-label="Select avatar"
              >
                <UserIcon class="w-6 h-6" />
              </button>
              <div class="flex-1">
                <div class="text-sm font-medium text-white">{{ user.name }}</div>
                <div class="text-xs text-gray-400">{{ user.email }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Profile Actions Cards -->
        <div class="space-y-2 sm:space-y-3">
          <div class="bg-black rounded-xl shadow-lg p-3 border border-gray-800 sm:p-4">
            <ul class="space-y-1 sm:space-y-2">
              <li>
                <button
                  @click="editProfile"
                  class="w-full flex items-center px-3 py-2 text-left text-white hover:bg-gray-800 rounded-lg transition-colors duration-200 sm:px-4 sm:py-3"
                >
                  <UserIcon class="h-5 w-5 mr-3 text-gray-400" />
                  <span class="text-sm font-medium">Edit Profile</span>
                </button>
              </li>
              <li>
                <button
                  @click="router.visit('/withdraw')"
                  class="w-full flex items-center px-3 py-2 text-left text-white hover:bg-gray-800 rounded-lg transition-colors duration-200 sm:px-4 sm:py-3"
                >
                  <WalletIcon class="h-5 w-5 mr-3 text-gray-400" />
                  <span class="text-sm font-medium">Withdraw</span>
                </button>
              </li>
              <li>
                <button
                  @click="router.visit('/trade')"
                  class="w-full flex items-center px-3 py-2 text-left text-white hover:bg-gray-800 rounded-lg transition-colors duration-200 sm:px-4 sm:py-3"
                >
                  <ChartBarIcon class="h-5 w-5 mr-3 text-gray-400" />
                  <span class="text-sm font-medium">Trade</span>
                </button>
              </li>
              <li>
                <button
                  @click="router.visit('/swap')"
                  class="w-full flex items-center px-3 py-2 text-left text-white hover:bg-gray-800 rounded-lg transition-colors duration-200 sm:px-4 sm:py-3"
                >
                  <ArrowsRightLeftIcon class="h-5 w-5 mr-3 text-gray-400" />
                  <span class="text-sm font-medium">Swap</span>
                </button>
              </li>
            </ul>
          </div>
          <div class="bg-black rounded-xl shadow-lg p-3 border border-gray-800 sm:p-4">
            <button
              @click="logout"
              class="w-full flex items-center px-3 py-2 text-left text-red-400 hover:bg-red-900 hover:text-white rounded-lg transition-colors duration-200 sm:px-4 sm:py-3"
            >
              <ArrowRightOnRectangleIcon class="h-5 w-5 mr-3" />
              <span class="text-sm font-medium">Logout</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Avatar Selection Modal -->
      <div v-if="showAvatarModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl p-4 w-full max-w-sm max-h-[75vh] overflow-y-auto shadow-2xl border border-gray-700">
          <div class="flex justify-between items-center mb-3 pb-2 border-b border-gray-700">
            <h2 class="text-base font-semibold text-white">Select Avatar</h2>
            <button @click="showAvatarModal = false" class="text-gray-400 hover:text-white p-1 rounded-full hover:bg-gray-800 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <img
              v-for="avatar in avatars"
              :key="avatar.path"
              :src="avatar.path"
              :alt="avatar.name"
              class="w-14 h-14 rounded-full cursor-pointer border-2 border-transparent hover:border-blue-500 transition-all duration-200 hover:scale-105"
              @click="selectAvatar(avatar.path)"
            />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.bg-black {
  background-color: #181A20 !important;
}

.text-white {
  color: #fff !important;
}

/* Mobile-specific responsive adjustments */
@media (max-width: 767px) {
  /* Professional height management */
  .min-h-screen {
    min-height: 100vh !important;
    min-height: 100dvh !important; /* Dynamic viewport height for mobile */
  }
  
  /* Reduce font sizes for professional mobile look */
  .text-xl {
    font-size: 1rem !important; /* More compact header */
    line-height: 1.5rem !important;
    font-weight: 600 !important; /* Slightly bolder for better hierarchy */
  }
  
  .text-lg {
    font-size: 0.95rem !important; /* Professional modal header */
    line-height: 1.4rem !important;
    font-weight: 600 !important;
  }
  
  .text-base {
    font-size: 0.8rem !important; /* Compact section headers */
    line-height: 1.2rem !important;
    font-weight: 600 !important;
  }
  
  .text-sm {
    font-size: 0.7rem !important; /* Smaller button text */
    line-height: 0.95rem !important;
    font-weight: 500 !important;
  }
  
  .text-xs {
    font-size: 0.6rem !important; /* Compact user info */
    line-height: 0.85rem !important;
    font-weight: 400 !important;
  }
  
  /* Professional container padding */
  .px-3 {
    padding-left: 0.75rem !important;
    padding-right: 0.75rem !important;
  }
  
  .py-1 {
    padding-top: 0.25rem !important;
    padding-bottom: 0.25rem !important;
  }
  
  .pt-4 {
    padding-top: 0.5rem !important; /* Tighter header spacing */
  }
  
  /* Professional margins */
  .mb-2 {
    margin-bottom: 0.5rem !important;
  }
  
  .mb-3 {
    margin-bottom: 0.75rem !important;
  }
  
  .mb-4 {
    margin-bottom: 0.5rem !important; /* Tighter section spacing */
  }
  
  /* Professional card padding */
  .p-3 {
    padding: 0.75rem !important; /* Balanced padding */
  }
  
  .p-6 {
    padding: 1rem !important;
  }
  
  /* Professional spacing between elements */
  .space-y-4 > * + * {
    margin-top: 0.75rem !important;
  }
  
  .space-y-3 > * + * {
    margin-top: 0.5rem !important;
  }
  
  .space-y-2 > * + * {
    margin-top: 0.375rem !important;
  }
  
  .space-y-1 > * + * {
    margin-top: 0.25rem !important;
  }
  
  /* Professional button styling */
  .px-3 {
    padding-left: 0.75rem !important;
    padding-right: 0.75rem !important;
  }
  
  .py-2 {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
  }
  
  .py-3 {
    padding-top: 0.625rem !important;
    padding-bottom: 0.625rem !important;
  }
  
  /* Professional icon sizes */
  .w-5 {
    width: 1rem !important;
    height: 1rem !important;
  }
  
  .h-5 {
    width: 1rem !important;
    height: 1rem !important;
  }
  
  .w-6 {
    width: 1.125rem !important;
    height: 1.125rem !important;
  }
  
  .h-6 {
    width: 1.125rem !important;
    height: 1.125rem !important;
  }
  
  .w-10 {
    width: 2rem !important;
    height: 2rem !important;
  }
  
  .h-10 {
    width: 2rem !important;
    height: 2rem !important;
  }
  
  .w-16 {
    width: 3rem !important;
    height: 3rem !important;
  }
  
  .h-16 {
    width: 3rem !important;
    height: 3rem !important;
  }
  
  /* Professional border radius */
  .rounded-xl {
    border-radius: 0.5rem !important; /* Slightly more rounded for modern look */
  }
  
  .rounded-lg {
    border-radius: 0.375rem !important;
  }
  
  /* Professional modal sizing */
  .max-h-\[80vh\] {
    max-height: 75vh !important; /* Better modal height */
  }
  
  .w-11\/12 {
    width: 90% !important; /* Slightly wider modal */
  }
  
  /* Professional grid spacing */
  .gap-4 {
    gap: 0.75rem !important;
  }
  
  /* Professional transition effects */
  .transition-colors {
    transition-duration: 0.15s !important; /* Faster transitions for mobile */
  }
  
  /* Professional hover states */
  .hover\:bg-gray-800:hover {
    background-color: rgba(55, 65, 81, 0.8) !important;
  }
  
  .hover\:bg-gray-700:hover {
    background-color: rgba(55, 65, 81, 0.9) !important;
  }
  
  /* Professional shadow effects */
  .shadow-lg {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2) !important;
  }
  
  /* Professional border styling */
  .border-gray-800 {
    border-color: rgba(55, 65, 81, 0.8) !important;
  }
  
  /* Professional background colors */
  .bg-gray-900 {
    background-color: rgba(17, 24, 39, 0.95) !important;
  }
  
  .bg-gray-800 {
    background-color: rgba(31, 41, 55, 0.9) !important;
  }
}
</style>