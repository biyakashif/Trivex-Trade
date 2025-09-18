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
    // Ensure CSRF cookie is set (for Laravel Sanctum or strict CSRF setups)
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const response = await fetch(route('profile.avatar'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ avatar: avatarPath }),
      credentials: 'include',
    });

    if (!response.ok) {
      const err = await response.json().catch(() => null);
      console.error('Failed to update avatar', response.status, err);
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
      <h1 class="text-xl font-bold text-white text-center pt-4">Account</h1>
    </template>

    <div class="py-2 bg-black min-h-screen sm:py-4">
      <div class="px-4 sm:max-w-2xl md:max-w-3xl lg:max-w-4xl mx-auto">
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
                  <svg class="h-4 w-4 ml-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </li>
              <li>
                <button
                  @click="router.visit('/withdraw')"
                  class="w-full flex items-center px-3 py-2 text-left text-white hover:bg-gray-800 rounded-lg transition-colors duration-200 sm:px-4 sm:py-3"
                >
                  <WalletIcon class="h-5 w-5 mr-3 text-gray-400" />
                  <span class="text-sm font-medium">Withdraw</span>
                  <svg class="h-4 w-4 ml-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </li>
              <li>
                <button
                  @click="router.visit('/trade')"
                  class="w-full flex items-center px-3 py-2 text-left text-white hover:bg-gray-800 rounded-lg transition-colors duration-200 sm:px-4 sm:py-3"
                >
                  <ChartBarIcon class="h-5 w-5 mr-3 text-gray-400" />
                  <span class="text-sm font-medium">Trade</span>
                  <svg class="h-4 w-4 ml-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </li>
              <li>
                <button
                  @click="router.visit('/swap')"
                  class="w-full flex items-center px-3 py-2 text-left text-white hover:bg-gray-800 rounded-lg transition-colors duration-200 sm:px-4 sm:py-3"
                >
                  <ArrowsRightLeftIcon class="h-5 w-5 mr-3 text-gray-400" />
                  <span class="text-sm font-medium">Swap</span>
                  <svg class="h-4 w-4 ml-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
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
              <svg class="h-4 w-4 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Avatar Selection Modal -->
      <div v-if="showAvatarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-gray-900 rounded-lg p-6 w-11/12 max-w-md max-h-[80vh] overflow-y-auto">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-white">Select Avatar</h2>
            <button @click="showAvatarModal = false" class="text-gray-400 hover:text-white">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <img
              v-for="avatar in avatars"
              :key="avatar.path"
              :src="avatar.path"
              :alt="avatar.name"
              class="w-16 h-16 rounded-full cursor-pointer border-2 border-transparent hover:border-blue-500 transition"
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
</style>