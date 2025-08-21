<template>
  <button :class="$style.button" @click="$emit('click')">
    <slot />
  </button>
</template>

<script setup lang="ts">
defineEmits<{ (e: "click"): void }>();
</script>

<style module src="./SidebarButton.module.css"></style>
