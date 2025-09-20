<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section class="bg-black rounded-xl p-4 shadow-lg border border-gray-800">
        <header>
            <h2 class="text-lg font-semibold text-white">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-400">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-4 space-y-4"
        >
            <div>
                <InputLabel for="name" value="Name" class="text-white" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full bg-black text-white border border-gray-700 focus:border-blue-500 focus:ring-blue-500 placeholder-gray-500"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2 text-red-400" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" class="text-white" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-black text-white border border-gray-700 focus:border-blue-500 focus:ring-blue-500 placeholder-gray-500"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2 text-red-400" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-300">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-blue-400 underline hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-400"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button :disabled="form.processing" class="action-btn focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed">Save</button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-green-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

<style scoped>
.action-btn {
    background: #23262F !important;
    color: #fff !important;
    padding-left: 1rem;
    padding-right: 1rem;
}
.action-btn:hover {
    background: #f3f4f6 !important;
    color: #181A20 !important;
}
.action-btn:hover * {
    color: #181A20 !important;
}

/* Mobile-specific responsive adjustments */
@media (max-width: 767px) {
  /* Professional height management */
  .min-h-screen {
    min-height: 100vh !important;
    min-height: 100dvh !important; /* Dynamic viewport height for mobile */
  }
  
  /* Professional font hierarchy */
  .text-lg {
    font-size: 1rem !important; /* Professional section header */
    line-height: 1.4rem !important;
    font-weight: 600 !important;
  }
  
  .text-sm {
    font-size: 0.7rem !important; /* Smaller description text */
    line-height: 0.95rem !important;
    font-weight: 500 !important;
  }
  
  /* Professional section padding */
  .p-4 {
    padding: 0.75rem !important; /* Compact padding */
  }
  
  .p-6 {
    padding: 1rem !important;
  }
  
  /* Professional margins */
  .mt-1 {
    margin-top: 0.25rem !important;
  }
  
  .mt-2 {
    margin-top: 0.375rem !important;
  }
  
  .mt-4 {
    margin-top: 0.75rem !important; /* Tighter form spacing */
  }
  
  .mt-6 {
    margin-top: 1rem !important;
  }
  
  /* Professional form spacing */
  .space-y-4 > * + * {
    margin-top: 0.75rem !important; /* Tighter field spacing */
  }
  
  .space-y-6 > * + * {
    margin-top: 1rem !important;
  }
  
  /* Professional button area */
  .gap-4 {
    gap: 0.75rem !important;
  }
  
  /* Professional border radius */
  .rounded-xl {
    border-radius: 0.5rem !important; /* Modern look */
  }
  
  .rounded-lg {
    border-radius: 0.375rem !important;
  }
  
  .rounded-md {
    border-radius: 0.1875rem !important;
  }
  
  /* Professional button styling */
  .action-btn {
    padding-left: 0.75rem !important;
    padding-right: 0.75rem !important;
    padding-top: 0.375rem !important;
    padding-bottom: 0.375rem !important;
    font-size: 0.8rem !important;
    font-weight: 500 !important;
    border-radius: 0.375rem !important;
  }
  
  /* Professional shadow effects */
  .shadow-lg {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2) !important;
  }
  
  /* Professional border styling */
  .border-gray-800 {
    border-color: rgba(55, 65, 81, 0.8) !important;
  }
  
  /* Professional transition effects */
  .transition {
    transition-duration: 0.15s !important; /* Faster transitions for mobile */
  }
}
</style>
