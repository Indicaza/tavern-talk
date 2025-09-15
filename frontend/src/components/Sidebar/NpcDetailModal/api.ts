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
  let data: any;
  try {
    data = await res.json();
  } catch {
    if (!res.ok) fail(res, null, "Failed to create chat");
    throw new Error("Malformed response while creating chat");
  }
  if (!res.ok) fail(res, data, "Failed to create chat");
  return data as Chat;
}

export async function createChatForNpc(npcId: string, title?: string) {
  const res = await fetch(`${BASE}/api/chats`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ npc_id: npcId, title }),
  });
  let data: any;
  try {
    data = await res.json();
  } catch {
    if (!res.ok) throw new Error("Failed to create chat");
    throw new Error("Malformed response while creating chat");
  }
  if (!res.ok) {
    const msg =
      (typeof data?.message === "string" && data.message) ||
      (typeof data?.error === "string" && data.error) ||
      "Failed to create chat";
    throw new Error(msg);
  }
  return data as { id: string };
}
