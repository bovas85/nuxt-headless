# Nuxt 4 Headless (WordPress REST API)

> Modern, blazing fast Nuxt 4 + WordPress REST API starter boilerplate.

## Features

- ⚡ **Nuxt 4** & **Vue 3.5+** with Composition API (`<script setup lang="ts">`)
- 🚀 **Vite 6** for instant Hot Module Replacement and builds
- 🍍 **Pinia** for scalable, typed, SSR-friendly state management
- 🛠️ **TypeScript & Vue-TSC** for full type-safety
- 🎨 **Bulma + SCSS** with fluid responsive typography (`clamp()`)
- 📱 **Swiper 11** for modern touch-friendly carousels
- 🖼️ **Native Responsive Images** with `<picture>` and native lazy loading
- 🔍 **Nuxt SEO** ready with `@nuxtjs/sitemap` and `useSeoMeta()`
- ✅ **@nuxt/eslint** flat configuration for clean code formatting
- 🐳 **WordPress Docker Compose** starter kit with custom theme and plugin

---

## Getting Started

### 1. Requirements
- Node.js `^22.0.0` or `^24.0.0` (LTS recommended)
- Yarn 1.x or modern package manager

### 2. Install Dependencies
```bash
yarn install
```

### 3. Local WordPress Backend (Docker)
A lightweight headless WordPress starter environment is provided in the `wordpress/` directory:

```bash
cd wordpress
docker compose up -d
```
Visit `http://localhost:8080` to complete WordPress setup. In WordPress Admin:
1. Activate the **Moustache Design** theme (`themes/moustache`).
2. Activate the **Moustache Design** plugin (`plugins/moustache`).
3. Set permalinks to **Post name** in **Settings > Permalinks**.

### 4. Development Server
```bash
yarn dev
```
Open `http://localhost:3000` to view the application with instant HMR.

---

## Configuration

Configure your WordPress endpoints via environment variables or `nuxt.config.ts`:

```bash
# .env (optional)
NUXT_PUBLIC_WP_DOMAIN=https://api.yourdomain.com/wp-json
NUXT_PUBLIC_CLIENT_URL=https://api.yourdomain.com
NUXT_PUBLIC_SITE_URL=https://yourdomain.com
```

---

## Build & Deployment

```bash
# Typecheck
yarn typecheck

# Lint
yarn lint

# Build for SSR Node.js server
yarn build
yarn preview

# Static Site Generation (SSG for Netlify, Vercel, Cloudflare Pages, S3)
yarn generate
```

---

## License

[MIT](LICENSE)
