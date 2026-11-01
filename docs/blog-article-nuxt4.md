# Creating a Modern Headless Website with Nuxt 4 and WordPress REST API

*A modernized guide to marrying the familiar WordPress CMS content editing experience with the raw speed, developer experience, and SEO architecture of Nuxt 4, Vue 3.5, and Vite.*

---

## Introduction

A few years ago, I wrote about coupling WordPress as a headless CMS with Nuxt.js. Back then, the ecosystem was Nuxt 2, Vue 2 (Options API), Webpack 4, Vuex, Axios, and WordPress REST API v2 plugins.

Since then, the web ecosystem has undergone massive leaps forward:
- **Nuxt 4** introduces a dedicated `app/` directory convention, Nitro server engine, fine-grained payload extraction, and instant Vite HMR.
- **Vue 3.5+** standardizes on `<script setup lang="ts">`, Composition API, and built-in reactivity primitives.
- **Pinia** has officially replaced Vuex for typed, modular state management.
- **WordPress** natively supports REST API toggles for custom fields in modern ACF (Advanced Custom Fields), reducing reliance on legacy glue plugins.

If you are building a website today where your clients demand the comfort of the WordPress admin panel, but you want modern frontend architecture (zero-bloat HTML, Lighthouse 100s, instant page transitions, and effortless SSG or Edge SSR deployment), **Nuxt 4 + WordPress REST API** remains one of the most practical and productive stacks available.

In this guide, we'll walk through how the modern architecture works using the updated open-source boilerplate: [`bovas85/nuxt-headless`](https://github.com/bovas85/nuxt-headless).

---

## Why Headless WordPress with Nuxt 4?

1. **Client Comfort**: Non-technical clients and marketing teams already know how to publish posts, upload images, and edit pages in WordPress. You do not need to train them on a proprietary headless CMS or headless SaaS dashboard with per-seat pricing.
2. **Speed & Security**: The public-facing frontend is an optimized Nuxt 4 application. Visitors never touch your PHP server directly. WordPress can even be hidden behind a VPN, basic auth, or private subnet.
3. **SEO & Core Web Vitals**: With Nuxt 4's server engine (Nitro) and `useAsyncData`, pages are pre-rendered with complete semantic HTML, open graph tags, and zero layout shift.
4. **Developer Happiness**: Vite builds in milliseconds, TypeScript provides strict type checking, and Pinia gives you typed stores without string mutation boilerplate.

---

## The Stack at a Glance

| Layer | Legacy (Nuxt 2) | Modern (Nuxt 4) |
|---|---|---|
| **Framework** | Nuxt 2.14 / 2.15 | Nuxt 4.6+ (`future: { compatibilityVersion: 4 }`) |
| **Component Model** | Vue 2 Options API (`export default {}`) | Vue 3.5+ `<script setup lang="ts">` |
| **Bundler** | Webpack 4 | Vite 6 |
| **State Management** | Vuex 3 (`store/index.js` mutations) | Pinia (`@pinia/nuxt`) |
| **Data Fetching** | `@nuxtjs/axios` + `asyncData` / `nuxtServerInit` | Native `$fetch` + `useAsyncData()` composable |
| **Styling** | node-sass / legacy libsass | Dart Sass with `@use` & modern CSS `clamp()` |
| **Headless CMS** | WordPress 5.x + 6 separate plugins | WordPress 6.x + Docker Compose + ACF 6.x |

---

## Step 1: WordPress Backend Setup

To make WordPress serve clean JSON payloads to Nuxt, you need a minimal, headless-oriented installation.

### 1. Run WordPress with Docker Compose
Rather than manually configuring LAMP/LEMP stacks, you can spin up WordPress and MySQL in one command:

```bash
cd wordpress
docker compose up -d
```
Your WordPress admin will be available at `http://localhost:8080/wp-admin`.

### 2. Configure Permalinks
By default, WordPress uses query strings (`/?p=123`). This will break REST API routing.
- Go to **Settings > Permalinks**.
- Choose **Post name** (`/%postname%/`).
- Click **Save Changes**.

### 3. Essential Plugins
In modern WordPress, the plugin footprint is much smaller:
- **Advanced Custom Fields (ACF)** (*Required*): Used to define structured content models. In ACF 5.11+ / 6+, open your Field Group and enable the native **"Show in REST API"** toggle.
- **WP REST API Menus** (*Recommended*): Exposes `/wp-json/menus/v1/menus` so Nuxt can fetch navigation dynamically.
- **Contact Form 7** (*Optional*): Exposes `/wp-json/contact-form-7/v1/contact-forms/:id/feedback` for form handling.
- **WP REST Cache** (*Recommended*): Caches JSON REST responses to make your build and SSR fetches blazing fast.

### 4. Custom Theme & Plugin
In the boilerplate repository, the `wordpress/` directory provides:
- **`themes/moustache`**: Registers responsive image thumbnail sizes (`small` 600px, `medium` 1024px, `large` 1920px, `ultra` 2048px, `4k` 4096px) matching Nuxt's `<ResponsiveImage />` and `<AppImage />` components. It also adds a cache-skip filter for Contact Form 7 submissions.
- **`plugins/moustache`**: Registers a custom post type (`casestudies`) with REST support, meta query whitelist, and a lightweight standalone contact endpoint (`/wp-json/forms/contact`).

---

## Step 2: Nuxt 4 Architecture

In Nuxt 4, application code lives cleanly in the `app/` folder:

