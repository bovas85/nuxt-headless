# WordPress Headless Setup & Onboarding Guide

> Step-by-step guide to configure WordPress as a headless CMS for the Nuxt 4 frontend, based on the architecture described in [Creating a Website with Nuxt.js and WordPress REST API](https://medium.com/@bike.challenge/creating-a-website-with-nuxt-js-and-wordpress-rest-api-cf37c6f4bc2b).

---

## 1. Quickstart with Docker (Recommended)

A pre-configured Docker Compose environment is provided to run WordPress and MySQL locally:

```bash
cd wordpress
docker compose up -d
```

- **WordPress Admin**: `http://localhost:8080/wp-admin`
- **Database Port**: `3306`

---

## 2. Required WordPress Plugins

To enable the REST endpoints and custom data structures expected by the Nuxt frontend, install and activate the following plugins:

| Plugin | Required / Optional | Purpose |
|---|---|---|
| **Advanced Custom Fields (ACF)** (Free or Pro) | **Required** | Defines structured fields (Hero, banner, metadata). In ACF v5.11+, ensure **"Show in REST API"** is enabled on each field group. |
| **ACF to REST API** | *Legacy / Optional* | Only needed if using older ACF versions (<5.11) that do not have native REST API toggles. |
| **WP REST API Menus** (`wp-rest-api-v2-menus`) | **Recommended** | Exposes `/wp-json/menus/v1/menus` so Nuxt can fetch navigation dynamically instead of hardcoding links. |
| **Contact Form 7** | **Optional** | Provides `/wp-json/contact-form-7/v1/contact-forms/:id/feedback` for contact form submissions. |
| **WP REST Cache** | **Recommended** | Caches JSON REST API responses to dramatically speed up SSR requests and static builds (`yarn generate`). |
| **Safe SVG** | **Recommended** | Allows uploading `.svg` vector logos and icons to the WordPress media library. |

---

## 3. Activate Bundled Theme & Custom Plugin

The `wordpress/` directory includes a custom theme and plugin designed specifically for headless usage:

### A. Theme: `themes/moustache`
- **Path**: `wp-content/themes/moustache`
- **Role**:
  - Registers responsive image thumbnail sizes (`small` 600px, `medium` 1024px, `large` 1920px, `ultra` 2048px, `4k` 4096px) matching the Nuxt `<ResponsiveImage />` and `<AppImage />` components.
  - Adds the `rest_cache_skip` filter for Contact Form 7 so caching does not intercept form submissions.
- **Activation**: Go to **Appearance > Themes** and activate **Moustache Design**.

### B. Plugin: `plugins/moustache`
- **Path**: `wp-content/plugins/moustache`
- **Role**:
  - Registers the custom post type `casestudies` with `'show_in_rest' => true` (`/wp-json/wp/v2/casestudies`).
  - Whitelists `meta_query` in the REST API.
  - Exposes a lightweight custom contact endpoint at `/wp-json/forms/contact` as an alternative to Contact Form 7.
  - Enforces client upload size limits and strips unnecessary admin panels for non-superadmins.
- **Activation**: Go to **Plugins** and activate **Moustache Design**.

---

## 4. WordPress Configuration Checklist

### 1. Permalinks (Crucial)
1. In WordPress Admin, navigate to **Settings > Permalinks**.
2. Select **Post name** (`/%postname%/`).
3. Click **Save Changes**.
*(Default query-string permalinks like `/?p=123` will break WordPress REST API routing).*

### 2. CORS (Cross-Origin Resource Sharing)
If Nuxt and WordPress run on different ports/domains (e.g. Nuxt on `localhost:3000` and WP on `localhost:8080` or `api.yourdomain.com`), configure CORS headers so browser fetch requests succeed.

Add the following to your `wp-config.php` (or in `themes/moustache/functions.php`):

```php
add_action( 'init', function() {
    $origin = get_http_origin();
    $allowed_origins = array(
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'https://yourdomain.fyi'
    );

    if ( in_array( $origin, $allowed_origins ) ) {
        header( "Access-Control-Allow-Origin: " . esc_url_raw( $origin ) );
        header( "Access-Control-Allow-Methods: GET, POST, OPTIONS" );
        header( "Access-Control-Allow-Credentials: true" );
        header( "Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Wpnonce" );
    }

    if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
        status_header( 200 );
        exit();
    }
} );
```

---

## 5. Setting Up the Homepage in WordPress

The Nuxt homepage calls `/wp-json/wp/v2/pages/2` by default.

1. Create or edit the page with ID `2` (or set a custom ID in `useWpApi(pageId)`).
2. Create an **ACF Field Group** titled `Hero` assigned to that Page:
   - **`hero`** (Group):
     - **`title`** (Text)
     - **`description`** (Text / Textarea)
     - **`desktop_bg`** (Image – Return format: Array / Object)
     - **`mobile_bg`** (Image – Return format: Array / Object)
3. Ensure **"Show in REST API"** is toggled ON in the ACF Field Group settings.
4. Publish the page.

---

## 6. Connecting Nuxt to WordPress

In the project root, create a `.env` file (or set environment variables):

```bash
NUXT_PUBLIC_WP_DOMAIN=http://localhost:8080/wp-json
NUXT_PUBLIC_CLIENT_URL=http://localhost:8080
NUXT_PUBLIC_SITE_URL=http://localhost:3000
```

When you start Nuxt with `yarn dev`, the server will fetch live data from your WordPress installation. If WordPress is offline or unreachable, Nuxt falls back to mock placeholder data automatically.
