<template>
  <main v-if="acf" class="home">
    <HeroSection :acf="acf" :animate-header="animateHeader" />
  </main>
</template>

<script setup lang="ts">
const appStore = useAppStore()
const { fetchHomePage } = useWpApi()

const { data: homePage } = await useAsyncData('homePage', async () => {
  if (appStore.homePage) return appStore.homePage
  const data = await fetchHomePage()
  appStore.setHomepage(data)
  return data
})

const acf = computed(() => homePage.value?.acf)
const animateHeader = ref(false)

useHead({
  title: 'Home'
})

onMounted(() => {
  animateHeader.value = true
})
</script>

<style lang="scss" scoped>
h1 {
  @include media(sm) {
    max-width: 480px;
    padding-left: 0;
  }
}

section {
  margin: calc($gap / 1.5) 0;
  width: 100%;

  @include media(md) {
    margin: $gap * 3 auto;
  }
}
</style>
