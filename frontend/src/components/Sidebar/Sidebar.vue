<script setup lang="ts">
import { ref, onMounted } from "vue";
import SidebarButton from "./SidebarButton/SidebarButton.vue";
import CreateNpcModal from "./CreateNpcModal/CreateNpcModal.vue";
import styles from "./Sidebar.module.css";
import { getNpcs, deleteNpc } from "./api";
import type { Character } from "../../types/character";

type Props = {
  collapsed?: boolean;
};
const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "toggle"): void;
  (e: "npc-created", id: string): void;
}>();

const showCreate = ref(false);
const npcs = ref<Character[]>([]);

async function loadNpcs() {
  npcs.value = await getNpcs();
}

async function removeNpc(id: string) {
  await deleteNpc(id);
  await loadNpcs();
}

onMounted(loadNpcs);
</script>

<template>
  <button
    v-if="props.collapsed"
    :class="styles.revealBtn"
    @click="emit('toggle')"
    aria-label="Open sidebar"
    title="Open sidebar"
  >
    ›
  </button>

  <aside :class="[styles.sidebar, { [styles.collapsed]: !!props.collapsed }]">
    <div :class="styles.topRow">
      <button :class="styles.collapseBtn" @click="emit('toggle')">
        {{ props.collapsed ? "›" : "‹" }}
      </button>
      <span v-if="!props.collapsed" :class="styles.title">NPCs</span>
    </div>

    <ul :class="styles.chatList">
      <li v-for="npc in npcs" :key="npc.id" :class="styles.chatItem">
        <span :class="styles.dot"></span>
        <span v-if="!props.collapsed">{{ npc.name }}</span>
        <button
          style="margin-left: auto"
          @click.stop="removeNpc(npc.id)"
          aria-label="Delete NPC"
          title="Delete NPC"
        >
          ✖
        </button>
      </li>
    </ul>

    <div style="padding: 8px; margin-top: auto">
      <SidebarButton label="Create NPC" @click="showCreate = true">
        <template #icon>👤</template>
      </SidebarButton>
    </div>
  </aside>

  <CreateNpcModal
    :open="showCreate"
    @close="showCreate = false"
    @created="
      async (npc) => {
        emit('npc-created', npc.id);
        await loadNpcs();
      }
    "
  />
</template>
