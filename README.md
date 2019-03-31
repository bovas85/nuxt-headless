# Nuxt Headless

[![Greenkeeper badge](https://badges.greenkeeper.io/bovas85/nuxt-headless.svg)](https://greenkeeper.io/)

> Nuxt + WordPress REST API boilerplate

## [Live demo link](https://nuxt-headless.netlify.com/)

# Changes
> **[ 1.1.0 ]**

- 21 - March - 2019
  - Nuxt updated to v2.5
  - Added postcss-nested
  - added more docs and comments

# Use in production? 

Please note this is a boilerplate, it contains some defaults you might want to pay attention to:
- CORS - You should set this to work only on your website url when in production or staging, work locally on your machine for dev. 
- Plugins and Theme, most of these can be removed, but you'll need ACF (free version is OK) to make it work. 

## Installation Steps

1. Copy the contents of the /wordpress folder in your wordpress installation (make sure to edit wp-config.php)

2. Make sure you activate all the plugins and set Moustache Design as your active theme in wordpress (you can rename it if you want)

3. Set permalinks in wordpress to anything but the default (I suggest `/%postname%/`)

4. Make sure you set your CORS correctly to point to your website when in production/staging

5. Make sure assets/config.js reflects your configuration and endpoints for the WP rest api and wordpress url

6. You can edit all the files and config freely. This is MIT licensed, but credit is welcome.

Check how I used this in my blog at https://medium.com/@moustachedesign/creating-a-website-with-nuxt-js-and-wordpress-rest-api-51cf66599cf3


## Vuex config

The current config allows to either initialise the api calls in `nuxtServerInit`, or commit `fetch` mutations from each page.
You can otherwise skip Vuex and load the data for each page using `asyncData`.


## Build Setup

```bash
# install dependencies
$ yarn # Or npm install

# serve with hot reload at localhost:3000
# service worker is disabled in dev
$ yarn dev # or npm run dev

# build for production and launch server
$ yarn build # or npm run build
$ yarn start # or npm start

# generate static project
$ yarn generate # or npm run generate
```

## Hosting

I suggest now for SSR websites using nodeJS, or Netlify for static sites (free hosting) using `yarn generate`

You can of course use this as middleware following Nuxt Docs below.

## Nuxt Docs

For detailed explanation on how things work with Nuxt, checkout the [Nuxt.js docs](https://github.com/nuxt/nuxt.js).

## How to contribute
All PRs are very welcome and much needed.
Steps to contribute:
- Fork development branch
- Make your changes
- Make a PR
- PR is either approved or sent back
- If PR is approved I'll include it in the next release

