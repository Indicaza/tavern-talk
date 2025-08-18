<script setup lang="ts"></script>

<template>
  <!-- wrapper that the global CSS positions/offsets -->
  <header class="navbar navbar-offset">
    <div class="navbarContainer">
      <div class="navbarLeft">
        <h1 class="navbarTitle">TavernTalk</h1>
      </div>

      <div class="navbarRight">
        <!-- replace with your avatar later -->
        <img
          class="userAvatar"
          src="https://placehold.co/60x60/png"
          alt="User"
        />
        <!-- example dropdown stub -->
        <!-- <div class="dropdownMenu open">
          <div class="dropdownItem">Settings</div>
          <div class="dropdownItem">Sign out</div>
        </div> -->
      </div>
    </div>
  </header>
</template>

<style src="./Navbar.css"></style>
