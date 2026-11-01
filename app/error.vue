<template>
  <div class="page-not-found">
    <div class="container is-flex">
      <div class="content-box">
        <h1>{{ error?.statusCode || 404 }}</h1>
        <p>{{ error?.statusMessage || 'Page not found' }}</p>
        <button class="button button--sent" @click="handleError">HOME</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { NuxtError } from '#app'

defineProps<{
  error?: NuxtError
}>()

useHead({
  title: 'Page Not Found'
})

const handleError = () => clearError({ redirect: '/' })
</script>

<style lang="scss" scoped>
.page-not-found {
  height: 100vh;
  height: calc(100vh - 180px);
  background-color: $yellow;
  background-repeat: no-repeat;
  background-size: 80%;
  background-position: center;

  @include media(sm) {
    background-size: 70%;
  }

  .container {
    height: 100%;
    align-items: center;
    justify-content: center;
    text-align: center;

    .content-box {
      h1 {
        font-size: 72px;
        color: white;
        margin-bottom: calc($gap / 2);
      }
      p {
        font-size: 20px;
        color: white;
        margin-bottom: $gap;
      }
    }
  }
}
</style>
