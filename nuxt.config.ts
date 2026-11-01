export default defineNuxtConfig({
  compatibilityDate: '2025-01-01',

  future: {
    compatibilityVersion: 4
  },

  modules: [
    '@pinia/nuxt',
    '@vueuse/nuxt',
    '@nuxt/eslint'
  ],

  components: [
    {
      path: '~/components',
      pathPrefix: false
    }
  ],

  app: {
    head: {
      titleTemplate: '%s - Nuxt Headless',
      title: 'Nuxt Headless',
      htmlAttrs: {
        lang: 'en'
      },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'msapplication-TileColor', content: '#ffffff' },
        { name: 'theme-color', content: '#ffffff' },
        { name: 'description', content: 'Nuxt 4 Headless with WordPress REST API' },
        { name: 'keywords', content: 'Nuxt, Nuxt 4, headless, CMS, Vue, WordPress' },
        { property: 'og:title', content: 'Nuxt Headless' },
        { property: 'og:site_name', content: 'Nuxt Headless Website' },
        { property: 'og:description', content: 'Nuxt 4 Headless with WordPress REST API' },
        { property: 'og:locale', content: 'en_GB' },
        { property: 'og:type', content: 'website' }
      ],
      link: [
        { rel: 'icon', type: 'image/png', href: '/icon.png' }
      ]
    }
  },

  css: [
    '~/assets/css/main.scss'
  ],

  vite: {
    css: {
      preprocessorOptions: {
        scss: {
          additionalData: '@use "~/assets/css/variables.scss" as *;\n',
          silenceDeprecations: [
            'color-functions',
            'if-function',
            'import',
            'global-builtin',
            'legacy-js-api'
          ]
        }
      }
    }
  },

  runtimeConfig: {
    public: {
      wpDomain: process.env.NUXT_PUBLIC_WP_DOMAIN || 'https://api.moustachedesign.fyi/wp-json',
      clientUrl: process.env.NUXT_PUBLIC_CLIENT_URL || 'https://api.moustachedesign.fyi',
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'https://nuxt-headless.netlify.com'
    }
  }
})
