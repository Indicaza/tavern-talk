<script setup lang="ts">
import { ref, onMounted, nextTick, watch, computed } from "vue";
import styles from "./ChatWindow.module.css";
import { getMessages, sendMessage, type ChatMessage } from "./api";

type Props = { chatId: string | null };
const props = defineProps<Props>();

type Msg = {
  id: string | number;
  role: "user" | "assistant" | "system";
  content: string;
};

const messages = ref<Msg[]>([]);
const input = ref("");
const listEl = ref<HTMLElement | null>(null);
const textareaEl = ref<HTMLTextAreaElement | null>(null);
const grown = ref(false);
const sending = ref(false);

function ensureId(v: unknown): string | null {
  if (typeof v === "string") return v;
  if (
    v &&
    typeof v === "object" &&
    "id" in (v as any) &&
    typeof (v as any).id === "string"
  )
    return (v as any).id;
  return null;
}

const visibleMessages = computed(() =>
  messages.value.filter(
    (m) => !(m.role === "system" && (m.id === "hint" || m.id === "empty"))
  )
);
const hasMessages = computed(() => visibleMessages.value.length > 0);
const uiEmptyText = computed(() =>
  ensureId(props.chatId)
    ? "Say hello to begin."
    : "Select an NPC to start a chat."
);

function scrollToBottom() {
  const el = listEl.value;
  if (!el) return;
  el.scrollTop = el.scrollHeight;
}

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
  const id = ensureId(props.chatId);
  if (!id) {
    messages.value = [];
    await nextTick();
    scrollToBottom();
    return;
  }
  try {
    const hist = await getMessages(id);
    messages.value = mapHistory(hist);
  } catch (e: any) {
    messages.value = [];
  } finally {
    await nextTick();
    scrollToBottom();
  }
}

async function send() {
  const text = input.value.trim();
  const id = ensureId(props.chatId);
  if (!text || !id || sending.value) return;

  const tempId = crypto.randomUUID();
  messages.value.push({ id: tempId, role: "user", content: text });
  input.value = "";
  sending.value = true;

  try {
    const resp = await sendMessage(id, text);
    messages.value.push({
      id: resp.npc.id,
      role: "assistant",
      content: (resp.npc.content ?? "").toString(),
    });
  } catch {
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

onMounted(() => nextTick(scrollToBottom));

watch(
  () => messages.value.length,
  async () => {
    await nextTick();
    scrollToBottom();
  }
);

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
      <div :class="styles.messages" ref="listEl" aria-live="polite">
        <div v-if="!hasMessages" :class="styles.emptyState">
          {{ uiEmptyText }}
        </div>
        <div
          v-else
          v-for="m in visibleMessages"
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
              ensureId(props.chatId)
                ? 'Message TavernTalk…'
                : 'Select an NPC to start a chat…'
            "
            autocomplete="off"
            rows="1"
            @keydown="onKeydown"
            :disabled="!ensureId(props.chatId) || sending"
          />
          <button
            :class="styles['composer-btn']"
            type="submit"
            :disabled="!ensureId(props.chatId) || sending || !input.trim()"
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
