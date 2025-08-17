<script setup lang="ts">
const emit = defineEmits<{
  (e: "reset"): void;
  (e: "logout"): void;
}>();
</script>

<template>
  <header class="nav">
    <div class="brand">
      <span class="dot"></span>
      <h1 class="title">TavernTalk</h1>
    </div>
    <div class="actions">
      <button class="btn ghost" @click="emit('reset')">Reset</button>
      <button class="btn danger" @click="emit('logout')">Logout</button>
    </div>
  </header>
</template>

<style scoped src="./Navbar.css"></style>
