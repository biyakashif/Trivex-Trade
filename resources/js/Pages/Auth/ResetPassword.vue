<template>
    <div class="min-h-screen bg-black">
        <!-- Navbar -->
        <NavLink />

        <!-- Main Content -->
    <div class="min-h-screen bg-black flex items-center justify-center py-16 relative overflow-hidden">
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
                    <div class="bg-black p-6 rounded-lg shadow-md w-full max-w-md border border-gray-700">
                        <div class="flex justify-center mb-6">
                            <ApplicationLogo class="h-12" />
                        </div>
                        <div v-if="status" class="mb-4 text-green-600 text-sm text-center">
                            {{ status }}
                        </div>
                        <div v-if="Object.keys(form.errors).length" class="mb-4 text-red-600 text-sm">
                            <div v-for="error in form.errors" :key="error">{{ error }}</div>
                        </div>
                        <form @submit.prevent="submit">
                            <input type="hidden" v-model="form.token" />
                            <div class="mb-4">
                                <label for="email" class="block text-white text-sm font-medium">Email</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 w-full p-2 border border-gray-700 rounded-md bg-black text-white placeholder-gray-500"
                                    :class="{ 'border-red-500': form.errors.email }"
                                    required
                                    autofocus
                                    disabled
                                />
                            </div>
                            <div class="mb-4">
                                <label for="password" class="block text-white text-sm font-medium">Password</label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="mt-1 w-full p-2 border border-gray-700 rounded-md focus:ring focus:ring-blue-500 bg-black text-white placeholder-gray-500"
                                    :class="{ 'border-red-500': form.errors.password }"
                                    required
                                />
                            </div>
                            <div class="mb-4">
                                <label for="password_confirmation" class="block text-white text-sm font-medium">Confirm Password</label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="mt-1 w-full p-2 border border-gray-700 rounded-md focus:ring focus:ring-blue-500 bg-black text-white placeholder-gray-500"
                                    :class="{ 'border-red-500': form.errors.password_confirmation }"
                                    required
                                />
                            </div>
                            <div class="flex justify-center">
                                <button
                                    type="submit"
                                    class="bg-white text-black w-full py-0.5 rounded-full hover:bg-gray-200 disabled:opacity-50 text-base font-semibold shadow-md"
                                    :disabled="form.processing"
                                >
                                    Reset Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Mobile View (< 768px) -->
            <div class="md:hidden w-full max-w-md mx-auto p-6 relative z-10">
                <div class="bg-black p-6 rounded-lg shadow-md border border-gray-700">
                    <div class="flex justify-center mb-6">
                        <ApplicationLogo class="h-12" />
                    </div>
                    <div v-if="status" class="mb-4 text-green-600 text-sm text-center">
                        {{ status }}
                    </div>
                    <div v-if="Object.keys(form.errors).length" class="mb-4 text-red-600 text-sm">
                        <div v-for="error in form.errors" :key="error">{{ error }}</div>
                    </div>
                    <form @submit.prevent="submit">
                        <input type="hidden" v-model="form.token" />
                        <div class="mb-4">
                            <label for="email" class="block text-white text-sm font-medium">Email</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 w-full p-2 border border-gray-700 rounded-md bg-black text-white placeholder-gray-500"
                                :class="{ 'border-red-500': form.errors.email }"
                                required
                                autofocus
                                disabled
                            />
                        </div>
                        <div class="mb-4">
                            <label for="password" class="block text-white text-sm font-medium">Password</label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 w-full p-2 border border-gray-700 rounded-md focus:ring focus:ring-blue-500 bg-black text-white placeholder-gray-500"
                                :class="{ 'border-red-500': form.errors.password }"
                                required
                            />
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-white text-sm font-medium">Confirm Password</label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="mt-1 w-full p-2 border border-gray-700 rounded-md focus:ring focus:ring-blue-500 bg-black text-white placeholder-gray-500"
                                :class="{ 'border-red-500': form.errors.password_confirmation }"
                                required
                            />
                        </div>
                        <div class="flex justify-center">
                            <button
                                type="submit"
                                class="bg-white text-black w-full py-0.5 rounded-full hover:bg-gray-200 disabled:opacity-50 text-base font-semibold shadow-md"
                                :disabled="form.processing"
                            >
                                Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Floating Chat -->
        <FloatingChat :telegram-username="'TrivexSupport'" />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import FloatingChat from '@/Components/FloatingChat.vue';
import NavLink from '@/Components/NavLink.vue';
import { useCryptoStore } from '@/Store/crypto.js';

// Access the page props (status) for displaying success/error messages
const { props } = usePage();
const status = props.status;
const sidebarOpen = ref(false);

// Form setup with token and email passed as props
const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('password.store'), {
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
            setTimeout(() => {
                window.location.href = route('login');
            }, 2000);
        },
        onError: () => {
            // Errors are automatically available in form.errors
        },
    });
}

// Crypto icons setup
const cryptoStore = useCryptoStore();
const cryptoIds = ref(['btc', 'eth', 'usdt', 'bnb', 'sol', 'xrp', 'ada', 'doge']);
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
        transform: scale(3); /* Scale to 3x size */
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