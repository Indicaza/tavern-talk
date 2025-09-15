<template>
  <div v-if="open && npc" :class="styles.overlay" @click.self="emit('close')">
    <div :class="styles.modal">
      <div :class="styles.header">
        <button :class="styles.closeBtn" @click="emit('close')">✕</button>
        <div :class="styles.headerGrid">
          <img
            v-if="npc.portrait_url"
            :src="npc.portrait_url"
            :alt="npc.name"
            :class="styles.portrait"
          />
          <div v-else :class="styles.portraitPending" />
          <div :class="styles.headerMeta">
            <div :class="styles.title">{{ npc.name }}</div>
            <div :class="styles.subtitle">
              <span>{{ npc.race }}</span>
              <span v-if="npc.subrace">• {{ npc.subrace }}</span>
              <span>• {{ npc.class }} {{ npc.level }}</span>
            </div>
            <div :class="styles.chips">
              <span :class="styles.chip">{{ isPc ? "PC" : "NPC" }}</span>
              <span :class="styles.chip">{{ npc.alignment }}</span>
              <span :class="styles.chip">{{ npc.background }}</span>
              <span :class="styles.chip">{{ npc.gender }}, {{ npc.age }}</span>
              <span v-if="portraitStatus" :class="styles.chip"
                >Portrait: {{ portraitStatus }}</span
              >
            </div>
          </div>
        </div>
      </div>

      <div :class="styles.body">
        <div :class="[styles.card, styles.span2]">
          <div :class="styles.sectionTitle">Ability Scores</div>
          <div :class="styles.abilityGrid">
            <div v-for="a in abilities" :key="a.key" :class="styles.ability">
              <div :class="styles.abbr">{{ a.abbr }}</div>
              <div :class="styles.score">{{ a.score }}</div>
              <div :class="styles.mod">{{ formatMod(mod(a.score)) }}</div>
              <div :class="styles.abilityLabel">{{ a.label }}</div>
            </div>
          </div>
        </div>

        <div :class="styles.card">
          <div :class="styles.cardLabel">Persona</div>
          <div :class="styles.cardValue">{{ npc.personality_type || "—" }}</div>
        </div>

        <div :class="styles.card">
          <div :class="styles.cardLabel">Pitch</div>
          <div :class="styles.cardValue">{{ npc.short_pitch || "—" }}</div>
        </div>

        <div :class="[styles.card, styles.span2]">
          <div :class="styles.cardLabel">Biography</div>
          <div :class="styles.cardValue">{{ npc.bio || "—" }}</div>
        </div>

        <div :class="[styles.actions, styles.span2]">
          <button :class="styles.secondary" @click="emit('close')">
            Close
          </button>
          <button :class="styles.primary" :disabled="creating" @click="onTalk">
            {{ creating ? "Opening..." : "Talk to NPC" }}
          </button>
        </div>

        <div v-if="error" :class="styles.error">{{ error }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import styles from "./NpcDetailModal.module.css";
import type { Character } from "@/types/character";
import { createChat } from "./api";
import { ref, computed } from "vue";

type Props = {
  open: boolean;
  npc: Character | null;
};
const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "open-chat", chatId: string): void;
}>();

const error = ref<string | null>(null);
const creating = ref(false);

const npc = computed(() => props.npc);

const isPc = computed<boolean>(() => {
  const n = npc.value as any;
  return !!(n && n.is_pc);
});

const portraitStatus = computed<string | null>(() => {
  const n = npc.value as any;
  return (n && n.portrait_status) || null;
});

function mod(score: number) {
  return Math.floor((score - 10) / 2);
}
function formatMod(n: number) {
  return n >= 0 ? `+${n}` : `${n}`;
}

const abilities = computed(() => {
  if (!npc.value) return [];
  const n = npc.value;
  return [
    { key: "str", abbr: "STR", label: "Strength", score: n.str_score ?? 10 },
    { key: "dex", abbr: "DEX", label: "Dexterity", score: n.dex_score ?? 10 },
    {
      key: "con",
      abbr: "CON",
      label: "Constitution",
      score: n.con_score ?? 10,
    },
    {
      key: "int",
      abbr: "INT",
      label: "Intelligence",
      score: n.int_score ?? 10,
    },
    { key: "wis", abbr: "WIS", label: "Wisdom", score: n.wis_score ?? 10 },
    { key: "cha", abbr: "CHA", label: "Charisma", score: n.cha_score ?? 10 },
  ];
});

async function onTalk() {
  try {
    if (!npc.value) return;
    creating.value = true;
    error.value = null;
    const chat = await createChat(npc.value.id);
    emit("open-chat", chat.id);
    emit("close");
  } catch (e: any) {
    error.value = e?.message || "Failed to open chat";
  } finally {
    creating.value = false;
  }
}
</script>
