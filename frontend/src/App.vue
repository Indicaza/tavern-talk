<script setup lang="ts">
import { ref, onMounted } from "vue";
import { setSidebarCollapsed } from "@/ui";
import Navbar from "@/components/Navbar/Navbar.vue";
import Sidebar from "@/components/Sidebar/Sidebar.vue";
import ChatWindow from "@/components/ChatWindow/ChatWindow.vue";

const collapsed = ref(false);
const currentChatId = ref<string | null>(null);

function handleToggle() {
  collapsed.value = !collapsed.value;
  setSidebarCollapsed(collapsed.value);
}

function handleOpenChat(id: string) {
  currentChatId.value = id;
}

onMounted(() => setSidebarCollapsed(collapsed.value));
</script>

<template>
  <Navbar />
  <Sidebar
    :collapsed="collapsed"
    @toggle="handleToggle"
    @open-chat="handleOpenChat"
  />
  <ChatWindow :chatId="currentChatId" />
</template>
