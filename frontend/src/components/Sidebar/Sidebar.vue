<script setup lang="ts">
import type { Chat } from "@/types/chat";
import { ref } from "vue";
import SidebarButton from "./SidebarButton/SidebarButton.vue";
import Modal from "../Modal/Modal.vue";

type Props = {
  collapsed?: boolean;
  chats: Chat[];
  selectedChat: Chat | null;
};
const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "toggle"): void;
  (e: "select-chat", chat: Chat): void;
}>();

const showModal = ref(false);
</script>

<template>
  <!-- 1) Reveal button OUTSIDE -->
  <button
    v-if="props.collapsed"
    class="revealBtn"
    @click="emit('toggle')"
    aria-label="Open sidebar"
    title="Open sidebar"
  >
    ›
  </button>

  <!-- Sidebar -->
  <aside class="sidebar" :class="{ closed: !!props.collapsed }">
    <div class="topRow">
      <button
        class="collapseBtn"
        @click="emit('toggle')"
        aria-label="Collapse sidebar"
        title="Collapse sidebar"
      >
        {{ props.collapsed ? "›" : "‹" }}
      </button>
      <span class="title" v-if="!props.collapsed">Chats</span>
    </div>

    <ul class="chatList">
      <li
        v-for="chat in props.chats"
        :key="chat.id"
        class="chatItem"
        :class="{ active: props.selectedChat?.id === chat.id }"
        @click="emit('select-chat', chat)"
      >
        <span class="dot"></span>
        <span v-if="!props.collapsed">{{ chat.name }}</span>
      </li>
    </ul>

    <!-- NEW: NPC Generator Button -->
    <SidebarButton @click="showModal = true">✨ Generate NPC</SidebarButton>
  </aside>

  <!-- Modal -->
  <Modal :open="showModal" @close="showModal = false">
    <h2>Generate NPC</h2>
    <p>Would you like to roll randomly or enter your own prompt?</p>
    <div style="display: flex; gap: 1rem; margin-top: 1rem">
      <button
        @click="
          () => {
            console.log('Random');
            showModal = false;
          }
        "
      >
        🎲 Random
      </button>
      <button
        @click="
          () => {
            console.log('Custom');
            showModal = false;
          }
        "
      >
        ✍️ Custom
      </button>
    </div>
  </Modal>
</template>

<style src="./Sidebar.css"></style>
