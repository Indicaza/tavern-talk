<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from "vue";

type Chat = { id: number; name: string };
type Msg = {
  id: number;
  role: "user" | "system" | "assistant";
  text: string;
  ts: number;
};

const props = defineProps<{
  selectedChat: Chat | null;
}>();

const threads = ref<Record<number, Msg[]>>({});
const draft = ref("");
const listRef = ref<HTMLElement | null>(null);

const activeMessages = ref<Msg[]>([]);

watch(
  () => props.selectedChat?.id ?? null,
  async (id) => {
    if (!id) {
      activeMessages.value = [];
      return;
    }
    if (!threads.value[id]) {
      threads.value[id] = [
        {
          id: Date.now(),
          role: "system",
          text: `Welcome to ${props.selectedChat?.name}.`,
          ts: Date.now(),
        },
      ];
    }
    activeMessages.value = threads.value[id];
    await nextTick();
    scrollToBottom();
  },
  { immediate: true }
);

const send = async () => {
  if (!props.selectedChat) return;
  const text = draft.value.trim();
  if (!text) return;

  const id = props.selectedChat.id;

  const userMsg: Msg = {
    id: Date.now(),
    role: "user",
    text,
    ts: Date.now(),
  };
  threads.value[id] = [...(threads.value[id] || []), userMsg];
  activeMessages.value = threads.value[id];
  draft.value = "";
  await nextTick();
  scrollToBottom();

  const reply: Msg = {
    id: Date.now() + 1,
    role: "assistant",
    text: "Acknowledged. This will call the API soon.",
    ts: Date.now(),
  };
  threads.value[id] = [...threads.value[id], reply];
  activeMessages.value = threads.value[id];
  await nextTick();
  scrollToBottom();
};

const onKey = (e: KeyboardEvent) => {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    send();
  }
};

const scrollToBottom = () => {
  if (!listRef.value) return;
  listRef.value.scrollTop = listRef.value.scrollHeight;
};

onMounted(scrollToBottom);
</script>

<template>
  <section class="chatwin">
    <div class="messages" ref="listRef">
      <div v-if="!selectedChat" class="empty">
        <div class="empty-box">
          <div class="empty-title">Select a chat to start messaging</div>
          <div class="empty-sub">Your conversation will appear here.</div>
        </div>
      </div>
      <div v-else class="stack">
        <div
          v-for="m in activeMessages"
          :key="m.id"
          class="msg"
          :data-role="m.role"
          :title="new Date(m.ts).toLocaleTimeString()"
        >
          <div class="bubble">{{ m.text }}</div>
        </div>
      </div>
    </div>

    <div class="composer" v-if="selectedChat">
      <textarea
        class="input"
        v-model="draft"
        placeholder="Type a message…"
        @keydown="onKey"
      />
      <button class="send" @click="send">Send</button>
    </div>
  </section>
</template>

<style scoped src="./ChatWindow.css"></style>
