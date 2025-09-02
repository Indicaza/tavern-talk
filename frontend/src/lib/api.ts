export type Character = {
  id: string;
  is_pc: boolean;
  owner_user_id: string | null;
  name: string;
  race: string;
  subrace: string | null;
  class: string;
  level: number;
  gender: string;
  age: number;
  alignment: string;
  background: string | null;
  personality_type: string | null;
  bio: string | null;
  short_pitch: string | null;
  portrait_url: string | null;
  created_at: string;
  updated_at: string;
};

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
