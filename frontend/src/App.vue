<script setup>
import { ref, onMounted } from "vue";

const apiUrl = import.meta.env.VITE_API_URL;

const status = ref(null);
const error = ref(null);

const ping = async () => {
  try {
    const res = await fetch(`${apiUrl}/api/health`);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    status.value = await res.json();
  } catch (e) {
    error.value = String(e);
  }
};

onMounted(ping);
</script>

<template>
  <main
    style="
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial,
        sans-serif;
      padding: 2rem;
      line-height: 1.4;
    "
  >
    <h1>TavernTalk</h1>
    <p>API: {{ apiUrl }}</p>
    <pre v-if="status">{{ JSON.stringify(status, null, 2) }}</pre>
    <pre v-else-if="error">{{ error }}</pre>
    <p v-else>Checking health…</p>
  </main>
</template>
