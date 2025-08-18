<script setup lang="ts">
import type { Chat } from "@/types/chat";

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
</script>

<template>
  <!-- 1) Reveal button OUTSIDE the aside so transform on sidebar won't hide it -->
  <button
    v-if="props.collapsed"
    class="revealBtn"
    @click="emit('toggle')"
    aria-label="Open sidebar"
    title="Open sidebar"
  >
    ›
  </button>

  <!-- Sidebar itself -->
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
  </aside>
</template>

<style src="./Sidebar.css"></style>
