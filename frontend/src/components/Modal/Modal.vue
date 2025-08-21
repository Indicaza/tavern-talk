<template>
  <div v-if="open" :class="$style.backdrop" @click="emit('close')">
    <div :class="$style.modal" @click.stop>
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{ (e: "close"): void }>();
</script>

<style module src="./Modal.module.css"></style>
