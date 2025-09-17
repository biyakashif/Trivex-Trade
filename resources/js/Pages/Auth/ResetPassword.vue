<template>
<div class="min-h-screen flex flex-col items-center justify-center bg-[#181A20] px-4">
    <div class="mb-8 flex justify-center w-full">
        <Link :href="route('welcome')">
            <ApplicationLogo class="h-6 w-auto" />
        </Link>
    </div>
    <form @submit.prevent="submit" class="w-full max-w-sm">
        <h2 class="text-white text-3xl font-bold mb-6">Reset Password</h2>
        <div v-if="status" class="mb-4 text-green-400 text-sm text-center">
            {{ status }}
        </div>
        <div v-if="Object.keys(form.errors).length" class="mb-4 text-red-400 text-sm">
            <div v-for="error in form.errors" :key="error">{{ error }}</div>
        </div>
        <input type="hidden" v-model="form.token" />
        <div class="mb-6">
            <label for="email" class="block text-sm text-white mb-2">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                class="w-full px-4 py-2 rounded-md bg-[#23262F] text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 border-none"
                :class="{ 'ring-2 ring-red-500': form.errors.email }"
                placeholder="your@email.com"
                required
                autofocus
                disabled
            />
        </div>
        <div class="mb-6 relative">
            <label for="password" class="block text-sm text-white mb-2">Password</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                class="w-full px-4 py-2 rounded-md bg-[#23262F] text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 border-none"
                :class="{ 'ring-2 ring-red-500': form.errors.password }"
                placeholder="Password"
                required
            />
        </div>
        <div class="mb-8 relative">
            <label for="password_confirmation" class="block text-sm text-white mb-2">Confirm Password</label>
            <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                class="w-full px-4 py-2 rounded-md bg-[#23262F] text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 border-none"
                :class="{ 'ring-2 ring-red-500': form.errors.password_confirmation }"
                placeholder="Confirm Password"
                required
            />
        </div>
        <button
            type="submit"
            :disabled="form.processing"
        >
            Reset Password
        </button>
    </form>
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