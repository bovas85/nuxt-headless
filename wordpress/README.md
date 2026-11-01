# WordPress Headless Starter Kit

This directory provides the minimal custom theme and plugin required to serve WordPress data to the Nuxt headless frontend.

## Contents
- **`themes/moustache`**: Lightweight theme with required image thumbnail sizes and REST API features.
- **`plugins/moustache`**: Registers custom post types (`casestudies`), taxonomies, and custom REST API endpoints.
- **`docker-compose.yml`**: A 1-command Docker Compose setup to run WordPress locally.

## Quickstart (Local Docker)

To run a fresh WordPress instance locally with the custom theme and plugin mounted:

```bash
cd wordpress
docker compose up -d
```

1. Open `http://localhost:8080` to complete the 1-minute WordPress installation.
2. In WordPress Admin:
   - Go to **Appearance > Themes** and activate **Moustache Design**.
   - Go to **Plugins** and activate the **Moustache Design** plugin.
   - Go to **Settings > Permalinks** and choose **Post name** (e.g. `/%postname%/`).
3. Install recommended plugins:
   - **Advanced Custom Fields (ACF)**
   - **ACF to REST API**
   - **Contact Form 7**
   - **WP REST API Menus**
   - **Safe SVG**
