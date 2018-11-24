<template>
  <div>
    <div
      class="overlay"
      @click="$store.dispatch('resetScroll')"
      :class="{'is-visible': $store.state.navOpen}"/>
    <div 
      class="right-nav"
      :class="{'is-visible': $store.state.navOpen}"
    >
      <ul class="menu">
        <nuxt-link
          v-for="menu in menuItems"
          :key="menu === '' ? 'home': menu"
          exact
          :to="`/${menu}`"
        >
          {{ menu === '' ? 'Home' : menu }}
        </nuxt-link>
      </ul>
      <div class="bottom-section">
        <p>email: <a href="mailto:hello@email.com?Subject=Hello">hello@email.com</a></p>
      </div>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'TheMenuMobile',
    props: {
      menuItems: {
        type: Array,
        default: () => []
      }
    }
  }
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
    .close-button {
      display: flex;
      height: 60px;
      justify-content: flex-end;
      align-items: center;
      .text {
        cursor: pointer;
        font-size: 14px;
        letter-spacing: 1.2px;
        text-align: left;
        color: $grey;
      }
    }

    .menu-items {
      margin-bottom: 100px;
      li {
        padding: 15px 0;
        display: flex;
        cursor: pointer;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ededed;
        transition: all 0.3s ease-in-out;
        &:hover,
        &:hover i,
        &:hover a {
          color: $red;
        }
      }
      a {
        font-size: 14px;
        line-height: 1;
        letter-spacing: 0.5px;
        text-align: left;
        color: $secondary;
        text-transform: uppercase;
        transition: all 0.3s ease-in-out;
      }
      i {
        line-height: 1;
        width: 7px;
        font-size: 16px;
        height: 19px;
        transition: all 0.3s ease-in-out;
      }
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
      &.nuxt-link-active {
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
  .social-list {
    display: flex;
    justify-content: space-around;
    width: 100%;
    margin-top: $gap;
    margin-bottom: 50px;

    img {
      border-radius: 100%;
      width: 36px;
      height: 36px;
    }
  }
</style>