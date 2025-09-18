<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/solid';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

// Go back in history
const goBack = () => {
  window.history.back();
};
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <button @click="goBack" class="flex items-center space-x-2 text-white mb-4 hover:text-gray-300">
            <ArrowLeftIcon class="h-5 w-5" />
            <span>Back</span>
        </button>

        <div class="pt-4 py-12">
            <div class="mx-auto space-y-6 px-4 sm:max-w-2xl md:max-w-3xl lg:max-w-4xl">
                <div class="bg-black p-6 rounded-xl shadow-lg">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="w-full"
                    />
                </div>

                <div class="bg-black p-6 rounded-xl shadow-lg">
                    <UpdatePasswordForm class="w-full" />
                </div>
<!-- 
                <div class="bg-black p-6 rounded-xl shadow-lg">
                    <DeleteUserForm class="w-full" />
                </div> -->
            </div>
        </div>
    </AuthenticatedLayout>
</template>