const BASE = import.meta.env.VITE_API_URL ?? "http://localhost:9000";

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
    alignment: string;
  };
  last_message_at: string | null;
  updated_at: string;
  created_at: string;
};

export async function getChats(): Promise<ChatListItem[]> {
  const res = await fetch(`${BASE}/api/chats`);
  const data = await res.json();
  if (!res.ok) throw new Error("Failed to load chats");
  return data as ChatListItem[];
}

export async function deleteChat(id: string): Promise<void> {
  const res = await fetch(`${BASE}/api/chats/${id}`, { method: "DELETE" });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data?.message || "Failed to delete chat");
  }
}

export async function createChat(npcId: string, title?: string) {
  const res = await fetch(`${BASE}/api/chats`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ npc_id: npcId, title }),
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data?.message || "Failed to create chat");
  return data as ChatListItem;
}
