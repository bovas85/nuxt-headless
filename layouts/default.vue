<template>
  <div class="app">
    <cookie-box />
    <the-nav />
    <nuxt/>
    <the-footer />
  </div>
</template>

<script>
  import TheNav from '@/components/Nav/TheNav'
  import TheFooter from '@/components/Footers/TheFooter'

  export default {
    components: {
      TheNav,
      TheFooter,
      CookieBox: () => import('@/components/UI/CookieBox')
    }
  }
</script>

<style>
  body {
    overflow: initial;
  }
</style>
