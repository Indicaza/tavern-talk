<script setup lang="ts">
import { ref } from "vue";
import Navbar from "./components/Navbar/Navbar.vue";
import Sidebar from "./components/Sidebar/Sidebar.vue";
import ChatWindow from "./components/ChatWindow/ChatWindow.vue";

type Chat = { id: number; name: string };

const selectedChat = ref<Chat | null>(null);

const handleSelectChat = (chat: Chat) => {
  selectedChat.value = chat;
};

const handleReset = () => {
  selectedChat.value = null;
};

const handleLogout = () => {
  selectedChat.value = null;
};
</script>

<template>
  <div class="app-shell">
    <Sidebar @select-chat="handleSelectChat" :selected-chat="selectedChat" />
    <div class="main">
      <Navbar @reset="handleReset" @logout="handleLogout" />
      <ChatWindow :selected-chat="selectedChat" />
    </div>
  </div>
</template>

<style scoped>
.app-shell {
  display: flex;
  width: 100vw;
  height: 100vh;
  background: #0f172a;
  color: #e2e8f0;
}

.main {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}
</style>
