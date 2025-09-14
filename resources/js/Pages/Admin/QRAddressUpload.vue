<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    symbols: { type: Array, required: true },
});

console.log('QRAddressUpload.vue rendered with symbols:', props.symbols);

const form = useForm({
    symbol: '',
    qr_code: null,
    address: '',
});

// Debug: Log when a file is selected
const onFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        console.log('File selected:', {
            name: file.name,
            size: file.size,
            type: file.type,
        });
        form.qr_code = file;
    } else {
        console.log('No file selected');
        form.qr_code = null;
    }
};

const submit = () => {
    console.log('Submitting form:', {
        symbol: form.symbol,
        qr_code: form.qr_code ? {
            name: form.qr_code.name,
            size: form.qr_code.size,
            type: form.qr_code.type,
        } : null,
        address: form.address,
    });

    form.post(route('admin.qr-address-upload.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            console.log('Form submitted successfully');
            form.reset();
        },
        onError: (errors) => {
            console.error('Error uploading QR code and wallet address:', errors);
            alert('Error uploading QR code and wallet address: ' + JSON.stringify(errors));
        },
    });
};

// Security Setup Logic
const securityForm = useForm({
    answer1: '',
    answer2: '',
    answer3: '',
    password: '',
    password_confirmation: '',
});

const passwordForm = useForm({
    password: '',
});

const recoveryForm = useForm({
    questionIndex: null,
    answer: '',
    new_password: '',
    new_password_confirmation: '',
});

const showSetupModal = ref(false);
const showPasswordModal = ref(false);
const showRecoveryModal = ref(false);
const isAuthenticated = ref(false);
const securityError = ref(null);
const questions = ref([
    { index: 1, text: "What is your best friend's name?" },
    { index: 2, text: "What is your favorite pet's name?" },
    { index: 3, text: "What is your birth city's name?" },
]);
const selectedQuestion = ref(null);

