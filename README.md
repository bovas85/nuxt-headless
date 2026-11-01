# Nuxt 4 Headless (WordPress REST API)

> Modern, blazing fast Nuxt 4 + WordPress REST API starter boilerplate.
> Based on the architecture and guide: [Creating a Website with Nuxt.js and WordPress REST API](https://medium.com/@bike.challenge/creating-a-website-with-nuxt-js-and-wordpress-rest-api-cf37c6f4bc2b) by Alessandro Giordo.

---

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
- Yarn 1.x (or pnpm / npm)

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
3. Set permalinks to **Post name** in **Settings > Permalinks** (`/%postname%/`).

> 📖 **Full WordPress Onboarding Guide**: See [`wordpress/README.md`](wordpress/README.md) for required plugins, ACF field setup, CORS configuration, and REST API details.

### 4. Development Server
```bash
yarn dev
```
Open `http://localhost:3000` to view the application with instant HMR.

---

## WordPress Plugins & Backend Architecture

To power the headless frontend, WordPress requires the following plugins and settings:

- **Advanced Custom Fields (ACF)** (*Required*): Powers dynamic page layouts and Hero sections. Ensure **"Show in REST API"** is enabled on field groups.
- **WP REST API Menus** (*Recommended*): Exposes `/wp-json/menus/v1/menus` for dynamic navigation menus.
- **Contact Form 7** (*Optional*): Handles contact form submissions via `/wp-json/contact-form-7/v1/contact-forms/:id/feedback`. (The bundled `plugins/moustache` also provides an endpoint at `/wp-json/forms/contact`).
- **WP REST Cache** (*Recommended*): Caches REST JSON responses for ultra-fast SSR responses and static site generation (`yarn generate`).
- **Safe SVG** (*Recommended*): Allows uploading SVG icons and logos to WordPress media.

For detailed setup instructions, field group schemas, and CORS config, read the [WordPress Headless Setup Guide](wordpress/README.md).

---

## Configuration

Configure your WordPress endpoints via environment variables or `nuxt.config.ts`:

```bash
# .env (optional)
NUXT_PUBLIC_WP_DOMAIN=https://api.yourdomain.com/wp-json
NUXT_PUBLIC_CLIENT_URL=https://api.yourdomain.com
NUXT_PUBLIC_SITE_URL=https://yourdomain.com
```

*Note: If WordPress is unreachable or during initial setup, Nuxt automatically falls back to built-in mock data so frontend development is never blocked.*

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
