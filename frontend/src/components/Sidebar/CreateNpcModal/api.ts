import type { Character } from "../../../types/character";

const BASE = import.meta.env.VITE_API_URL ?? "http://localhost:9000";

export async function createNpc(prompt: string): Promise<Character> {
  const res = await fetch(`${BASE}/api/npcs`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ prompt }),
  });
  let data: any;
  try {
    data = await res.json();
  } catch {
    if (!res.ok) throw new Error("Failed to create NPC");
    throw new Error("Malformed response while creating NPC");
  }
  if (!res.ok) {
    const msg =
      (typeof data?.message === "string" && data.message) ||
      (typeof data?.error === "string" && data.error) ||
      "Failed to create NPC";
    throw new Error(msg);
  }
  return data as Character;
}
