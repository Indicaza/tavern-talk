const BASE = import.meta.env.VITE_API_URL ?? "http://localhost:9000";

export type Chat = {
  id: string;
  npc_id: string;
  title: string | null;
  created_at: string;
  updated_at: string;
};

function fail(res: Response, data: any, fallback: string): never {
  const msg =
    (typeof data?.message === "string" && data.message) ||
    (typeof data?.error === "string" && data.error) ||
    `${fallback} (${res.status})`;
  throw new Error(msg);
}

export async function createChat(
  npcId: string,
  title?: string | null
): Promise<Chat> {
  const res = await fetch(`${BASE}/api/chats`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ npc_id: npcId, title: title ?? null }),
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    fail(res, data, "Failed to create chat");
  }
  return res.json();
}
