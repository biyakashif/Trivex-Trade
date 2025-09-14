
<template>
    <!-- Navbar -->
    <NavLink />

    <!-- Main Content -->
    <div class="min-h-screen bg-black flex items-center justify-center py-16 relative overflow-hidden">
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
                        class="w-[70px] h-[70px] opacity-20"
                    />
                </div>
            </div>
        </div>

        <!-- Desktop View (≥ 768px) -->
        <div class="hidden md:flex w-full max-w-5xl mx-auto relative z-10">
            <!-- Left Side: Placeholder for spacing (icons are now in background) -->
            <div class="md:w-1/2 p-6"></div>

            <!-- Right Side: Verification Form -->
            <div class="md:w-1/2 flex items-center justify-center p-6">
                <div class="bg-black p-6 rounded-lg shadow-md w-full max-w-md border border-gray-700">
                    <!-- Instruction Text -->
                    <div class="mb-4 text-sm text-gray-300 text-center">
                        Thanks for signing up! We have sent a 6-digit verification code to your email. Please enter the code below to verify your email address.
                    </div>

                    <!-- Session Status -->
                    <div v-if="$page.props.status === 'verification-link-sent'" class="mb-4 text-sm text-green-600 font-medium">
                        A new verification code has been sent to your email address.
                    </div>

                    <!-- Validation Errors -->
                    <div v-if="Object.keys(verifyForm.errors).length" class="mb-4 text-red-600 text-sm">
                        <div v-for="error in verifyForm.errors" :key="error">{{ error }}</div>
                    </div>

                    <!-- Verification Form -->
                    <form @submit.prevent="verifyCode">
                        <!-- Code -->
                        <div class="mb-4">
                            <label for="code-desktop" class="block text-white text-sm font-medium">Verification Code</label>
                            <input
                                id="code-desktop"
                                v-model="verifyForm.code"
                                type="text"
                                class="mt-1 w-full p-2 border border-gray-700 rounded-md focus:ring focus:ring-blue-500 bg-black text-white placeholder-gray-500"
                                :class="{ 'border-red-500': verifyForm.errors.code }"
                                required
                                autofocus
                                maxlength="6"
                                placeholder="Enter 6-digit code"
                            />
                        </div>

                        <!-- Submit, Resend, and Logout -->
                        <div class="flex flex-col gap-2 mt-4">
                            <button
                                type="submit"
                                class="bg-white text-black w-full py-0.5 rounded-full hover:bg-gray-200 disabled:opacity-50 text-base font-semibold shadow-md"
                                :disabled="verifyForm.processing"
                            >
                                Verify Code
                            </button>
                            <div class="flex justify-between mt-2">
                                <form @submit.prevent="resendEmail">
                                    <button
                                        type="submit"
                                        class="text-blue-600 hover:underline text-sm"
                                        :disabled="resendForm.processing"
                                    >
                                        Resend Code
                                    </button>
                                </form>
                                <form @submit.prevent="logout">
                                    <button
                                        type="submit"
                                        class="text-blue-600 hover:underline text-sm"
                                        :disabled="logoutForm.processing"
                                    >
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>

        <!-- Mobile View (< 768px) -->
        <div class="md:hidden w-full max-w-md mx-auto p-6 relative z-10">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="bg-black p-6 rounded-lg shadow-md border border-gray-700">
                <!-- Instruction Text -->
                <div class="mb-4 text-sm text-gray-600 text-center">
                    Thanks for signing up! We have sent a 6-digit verification code to your email. Please enter the code below to verify your email address.
                </div>

                <!-- Session Status -->
                <div v-if="$page.props.status === 'verification-link-sent'" class="mb-4 text-sm text-green-600 font-medium">
                    A new verification code has been sent to your email address.
                </div>

                <!-- Validation Errors -->
                <div v-if="Object.keys(verifyForm.errors).length" class="mb-4 text-red-600 text-sm">
                    <div v-for="error in verifyForm.errors" :key="error">{{ error }}</div>
                </div>

                <!-- Verification Form -->
                <form @submit.prevent="verifyCode">
                    <!-- Code -->
                    <div class="mb-4">
                        <label for="code-mobile" class="block text-gray-700 text-sm font-medium">Verification Code</label>
                        <input
                            id="code-mobile"
                            v-model="verifyForm.code"
                            type="text"
                                class="mt-1 w-full p-2 border border-gray-700 rounded-md focus:ring focus:ring-blue-500 bg-black text-white placeholder-gray-500"
                            :class="{ 'border-red-500': verifyForm.errors.code }"
                            required
                            autofocus
                            maxlength="6"
                            placeholder="Enter 6-digit code"
                        />
                    </div>

                    <!-- Submit, Resend, and Logout -->
                    <div class="flex justify-between items-center">
                        <button
                            type="submit"
                                class="bg-white text-black w-full py-0.5 rounded-full hover:bg-gray-200 disabled:opacity-50 text-base font-semibold shadow-md"
                            :disabled="verifyForm.processing"
                        >
                            Verify Code
                        </button>

                        <form @submit.prevent="resendEmail">
                            <button
                                type="submit"
                                class="text-blue-600 hover:underline text-sm"
                                :disabled="resendForm.processing"
                            >
                                Resend Code
                            </button>
                        </form>

                        <form @submit.prevent="logout">
                            <button
                                type="submit"
                                class="text-blue-600 hover:underline text-sm"
                                :disabled="logoutForm.processing"
                            >
                                Log Out
                            </button>
                        </form>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useCryptoStore } from '@/Store/crypto.js';
