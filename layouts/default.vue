<template>
  <div class="app">
    <cookie-box/>
    <the-nav/>
    <nuxt/>
    <the-footer/>
  </div>
</template>

<script>
  import TheNav from "@/components/Nav/TheNav";

  export default {
    components: {
      TheNav,
      TheFooter: () => import("@/components/Footers/TheFooter"),
      CookieBox: () => import("@/components/UI/CookieBox")
    }
  };
</script>

<style>
  body {
    overflow: initial;
  }
</style>
