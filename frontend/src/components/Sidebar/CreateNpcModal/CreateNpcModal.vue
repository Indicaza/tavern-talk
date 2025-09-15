<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
import { createNpc } from "../CreateNpcModal/api";
import type { Character } from "../../../types/character";
import styles from "./CreateNpcModal.module.css";

type Props = { open: boolean };
const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "created", npc: Character): void;
}>();

const prompt = ref("");
const loading = ref(false);
const error = ref<string | null>(null);

async function doCreate(useRandom: boolean) {
  try {
    loading.value = true;
    error.value = null;
    const text = useRandom ? "" : prompt.value.trim();
    const npc = await createNpc(text);
    emit("created", npc);
    emit("close");
    prompt.value = "";
  } catch (e: any) {
    error.value = e?.message ?? "Failed to create NPC";
  } finally {
    loading.value = false;
  }
}

function onKey(e: KeyboardEvent) {
  if (e.key === "Escape") emit("close");
  if (
    (e.metaKey || e.ctrlKey) &&
    e.key.toLowerCase() === "enter" &&
    !loading.value
  ) {
    doCreate(false);
  }
}
onMounted(() => document.addEventListener("keydown", onKey));
onBeforeUnmount(() => document.removeEventListener("keydown", onKey));
</script>

<template>
  <div v-if="props.open" :class="styles.overlay" @click.self="emit('close')">
    <div :class="styles.modal">
      <div :class="styles.header">
        <div :class="styles.headerMeta">
          <h2 :class="styles.title">Create NPC</h2>
          <p :class="styles.subtitle">
            Enter a prompt or roll the dice for a surprise.
          </p>
        </div>
        <button
          :class="styles.closeBtn"
          @click="emit('close')"
          aria-label="Close"
        >
          ✕
        </button>
      </div>

      <div :class="styles.body">
        <div :class="styles.field">
          <label :class="styles.label">Prompt</label>
          <textarea
            :class="styles.textarea"
            v-model="prompt"
            placeholder="A mischievous gnome wizard-engineer who lives to cause laughter…"
          />
          <div :class="styles.hint">
            <span
              >Tip: Press <kbd :class="styles.kbd">⌘</kbd
              ><kbd :class="styles.kbd">Enter</kbd> to create</span
            >
          </div>
        </div>

        <div v-if="error" :class="styles.error">{{ error }}</div>

        <div :class="styles.actions">
          <button
            :class="styles.secondary"
            @click="emit('close')"
            :disabled="loading"
          >
            Cancel
          </button>
          <button
            :class="styles.secondaryGhost"
            @click="doCreate(true)"
            :disabled="loading"
          >
            🎲 Random
          </button>
          <button
            :class="styles.primary"
            @click="doCreate(false)"
            :disabled="loading"
          >
            {{ loading ? "Creating…" : "Create NPC" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
