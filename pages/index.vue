<template>
  <main class="home" v-if='acf'>
    <hero-section
      :acf="acf"
      :animateHeader="animateHeader"
      :bgImage="bgImage"
    />
  </main>
</template>

<script>
  import debounce from 'lodash/debounce'
  import HeroSection from '@/components/Sections/Home/HeroSection'
  import Config from '~/assets/config'
  import get from 'lodash/get'
  let scroller, steps

  export default {
    scrollToTop: true,
    async fetch ({ app, store }) {
      const home = await app.$http.$get(Config.wpDomain + Config.api.homePage)
      store.commit('setHomepage', home)
    },
    data () {
      return {
        animateHeader: false
      }
    },
    components: {
      HeroSection
    },
    head () {
      return { title: 'Home' }
    },
    async mounted () {
      if (process.client) {
        const home = await this.$http.$get(Config.wpDomain + Config.api.homePage)
        this.$store.commit('setHomepage', home)
        setTimeout(() => {
          this.animateHeader = true
          this.handleScroll()
        }, 150)
        if (this.$route.hash) {
          if (process.browser) {
            window && window.scrollTo(0, 0)
          }
        }
      }
    },
    methods: {
      hideMenu () {
        this.$store.commit('hideMenuBg')
      },
      showMenu () {
        this.$store.commit('showMenuBg')
      },
      handleStepEnter (response) {
        switch (response.index) {
          case 0:
            this.hideMenu()
            this.animateHeader = true
            break
          default:
            break
        }
      },
      handleScroll () {
        const step = document.querySelector('.step')
        if (step) {

          if (window.innerWidth > 577) {
            scroller = this.scrollama()
            steps = null
            steps = scroller
              .setup({
                step,
                offset: 0.6,
                debug: false
              })
              .onStepEnter(this.handleStepEnter)
              .onStepExit(this.showMenu)
  
            steps.resize()
            steps.enable()
          } else {
            scroller = this.scrollama()
            steps = null
            steps = scroller
              .setup({
                step,
                offset: 0.9,
                debug: false
              })
              .onStepEnter(this.handleStepEnter)
              .onStepExit(this.showMenu)
  
            steps.resize()
            steps.enable()
          }
        } else {
          setTimeout(() => this.handleScroll(), 600)
        }

        window.addEventListener(
          'resize',
          this.scrollamaResize,
          { passive: true },
          false
        )
      },
      scrollamaResize: debounce(function () {
        let step = document.querySelector('.step')
        if (step && step.length) {
          this.handleScroll()
        }
      }, 150)
    },
    beforeDestroy () {
      if (typeof scroller !== 'undefined') {
        scroller.disable && scroller.disable()
      }
      scroller = null
      steps = null
      window.removeEventListener('resize', this.scrollamaResize, false)
    },
    computed: {
      homePage () {
        if (this.$store.state.homePage == null) return false
        return this.$store.state.homePage
      },
      acf () {
        if (this.$store.state.homePage == null) return false
        return this.$store.state.homePage.acf
      },
      testimonials () {
        return get(this.acf, 'testimonials.testimonials')
      },
      bgImage () {
        if (process.browser) {
          if (this.$store.state.window < 577) {
            return get(
              this.homePage,
              'acf.hero.mobile_bg.sizes.large',
              'https://placehold.it/2048/2048'
            )
          } else if (
            this.$store.state.window > 576 &&
            this.$store.state.window < 1440
          ) {
            return get(
              this.homePage,
              'acf.hero.desktop_bg.sizes.large',
              'https://placehold.it/2048/2048'
            )
          } else
            return get(
              this.homePage,
              'acf.hero.desktop_bg.sizes.ultra',
              'https://placehold.it/2048/2048'
            )
        }
        return get(
          this.homePage,
          'acf.hero.desktop_bg.sizes.large',
          'https://placehold.it/2048/2048'
        )
      }
    }
  }
</script>

<style lang="scss" scoped>
  h1 {
    @include media(sm) {
      max-width: 480px;
      padding-left: 0;
    }
  }

  section {
    margin: $gap / 1.5 0;
    width: 100%;

    @include media(md) {
      margin: $gap * 3 auto;
    }
  }
</style>
