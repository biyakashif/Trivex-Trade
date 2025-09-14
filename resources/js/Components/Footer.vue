<template>
  <footer class="w-full border-t border-gray-700 bg-black text-white mt-auto">
    <!-- Top Footer -->
    <div class="py-12 px-6 md:px-12">
      <div class="container mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
        <!-- Our Company -->
        <div>
          <h4 class="text-lg font-semibold text-white mb-4">Our Company</h4>
          <ul class="space-y-2">
            <li v-if="isAuthenticated">
              <Link href="/vendor/dashboard" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                Home
              </Link>
            </li>
            <li v-else>
              <Link href="/" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                Home
              </Link>
            </li>
            <li>
              <Link href="/about#about-us" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                About Us
              </Link>
            </li>
            <li>
              <Link href="/about#contact" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                Contact
              </Link>
            </li>
          </ul>
        </div>

        <!-- Help & Support -->
        <div>
          <h4 class="text-lg font-semibold text-white mb-4">Help & Support</h4>
          <ul class="space-y-2">
            <li>
              <Link href="/about#terms" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                Terms of Service
              </Link>
            </li>
            <li>
              <Link href="/register" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                Register
              </Link>
            </li>
            <li>
              <Link href="/login" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                Login
              </Link>
            </li>
          </ul>
        </div>

        <!-- Contact Info -->
        <div>
          <h4 class="text-lg font-semibold text-white mb-4">Contact Us</h4>
          <div class="text-base text-gray-300 space-y-2">
            <div>
              <a href="mailto:support@TrivexTrade.com" class="hover:text-blue-400">
                support@TrivexTrade.com
              </a>
            </div>
            <div>
              <a href="tel:+3521112344" class="hover:text-blue-400">
                +352 111 2344
              </a>
            </div>
            <div>35A Boulevard, Luxembourg City, Luxembourg</div>
            <div>
              <a href="https://t.me/TrivexSupport" target="_blank" class="hover:text-blue-400">
                @TrivexSupport (Telegram)
              </a>
            </div>
          </div>
        </div>

        <!-- Facts Section -->
        <div>
          <h4 class="text-lg font-semibold text-white mb-4">Our Impact</h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-gray-900 p-4 rounded-lg text-center border border-gray-700">
              <h5 class="text-blue-400 font-semibold text-base">{{ marketCap }}</h5>
              <span class="text-gray-300 text-sm">Market Cap</span>
            </div>
            <div class="bg-gray-900 p-4 rounded-lg text-center border border-gray-700">
              <h5 class="text-blue-400 font-semibold text-base">{{ dailyTransactions }}</h5>
              <span class="text-gray-300 text-sm">Daily Transactions</span>
            </div>
            <div class="bg-gray-900 p-4 rounded-lg text-center border border-gray-700">
              <h5 class="text-blue-400 font-semibold text-base">{{ activeAccounts }}</h5>
              <span class="text-gray-300 text-sm">Active Accounts</span>
            </div>
            <div class="bg-gray-900 p-4 rounded-lg text-center border border-gray-700">
              <h5 class="text-blue-400 font-semibold text-base">127</h5>
              <span class="text-gray-300 text-sm">Supported Countries</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Footer -->
    <div class="border-t border-gray-700 bg-black text-center py-4">
      <p class="text-sm text-gray-300 m-0">
        <strong class="text-blue-400 font-semibold">Trivex Trade</strong> © 2012
      </p>
    </div>
  </footer>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

// Props or logic to determine user authentication
const isAuthenticated = ref(false); // Replace with actual auth logic

const marketCap = ref('$90.76B');
const dailyTransactions = ref('243K');
const activeAccounts = ref('369K');

// Dynamic updates
const getRandomInRange = (min, max) => {
  return Math.random() * (max - min) + min;
};

const updateValues = () => {
  marketCap.value = `$${(90 + getRandomInRange(0.2, 0.76)).toFixed(2)}B`;
  dailyTransactions.value = `${Math.floor(getRandomInRange(200000, 245000) / 1000)}K`;
  activeAccounts.value = `${Math.floor(getRandomInRange(200000, 370000) / 1000)}K`;
};

onMounted(() => {
  updateValues();
  setInterval(updateValues, 20000);
});
</script>

<style scoped>
:root {
  --teal-600: #319795;
  --teal-700: #2c7a7b;
  --gray-100: #f3f4f6;
}
</style>