const Config = require('./assets/config')
const axios = require('axios')
const opn = require('opn')

module.exports = {
  mode: 'universal',
  /*
  ** Headers
  ** Common headers are already provided by @nuxtjs/pwa preset
  */
  head: {
    titleTemplate: titleChunk => {
      // If undefined or blank then we don't need the hyphen
      return titleChunk ? `${titleChunk} - Nuxt Headless` : 'Nuxt Headless'
    },
    htmlAttrs: {
      lang: 'en'
    },
    meta: [
      { charset: 'utf-8' },
      {
        hid: 'viewport',
        name: 'viewport',
        content: 'width=device-width, initial-scale=1'
      },
      { name: 'msapplication-TileColor', content: '#ffffff' },
      { name: 'msapplication-TileImage', content: '/ms-icon-144x144.png' },
      { name: 'theme-color', content: '#ffffff' },
      {
        hid: 'description',
        name: 'description',
        content: 'Nuxt Headless'
      },
      {
        hid: 'keywords',
        name: 'keywords',
        content: 'Nuxt, headless, CMS, Vue, Vue.js, Nuxt.js'
      },
      {
        hid: 'image',
        name: 'image',
        content: 'https://nuxt-headless.netlify.com/images/seo.png'
      },
      { hid: 'name', itemprop: 'name', content: 'Nuxt Headless' },
      {
        hid: 'description',
        itemprop: 'description',
        content: 'Nuxt Headless'
      },
      {
        hid: 'image',
        itemprop: 'image',
        content: 'https://nuxt-headless.netlify.com/images/seo.png'
      },
      {
        hid: 'twitter:card',
        name: 'twitter:card',
        content: 'summary_large_image'
      },
      {
        hid: 'twitter:title',
        name: 'twitter:title',
        content: 'Nuxt Headless'
      },
      {
        hid: 'twitter:description',
        name: 'twitter:description',
        content: 'Nuxt Headless'
      },
      { hid: 'twitter:site', name: 'twitter:site', content: '@moustacheDsign' },
      {
        hid: 'twitter:creator',
        name: 'twitter:creator',
        content: '@moustacheDsign'
      },
      {
        hid: 'twitter:image',
        name: 'twitter:image',
        content: 'https://nuxt-headless.netlify.com/images/seo.png'
      },
      {
        hid: 'twitter:image:alt',
        name: 'twitter:image:alt',
        content: 'My Website Image'
      },
      {
        hid: 'og:title',
        property: 'og:title',
        content: 'Nuxt Headless'
      },
      { hid: 'og:url', property: 'og:url', content: Config.url },
      {
        hid: 'og:site_name',
        property: 'og:site_name',
        content: 'Nuxt Headless Website'
      },
      {
        hid: 'og:description',
        property: 'og:description',
        content: 'Nuxt Headless'
      },
      { hid: 'og:locale', property: 'og:locale', content: 'en_GB' },
      { hid: 'og:type', property: 'og:type', content: 'website' },
      {
        hid: 'og:image',
        property: 'og:image',
        content: 'https://nuxt-headless.netlify.com/images/seo.png'
      },
      {
        hid: 'og:image:url',
        property: 'og:image:url',
        content: 'https://nuxt-headless.netlify.com/images/seo.png'
      },
      {
        hid: 'og:image:width',
        property: 'og:image:width',
        content: '1200'
      },
      {
        hid: 'og:image:height',
        property: 'og:image:height',
        content: '628'
      }
    ],
    script: [
      // IE 11 polyfill for Array.find
      {
        src: 'https://cdn.polyfill.io/v2/polyfill.js?features=es6'
      }
    ]
  },
  /*
  ** PWA Configuration
  */
  manifest: {
    name: 'Nuxt Headless',
    short_name: 'Nuxt-headless',
    theme_color: '#000000',
    background_color: '#f2d636',
    display: 'standalone',
    description: ''
  },
  /*
  ** Build configuration
  */
  build: {
    extractCSS: true,
    optimization: {
      splitChunks: {
        cacheGroups: {
          styles: {
            name: 'styles',
            test: /\.(css|vue)$/,
            chunks: 'all',
            enforce: true
          }
        }
      }
    },
    postcss: [require('postcss-responsive-type')()],
    analyze: {
      analyzerMode: 'static',
      openAnalyzer: true
    },
    extend (config, { isDev, isClient }) {
      if (isDev && isClient) {
        /*
        ** Run ESLint on save
        */
        config.module.rules.push({
          enforce: 'pre',
          test: /\.(js|vue)$/,
          loader: 'eslint-loader',
          exclude: /(node_modules)/,
          options: {
            // fix: true
          }
        })
      }
    }
  },
  hooks: {
    listen (server, { host, port }) {
      opn(`http://${host}:${port}`)
    }
  },
  generate: {
    fallback: '404.html',
    routes: function () {
      return axios.get(`${Config.wpDomain}${Config.api.projects}`).then(res => {
        const filtered = res.data.filter(project => {
          return project.acf.status === 'true'
        })
        return filtered.map(project => {
          return { route: '/' + project.slug, payload: project }
        })
      })
    }
  },
  render: {
    static: {
      maxAge: 2592000
    }
  },
  css: [
    // node.js module but we specify the pre-processor
    '@/assets/css/main.scss'
  ],
  /*
  ** Customize the progress-bar style
  */
  loading: {
    color: '#f4a261',
    height: '4px',
    failedColor: '#DF4661'
  },
  /*
  ** Modules
  */
  modules: [
    '@nuxtjs/pwa',
    '@nuxtjs/axios',
    '@nuxtjs/sitemap',
    'cookie-universal-nuxt',
    [
      '@nuxtjs/google-analytics',
      {
        id: 'UA-xxxxxxx-3'
      }
    ],
    ['nuxt-sass-resources-loader', '~/assets/css/variables.scss'] // load variables for all pages
  ],
  workbox: {
    runtimeCaching: [
      {
        // Should be a regex string. Compiles into new RegExp('https://my-cdn.com/.*')
        urlPattern: 'https://api.moustachedesign.xyz/.*',
        // Defaults to `networkFirst` if omitted
        handler: 'cacheFirst',
        // Defaults to `GET` if omitted
        method: 'GET'
      }
    ]
  },
  sitemap: {
    path: '/sitemap.xml',
    hostname: 'https://nuxt-headless.netlify.com',
    cacheTime: 1000 * 60 * 15,
    exclude: ['/.git']
  },
  axios: {
    timeout: 6000,
    debug: false,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  },
  plugins: [
    '~/plugins/axios.js',
    '~/plugins/store.js',
    { src: '~/plugins/vue-media.js', ssr: false },
    { src: '~/plugins/vue-intersect', ssr: false },
    { src: '~/plugins/nuxt-swiper.js', ssr: false },
    { src: '~/plugins/vuelidate.js', ssr: true },
    { src: '~/plugins/vue-localstorage.js', ssr: false },
    { src: '~/plugins/vue-progressive-image.js', ssr: false },
    { src: '~/plugins/vue-smooth-scroll.js', ssr: false }
  ]
}
