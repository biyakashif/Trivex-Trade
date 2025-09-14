<template>
    <!-- Navbar -->
    <NavLink />

    <!-- Main Content -->
    <div class="min-h-screen bg-gray-100 flex items-center justify-center py-16 relative overflow-hidden">
        <!-- Background Crypto Icons (Visible in both Desktop and Mobile) -->
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
            <!-- Left Side: Placeholder for spacing (icons are now in background) -->
            <div class="md:w-1/2 p-6"></div>

            <!-- Right Side: Registration Form -->
            <div class="md:w-1/2 flex items-center justify-center p-6">
                <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
                    <!-- Registration Disabled Message -->
                    <div v-if="isRegistrationDisabled" class="mb-4 text-gray-700 text-center">
                        <p class="text-lg font-semibold">Registration Currently Unavailable</p>
                        <p class="mt-2">
                            We are temporarily unable to accept new registrations due to high user traffic or ongoing server maintenance. For further assistance, please contact our support team at <a href="mailto:support@example.com" class="text-blue-600 hover:underline">support@example.com</a>.
                        </p>
                        <p class="mt-4">
                            <Link href="/login" class="text-blue-600 hover:underline">Return to Login</Link>
                        </p>
                    </div>

                    <!-- Validation Errors -->
                    <div v-if="!isRegistrationDisabled && Object.keys(form.errors).length" class="mb-4 text-red-600 text-sm">
                        <div v-for="error in form.errors" :key="error">{{ error }}</div>
                    </div>

                    <!-- Registration Form -->
                    <form @submit.prevent="submit" v-if="!isRegistrationDisabled">
                        <!-- Name -->
                        <div class="mb-4">
                            <label for="name-desktop" class="block text-gray-700 text-sm font-medium">Name</label>
                            <input
                                id="name-desktop"
                                v-model="form.name"
                                type="text"
                                class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                                :class="{ 'border-red-500': form.errors.name }"
                                required
                                autofocus
                            />
                        </div>

                        <!-- Email Address -->
                        <div class="mb-4">
                            <label for="email-desktop" class="block text-gray-700 text-sm font-medium">Email</label>
                            <input
                                id="email-desktop"
                                v-model="form.email"
                                type="email"
                                class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                                :class="{ 'border-red-500': form.errors.email }"
                                required
                            />
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password-desktop" class="block text-gray-700 text-sm font-medium">Password</label>
                            <input
                                id="password-desktop"
                                v-model="form.password"
                                type="password"
                                class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                                :class="{ 'border-red-500': form.errors.password }"
                                required
                                autocomplete="new-password"
                            />
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="password_confirmation-desktop" class="block text-gray-700 text-sm font-medium">Confirm Password</label>
                            <input
                                id="password_confirmation-desktop"
                                v-model="form.password_confirmation"
                                type="password"
                                class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                                :class="{ 'border-red-500': form.errors.password_confirmation }"
                                required
                            />
                        </div>

                        <!-- Submit and Login Link -->
                        <div class="flex justify-between items-center">
                            <Link href="/login" class="text-sm text-blue-600 hover:underline">
                                Already registered?
                            </Link>
                            <button
                                type="submit"
                                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 disabled:opacity-50"
                                :disabled="form.processing || isRegistrationDisabled"
                            >
                                Register
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile View (< 768px) -->
        <div class="md:hidden w-full max-w-md mx-auto p-6 relative z-10">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <!-- Registration Disabled Message -->
                <div v-if="isRegistrationDisabled" class="mb-4 text-gray-700 text-center">
                    <p class="text-lg font-semibold">Registration Currently Unavailable</p>
                    <p class="mt-2">
                        We are temporarily unable to accept new registrations due to high user traffic or ongoing server maintenance. For further assistance, please contact our support team at <a href="mailto:support@example.com" class="text-blue-600 hover:underline">support@example.com</a>.
                    </p>
                    <p class="mt-4">
                        <Link href="/login" class="text-blue-600 hover:underline">Return to Login</Link>
                    </p>
                </div>

                <!-- Validation Errors -->
                <div v-if="!isRegistrationDisabled && Object.keys(form.errors).length" class="mb-4 text-red-600 text-sm">
                    <div v-for="error in form.errors" :key="error">{{ error }}</div>
                </div>

                <!-- Registration Form -->
                <form @submit.prevent="submit" v-if="!isRegistrationDisabled">
                    <!-- Name -->
                    <div class="mb-4">
                        <label for="name-mobile" class="block text-gray-700 text-sm font-medium">Name</label>
                        <input
                            id="name-mobile"
                            v-model="form.name"
                            type="text"
                            class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                            :class="{ 'border-red-500': form.errors.name }"
                            required
                            autofocus
                        />
                    </div>

                    <!-- Email Address -->
                    <div class="mb-4">
                        <label for="email-mobile" class="block text-gray-700 text-sm font-medium">Email</label>
                        <input
                            id="email-mobile"
                            v-model="form.email"
                            type="email"
                            class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                            :class="{ 'border-red-500': form.errors.email }"
                            required
                        />
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password-mobile" class="block text-gray-700 text-sm font-medium">Password</label>
                        <input
                            id="password-mobile"
                            v-model="form.password"
                            type="password"
                            class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                            :class="{ 'border-red-500': form.errors.password }"
                            required
                            autocomplete="new-password"
                        />
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="password_confirmation-mobile" class="block text-gray-700 text-sm font-medium">Confirm Password</label>
                        <input
                            id="password_confirmation-mobile"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 w-full p-2 border rounded-md focus:ring focus:ring-blue-300"
                            :class="{ 'border-red-500': form.errors.password_confirmation }"
                            required
                        />
                    </div>

                    <!-- Submit and Login Link -->
                    <div class="flex justify-between items-center">
                        <Link href="/login" class="text-sm text-blue-600 hover:underline">
                            Already registered?
                        </Link>
                        <button
                            type="submit"
                            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 disabled:opacity-50"
                            :disabled="form.processing || isRegistrationDisabled"
                        >
                            Register
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useCryptoStore } from '@/Store/crypto.js';
import NavLink from '@/Components/NavLink.vue';

// Form setup
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

// Registration status (hardcoded for now)
const isRegistrationDisabled = ref(false);

function submit() {
    form.post('/register', {
        preserveState: true, // Stay on register page if there are errors
        preserveScroll: true, // Prevent scrolling to top
        onSuccess: () => {
            form.reset(); // Reset form fields
        },
        onError: (errors) => {
            console.log('Registration errors:', errors);
            if (errors.message) {
                alert(errors.message);
            }
        },
    });
}

// Crypto icons setup
const cryptoStore = useCryptoStore();
const cryptoIds = ref(['btc', 'eth', 'usdt', 'bnb', 'sol', 'xrp', 'ada', 'doge']);
</script>

<script>
export default {
    name: 'Register',
    props: {},
};
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