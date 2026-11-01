# Moustache Design WordPress REST API Documentation

This plugin and theme bundle extends the WordPress REST API to serve the Nuxt headless frontend.

---

## 1. Custom Post Types

### Case Studies
- **Post Type**: `casestudies`
- **REST Base**: `/wp-json/wp/v2/casestudies`
- **Method**: `GET`
- **Parameters**: Supports standard WP REST API query arguments (`per_page`, `page`, `slug`, `search`, etc.).
- **Example**:
  ```bash
  curl https://api.yourdomain.fyi/wp-json/wp/v2/casestudies
  ```

---

## 2. Forms Endpoints

### Custom Contact Form
- **Route**: `/wp-json/forms/contact`
- **Method**: `POST`
- **Content-Type**: `application/json` or `multipart/form-data`
- **Payload Parameters**:
  - `your-name` (string, required)
  - `your-email` (string, required)
  - `your-message` (string, required)
- **Response**:
  ```json
  "Form received (CONTACT)"
  ```

---

## 3. Query Variable Extensions

- **`meta_query`**: Whitelisted in `rest_query_vars`, allowing queries to filter posts by custom meta keys through the REST API.

---

## 4. Custom Responsive Image Sizes

The `themes/moustache` theme automatically generates the following image dimensions on upload:

| Size Name | Dimensions (W x H) | Crop | Used in Nuxt Component |
|---|---|---|---|
| `small` | 600 x 600 | No | `<ResponsiveImage>` (mobile) |
| `medium` | 1024 x 1024 | No | `<ResponsiveImage>` (tablet) |
| `large` | 1920 x 1920 | No | `<ResponsiveImage>` (desktop) |
| `ultra` | 2048 x 2048 | No | `<ResponsiveImage>` (wide) |
| `4k` | 4096 x 4096 | No | `<ResponsiveImage>` (retina/4k) |

These sizes are exposed under `media_details.sizes` in REST responses.
