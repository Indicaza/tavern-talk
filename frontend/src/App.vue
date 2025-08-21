<script setup lang="ts">
import { ref, onMounted } from "vue";
import { setSidebarCollapsed } from "@/ui";
import Navbar from "@/components/Navbar/Navbar.vue";
import Sidebar from "@/components/Sidebar/Sidebar.vue";
import ChatWindow from "@/components/ChatWindow/ChatWindow.vue";
import type { Chat } from "@/types/chat";

const chats: Chat[] = [
  { id: 1, name: "General" },
  { id: 2, name: "Development" },
  { id: 3, name: "Random" },
];
const collapsed = ref(false);
const selectedChat = ref<Chat | null>(chats[0]);

function handleToggle() {
  collapsed.value = !collapsed.value;
  setSidebarCollapsed(collapsed.value);
}
function handleSelect(chat: Chat) {
  selectedChat.value = chat;
}
onMounted(() => setSidebarCollapsed(collapsed.value));
</script>

<template>
  <Navbar />
  <Sidebar
    :collapsed="collapsed"
    :chats="chats"
    :selectedChat="selectedChat"
    @toggle="handleToggle"
    @select-chat="handleSelect"
  />
  <ChatWindow />
</template>