onMounted(async () => {
    try {
        const response = await fetch(route('admin.check-security-setup'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const data = await response.json();
        if (data.hasSecurity) {
            showPasswordModal.value = true;
        } else {
            showSetupModal.value = true;
        }
    } catch (error) {
        console.error('Error checking security setup:', error);
        securityError.value = 'Failed to check security setup';
    }
});

const submitSecuritySetup = () => {
    securityForm.post(route('admin.setup-security'), {
        preserveState: true,
        onSuccess: () => {
            showSetupModal.value = false;
            isAuthenticated.value = true;
            securityError.value = null;
            securityForm.reset();
        },
        onError: (errors) => {
            console.error('Error setting up security:', errors);
            securityError.value = errors.error || 'Failed to setup security';
        },
    });
};

const verifyPassword = () => {
    passwordForm.post(route('admin.verify-password'), {
        preserveState: true,
        onSuccess: () => {
            showPasswordModal.value = false;
            isAuthenticated.value = true;
            securityError.value = null;
            passwordForm.reset();
        },
        onError: (errors) => {
            console.error('Error verifying password:', errors);
            securityError.value = errors.error || 'Incorrect password';
        },
    });
};

const startRecovery = () => {
    showPasswordModal.value = false;
    showRecoveryModal.value = true;
    selectedQuestion.value = questions.value[Math.floor(Math.random() * questions.value.length)];
    recoveryForm.questionIndex = selectedQuestion.value.index;
};

const submitRecovery = () => {
    recoveryForm.post(route('admin.recover-password'), {
        preserveState: true,
        onSuccess: () => {
            showRecoveryModal.value = false;
            isAuthenticated.value = true;
            securityError.value = null;
            recoveryForm.reset();
        },
        onError: (errors) => {
            console.error('Error recovering password:', errors);
            securityError.value = errors.error || 'Failed to recover password';
        },
    });
};

const closeSetupModal = () => {
    // Prevent closing setup modal until security is set
    securityError.value = 'Please complete security setup';
};

const closePasswordModal = () => {
    // Prevent closing password modal until authenticated
    securityError.value = 'Please enter your password';
};

const closeRecoveryModal = () => {
    showRecoveryModal.value = false;
    showPasswordModal.value = true;
    securityError.value = null;
    recoveryForm.reset();
};
</script>

<template>
    <Head title="Upload QR Code and Wallet Address" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold">Upload QR Code and Wallet Address</h1>
        </template>

        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4">

                <!-- Form -->
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <!-- Coin Selection -->
                        <div class="mb-3">
                            <label for="symbol" class="block text-sm font-medium text-gray-700 mb-1">Cryptocurrency</label>
                            <select
                                v-model="form.symbol"
                                id="symbol"
                                class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="" disabled>Select a cryptocurrency</option>
                                <option v-for="symbol in symbols" :key="symbol" :value="symbol">{{ symbol.toUpperCase() }}</option>
                            </select>
                            <span v-if="form.errors.symbol" class="text-red-500 text-xs">{{ form.errors.symbol }}</span>
                        </div>

                        <!-- QR Image Upload -->
                        <div class="mb-3">
                            <label for="qr_code" class="block text-sm font-medium text-gray-700 mb-1">QR Code Image</label>
                            <input
                                type="file"
                                id="qr_code"
                                accept="image/png, image/jpeg, image/jpg"
                                @change="onFileChange"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <span v-if="form.errors.qr_code" class="text-red-500 text-xs">{{ form.errors.qr_code }}</span>
                        </div>

                        <!-- Wallet Address -->
                        <div class="mb-3">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Wallet Address</label>
                            <input
                                v-model="form.address"
                                type="text"
                                id="address"
                                class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            />
                            <span v-if="form.errors.address" class="text-red-500 text-xs">{{ form.errors.address }}</span>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600"
                            :disabled="form.processing || !isAuthenticated"
                        >
                            Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Setup Modal -->
        <div v-if="showSetupModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-4 w-11/12 max-w-md">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-base font-semibold text-gray-800">Setup Security</h2>
                    <button disabled class="text-gray-400 cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitSecuritySetup">
                    <div v-for="(question, index) in questions" :key="index" class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ question.text }}</label>
                        <input
                            v-model="securityForm[`answer${index + 1}`]"
                            type="text"
                            class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <span v-if="securityForm.errors[`answer${index + 1}`]" class="text-red-500 text-xs">{{ securityForm.errors[`answer${index + 1}`] }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input
                            v-model="securityForm.password"
                            type="password"
                            class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <span v-if="securityForm.errors.password" class="text-red-500 text-xs">{{ securityForm.errors.password }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input
                            v-model="securityForm.password_confirmation"
                            type="password"
                            class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <span v-if="securityForm.errors.password_confirmation" class="text-red-500 text-xs">{{ securityForm.errors.password_confirmation }}</span>
                    </div>
                    <div v-if="securityError" class="mb-2 p-1 bg-red-100 text-red-800 rounded-md text-xs text-center">
                        {{ securityError }}
                    </div>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600"
                        :disabled="securityForm.processing"
                    >
                        Setup Security
                    </button>
                </form>
            </div>
        </div>

        <!-- Password Entry Modal -->
        <div v-if="showPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-4 w-11/12 max-w-md">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-base font-semibold text-gray-800">Enter Password</h2>
                    <button disabled class="text-gray-400 cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="verifyPassword">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <span v-if="passwordForm.errors.password" class="text-red-500 text-xs">{{ passwordForm.errors.password }}</span>
                    </div>
                    <div v-if="securityError" class="mb-2 p-1 bg-red-100 text-red-800 rounded-md text-xs text-center">
                        {{ securityError }}
                    </div>
                    <div class="flex justify-between">
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600"
                            :disabled="passwordForm.processing"
                        >
                            Submit
                        </button>
                        <button
                            type="button"
                            @click="startRecovery"
                            class="px-4 py-2 bg-gray-500 text-white rounded-md text-sm hover:bg-gray-600"
                        >
                            Recover Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Password Recovery Modal -->
        <div v-if="showRecoveryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-4 w-11/12 max-w-md">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-base font-semibold text-gray-800">Recover Password</h2>
                    <button @click="closeRecoveryModal" class="text-gray-600 hover:text-gray-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitRecovery">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ selectedQuestion?.text }}</label>
                        <input
                            v-model="recoveryForm.answer"
                            type="text"
                            class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <span v-if="recoveryForm.errors.answer" class="text-red-500 text-xs">{{ recoveryForm.errors.answer }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input
                            v-model="recoveryForm.new_password"
                            type="password"
                            class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <span v-if="recoveryForm.errors.new_password" class="text-red-500 text-xs">{{ recoveryForm.errors.new_password }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input
                            v-model="recoveryForm.new_password_confirmation"
                            type="password"
                            class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <span v-if="recoveryForm.errors.new_password_confirmation" class="text-red-500 text-xs">{{ recoveryForm.errors.new_password_confirmation }}</span>
                    </div>
                    <div v-if="securityError" class="mb-2 p-1 bg-red-100 text-red-800 rounded-md text-xs text-center">
                        {{ securityError }}
                    </div>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600"
                        :disabled="recoveryForm.processing"
                    >
                        Recover Password
                    </button>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>