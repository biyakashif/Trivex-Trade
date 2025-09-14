<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useCryptoStore } from '@/Store/crypto.js';
import Footer from '@/Components/Footer.vue';
import NavLink from '@/Components/NavLink.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import FloatingChat from '@/Components/FloatingChat.vue';
// Import images
import appleStoreImg from '@/assets/apple-store-img.png';
import googlePlayImg from '@/assets/google-play-img.png';
import tchartviewImg from '@/assets/tchartview.png';
import tradeImg from '@/assets/trade.png';

const sidebarOpen = ref(false);
const showModal = ref(false); // Reactive ref for modal visibility
const { props } = usePage();
const user = props.auth.user;
const cryptoStore = useCryptoStore();
const cryptoIds = ref(['btc', 'eth', 'usdt', 'bnb', 'sol', 'xrp', 'ada', 'doge']);
const isRegistrationDisabled = ref(false);
let pollingInterval = null;

// Fetch the registration status
const fetchRegistrationStatus = async () => {
    try {
        const response = await fetch('/settings/registration-status', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        isRegistrationDisabled.value = data.registrationDisabled;
    } catch (error) {
        console.error('Error fetching registration status:', error);
        isRegistrationDisabled.value = false;
    }
};

// Function to show modal and set auto-close
const openModal = () => {
    showModal.value = true;
    setTimeout(() => {
        showModal.value = false;
    }, 5000); // Auto-close after 5 seconds
};

// Function to close modal
const closeModal = () => {
    showModal.value = false;
};

onMounted(() => {
    cryptoStore.startAutoRefresh();
    fetchRegistrationStatus();
    pollingInterval = setInterval(fetchRegistrationStatus, 10000);

    // Add event listener for close button
    const closeButton = document.getElementById('closeModal');
    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    // Clean up event listener
    const closeButton = document.getElementById('closeModal');
    if (closeButton) {
        closeButton.removeEventListener('click', closeModal);
    }
});
</script>

<template>
  <Head title="Welcome" />
  <div v-if="$page.props.errors.error" class="mb-4 text-red-500 text-center font-semibold">
    {{ $page.props.errors.error }}
  </div>

  <!-- Modal -->
  <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-xl max-w-md w-full text-center">
      <h3 class="text-2xl font-bold text-gray-900 mb-4">Coming Soon!</h3>
      <p class="text-gray-600 mb-6">Our mobile app will be available soon on iOS and Android.</p>
      <button id="closeModal" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-all duration-300">
        Close
      </button>
    </div>
  </div>

  <!-- Mobile View (< 768px) -->
  <div class="md:hidden min-h-screen bg-gray-50 flex flex-col items-center justify-center p-6">
    <!-- Logo -->
    <div class="mb-8">
      <Link :href="route('welcome')">
        <ApplicationLogo class="h-14 w-auto" />
      </Link>
    </div>

    <!-- Title -->
    <h1 class="text-3xl font-extrabold text-gray-900 text-center mb-6 leading-tight">
      Trade Crypto with Speed, Security, and Confidence
    </h1>

    <!-- First Three Animated Crypto Icons -->
    <div class="flex flex-row flex-wrap justify-center gap-4 mb-10">
      <div
        v-for="(symbol, index) in cryptoIds.slice(0, 3)"
        :key="symbol"
        class="crypto-icon"
        :class="`animation-delay-${index}`"
      >
        <img
          :src="cryptoStore.getIcon(symbol)"
          :alt="`${symbol} icon`"
          class="w-12 h-12 transition-transform duration-300 hover:scale-110"
        />
      </div>
    </div>

    <!-- Registration Disabled Message -->
    <div v-if="isRegistrationDisabled" class="mb-6 text-red-500 text-center max-w-sm">
      <p class="text-lg font-medium">
        New user registration is currently disabled due to high traffic or maintenance.
      </p>
    </div>

    <!-- Login and Register Buttons -->
    <div class="flex flex-col space-y-4 w-full max-w-xs">
      <Link
        :href="route('login')"
        class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-700 flex items-center justify-center text-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg"
      >
        <i class="fas fa-user mr-2"></i> Sign In
      </Link>
      <Link
        v-if="!isRegistrationDisabled"
        :href="route('register')"
        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center justify-center text-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg"
      >
        <i class="fas fa-user-plus mr-2"></i> Register
      </Link>
    </div>
  </div>

  <!-- Desktop View (≥ 768px) -->
  <div class="hidden md:block bg-gray-50">
    <!-- Navbar -->
    <NavLink :is-registration-disabled="isRegistrationDisabled" />

    <!-- Main Content -->
    <main :class="['pt-20 transition-all', sidebarOpen ? 'ml-[250px]' : 'ml-0']">
      <!-- Hero Section (Title and Register Form) -->
      <section class="bg-navy-900 py-16 px-6 md:px-12">
        <div class="container mx-auto relative z-10">
          <div class="bg-white bg-opacity-95 backdrop-blur-lg rounded-2xl shadow-xl p-10 md:p-12 max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row items-center gap-8">
              <!-- Left: Main Title -->
              <div class="md:w-1/2 flex flex-col justify-center items-start text-left">
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4 leading-tight">
                  Trade Crypto with Speed, Security, and Confidence
                </h1>
                <p class="text-lg text-gray-600 mb-6">
                  Join a platform designed for seamless and secure cryptocurrency trading.
                </p>
              </div>
              <!-- Right: Subtitle, Email Input, and Register Button -->
              <div class="md:w-1/2 flex flex-col justify-center items-end text-right">
                <p class="text-xl text-gray-700 mb-6 font-medium">
                  Start trading today with just a few clicks.
                </p>
                <div class="flex flex-col md:flex-row max-w-md w-full gap-4 md:gap-0">
                  <input
                    type="email"
                    placeholder="Enter your email"
                    required
                    class="flex-1 px-6 py-3 rounded-lg md:rounded-r-none border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-transparent shadow-sm text-lg transition-all duration-300"
                  />
                  <Link
                    v-if="!isRegistrationDisabled"
                    :href="route('register')"
                    class="bg-blue-600 text-white px-8 py-3 rounded-lg md:rounded-l-none hover:bg-blue-700 text-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg"
                  >
                    Register Now
                  </Link>
                </div>
                <!-- Registration Disabled Message -->
                <div v-if="isRegistrationDisabled" class="mt-4 text-red-500 text-right">
                  <p class="text-lg font-medium">
                    New user registration is currently disabled due to high traffic or maintenance.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Welcome Section -->
      <section class="py-16 px-6 md:px-12 bg-white relative">
        <!-- Scattered Crypto Icons -->
        <div class="absolute inset-0 z-0 overflow-hidden">
          <div
            v-for="(symbol, index) in cryptoIds"
            :key="symbol"
            class="crypto-icon absolute"
            :class="`animation-delay-${index}`"
            :style="{
              top: index % 2 === 0 ? `${Math.random() * 20 + 10}%` : `${Math.random() * 60 + 30}%`,
              left: index < 4 ? `${Math.random() * 20 + 10}%` : `${Math.random() * 60 + 30}%`,
            }"
          >
            <img
              :src="cryptoStore.getIcon(symbol)"
              :alt="`${symbol} icon`"
              class="w-16 h-16 transition-transform duration-300 hover:scale-110 opacity-30"
            />
          </div>
        </div>

        <div class="container mx-auto relative z-10">
          <!-- App Download and Images -->
          <div class="flex flex-col md:flex-row items-center gap-12">
            <!-- Left: App Download Section -->
            <div class="md:w-1/3 md:h-[650px] flex flex-col justify-center items-center text-center">
              <h2 class="text-3xl font-bold text-gray-900 mb-4">Trade Anywhere with Our Mobile App</h2>
              <p class="text-lg text-gray-600 mb-6">Available soon on iOS and Android</p>
              <div class="flex flex-col items-center gap-6">
                <button @click="openModal" class="crypto-icon animation-delay-1">
                  <img
                    :src="appleStoreImg"
                    alt="Download on the App Store"
                    class="h-14 w-auto transition-transform duration-300 hover:scale-105"
                  />
                </button>
                <button @click="openModal" class="crypto-icon animation-delay-2">
                  <img
                    :src="googlePlayImg"
                    alt="Get it on Google Play"
                    class="h-14 w-auto transition-transform duration-300 hover:scale-105"
                  />
                </button>
              </div>
            </div>

            <!-- Right: Two Images -->
            <div class="md:w-2/3 md:h-[650px] flex justify-center items-center relative">
              <img
                :src="tchartviewImg"
                alt="Chart View"
                class="w-[85%] h-auto object-contain crypto-icon animation-delay-0 absolute bottom-0 left-0 z-10"
              />
              <img
                :src="tradeImg"
                alt="Trade View"
                class="w-[65%] h-auto object-contain crypto-icon animation-delay-1 absolute top-0 right-0 z-20"
              />
            </div>
          </div>

          <!-- Get Started Section -->
          <div class="py-16 text-center">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-10">Get Started in 3 Simple Steps</h2>
            <div class="flex flex-col md:flex-row justify-center gap-8">
              <div class="flex-1">
                <div class="bg-gray-50 p-8 rounded-xl shadow-md flex flex-col items-center h-72 transition-all hover:shadow-xl hover:-translate-y-1">
                  <div class="text-xl font-bold text-teal-600 mb-2">1</div>
                  <div class="text-4xl text-teal-600 mb-4 transition-transform duration-300 hover:scale-110">
                    <i class="fas fa-user-plus"></i>
                  </div>
                  <h4 class="text-xl font-semibold text-gray-900 mb-2">Create Account</h4>
                  <p class="text-gray-600 mb-4 flex-1">Sign up with your email and verify your account.</p>
                  <Link
                    :href="route('register')"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 text-lg font-semibold transition-all duration-300"
                  >
                    Sign Up
                  </Link>
                </div>
              </div>
              <div class="flex-1">
                <div class="bg-gray-50 p-8 rounded-xl shadow-md flex flex-col items-center h-72 transition-all hover:shadow-xl hover:-translate-y-1">
                  <div class="text-xl font-bold text-teal-600 mb-2">2</div>
                  <div class="text-4xl text-teal-600 mb-4 transition-transform duration-300 hover:scale-110">
                    <i class="fas fa-wallet"></i>
                  </div>
                  <h4 class="text-xl font-semibold text-gray-900 mb-2">Make Deposit</h4>
                  <p class="text-gray-600 mb-4 flex-1">Fund your account securely and quickly.</p>
                  <Link
                    href="/deposit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 text-lg font-semibold transition-all duration-300"
                  >
                    Deposit
                  </Link>
                </div>
              </div>
              <div class="flex-1">
                <div class="bg-gray-50 p-8 rounded-xl shadow-md flex flex-col items-center h-72 transition-all hover:shadow-xl hover:-translate-y-1">
                  <div class="text-xl font-bold text-teal-600 mb-2">3</div>
                  <div class="text-4xl text-teal-600 mb-4 transition-transform duration-300 hover:scale-110">
                    <i class="fas fa-chart-line"></i>
                  </div>
                  <h4 class="text-xl font-semibold text-gray-900 mb-2">Start Trading</h4>
                  <p class="text-gray-600 mb-4 flex-1">Trade your favorite cryptocurrencies with ease.</p>
                  <Link
                    href="/trade"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 text-lg font-semibold transition-all duration-300"
                  >
                    Trade
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Recent Trading Activities -->
      <section class="py-16 bg-gray-100 px-6 md:px-12">
        <div class="container mx-auto">
          <div class="w-full">
            <div class="bg-transparent border-0">
              <div class="flex flex-wrap justify-between items-center border-b border-gray-300 pb-4 mb-8">
                <div>
                  <h4 class="text-3xl font-bold text-gray-900 mb-2">Recent Trading Activities</h4>
                  <p class="text-gray-600 mb-0">Real-time updates for top cryptocurrencies.</p>
                </div>
              </div>
              <div class="pt-4">
                <div class="overflow-x-auto rounded-lg shadow-md">
                  <table class="w-full text-left text-gray-900 bg-white">
                    <thead class="bg-gray-200 text-gray-700">
                      <tr>
                        <th class="p-4">Symbol</th>
                        <th class="p-4">Price</th>
                        <th class="p-4">24h %</th>
                        <th class="p-4 hidden md:table-cell">Market Cap</th>
                        <th class="p-4 hidden md:table-cell">Volume (24h)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="symbol in cryptoIds" :key="symbol" class="border-b hover:bg-gray-50">
                        <td class="p-4 flex items-center">
                          <img
                            :src="cryptoStore.getIcon(symbol)"
                            :alt="`${symbol} icon`"
                            class="w-6 h-6 mr-3"
                          />
                          {{ symbol.toUpperCase() }}
                        </td>
                        <td class="p-4">${{ cryptoStore.getPrice(symbol) || 'N/A' }}</td>
                        <td
                          class="p-4"
                          :class="cryptoStore.getChange(symbol) >= 0 ? 'text-green-600' : 'text-red-500'"
                        >
                          {{ cryptoStore.getChange(symbol) || 'N/A' }}%
                        </td>
                        <td class="p-4 hidden md:table-cell">
                          ${{ cryptoStore.getMarketCap(symbol) ? Number(cryptoStore.getMarketCap(symbol)).toLocaleString() : 'N/A' }}
                        </td>
                        <td class="p-4 hidden md:table-cell">
                          ${{ cryptoStore.getVolume(symbol) ? Number(cryptoStore.getVolume(symbol)).toLocaleString() : 'N/A' }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
    <FloatingChat :telegram-username="'TrivexSupport'" /> <!-- Replace with your Telegram username -->

    <!-- Footer -->
    <Footer />
  </div>
</template>

<style scoped>
/* Custom Color Variables */
:root {
  --navy-900: #1a202c;
  --teal-600: #319795;
  --teal-700: #2c7a7b;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
}

/* Crypto Icon Animation */
.crypto-icon {
  opacity: 0;
  transform: translateX(-4rem);
  animation: slideInFade 0.8s ease-out forwards;
}

@keyframes slideInFade {
  0% {
    opacity: 0;
    transform: translateX(-4rem);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Animation Delays for Mobile (3 Icons) */
@media (max-width: 768px) {
  .crypto-icon.animation-delay-0 {
    animation-delay: 0.2s;
  }
  .crypto-icon.animation-delay-1 {
    animation-delay: 0.4s;
  }
  .crypto-icon.animation-delay-2 {
    animation-delay: 0.6s;
  }
}

/* Animation Delays for Desktop (8 Icons) */
@media (min-width: 768px) {
  .crypto-icon.animation-delay-0 {
    animation-delay: 0.2s;
  }
  .crypto-icon.animation-delay-1 {
    animation-delay: 0.4s;
  }
  .crypto-icon.animation-delay-2 {
    animation-delay: 0.6s;
  }
  .crypto-icon.animation-delay-3 {
    animation-delay: 0.8s;
  }
  .crypto-icon.animation-delay-4 {
    animation-delay: 1.0s;
  }
  .crypto-icon.animation-delay-5 {
    animation-delay: 1.2s;
  }
  .crypto-icon.animation-delay-6 {
    animation-delay: 1.4s;
  }
  .crypto-icon.animation-delay-7 {
    animation-delay: 1.6s;
  }
}

/* Button Hover Effects */
a.bg-blue-600,
a.bg-gray-800 {
  transition: all 0.3s ease;
}

a.bg-blue-600:hover,
a.bg-gray-800:hover {
  transform: translateY(-2px);
}

/* Input Field Styles */
input {
  transition: all 0.3s ease;
}

input:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(49, 151, 149, 0.3);
}

/* Smooth Scroll Behavior */
html {
  scroll-behavior: smooth;
}

/* Table Row Hover */
tbody tr {
  transition: background-color 0.2s ease;
}
</style>