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
        <img
          :class="styles.userAvatar"
          src="https://placehold.co/60x60/png"
          alt="User"
        />
      </div>
    </div>
  </header>
</template>
