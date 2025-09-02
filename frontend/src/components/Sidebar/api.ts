import type { Character } from "../../types/character";

const BASE = import.meta.env.VITE_API_URL ?? "http://localhost:9000";

export async function getNpcs(): Promise<Character[]> {
  const res = await fetch(`${BASE}/api/npcs`);
  const data = await res.json();
  if (!res.ok) throw new Error("Failed to fetch NPCs");
  return data as Character[];
}

export async function deleteNpc(id: string): Promise<void> {
  const res = await fetch(`${BASE}/api/npcs/${id}`, { method: "DELETE" });
  if (!res.ok) throw new Error("Failed to delete NPC");
}
