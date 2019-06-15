import Config from './assets/config'
import axios from 'axios'
import open from 'open'

export default {
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
      {
        defer: true,
        body: true,
        src:
          'https://polyfill.io/v2/polyfill.min.js?features=IntersectionObserver'
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
    // hardSource: process.env.NODE_ENV === 'development',
    optimization: {
      runtimeChunk: true,
      splitChunks: {
        chunks: 'async',
        minSize: 30000,
        maxSize: 0,
        cacheGroups: {
          vendors: {
            test: /[\\/]node_modules[\\/]/,
            priority: -10,
            enforce: true
          },
          default: {
            minChunks: 2,
            priority: -20,
            reuseExistingChunk: true
          },
          styles: {
            name: 'styles',
            test: /\.(css|vue)$/,
            chunks: 'all',
            enforce: true
          }
        }
      }
    },
    postcss: {
      plugins: {
        'postcss-responsive-type': {},
        'postcss-nested': {}
      }
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
      if (process.env.NODE_ENV !== 'production') {
        open(`http://${host}:${port}`)
      }
    }
  },
  http: {
    retry: 3
  },
  generate: {
    // return an array of strings of your dynamic pages
    fallback: '404.html',
    routes: function () {
      // returns an array of strings for each dynamic page found
      // return axios.get(`${Config.wpDomain}${Config.api.yourPostsListEndpoint}`).then(res => {
      //   return res.data.slug
      // })
      return []
    }
  },
  render: {
    static: {
      maxAge: 2592000
    }
  },
  css: [
    // main css file
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
    '@nuxt/http',
    '@nuxtjs/sitemap',
    'cookie-universal-nuxt',
    [
      '@nuxtjs/google-analytics',
      {
        id: 'UA-xxxxxxx-3'
      }
    ],
    '@nuxtjs/component-cache',
    '@nuxtjs/style-resources'
  ],
  styleResources: {
    // injects the variables in each component
    scss: '~/assets/css/variables.scss'
  },
  workbox: {
    runtimeCaching: [
      {
        urlPattern: 'https://api.wordpress.com/wp-content/uploads/.*',
        handler: 'staleWhileRevalidate',
        strategyOptions: {
          cacheName: 'images',
          cacheExpiration: {
            maxEntries: 30,
            maxAgeSeconds: 300
          },
          cacheableResponse: { statuses: [0, 200] }
        }
      }
    ]
  },
  sitemap: {
    path: '/sitemap.xml',
    hostname: 'https://nuxt-headless.netlify.com',
    cacheTime: 1000 * 60 * 15,
    exclude: ['/.git']
  },
  http: {
    retry: 3
  },
  plugins: [
    '~/plugins/store.js',
    '~/plugins/vuelidate.js',
    '~/plugins/lazysizes.client.js',
    '~/plugins/vue-media.client.js',
    '~/plugins/vue-localstorage.client.js',
    '~/plugins/vue-smooth-scroll.client.js',
    '~/plugins/splitting.client.js',
    '~/plugins/hotjar.client.js'
  ]
}
