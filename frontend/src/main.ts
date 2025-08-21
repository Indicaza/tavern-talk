import { createApp } from "vue";
import App from "./App.vue";
import "./reset.css";
import "./style.css";
import { applyTheme } from "./theme";

applyTheme();
createApp(App).mount("#app");