import NavLink from '@/Components/NavLink.vue';

const verifyForm = useForm({
    code: '',
});

const resendForm = useForm({});
const logoutForm = useForm({});

function verifyCode() {
    verifyForm.post('/email/verify-code', {
        onSuccess: () => {
            verifyForm.reset('code');
            console.log('Verification successful, redirecting to dashboard...');
        },
        onError: (errors) => {
            console.error('Verification failed:', errors);
        },
        preserveState: true, // Preserve state to show errors
    });
}

function resendEmail() {
    resendForm.post('/email/verification-notification', {
        onSuccess: () => {
            console.log('Verification code resent successfully!');
        },
        onError: (errors) => {
            console.error('Failed to resend verification code:', errors);
        },
        preserveState: true, // Preserve the page state to stay on /verify-email
        preserveScroll: true, // Prevent scrolling to the top
    });
}

function logout() {
    logoutForm.post('/logout');
}

const cryptoStore = useCryptoStore();
const cryptoIds = ref(['btc', 'eth', 'usdt', 'bnb', 'sol', 'xrp', 'ada', 'doge']);
</script>

<script>
export default {
    name: 'EmailVerification',
    props: {
        status: String,
    },
};
</script>

<style scoped>
.crypto-icon {
    opacity: 0;
    transform: scale(1);
    animation: scaleAndFade 2s infinite ease-in-out;
}

@keyframes scaleAndFade {
    0% { opacity: 0; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(3); }
    100% { opacity: 0; transform: scale(1); }
}

.crypto-icon.animation-delay-0 { animation-delay: 0s; }
.crypto-icon.animation-delay-1 { animation-delay: 0.25s; }
.crypto-icon.animation-delay-2 { animation-delay: 0.5s; }
.crypto-icon.animation-delay-3 { animation-delay: 0.75s; }
.crypto-icon.animation-delay-4 { animation-delay: 1s; }
.crypto-icon.animation-delay-5 { animation-delay: 1.25s; }
.crypto-icon.animation-delay-6 { animation-delay: 1.5s; }
.crypto-icon.animation-delay-7 { animation-delay: 1.75s; }

.crypto-icon:nth-child(1) { top: 20%; left: 10%; }
.crypto-icon:nth-child(2) { top: 30%; left: 70%; }
.crypto-icon:nth-child(3) { top: 50%; left: 20%; }
.crypto-icon:nth-child(4) { top: 70%; left: 80%; }
.crypto-icon:nth-child(5) { top: 40%; left: 40%; }
.crypto-icon:nth-child(6) { top: 60%; left: 60%; }
.crypto-icon:nth-child(7) { top: 80%; left: 30%; }
.crypto-icon:nth-child(8) { top: 10%; left: 50%; }
</style>