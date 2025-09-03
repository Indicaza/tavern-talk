<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import SidebarButton from "./SidebarButton/SidebarButton.vue";
import CreateNpcModal from "./CreateNpcModal/CreateNpcModal.vue";
import NpcDetailModal from "./NpcDetailModal/NpcDetailModal.vue";
import styles from "./Sidebar.module.css";
import { getNpcs, deleteNpc } from "./api";
import type { Character } from "../../types/character";
import { getChats, deleteChat } from "./Chats/api";

export type ChatListItem = {
  id: string;
  title: string | null;
  npc?: {
    id: string;
    name: string;
    portrait_url: string | null;
    class: string;
    level: number;
    race: string;
    alignment: string | null;
  } | null;
  last_message_at: string | null;
};

type Props = { collapsed?: boolean };
const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "toggle"): void;
  (e: "npc-created", id: string): void;
  (e: "open-chat", id: string): void;
}>();

const showCreate = ref(false);

const npcsOpen = ref(true);
const allOpen = ref(true);
const search = ref("");
const debounced = ref("");

const chatsOpen = ref(true);
const allChatsOpen = ref(true);

const npcs = ref<Character[]>([]);
const chats = ref<ChatListItem[]>([]);
const selected = ref<Character | null>(null);

const SB_COLLAPSED = 240;
const SB_EXPANDED = 300;

function applySidebarWidth() {
  const root = document.documentElement;
  const anyGroupOpen = npcsOpen.value || chatsOpen.value;
  const px = props.collapsed ? 0 : anyGroupOpen ? SB_EXPANDED : SB_COLLAPSED;
  root.style.setProperty("--sidebar-width", px + "px");
}

async function loadNpcs() {
  npcs.value = await getNpcs();
}

async function removeNpc(id: string) {
  await deleteNpc(id);
  await loadNpcs();
}

async function loadChats() {
  try {
    chats.value = await getChats();
  } catch {
    chats.value = [];
  }
}

async function removeChat(id: string) {
  await deleteChat(id);
  await loadChats();
}

let t: number | undefined;
watch(search, (v) => {
  if (t) clearTimeout(t);
  t = window.setTimeout(() => (debounced.value = v.trim().toLowerCase()), 250);
});

const filtered = computed(() => {
  const q = debounced.value;
  if (!q) return npcs.value;
  return npcs.value.filter((c: Character) => {
    const hay = `${c.name} ${c.class} ${c.race} ${c.alignment}`.toLowerCase();
    return hay.includes(q);
  });
});

watch([() => props.collapsed, npcsOpen, chatsOpen], applySidebarWidth, {
  immediate: true,
});

onMounted(() => {
  applySidebarWidth();
  loadNpcs();
  loadChats();
});

function formatDate(d: string | null) {
  if (!d) return "";
  const dt = new Date(d);
  return dt.toLocaleDateString(undefined, { month: "short", day: "numeric" });
}
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

    <div :class="styles.groupHeader" @click="npcsOpen = !npcsOpen">
      <span
        :class="[
          styles.caret,
          npcsOpen ? styles.caretOpen : styles.caretClosed,
        ]"
        >▶</span
      >
      <span>NPCs</span>
    </div>

    <div :class="[styles.groupContent, npcsOpen ? styles.open : styles.closed]">
      <div :class="styles.groupInner">
        <div :class="styles.actionsAreaTop">
          <SidebarButton label="Create NPC" @click="showCreate = true">
            <template #icon>👤</template>
          </SidebarButton>
        </div>

        <div :class="styles.searchWrap">
          <input
            :class="styles.searchInput"
            v-model="search"
            type="text"
            placeholder="Search name, class, race, alignment…"
          />
        </div>

        <div
          :class="[styles.subHeader, styles.indent1]"
          @click="allOpen = !allOpen"
        >
          <span
            :class="[
              styles.caret,
              allOpen ? styles.caretOpen : styles.caretClosed,
            ]"
            >▶</span
          >
          <span>All NPCs</span>
        </div>

        <div
          :class="[
            styles.subContent,
            styles.indent2,
            allOpen ? styles.open : styles.closed,
          ]"
        >
          <ul v-if="filtered.length" :class="styles.list">
            <li
              v-for="npc in filtered"
              :key="npc.id"
              :class="styles.row"
              @click="selected = npc"
            >
              <img
                v-if="npc.portrait_url"
                :src="npc.portrait_url"
                :alt="npc.name"
                :class="styles.thumb"
              />
              <div :class="styles.meta">
                <div :class="styles.name">{{ npc.name }}</div>
                <div :class="styles.sub">
                  {{ npc.race }} • {{ npc.class }} • Lv {{ npc.level }} •
                  {{ npc.alignment }}
                </div>
              </div>
              <button :class="styles.deleteBtn" @click.stop="removeNpc(npc.id)">
                ✕
              </button>
            </li>
          </ul>
          <div v-else :class="styles.empty">No NPCs match your search.</div>
        </div>
      </div>
    </div>

    <div :class="styles.groupHeader" @click="chatsOpen = !chatsOpen">
      <span
        :class="[
          styles.caret,
          chatsOpen ? styles.caretOpen : styles.caretClosed,
        ]"
        >▶</span
      >
      <span>Chats</span>
    </div>

    <div
      :class="[styles.groupContent, chatsOpen ? styles.open : styles.closed]"
    >
      <div :class="styles.groupInner">
        <div
          :class="[styles.subHeader, styles.indent1]"
          @click="allChatsOpen = !allChatsOpen"
        >
          <span
            :class="[
              styles.caret,
              allChatsOpen ? styles.caretOpen : styles.caretClosed,
            ]"
            >▶</span
          >
          <span>All Chats</span>
        </div>

        <div
          :class="[
            styles.subContent,
            styles.indent2,
            allChatsOpen ? styles.open : styles.closed,
          ]"
        >
          <ul v-if="chats.length" :class="styles.list">
            <li
              v-for="c in chats"
              :key="c.id"
              :class="styles.row"
              @click="$emit('open-chat', c.id)"
            >
              <img
                v-if="c.npc?.portrait_url"
                :src="c.npc.portrait_url"
                :alt="c.npc?.name || c.title || 'Chat'"
                :class="styles.thumb"
              />
              <div :class="styles.meta">
                <div :class="styles.name">
                  {{
                    c.title || (c.npc?.name ? `${c.npc.name} Chat` : "Untitled")
                  }}
                </div>
                <div :class="styles.sub">
                  <span v-if="c.npc">
                    {{ c.npc.race }} • {{ c.npc.class }} • Lv {{ c.npc.level }}
                  </span>
                  <span v-if="c.last_message_at">
                    • {{ formatDate(c.last_message_at) }}
                  </span>
                </div>
              </div>
              <button :class="styles.deleteBtn" @click.stop="removeChat(c.id)">
                ✕
              </button>
            </li>
          </ul>
          <div v-else :class="styles.empty">No chats yet.</div>
        </div>
      </div>
    </div>
  </aside>

  <CreateNpcModal
    :open="showCreate"
    @close="showCreate = false"
    @created="
      async (npc) => {
        emit('npc-created', npc.id);
        await loadNpcs();
        await loadChats();
      }
    "
  />

  <NpcDetailModal
    :open="!!selected"
    :npc="selected"
    @close="selected = null"
    @open-chat="
      async (id) => {
        emit('open-chat', id);
        await loadChats();
      }
    "
  />
</template>
