<script setup lang="ts">
import styles from "./SidebarButton.module.css";

type Props = {
  label: string;
};
const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "click"): void;
}>();
</script>

<template>
  <button :class="styles.button" @click="emit('click')">
    <slot name="icon" />
    <span>{{ label }}</span>
  </button>
</template>
