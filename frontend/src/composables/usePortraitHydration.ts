import { Ref, watchEffect } from "vue";
import { pollNpcPortrait, type Character } from "@/lib/api";

export function ensurePortraitHydrationForList(npcsRef: Ref<Character[]>) {
  const inflight = new Set<string>();
  watchEffect(() => {
    for (const n of npcsRef.value) {
      if (!n.portrait_url && !inflight.has(n.id)) {
        inflight.add(n.id);
        pollNpcPortrait(n.id)
          .then((updated) => {
            const i = npcsRef.value.findIndex((x) => x.id === updated.id);
            if (i >= 0) npcsRef.value[i] = { ...updated };
            inflight.delete(n.id);
          })
          .catch(() => inflight.delete(n.id));
      }
    }
  });
}
