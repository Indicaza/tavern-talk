<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from "vue";

type Msg = {
  id: string | number;
  role: "user" | "assistant" | "system";
  content: string;
};

const messages = ref<Msg[]>([
  { id: 1, role: "assistant", content: "Welcome to TavernTalk! 🍻" },
]);

const input = ref("");
const listEl = ref<HTMLElement | null>(null);
const textareaEl = ref<HTMLTextAreaElement | null>(null);
const grown = ref(false);

function scrollToBottom() {
  const el = listEl.value;
  if (!el) return;
  el.scrollTop = el.scrollHeight;
}

onMounted(() => nextTick(scrollToBottom));
watch(
  () => messages.value.length,
  async () => {
    await nextTick();
    scrollToBottom();
  }
);

async function send() {
  const text = input.value.trim();
  if (!text) return;

  messages.value.push({ id: crypto.randomUUID(), role: "user", content: text });
  input.value = "";

  // Simulate latency + assistant reply
  setTimeout(() => {
    messages.value.push({
      id: crypto.randomUUID(),
      role: "assistant",
      content: `Echo: ${text}`,
    });
  }, 500);
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    send();
  }
}

watch(input, async () => {
  await nextTick();
  if (textareaEl.value) {
    textareaEl.value.style.height = "auto"; // reset
    textareaEl.value.style.height = textareaEl.value.scrollHeight + "px";
    grown.value = textareaEl.value.scrollHeight > 48; // toggle pill vs square
  }
});
</script>

<template>
  <section class="chatwin">
    <div class="chatwin-content">
      <!-- Scrollable messages -->
      <div class="messages" ref="listEl">
        <div v-for="m in messages" :key="m.id" class="bubble" :class="m.role">
          {{ m.content }}
        </div>
      </div>

      <!-- Composer -->
      <form class="composer" @submit.prevent="send">
        <div class="composer-inner" :class="{ grown }">
          <textarea
            ref="textareaEl"
            class="composer-input"
            v-model="input"
            placeholder="Message TavernTalk…"
            autocomplete="off"
            rows="1"
            @keydown="onKeydown"
          />
          <button class="composer-btn" type="submit">➤</button>
        </div>
      </form>
    </div>
  </section>
</template>

<style src="./ChatWindow.css"></style>
