<script setup lang="ts">
import styles from "./Navbar.module.css";
</script>

<template>
  <header :class="styles.navbar">
    <div :class="styles.navbarContainer">
      <div :class="styles.navbarLeft">
        <h1 :class="styles.navbarTitle">TavernTalk</h1>
      </div>

      <div :class="styles.navbarRight">
        <img :class="styles.userAvatar" src="" alt="User" />
      </div>
    </div>
  </header>
</template>
