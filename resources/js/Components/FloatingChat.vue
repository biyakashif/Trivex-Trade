<template>
    <div class="fixed bottom-20 right-0 z-[200]">
        <button
            @click="openTelegramChat"
            class="bg-blue-600 text-white rounded-full p-4 shadow-lg hover:bg-blue-700 transition-all duration-200 focus:outline-none flex items-center justify-center space-x-2"
            aria-label="Open Telegram Chat"
        >
            <span class="text-sm font-medium animate-pulse-opacity">Help</span>
        </button>
    </div>
</template>

<script setup>
import { defineProps } from 'vue'

const props = defineProps({
    telegramUsername: {
        type: String,
        default: 'TrivexSupport' 
    }
})

const openTelegramChat = () => {
    const telegramUrl = `https://t.me/${props.telegramUsername}`
    window.open(telegramUrl, '_blank')
}
</script>

<style scoped>
.animate-pulse-opacity {
    animation: pulseOpacity 2s infinite;
}

@keyframes pulseOpacity {
    0% { opacity: 1; }
    50% { opacity: 0.6; }
    100% { opacity: 1; }
}
</style>