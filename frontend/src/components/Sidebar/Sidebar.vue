<script setup lang="ts">
import { ref, computed } from "vue";

type Chat = { id: number; name: string };

const props = defineProps<{
  selectedChat: Chat | null;
}>();

const emit = defineEmits<{
  (e: "select-chat", chat: Chat): void;
}>();

const chats = ref<Chat[]>([
  { id: 1, name: "General" },
  { id: 2, name: "Development" },
  { id: 3, name: "Random" },
]);

const selectedId = computed(() => props.selectedChat?.id ?? null);

const selectChat = (chat: Chat) => {
  emit("select-chat", chat);
};
</script>

<template>
  <aside class="sidebar">
    <div class="section-title">Chats</div>
    <ul class="chat-list">
      <li
        v-for="c in chats"
        :key="c.id"
        class="chat-item"
        :data-active="selectedId === c.id"
        @click="selectChat(c)"
      >
        <span class="chat-name">{{ c.name }}</span>
      </li>
    </ul>
  </aside>
</template>

<style scoped src="./Sidebar.css"></style>
