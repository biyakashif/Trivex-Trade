<template>
    <!-- Navbar -->
    <NavLink />

    <!-- Main Content -->
    <div class="min-h-screen bg-gray-100 flex items-center justify-center py-16 relative overflow-hidden">
        <!-- Background Crypto Icons -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="relative w-full h-full">
                <div
                    v-for="(symbol, index) in cryptoIds"
                    :key="symbol"
                    class="crypto-icon absolute"
                    :class="`animation-delay-${index}`"
                >
                    <img
                        :src="cryptoStore.getIcon(symbol)"
                        :alt="`${symbol} icon`"
                        class="w-[70px] h-[70px] opacity-2"
                    />
                </div>
            </div>
        </div>

        <!-- Desktop View (≥ 768px) -->
        <div class="hidden md:flex w-full max-w-5xl mx-auto relative z-10">
            <div class="md:w-1/2 p-6"></div>
            <div class="md:w-1/2 flex items-center justify-center p-6">
                <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
                    <div v-if="$page.props.flash.success" class="mb-4 text-green-600 text-sm">
                        {{ $page.props.flash.success }}
                    </div>
                    <div v-if="form.errors.email || form.errors.password" class="mb-4 text-red-600 text-sm">
                        <div v-if="form.errors.email">{{ form.errors.email }}</div>
                        <div v-if="form.errors.password">{{ form.errors.password }}</div>
                    </div>
                    <div v-if="$page.props.flash.error" class="mb-4 text-red-600 text-sm">
                        {{ $page.props.flash.error }}
                    </div>
                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <label for="email-desktop" class="block text-gray-700 text-sm font-medium">Email</label>
                            <input
                                id="email-desktop"
                                v-model="form.email"
                                type="email"
                                class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                                :class="{ 'border-red-500': form.errors.email }"
                                required
                                autofocus
                            />
                        </div>
                        <div class="mb-4">
                            <label for="password-desktop" class="block text-gray-700 text-sm font-medium">Password</label>
                            <input
                                id="password-desktop"
                                v-model="form.password"
                                type="password"
                                class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                                :class="{ 'border-red-500': form.errors.password }"
                                required
                                autocomplete="current-password"
                            />
                        </div>
                        <div class="flex items-center mb-4">
                            <input
                                id="remember_me-desktop"
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label for="remember_me-desktop" class="ml-2 text-sm text-gray-600">Remember me</label>
                        </div>
                        <div class="flex justify-between items-center">
                            <Link :href="route('password.request')" class="text-sm text-blue-600 hover:underline">
                                Forgot your password?
                            </Link>
                            <button
                                type="submit"
                                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Log in
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile View (< 768px) -->
        <div class="md:hidden w-full max-w-md mx-auto p-6 relative z-10">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div v-if="$page.props.flash.success" class="mb-4 text-green-600 text-sm">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="form.errors.email || form.errors.password" class="mb-4 text-red-600 text-sm">
                    <div v-if="form.errors.email">{{ form.errors.email }}</div>
                    <div v-if="form.errors.password">{{ form.errors.password }}</div>
                </div>
                <div v-if="$page.props.flash.error" class="mb-4 text-red-600 text-sm">
                    {{ $page.props.flash.error }}
                </div>
                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label for="email-mobile" class="block text-gray-700 text-sm font-medium">Email</label>
                        <input
                            id="email-mobile"
                            v-model="form.email"
                            type="email"
                            class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                            :class="{ 'border-red-500': form.errors.email }"
                            required
                            autofocus
                        />
                    </div>
                    <div class="mb-4">
                        <label for="password-mobile" class="block text-gray-700 text-sm font-medium">Password</label>
                        <input
                            id="password-mobile"
                            v-model="form.password"
                            type="password"
                            class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                            :class="{ 'border-red-500': form.errors.password }"
                            required
                            autocomplete="current-password"
                        />
                    </div>
                    <div class="flex items-center mb-4">
                        <input
                            id="remember_me-mobile"
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="remember_me-mobile" class="ml-2 text-sm text-gray-600">Remember me</label>
                    </div>
                    <div class="flex justify-between items-center">
                        <Link :href="route('password.request')" class="text-sm text-blue-600 hover:underline">
                            Forgot your password?
                        </Link>
                        <button
                            type="submit"
                            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            Log in
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useCryptoStore } from '@/Store/crypto.js';
import NavLink from '@/Components/NavLink.vue';
import { Link } from '@inertiajs/vue3';

// Form setup
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (response) => {
            form.reset('password');
            if (response.props.redirect) {
                router.visit(response.props.redirect, {
                    preserveState: false,
                    preserveScroll: false,
                });
            }
        },
        onError: (errors) => {
            console.log('Login errors:', errors);
        },
    });
}

// Crypto icons setup
const cryptoStore = useCryptoStore();
const cryptoIds = ref(['btc', 'eth', 'usdt', 'bnb', 'sol', 'xrp', 'ada', 'doge']);

// Access page props
const $page = usePage();
</script>

<style scoped>
/* Crypto Icon Animation */
.crypto-icon {
    opacity: 0;
    transform: scale(1);
    animation: scaleAndFade 2s infinite ease-in-out;
}

/* Keyframes for scaling and fading */
@keyframes scaleAndFade {
    0% {
        opacity: 0;
        transform: scale(1);
    }
    50% {
        opacity: 0.3;
        transform: scale(3);
    }
    100% {
        opacity: 0;
        transform: scale(1);
    }
}

/* Sequential Delays for 8 Icons */
.crypto-icon.animation-delay-0 { animation-delay: 0s; }
.crypto-icon.animation-delay-1 { animation-delay: 0.25s; }
.crypto-icon.animation-delay-2 { animation-delay: 0.5s; }
.crypto-icon.animation-delay-3 { animation-delay: 0.75s; }
.crypto-icon.animation-delay-4 { animation-delay: 1s; }
.crypto-icon.animation-delay-5 { animation-delay: 1.25s; }
.crypto-icon.animation-delay-6 { animation-delay: 1.5s; }
.crypto-icon.animation-delay-7 { animation-delay: 1.75s; }

/* Random positioning for icons */
.crypto-icon:nth-child(1) { top: 20%; left: 10%; }
.crypto-icon:nth-child(2) { top: 30%; left: 70%; }
.crypto-icon:nth-child(3) { top: 50%; left: 20%; }
.crypto-icon:nth-child(4) { top: 70%; left: 80%; }
.crypto-icon:nth-child(5) { top: 40%; left: 40%; }
.crypto-icon:nth-child(6) { top: 60%; left: 60%; }
.crypto-icon:nth-child(7) { top: 80%; left: 30%; }
.crypto-icon:nth-child(8) { top: 10%; left: 50%; }
</style>