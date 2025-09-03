<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from "vue";
import styles from "./ChatWindow.module.css";
import { getMessages, sendMessage, type ChatMessage } from "./api";

type Props = { chatId: string | null };
const props = defineProps<Props>();

type Msg = {
  id: string | number;
  role: "user" | "assistant" | "system";
  content: string;
};

const messages = ref<Msg[]>([
  { id: "welcome", role: "assistant", content: "Welcome to TavernTalk! 🍻" },
]);

const input = ref("");
const listEl = ref<HTMLElement | null>(null);
const textareaEl = ref<HTMLTextAreaElement | null>(null);
const grown = ref(false);
const sending = ref(false);

function scrollToBottom() {
  const el = listEl.value;
  if (!el) return;
  el.scrollTop = el.scrollHeight;
}

function uiError(msg: string, detail?: unknown) {
  console.error("[ChatWindow]", msg, detail);
  messages.value.push({
    id: `sys-${Date.now()}`,
    role: "system",
    content: msg,
  });
}

onMounted(() => nextTick(scrollToBottom));
watch(
  () => messages.value.length,
  async () => {
    await nextTick();
    scrollToBottom();
  }
);

function mapHistory(rows: ChatMessage[]): Msg[] {
  return rows
    .filter((m) => m.role !== "system")
    .map((m) => ({
      id: m.id,
      role: m.role === "npc" ? "assistant" : "user",
      content: (m.content ?? "").toString(),
    }));
}

async function loadHistory() {
  if (!props.chatId) {
    messages.value = [
      {
        id: "hint",
        role: "system",
        content: "Select an NPC and start a chat.",
      },
    ];
    return;
  }
  try {
    const hist = await getMessages(props.chatId);
    const mapped = mapHistory(hist);
    messages.value = mapped.length
      ? mapped
      : [{ id: "empty", role: "system", content: "Say hello to begin." }];
  } catch (e: any) {
    uiError(e?.message ?? "Failed to load messages", e);
  } finally {
    await nextTick();
    scrollToBottom();
  }
}

async function send() {
  const text = input.value.trim();
  if (!text || !props.chatId || sending.value) return;

  const tempId = crypto.randomUUID();
  messages.value.push({ id: tempId, role: "user", content: text });
  input.value = "";
  sending.value = true;

  try {
    const resp = await sendMessage(props.chatId, text);
    messages.value.push({
      id: resp.npc.id,
      role: "assistant",
      content: (resp.npc.content ?? "").toString(),
    });
  } catch (e: any) {
    uiError(e?.message ?? "Failed to send message", e);
  } finally {
    sending.value = false;
    await nextTick();
    scrollToBottom();
  }
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
    textareaEl.value.style.height = "auto";
    textareaEl.value.style.height = textareaEl.value.scrollHeight + "px";
    grown.value = textareaEl.value.scrollHeight > 48;
  }
});

watch(
  () => props.chatId,
  async () => {
    await loadHistory();
  },
  { immediate: true }
);
</script>

<template>
  <section :class="styles.chatwin">
    <div :class="styles['chatwin-content']">
      <!-- <div :class="styles.messages" ref="listEl" aria-live="polite">
        <div
          v-for="m in messages"
          :key="m.id"
          :class="[styles.bubble, styles[m.role]]"
        >
          {{ m.content }}
        </div>
      </div> -->
      <div :class="styles.messages" ref="listEl" aria-live="polite">
        <!-- Empty/system state -->
        <div
          v-if="messages.length === 1 && messages[0].role === 'system'"
          :class="styles.emptyState"
        >
          {{ messages[0].content }}
        </div>

        <!-- Normal bubbles -->
        <div
          v-else
          v-for="m in messages"
          :key="m.id"
          :class="[styles.bubble, styles[m.role]]"
        >
          {{ m.content }}
        </div>
      </div>

      <form :class="styles.composer" @submit.prevent="send">
        <div :class="[styles['composer-inner'], { [styles.grown]: grown }]">
          <textarea
            ref="textareaEl"
            :class="styles['composer-input']"
            v-model="input"
            :placeholder="
              props.chatId
                ? 'Message TavernTalk…'
                : 'Select an NPC to start a chat…'
            "
            autocomplete="off"
            rows="1"
            @keydown="onKeydown"
            :disabled="!props.chatId || sending"
          />
          <button
            :class="styles['composer-btn']"
            type="submit"
            :disabled="!props.chatId || sending || !input.trim()"
            aria-label="Send"
            title="Send"
          >
            ➤
          </button>
        </div>
      </form>
    </div>
  </section>
</template>
