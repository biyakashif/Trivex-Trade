<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { EnvelopeIcon, PhoneIcon, MapPinIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/solid';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NavLink from '@/Components/NavLink.vue';
import Footer from '@/Components/Footer.vue';
</script>

<template>
  <Head title="About Us | Contact | Terms of Service" />

  <!-- Desktop View (≥ 768px) -->
  <div class="min-h-screen bg-black">
    <!-- Navbar -->
    <NavLink />

    <!-- Sticky Section Navigation -->
  <nav class="bg-black shadow-md sticky top-0 z-20 hidden md:block border-b border-gray-700">
      <div class="container mx-auto px-6 py-4">
        <ul class="flex space-x-8 justify-center">
          <li>
            <Link
              href="#about-us"
              class="text-white hover:text-blue-400 font-semibold transition-colors duration-300"
            >
              About Us
            </Link>
          </li>
          <li>
            <Link
              href="#contact"
              class="text-white hover:text-blue-400 font-semibold transition-colors duration-300"
            >
              Contact
            </Link>
          </li>
          <li>
            <Link
              href="#terms"
              class="text-white hover:text-blue-400 font-semibold transition-colors duration-300"
            >
              Terms of Service
            </Link>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Main Content -->
  <main class="pt-20 md:pt-24">
      <!-- About Us Section -->
  <section id="about-us" class="py-16 px-6 md:px-12 bg-black border-b border-gray-700">
        <div class="container mx-auto">
          <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-6 text-center">About Trivex Trade</h2>
          <div class="max-w-3xl mx-auto text-center">
            <p class="text-lg text-gray-300 mb-6">
              Founded in 2012, Trivex Trade is a leading cryptocurrency trading platform headquartered in Luxembourg. Our mission is to empower individuals and businesses to trade digital assets with speed, security, and confidence.
            </p>
            <p class="text-lg text-gray-300 mb-6">
              With a user base spanning 127 countries and a market cap exceeding $90 billion, we provide a robust platform for trading major cryptocurrencies like Bitcoin, Ethereum, and more. Our advanced technology ensures seamless transactions, real-time market data, and top-tier security protocols.
            </p>
            <p class="text-lg text-gray-300">
              At Trivex Trade, we are committed to innovation, transparency, and customer satisfaction, making us the trusted choice for over 369,000 active accounts worldwide.
            </p>
          </div>
        </div>
      </section>

      <!-- Contact Section -->
  <section id="contact" class="py-16 px-6 md:px-12 bg-black border-b border-gray-700">
        <div class="container mx-auto">
          <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-6 text-center">Contact Us</h2>
          <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Contact Info -->
            <div class="space-y-6">
              <div class="flex items-center">
                <EnvelopeIcon class="h-6 w-6 text-blue-400 mr-3" />
                <a href="mailto:support@TrivexTrade.com" class="text-lg text-white hover:text-blue-400">
                  support@TrivexTrade.com
                </a>
              </div>
              <div class="flex items-center">
                <PhoneIcon class="h-6 w-6 text-blue-400 mr-3" />
                <a href="tel:+3521112344" class="text-lg text-white hover:text-blue-400">
                  +352 111 2344
                </a>
              </div>
              <div class="flex items-center">
                <MapPinIcon class="h-6 w-6 text-blue-400 mr-3" />
                <span class="text-lg text-white">
                  123 Crypto Plaza, Luxembourg City, Luxembourg
                </span>
              </div>
              <div class="flex items-center">
                <ChatBubbleLeftRightIcon class="h-6 w-6 text-blue-400 mr-3" />
                <a
                  href="https://t.me/TrivexSupport"
                  target="_blank"
                  class="text-lg text-white hover:text-blue-400"
                >
                  @TrivexSupport (Telegram)
                </a>
              </div>
            </div>
            <!-- Contact Message -->
            <div class="text-lg text-gray-300">
              <p class="mb-4">
                Our support team is available 24/7 to assist with your trading needs. Whether you have questions about your account, trading pairs, or platform features, we're here to help.
              </p>
              <p>
                Reach out via email, phone, or Telegram for prompt and professional support.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- Terms of Service Section -->
  <section id="terms" class="py-16 px-6 md:px-12 bg-black">
        <div class="container mx-auto">
          <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-6 text-center">Terms of Service</h2>
          <div class="max-w-4xl mx-auto text-gray-300">
            <h3 class="text-xl font-semibold text-white mb-4">1. Acceptance of Terms</h3>
            <p class="mb-4">
              By accessing or using Trivex Trade, you agree to be bound by these Terms of Service. If you do not agree, please do not use our platform.
            </p>
            <h3 class="text-xl font-semibold text-white mb-4">2. Eligibility</h3>
            <p class="mb-4">
              You must be at least 18 years old and have the legal capacity to enter into contracts to use our services. By registering, you confirm that you meet these requirements.
            </p>
            <h3 class="text-xl font-semibold text-white mb-4">3. Account Security</h3>
            <p class="mb-4">
              You are responsible for maintaining the confidentiality of your account credentials. Trivex Trade is not liable for any unauthorized access resulting from your failure to secure your account.
            </p>
            <h3 class="text-xl font-semibold text-white mb-4">4. Trading Risks</h3>
            <p class="mb-4">
              Cryptocurrency trading involves significant risks, including the potential loss of your entire investment. You acknowledge that you trade at your own risk.
            </p>
            <h3 class="text-xl font-semibold text-white mb-4">5. Prohibited Activities</h3>
            <p class="mb-4">
              You agree not to engage in illegal activities, market manipulation, or any actions that may harm the platform or its users.
            </p>
            <h3 class="text-xl font-semibold text-white mb-4">6. Termination</h3>
            <p class="mb-4">
              We reserve the right to suspend or terminate your account for violations of these terms or any suspicious activity.
            </p>
            <h3 class="text-xl font-semibold text-white mb-4">7. Contact</h3>
            <p>
              For questions about these terms, contact us at
              <a href="mailto:support@TrivexTrade.com" class="text-blue-400 hover:underline">support@TrivexTrade.com</a>.
            </p>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <Footer />
  </div>

  <!-- Mobile View (< 768px) -->
  <div class="md:hidden min-h-screen bg-black">
    <!-- Logo and Mobile Nav -->
  <div class="bg-black shadow-md p-6 flex justify-between items-center border-b border-gray-700">
      <Link :href="route('about')">
        <ApplicationLogo class="h-10 w-auto" />
      </Link>
      <nav>
        <ul class="flex space-x-4">
          <li>
            <Link
              href="#about-us"
              class="text-white hover:text-blue-400 font-medium text-sm"
            >
              About
            </Link>
          </li>
          <li>
            <Link
              href="#contact"
              class="text-white hover:text-blue-400 font-medium text-sm"
            >
              Contact
            </Link>
          </li>
          <li>
            <Link
              href="#terms"
              class="text-white hover:text-blue-400 font-medium text-sm"
            >
              Terms
            </Link>
          </li>
        </ul>
      </nav>
    </div>

    <!-- Mobile Content -->
    <main class="pt-6">
      <!-- About Us -->
      <section id="about-us" class="py-12 px-6 bg-black border-b border-gray-700">
        <h2 class="text-2xl font-extrabold text-white mb-4 text-center">About Trivex Trade</h2>
        <p class="text-base text-gray-300">
          Trivex Trade, founded in 2012, is a premier cryptocurrency trading platform based in Luxembourg. We empower users in 127 countries to trade securely and efficiently, with over 369,000 active accounts and a market cap exceeding $90 billion.
        </p>
      </section>

      <!-- Contact -->
      <section id="contact" class="py-12 px-6 bg-black border-b border-gray-700">
        <h2 class="text-2xl font-extrabold text-white mb-4 text-center">Contact Us</h2>
        <div class="space-y-4 text-base text-gray-300">
          <div class="flex items-center">
            <EnvelopeIcon class="h-5 w-5 text-blue-400 mr-2" />
            <a href="mailto:support@TrivexTrade.com" class="hover:text-blue-400 text-white">
              support@TrivexTrade.com
            </a>
          </div>
          <div class="flex items-center">
            <PhoneIcon class="h-5 w-5 text-blue-400 mr-2" />
            <a href="tel:+3521112344" class="hover:text-blue-400 text-white">
              +352 111 2344
            </a>
          </div>
          <div class="flex items-center">
            <MapPinIcon class="h-5 w-5 text-blue-400 mr-2" />
            <span class="text-white">35A Boulevard, Luxembourg City, Luxembourg</span>
          </div>
          <div class="flex items-center">
            <ChatBubbleLeftRightIcon class="h-5 w-5 text-blue-400 mr-2" />
            <a href="https://t.me/TrivexSupport" target="_blank" class="hover:text-blue-400 text-white">
              @TrivexSupport (Telegram)
            </a>
          </div>
        </div>
      </section>

      <!-- Terms of Service -->
      <section id="terms" class="py-12 px-6 bg-black">
        <h2 class="text-2xl font-extrabold text-white mb-4 text-center">Terms of Service</h2>
        <div class="text-base text-gray-300">
          <h3 class="text-lg font-semibold text-white mb-2">1. Acceptance</h3>
          <p class="mb-3">
            Using Trivex Trade means you agree to these terms.
          </p>
          <h3 class="text-lg font-semibold text-white mb-2">2. Eligibility</h3>
          <p class="mb-3">
            You must be 18+ and legally able to trade.
          </p>
          <h3 class="text-lg font-semibold text-white mb-2">3. Account Security</h3>
          <p class="mb-3">
            Keep your account secure; you're responsible for any misuse.
          </p>
          <h3 class="text-lg font-semibold text-white mb-2">4. Trading Risks</h3>
          <p>
            Trading is risky, and losses are possible.
          </p>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <Footer />
  </div>
</template>

<style scoped>
:root {
  --navy-900: #1a202c;
  --teal-600: #319795;
  --teal-700: #2c7a7b;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
}

html {
  scroll-behavior: smooth;
}

section {
  scroll-margin-top: 80px;
}
</style>