```text
├── app/
│   ├── assets/          # Global SCSS, variables, mixins
│   ├── components/      # Auto-imported Vue components
│   ├── composables/     # Shared business logic (useWpApi.ts)
│   ├── layouts/         # Default and custom page layouts
│   ├── pages/           # File-based routing (index.vue, etc.)
│   ├── stores/          # Pinia stores (app.ts)
│   ├── app.vue          # Root component
│   └── error.vue        # Error boundaries
├── nuxt.config.ts       # Nuxt 4 configuration
└── package.json
```

### 1. `nuxt.config.ts`
Here is how we configure Nuxt 4 with Pinia, modern SCSS preprocessors, and environment variables:

```typescript
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
      pathPrefix: false // Allows <HeroSection /> instead of <SectionsHomeHeroSection />
    }
  ],

  runtimeConfig: {
    public: {
      wpDomain: process.env.NUXT_PUBLIC_WP_DOMAIN || 'https://api.moustachedesign.fyi/wp-json',
      clientUrl: process.env.NUXT_PUBLIC_CLIENT_URL || 'https://api.moustachedesign.fyi',
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'https://nuxt-headless.netlify.com'
    }
  }
})
```

---

## Step 3: Fetching Data with Modern Composables

In Nuxt 2, we relied on Vuex mutations or Axios inside `asyncData({ $axios })`. In Nuxt 4, we use native `$fetch` combined with a typed composable.

### `app/composables/useWpApi.ts`
```typescript
export const useWpApi = () => {
  const config = useRuntimeConfig()
  const wpDomain = config.public.wpDomain as string

  const fetchHomePage = async (pageId = '2') => {
    const url = `${wpDomain}/wp/v2/pages/${pageId}`
    console.info(`[useWpApi] Fetching homepage from ${url} (server: ${import.meta.server})`)
    try {
      return await $fetch<Record<string, any>>(url)
    } catch (error) {
      console.warn(`[useWpApi] Fetch failed, returning graceful fallback:`, error)
      return {
        id: 2,
        title: { rendered: 'Nuxt 4 Headless' },
        acf: {
          hero: {
            title: 'Nuxt 4 Headless',
            description: 'Modern boilerplate with WordPress REST API.',
            desktop_bg: { url: '/icon.png' },
            mobile_bg: { url: '/icon.png' }
          }
        }
      }
    }
  }

  return { fetchHomePage }
}
```

Notice the **graceful fallback**: if a developer clones the repository before setting up their WordPress container, Nuxt does not throw a white screen of death—it renders a clean mock layout so frontend work can continue unimpeded.

---

## Step 4: Page Rendering & SSR Hydration

In our page component (`app/pages/index.vue`), we consume this composable with Nuxt's `useAsyncData()`:

```vue
<template>
  <main v-if="acf" class="home">
    <HeroSection :acf="acf" :animate-header="animateHeader" />
  </main>
</template>

<script setup lang="ts">
const appStore = useAppStore()
const { fetchHomePage } = useWpApi()

// useAsyncData runs during server-side rendering (SSR)
// The payload is cached into __NUXT_DATA__ so client hydration performs 0 extra network calls!
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
```

### Why `useAsyncData` is Superior to Legacy `asyncData`:
1. **Server Fetch**: Nuxt executes the request on your Node.js/Nitro server.
2. **Payload Serialization**: The JSON result is serialized into a `<script id="__NUXT_DATA__">` payload in the rendered HTML.
3. **Instant Hydration**: When the browser loads the page, it hydrates the Vue state from the embedded payload without issuing a redundant HTTP request to WordPress.

---

## Step 5: Responsive Images with Modern Web Standards

In the past, developers often relied on JavaScript-heavy image lazy-loaders (`vue-lazyload`, `lazysizes`). 

Today, browsers have native support for `<picture>` elements and `loading="lazy"`. In the boilerplate's `app/components/UI/ResponsiveImage.vue`, we leverage the custom thumbnail sizes registered by the WordPress theme:

```vue
<template>
  <picture>
    <!-- WebP or high-res desktop -->
    <source
      media="(min-width: 1024px)"
      :srcset="image.sizes?.large || image.url"
    />
    <!-- Tablet / Mobile -->
    <source
      media="(min-width: 600px)"
      :srcset="image.sizes?.medium || image.url"
    />
    <img
      :src="image.sizes?.small || image.url"
      :alt="image.alt || 'Featured Image'"
      loading="lazy"
      decoding="async"
    />
  </picture>
</template>
```

This ensures mobile phones download lightweight 600px images while 4K displays receive crisp high-resolution assets, without layout shifts or third-party JS overhead.

---

## Step 6: Deployment & Build Strategies

Depending on your requirements, Nuxt 4 supports two primary deployment models:

### 1. Static Site Generation (SSG / Jamstack)
If your content doesn't change every minute, generate a purely static website:
```bash
yarn generate
```
Nuxt crawls your routes, calls the WordPress REST API at build time, and outputs static HTML/CSS/JS into `.output/public`. You can host this for free on Netlify, Cloudflare Pages, Vercel, or AWS S3. 
*(Bonus: Trigger automatic Netlify build webhooks whenever a post is published in WordPress!)*

### 2. Edge / Node.js Server-Side Rendering (SSR)
If you need dynamic previews or real-time page updates:
```bash
yarn build
node .output/server/index.mjs
```
The lightweight Nitro server handles requests dynamically with blazing-fast response times.

---

## Conclusion & Next Steps

Decoupling WordPress from the presentation layer gives you the best of both worlds: a proven, comfortable CMS for content teams, and an ultra-fast, modern developer experience with Nuxt 4.

Check out the complete source code and starter kit on GitHub:
👉 **[bovas85/nuxt-headless](https://github.com/bovas85/nuxt-headless)**

If you found this helpful, feel free to star the repo and join the discussion!
