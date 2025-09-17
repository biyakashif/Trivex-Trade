<template>
<div class="min-h-screen flex flex-col items-center justify-center bg-[#181A20] px-4">
    <div class="mb-8 flex justify-center w-full">
        <Link :href="route('welcome')">
            <ApplicationLogo class="h-6 w-auto" />
        </Link>
    </div>
    <form @submit.prevent="verifyCode" class="w-full max-w-sm">
        <h2 class="text-white text-3xl font-bold mb-6">Verify Email</h2>
        <div class="mb-4 text-sm text-gray-300 text-center">
            Thanks for signing up! We have sent a 6-digit verification code to your email. Please enter the code below to verify your email address.
        </div>
        <div v-if="$page.props.status === 'verification-link-sent'" class="mb-4 text-sm text-green-400">
            A new verification code has been sent to your email address.
        </div>
        <div v-if="Object.keys(verifyForm.errors).length" class="mb-4 text-red-400 text-sm">
            <div v-for="error in verifyForm.errors" :key="error">{{ error }}</div>
        </div>
        <div class="mb-8 flex justify-center gap-3">
            <input
                v-for="(digit, idx) in codeDigits"
                :key="idx"
                ref="codeInputs"
                :ref="el => codeInputs[idx] = el"
                v-model="codeDigits[idx]"
                @input="onInput(idx)"
                @keydown.backspace="onBackspace(idx, $event)"
                maxlength="1"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                class="w-12 h-12 text-2xl text-center rounded-md bg-[#23262F] text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 border-none"
                autocomplete="one-time-code"
                :autofocus="idx === 0"
            />
        </div>
        <button
            type="submit"
            :disabled="verifyForm.processing"
        >
            Verify Code
        </button>
        <div class="flex flex-col md:flex-row justify-between mt-6 gap-4">
            <form @submit.prevent="resendEmail" class="w-full md:w-auto">
                <button
                    type="submit"
                    class="w-full py-2 rounded-full bg-[#23262F] text-white text-base font-semibold hover:bg-[#23262F]/90 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 md:px-8 md:w-auto"
                    :disabled="resendForm.processing"
                >
                    Resend Code
                </button>
            </form>
            <form @submit.prevent="logout" class="w-full md:w-auto">
                <button
                    type="submit"
                    class="w-full py-2 rounded-full bg-[#23262F] text-white text-base font-semibold hover:bg-[#23262F]/90 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 md:px-8 md:w-auto"
                    :disabled="logoutForm.processing"
                >
                    Log Out
                </button>
            </form>
        </div>
    </form>
</div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ref, watch, nextTick } from 'vue';
import { useCryptoStore } from '@/Store/crypto.js';
import NavLink from '@/Components/NavLink.vue';

const verifyForm = useForm({
    code: '',
});

const resendForm = useForm({});
const logoutForm = useForm({});

// 6 digit code logic
const codeDigits = ref(['', '', '', '', '', '']);
const codeInputs = ref([]);

watch(codeDigits, (val) => {
    verifyForm.code = val.join('');
});

function onInput(idx) {
    const val = codeDigits.value[idx];
    if (val && val.length > 1) {
        codeDigits.value[idx] = val.slice(-1);
    }
    if (val && idx < 5) {
        nextTick(() => codeInputs.value[idx + 1]?.focus());
    }
}

function onBackspace(idx, e) {
    if (!codeDigits.value[idx] && idx > 0) {
        nextTick(() => codeInputs.value[idx - 1]?.focus());
    }
}

function verifyCode() {
    verifyForm.code = codeDigits.value.join(''); // Ensure code is set before submit
    verifyForm.post('/email/verify-code', {
        onSuccess: () => {
            codeDigits.value = ['', '', '', '', '', ''];
            verifyForm.reset('code');
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

<style scoped>
body {
    background: #181A20;
}

input:-webkit-autofill,
input:-webkit-autofill:focus,
input:-webkit-autofill:hover,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 1000px #23262F inset !important;
    box-shadow: 0 0 0 1000px #23262F inset !important;
    -webkit-text-fill-color: #fff !important;
    caret-color: #fff !important;
    color: #fff !important;
    transition: background-color 5000s ease-in-out 0s;
}

button[type="submit"] {
    background: #23262F;
    color: #fff;
    border-radius: 9999px;
    width: 100%;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    font-size: 1rem;
    font-weight: 400;
    box-shadow: none;
    transition: background 0.2s, color 0.2s;
    min-height: 2.2rem;
}
button[type="submit"]:hover:not(:disabled) {
    background: #f3f4f6;
    color: #181A20;
}
button[type="submit"]:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>