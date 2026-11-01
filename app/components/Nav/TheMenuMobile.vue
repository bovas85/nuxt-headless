<template>
  <div>
    <div
      class="overlay"
      :class="{ 'is-visible': appStore.navOpen }"
      @click="appStore.resetMenus()"
    />
    <div
      class="right-nav"
      :class="{ 'is-visible': appStore.navOpen }"
    >
      <ul class="menu">
        <NuxtLink
          v-for="menu in menuItems"
          :key="menu === '' ? 'home' : menu"
          :to="menu === '' ? '/' : `/${menu}`"
          @click="appStore.resetMenus()"
        >
          {{ menu === '' ? 'Home' : menu }}
        </NuxtLink>
      </ul>
      <div class="bottom-section">
        <p>email: <a href="mailto:hello@email.com?Subject=Hello">hello@email.com</a></p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    menuItems?: string[]
  }>(),
  {
    menuItems: () => ['']
  }
)

const appStore = useAppStore()
</script>

<style lang="scss" scoped>
.overlay {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 80px;
  opacity: 0;
  z-index: -2;
  cursor: pointer;
  transition: all 0.6s ease-in-out;
  &.is-visible {
    opacity: 1;
    z-index: 9000;
  }
}
.right-nav {
  background: white;
  overflow: auto;
  position: fixed;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  top: 60px;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100%;
  height: calc(100% - 60px);
  height: calc(100vh - 60px);
  opacity: 1;
  z-index: 9999;
  padding: 0 15px;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.5);
  transform: translateX(102%);
  transition: transform 0.6s ease-in-out;

  @include media(sm) {
    top: 90px;
    height: calc(100% - 90px);
    height: calc(100vh - 90px);
  }

  &.is-visible {
    opacity: 1;
    transform: translateX(0);
  }
}
.menu {
  display: flex;
  height: 100%;
  overflow: auto;
  justify-content: center;
  align-items: center;
  padding: 0;
  flex-direction: column;
  a {
    margin: 15px 0;
    cursor: pointer;
    font-size: 18px;
    color: black;
    text-transform: capitalize;
    font-weight: 400;
    &.router-link-active,
    &.router-link-exact-active {
      font-weight: bold;
    }
  }
}
.bottom-section {
  margin-top: auto;

  a {
    text-decoration: none;
    color: black;
    font-weight: 600;
  }
}
</style>