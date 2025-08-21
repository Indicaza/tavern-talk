import { theme } from "./theme";

export function setSidebarCollapsed(collapsed: boolean) {
  const width = collapsed ? "0px" : theme.layout.sidebarWidth;
  document.documentElement.style.setProperty("--sidebar-width", width);
}
