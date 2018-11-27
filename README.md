# Nuxt Headless

> Nuxt + WordPress REST API boilerplate

## Installation Steps

1. Copy the contents of the /wordpress folder in your wordpress installation (make sure to edit wp-config.php)

2. Make sure you activate all the plugins and set Moustache Design as your active theme in wordpress (you can rename it if you want)

3. Make sure assets/config.js reflects your configuration and endpoints for the WP rest api and wordpress url

4. You can edit all the files and config freely. This is MIT licensed, but credit is welcome.


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

You can ofcourse use this as middleware following Nuxt Docs below.

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

