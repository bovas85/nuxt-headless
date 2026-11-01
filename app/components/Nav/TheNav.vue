<template>
  <div class="navigation-bar">
    <div
      class="navigation"
      :class="{
        scrolled: appStore.menuScrolled,
        about: route.path === '/about',
        contact: route.path === '/contact'
      }"
    >
      <nav role="navigation" class="container is-flex navbar">
        <NuxtLink to="/" class="logo col--8-mobile col--4-tablet is-center">
          <img src="https://placehold.co/40x40" alt="Logo" />
        </NuxtLink>

        <div
          v-if="appStore.windowWidth < 1024"
          class="menu menu--mobile"
          @click="appStore.toggleMenu()"
        >
          <TransitionGroup name="rotate" mode="out-in">
            <div v-if="!appStore.navOpen" key="closed" class="rotate">
              <BurgerMenu
                :fill="(route.path === '/' && 'black') || appStore.menuScrolled ? 'black' : 'white'"
                :stroke="(route.path === '/' && 'black') || appStore.menuScrolled ? 'black' : 'white'"
              />
            </div>
            <div v-else key="open" class="rotate">
              <div :class="route.path === '/' && 'black'" class="close-icon">
                <span class="close-icon--line" />
                <span class="close-icon--line inverted" />
              </div>
            </div>
          </TransitionGroup>
        </div>
      </nav>
    </div>

    <ClientOnly>
      <div v-if="appStore.windowWidth < 1024" style="z-index: 9999" class="modal-container is-hidden-desktop">
        <TheMenuMobile :menu-items="menuItems" />
      </div>
    </ClientOnly>
  </div>
</template>

<script setup lang="ts">
const appStore = useAppStore()
const route = useRoute()
const menuItems = ref([''])

if (import.meta.client) {
  const { width } = useWindowSize()
  watch(
    width,
    (newWidth) => {
      appStore.setWindowWidth(newWidth)
    },
    { immediate: true }
  )

  const { effectiveType } = useNetwork()
  watch(
    effectiveType,
    (newType) => {
      appStore.setConnection(newType || null)
    },
    { immediate: true }
  )

  // Reset menu when route changes
  watch(
    () => route.path,
    () => {
      appStore.resetMenus()
    }
  )
}
</script>

<style lang="scss" scoped>
.navigation {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 5;
  height: 60px;
  background-color: transparent;
  box-shadow: unset;
  margin: 0 auto;
  transition: all 0.3s ease-in-out;
  padding: 0 $gap;

  @include media(sm) {
    height: 90px;
    padding: 0;
  }

  .navbar {
    align-items: center;
    height: 100%;
    justify-content: space-between;

    .logo {
      &:before,
      &:after {
        display: none;
      }
    }
    img {
      width: 50px;
      height: 31px;
      object-fit: contain;
      stroke: $primary;
    }
    .menu {
      cursor: pointer;
      .rotate {
        cursor: pointer;
      }
      &--mobile {
        display: block;
        position: relative;
      }
      &--desktop {
        display: flex;
        justify-content: space-around;

        a {
          cursor: pointer;
          font-size: $font-size + 4px;
          text-transform: capitalize;
          font-weight: 400;
          color: white;
          transition: color 0.3s ease-in-out;

          &.router-link-active {
            color: $primary;
          }

          &:not(:last-child) {
            margin-right: $gap;
          }

          position: relative;
          padding: calc($gap / 3) 0;

          &:hover {
            color: #fff;
            text-decoration: none;
          }

          &:before,
          &:after {
            content: "";
            position: absolute;
            width: 0%;
            height: 2px;
            bottom: -2px;
            background: #fff;
          }

          &:before {
            left: 0;
          }

          &:after {
            right: 0;
            background: #fff;
            transition: width 0.8s cubic-bezier(0.22, 0.61, 0.36, 1);
          }

          &:hover:before {
            background: #fff;
            width: 100%;
            transition: width 0.5s cubic-bezier(0.22, 0.61, 0.36, 1);
          }

          &:hover:after {
            background: transparent;
            width: 100%;
            transition: 0s;
          }

          &.router-link-active {
            color: $primary;

            &:before,
            &:after {
              background: $primary;
            }

            &:after {
              right: 0;
              background: $primary;
            }

            &:hover {
              &:before {
                background: $primary;
              }
              &:after {
                background: transparent;
                width: 100%;
                transition: 0s;
              }
            }
          }
        }

        &.black {
          a:not(.router-link-active) {
            color: black;

            &:hover {
              color: black;
              text-decoration: none;
            }

            &:before,
            &:after {
              content: "";
              position: absolute;
              width: 0%;
              height: 2px;
              bottom: -2px;
              background: black;
            }

            &:before {
              left: 0;
            }

            &:after {
              right: 0;
              background: black;
              transition: width 0.8s cubic-bezier(0.22, 0.61, 0.36, 1);
            }

            &:hover:before {
              background: black;
              width: 100%;
              transition: width 0.5s cubic-bezier(0.22, 0.61, 0.36, 1);
            }
          }
        }
      }
    }
  }

  &.scrolled {
    background-color: #f0efef;

    .navbar a {
      color: $secondary;

      &:before,
      &:after {
        background: $primary;
      }

      &:hover:before {
        background: $primary;
        width: 100%;
        transition: width 0.5s cubic-bezier(0.22, 0.61, 0.36, 1);
      }

      &:hover:after {
        background: transparent;
        width: 100%;
        transition: 0s;
      }
    }

    .close-icon {
      cursor: pointer;
      &--line {
        background: black;
      }
    }
  }

  &.contact,
  &.about {
    background-color: transparent;
    .navbar a {
      color: white;
    }
  }

  i {
    color: $grey;
    cursor: pointer;
    font-size: 14px;
    @media (min-width: $tablet) {
      font-size: 16px;
    }
  }
}

.close-icon {
  cursor: pointer;
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;

  &--line {
    position: absolute;
    top: 50%;
    cursor: pointer;
    display: block;
    margin-bottom: 3px;
    background: white;
    border-radius: 2px;
    opacity: 1;
    height: 2px;
    width: 15px;
    transform: rotate(45deg);
    &.inverted {
      transform: rotate(-45deg);
    }
  }

  &.black .close-icon--line {
    background: black;
  }
}
</style>
