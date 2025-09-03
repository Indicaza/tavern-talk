<script setup lang="ts">
import { ref } from "vue";
import styles from "./NpcDetailModal.module.css";
import type { Character } from "../../../types/character";
import { createChatForNpc } from "./api";

type Props = {
  open: boolean;
  npc: Character | null;
};
const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "open-chat", id: string): void;
}>();

const loading = ref(false);
const error = ref<string | null>(null);

async function startChat() {
  if (!props.npc || loading.value) return;
  loading.value = true;
  error.value = null;
  try {
    const chat = await createChatForNpc(props.npc.id, props.npc.name);
    emit("open-chat", chat.id);
    emit("close");
  } catch (e: any) {
    error.value = e?.message ?? "Unable to start chat";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div v-if="open && npc" :class="styles.overlay" @click.self="emit('close')">
    <div :class="styles.modal">
      <div :class="styles.header">
        <div :class="styles.titleRow">
          <img
            v-if="npc.portrait_url"
            :src="npc.portrait_url"
            :alt="npc.name"
            :class="styles.portrait"
          />
          <div>
            <h2 :class="styles.title">{{ npc.name }}</h2>
            <div :class="styles.subtitle">
              {{ npc.race }} • {{ npc.class }} • Lv {{ npc.level }}
            </div>
          </div>
        </div>
        <button :class="styles.closeBtn" @click="emit('close')">✕</button>
      </div>

      <div :class="styles.body">
        <div :class="styles.section">
          <div :class="styles.label">Alignment</div>
          <div :class="styles.value">{{ npc.alignment }}</div>
        </div>
        <div :class="styles.section">
          <div :class="styles.label">Personality</div>
          <div :class="styles.value">{{ npc.personality_type }}</div>
        </div>
        <div :class="styles.section">
          <div :class="styles.label">Background</div>
          <div :class="styles.value">{{ npc.background }}</div>
        </div>
        <div :class="styles.section">
          <div :class="styles.label">Pitch</div>
          <div :class="styles.value">{{ npc.short_pitch }}</div>
        </div>
        <div :class="styles.section">
          <div :class="styles.label">Bio</div>
          <div :class="styles.value">{{ npc.bio }}</div>
        </div>

        <div v-if="error" :class="styles.error">{{ error }}</div>

        <div :class="styles.actions">
          <button
            :class="styles.primary"
            @click="startChat"
            :disabled="loading"
          >
            {{ loading ? "Starting…" : "Start Chat" }}
          </button>
          <button :class="styles.secondary" @click="emit('close')">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
