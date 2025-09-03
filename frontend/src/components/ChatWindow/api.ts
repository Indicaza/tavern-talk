const BASE = import.meta.env.VITE_API_URL ?? "http://localhost:9000";

export type ChatMessage = {
  id: string;
  chat_id: string;
  role: "user" | "npc" | "system";
  content: string | null;
  created_at: string | null;
};

function fail(res: Response, data: any, fallback: string): never {
  const msg =
    (typeof data?.message === "string" && data.message) ||
    (typeof data?.error === "string" && data.error) ||
    `${fallback} (${res.status})`;
  throw new Error(msg);
}

export async function getMessages(chatId: string): Promise<ChatMessage[]> {
  const res = await fetch(
    `${BASE}/api/chats/${encodeURIComponent(chatId)}/messages`
  );
  let data: any;
  try {
    data = await res.json();
  } catch {
    if (!res.ok) fail(res, null, "Failed to load messages");
    throw new Error("Malformed response while loading messages");
  }
  if (!res.ok) fail(res, data, "Failed to load messages");
  if (!Array.isArray(data?.messages))
    throw new Error("Malformed response: messages[] missing");
  return data.messages as ChatMessage[];
}

export async function sendMessage(
  chatId: string,
  message: string
): Promise<{
  user: { id: string; content: string | null };
  npc: { id: string; content: string | null };
}> {
  const res = await fetch(
    `${BASE}/api/chats/${encodeURIComponent(chatId)}/messages`,
    {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message }),
    }
  );
  let data: any;
  try {
    data = await res.json();
  } catch {
    if (!res.ok) fail(res, null, "Failed to send message");
    throw new Error("Malformed response while sending message");
  }
  if (!res.ok) fail(res, data, "Failed to send message");
  if (!data?.npc || typeof data.npc.content === "undefined") {
    throw new Error("Malformed response: npc reply missing");
  }
  return data;
}
