<script setup lang="ts">
import type { Chat } from "@/types/chat";
import { ref } from "vue";
import SidebarButton from "./SidebarButton/SidebarButton.vue";
import Modal from "../Modal/Modal.vue";
import styles from "./Sidebar.module.css"; // ✅ import css module

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
  <!-- Reveal button -->
  <button
    v-if="props.collapsed"
    :class="styles.revealBtn"
    @click="emit('toggle')"
    aria-label="Open sidebar"
    title="Open sidebar"
  >
    ›
  </button>

  <!-- Sidebar -->
  <aside :class="[styles.sidebar, { [styles.collapsed]: !!props.collapsed }]">
    <div :class="styles.topRow">
      <button :class="styles.collapseBtn" @click="emit('toggle')">
        {{ props.collapsed ? "›" : "‹" }}
      </button>
      <span v-if="!props.collapsed" :class="styles.title">Chats</span>
    </div>

    <ul :class="styles.chatList">
      <li
        v-for="chat in props.chats"
        :key="chat.id"
        :class="[
          styles.chatItem,
          { [styles.active]: props.selectedChat?.id === chat.id },
        ]"
        @click="emit('select-chat', chat)"
      >
        <span :class="styles.dot"></span>
        <span v-if="!props.collapsed">{{ chat.name }}</span>
      </li>
    </ul>
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
