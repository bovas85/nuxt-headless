<template>
  <div v-show="!hide" class="cookies" :class="{ active: show }">
    <div class="container">
      <p>
        This site uses cookies.
        <NuxtLink to="/privacy-policy">Find out more.</NuxtLink>
        <button
          class="privacy-link"
          @click="acceptCookie"
        >
          OK, Close
        </button>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
const cookieConsent = useCookie<boolean | null>('cookie_consent', {
  default: () => null,
  maxAge: 60 * 60 * 24 * 365
})

const hide = ref(true)
const show = ref(false)

onMounted(() => {
  if (cookieConsent.value !== null) {
    hide.value = true
    show.value = false
  } else {
    setTimeout(() => {
      hide.value = false
      show.value = true
    }, 4000)
  }
})

const acceptCookie = () => {
  cookieConsent.value = true
  hide.value = true
  show.value = false
}
</script>

<style lang="scss" scoped>
.cookies {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  z-index: 9999;
  height: auto;
  color: white;
  background: black;
  opacity: 0;
  transition: opacity 0.6s ease-in-out;

  @include media(md) {
    height: 60px;
  }

  &.active {
    opacity: 1;
  }

  a {
    color: white;
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }

  .privacy-link {
    border: none;
    width: auto;
    padding: 10px 20px;
    color: black;
    background: $red;
    display: inline-flex;
    align-items: center;
    margin-top: calc($gap / 2);
    cursor: pointer;
    transition: all 0.3s ease-in-out;

    @include media(md) {
      margin-top: 0;
      margin-left: $gap;
    }

    &:hover {
      font-weight: bold;
      opacity: 0.9;
    }
  }

  p {
    font-size: 16px;
    font-weight: 400;
    letter-spacing: 1.1px;
    max-width: 95%;
    text-align: left;
    color: white;
    margin-right: 16px;

    @include media(md) {
      margin: 0;
    }
  }

  .container {
    display: flex;
    height: 100%;
    justify-content: center;
    align-items: center;
  }
}
</style>
