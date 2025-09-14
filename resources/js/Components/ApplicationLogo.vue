
<script setup>
// Update the import to use the correct file name (VendorLogo.png)
import Logo from '../assets/VendorLogo.png';
</script>

<template>
  <img :src="Logo" alt="Application Logo" class="h-12 w-auto transition-all duration-200 hover:scale-105" />
</template>

<style scoped>
img {
  max-height: 48px;
  width: auto;
}
</style>