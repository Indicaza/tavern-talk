<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
import { createNpc } from "../CreateNpcModal/api";
import type { Character } from "../../../types/character";
import styles from "./CreateNpcModal.module.css";

type Props = {
  open: boolean;
};
const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "created", npc: Character): void;
}>();

const prompt = ref("");
const withImage = ref(false);
const loading = ref(false);
const error = ref<string | null>(null);

async function doCreate(useRandom: boolean) {
  try {
    loading.value = true;
    error.value = null;
    const npc = await createNpc(
      useRandom ? "" : prompt.value.trim(),
      withImage.value
    );
    emit("created", npc);
    emit("close");
    prompt.value = "";
    withImage.value = false;
  } catch (e: any) {
    error.value = e?.message ?? "Failed to create NPC";
  } finally {
    loading.value = false;
  }
}

function onKey(e: KeyboardEvent) {
  if (e.key === "Escape") emit("close");
}
onMounted(() => document.addEventListener("keydown", onKey));
onBeforeUnmount(() => document.removeEventListener("keydown", onKey));
</script>

<template>
  <div v-if="props.open" :class="styles.overlay" @click.self="emit('close')">
    <div :class="styles.modal">
      <h2 :class="styles.title">Create NPC</h2>
      <p :class="styles.desc">
        Enter a prompt or leave blank for a random NPC.
      </p>

      <div :class="styles.field">
        <label :class="styles.label">Prompt</label>
        <textarea
          :class="styles.textarea"
          v-model="prompt"
          placeholder="A mischievous gnome wizard-engineer who lives to cause laughter…"
        />
      </div>

      <div :class="styles.row">
        <label :class="styles.checkbox">
          <input type="checkbox" v-model="withImage" />
          Generate portrait image
        </label>
        <div v-if="error" style="color: #b91c1c">{{ error }}</div>
      </div>

      <div :class="styles.actions">
        <button
          :class="[styles.btn]"
          @click="emit('close')"
          :disabled="loading"
        >
          Cancel
        </button>
        <button
          :class="[styles.btn, styles.secondary]"
          @click="doCreate(true)"
          :disabled="loading"
        >
          🎲 Random
        </button>
        <button
          :class="[styles.btn, styles.primary, { [styles.loading]: loading }]"
          @click="doCreate(false)"
          :disabled="loading"
        >
          {{ loading ? "Creating…" : "Create NPC" }}
        </button>
      </div>
    </div>
  </div>
</template>
