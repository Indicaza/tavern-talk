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
        <div :class="styles.headerGrid">
          <img
            v-if="npc.portrait_url"
            :src="npc.portrait_url"
            :alt="npc.name"
            :class="styles.portrait"
          />
          <div v-else :class="styles.portraitPending" aria-busy="true" />
          <div :class="styles.headerMeta">
            <h2 :class="styles.title">{{ npc.name }}</h2>
            <div :class="styles.subtitle">
              <span v-if="npc.level">Lv {{ npc.level }}</span>
              <span v-if="npc.level"> • </span>
              <span>{{ npc.race }}</span>
              <span> • </span>
              <span>{{ npc.class }}</span>
            </div>
            <div :class="styles.chips">
              <span v-if="npc.alignment" :class="styles.chip">{{
                npc.alignment
              }}</span>
              <span v-if="npc.personality_type" :class="styles.chip">{{
                npc.personality_type
              }}</span>
            </div>
          </div>
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
        <section :class="styles.card">
          <div :class="styles.cardLabel">Pitch</div>
          <div :class="styles.cardValue">{{ npc.short_pitch || "—" }}</div>
        </section>

        <section :class="styles.card">
          <div :class="styles.cardLabel">Background</div>
          <div :class="styles.cardValue">{{ npc.background || "—" }}</div>
        </section>

        <section :class="[styles.card, styles.span2]">
          <div :class="styles.cardLabel">Bio</div>
          <div :class="styles.cardValue">{{ npc.bio || "—" }}</div>
        </section>

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
