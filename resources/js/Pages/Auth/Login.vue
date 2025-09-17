<template>
<div class="min-h-screen flex flex-col items-center justify-center bg-[#181A20] px-4">
    <div class="mb-8 flex justify-center w-full">
        <Link :href="route('welcome')">
            <ApplicationLogo class="h-6 w-auto" />
        </Link>
    </div>
    <form @submit.prevent="submit" class="w-full max-w-sm">
        <h2 class="text-white text-3xl font-bold mb-6">Log In</h2>
        <!-- Removed Normal Login / Anonymous Login tab as per user request -->
        <div v-if="$page.props.flash.success" class="mb-4 text-green-400 text-sm">
            {{ $page.props.flash.success }}
        </div>
        <div v-if="form.errors.email || form.errors.password" class="mb-4 text-red-400 text-sm">
            <div v-if="form.errors.email">{{ form.errors.email }}</div>
            <div v-if="form.errors.password">{{ form.errors.password }}</div>
        </div>
        <div v-if="$page.props.flash.error" class="mb-4 text-red-400 text-sm">
            {{ $page.props.flash.error }}
        </div>
        <div class="mb-6">
            <label for="email" class="block text-sm text-white mb-2">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                class="w-full px-4 py-2 rounded-md bg-[#23262F] text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 border-none"
                :class="{ 'ring-2 ring-red-500': form.errors.email }"
                placeholder="234r53a@gmail.com"
                required
                autofocus
            />
        </div>
        <div class="mb-2 relative">
            <label for="password" class="block text-sm text-white mb-2">Password</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                class="w-full px-4 py-2 rounded-md bg-[#23262F] text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 border-none"
                :class="{ 'ring-2 ring-red-500': form.errors.password }"
                placeholder="12345678"
                required
                autocomplete="current-password"
            />
            <!-- Eye icon placeholder, not functional -->
            <span class="absolute right-4 top-9 text-gray-500 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
            </span>
        </div>
        <div class="flex justify-end mb-8">
            <Link :href="route('password.request')" class="text-sm text-blue-400 hover:underline">Forgot Password?</Link>
        </div>
        <button
            type="submit"
            class="w-full py-2 rounded-full bg-[#23262F] text-white text-base font-semibold hover:bg-[#23262F]/90 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
            :disabled="form.processing"
        >
            Log In
        </button>
        <div class="mt-8 text-center">
            <span class="text-gray-400">Don’t have an account?</span>
            <Link :href="route('register')" class="text-blue-400 hover:underline ml-1">Sign Up</Link>
        </div>
    </form>
</div>
</template>

<script setup>


import { useForm, usePage, Link, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
// If you have a logo component, you can import and use it instead of the <img> above.

const form = useForm({
    email: '',
    password: '',
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

const $page = usePage();
</script>

<style scoped>

/* Login page custom styles */

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