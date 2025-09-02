import type { Character } from "../../../types/character";

const BASE = import.meta.env.VITE_API_URL ?? "http://localhost:9000";

export async function createNpc(
  prompt: string,
  withImage: boolean
): Promise<Character> {
  const res = await fetch(`${BASE}/api/npcs`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ prompt, withImage }),
  });
  const data = await res.json();
  if (!res.ok) {
    const msg =
      typeof data?.message === "string" ? data.message : "Failed to create NPC";
    throw new Error(msg);
  }
  return data as Character;
}
