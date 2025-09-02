<script setup lang="ts">
import { ref } from "vue";
import SidebarButton from "./SidebarButton/SidebarButton.vue";
import CreateNpcModal from "./CreateNpcModal/CreateNpcModal.vue";
import styles from "./Sidebar.module.css";

type Props = {
  collapsed?: boolean;
};
const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "toggle"): void;
  (e: "npc-created", id: string): void;
}>();

const showCreate = ref(false);
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
      <span v-if="!props.collapsed" :class="styles.title">Actions</span>
    </div>

    <div style="padding: 8px; margin-top: auto">
      <SidebarButton label="Create NPC" @click="showCreate = true">
        <template #icon> 👤 </template>
      </SidebarButton>
    </div>
  </aside>

  <CreateNpcModal
    :open="showCreate"
    @close="showCreate = false"
    @created="(npc) => emit('npc-created', npc.id)"
  />
</template>
