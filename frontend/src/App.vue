<script setup lang="ts">
import { ref } from "vue";
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
}

function handleSelect(chat: Chat) {
  selectedChat.value = chat;
}
</script>

<template>
  <!-- one place to set the sidebar width; CSS does the rest -->
  <div
    class="app-shell"
    :style="{ '--sidebar-width': collapsed ? '0px' : '240px' }"
  >
    <Sidebar
      :collapsed="collapsed"
      :chats="chats"
      :selectedChat="selectedChat"
      @toggle="handleToggle"
      @select-chat="handleSelect"
    />

    <Navbar />

    <main class="content">
      <div class="content-inner">
        <ChatWindow :selectedChat="selectedChat" />
      </div>
    </main>
  </div>
</template>
