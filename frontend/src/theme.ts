// src/theme.ts

export const theme = {
  layout: {
    navbarHeight: "80px",
    sidebarWidth: "240px",
    contentMaxWidth: "800px",
    viewportMobile: "900px",
    zIndex: {
      sidebar: 15,
      navbar: 20,
      revealBtn: 30,
      modal: 50,
    },
    radii: {
      sm: "8px",
      md: "12px",
      lg: "14px",
      pill: "100px",
      round: "999px",
    },
    shadows: {
      soft: "2px 2px 10px 1px rgba(0,0,0,0.65)",
      insetThin: "inset 0 0 0 1px #1f2937",
      avatar: "3px 3px 3px rgba(1,1,1,0.95)",
      modal: "0 12px 30px rgba(0,0,0,0.4)",
    },
  },

  motion: {
    durations: {
      fast: "0.2s",
      base: "0.25s",
      slow: "0.35s",
    },
    easings: {
      standard: "ease",
      standardInOut: "cubic-bezier(0.4, 0, 0.2, 1)",
      bouncy: "cubic-bezier(0.68, -0.55, 0.27, 1.5)",
      springy: "cubic-bezier(0.25, 1.25, 0.5, 1)",
    },
    anim: {
      popStartY: "10px",
      popOvershootY: "-2px",
      popStartScale: "0.95",
      popOvershootScale: "1.02",
    },
  },

  colors: {
    app: { bg: "#212121", text: "#e5e7eb" },
    navbar: { bg: "#141414", border: "rgba(83,83,83,0.75)", title: "#ffffff" },
    sidebar: {
      bg: "#181818",
      border: "#1f2937",
      hover: "#111827",
      active: "#0b1222",
      title: "#94a3b8",
      dotOnline: "#22c55e",
      revealHover: "#273244",
      btnBorder: "#334155",
    },
    chat: {
      systemBg: "#1f2937",
      systemText: "#cbd5e1",
      bubbleBorder: "#101010",
      bubbleShadow: "2px 2px 10px 1px rgba(0,0,0,0.65)",
    },
    bubbles: {
      userBg: "#1d4ed8",
      userText: "#e6eeff",
      assistantBg: "#0f766e",
      assistantText: "#eafffa",
    },
    composer: {
      bg: "#303030",
      ring: "#1f2937",
      ringFocus: "#334155",
      inputText: "#e5e7eb",
      sendBg: "#2563eb",
      sendBgHover: "#1d4ed8",
    },
    modal: { bg: "#ffffff", text: "#0f172a" },
  },

  navbar: { paddingX: "45px", titleSize: "32px" },
  sidebar: {
    itemHeight: "400px",
    itemGap: "10px",
    itemRadius: "8px",
    collapseBtnSize: "42px",
    revealBtnSize: "42px",
  },
  chatWindow: { gap: "10px", padding: "16px" },
  bubbles: {
    maxWidth: "760px",
    relativeMax: "85%",
    radius: "14px",
    padding: "12px 14px",
    tailSize: "15px",
    tailWidth: "10px",
  },
  composer: {
    maxHeight: "200px",
    paddingX: "16px",
    paddingRightReserve: "56px",
    pillRadius: "100px",
    grownRadius: "14px",
    sendSize: "42px",
    sendFontSize: "1.1rem",
  },
} as const;

export function applyTheme(t: typeof theme = theme): void {
  const root = document.documentElement;

  set("--navbar-height", t.layout.navbarHeight);
  set("--sidebar-width", t.layout.sidebarWidth);
  set("--content-max-width", t.layout.contentMaxWidth);
  set("--viewport-mobile", t.layout.viewportMobile);

  set("--z-sidebar", t.layout.zIndex.sidebar);
  set("--z-navbar", t.layout.zIndex.navbar);
  set("--z-reveal", t.layout.zIndex.revealBtn);
  set("--z-modal", t.layout.zIndex.modal);

  set("--r-sm", t.layout.radii.sm);
  set("--r-md", t.layout.radii.md);
  set("--r-lg", t.layout.radii.lg);
  set("--r-pill", t.layout.radii.pill);
  set("--r-round", t.layout.radii.round);

  set("--shadow-soft", t.layout.shadows.soft);
  set("--shadow-inset-thin", t.layout.shadows.insetThin);
  set("--shadow-avatar", t.layout.shadows.avatar);
  set("--shadow-modal", t.layout.shadows.modal);

  set("--dur-fast", t.motion.durations.fast);
  set("--dur-base", t.motion.durations.base);
  set("--dur-slow", t.motion.durations.slow);

  set("--ease-standard", t.motion.easings.standard);
  set("--ease-standard-inout", t.motion.easings.standardInOut);
  set("--ease-bouncy", t.motion.easings.bouncy);
  set("--ease-springy", t.motion.easings.springy);

  set("--anim-pop-start-y", t.motion.anim.popStartY);
  set("--anim-pop-overshoot-y", t.motion.anim.popOvershootY);
  set("--anim-pop-start-scale", t.motion.anim.popStartScale);
  set("--anim-pop-overshoot-scale", t.motion.anim.popOvershootScale);

  setGroup("--app", t.colors.app);
  setGroup("--navbar", t.colors.navbar);
  setGroup("--sidebar", t.colors.sidebar);
  setGroup("--chat", t.colors.chat);
  setGroup("--bubbles", t.colors.bubbles);
  setGroup("--composer", t.colors.composer);
  setGroup("--modal", t.colors.modal);

  setGroup("--nav", {
    paddingX: t.navbar.paddingX,
    titleSize: t.navbar.titleSize,
  });
  setGroup("--sb", {
    itemHeight: t.sidebar.itemHeight,
    itemGap: t.sidebar.itemGap,
    itemRadius: t.sidebar.itemRadius,
    collapseBtnSize: t.sidebar.collapseBtnSize,
    revealBtnSize: t.sidebar.revealBtnSize,
  });
  setGroup("--chatwin", {
    gap: t.chatWindow.gap,
    padding: t.chatWindow.padding,
  });
  setGroup("--bubble", {
    maxWidth: t.bubbles.maxWidth,
    relativeMax: t.bubbles.relativeMax,
    radius: t.bubbles.radius,
    padding: t.bubbles.padding,
    tailSize: t.bubbles.tailSize,
    tailWidth: t.bubbles.tailWidth,
  });
  setGroup("--composer", {
    maxHeight: t.composer.maxHeight,
    paddingX: t.composer.paddingX,
    paddingRightReserve: t.composer.paddingRightReserve,
    pillRadius: t.composer.pillRadius,
    grownRadius: t.composer.grownRadius,
    sendSize: t.composer.sendSize,
    sendFontSize: t.composer.sendFontSize,
  });

  function set(name: string, value: string | number) {
    root.style.setProperty(name, String(value));
  }

  function setGroup(prefix: string, obj: Record<string, string | number>) {
    Object.entries(obj).forEach(([k, v]) => {
      const kebab = k.replace(/[A-Z]/g, (m) => "-" + m.toLowerCase());
      root.style.setProperty(`${prefix}-${kebab}`, String(v));
    });
  }
}
