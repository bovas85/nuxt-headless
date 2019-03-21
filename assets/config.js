'use strict'

export default {
// these are the rest api endpoints and your wordpress url 
  appTitleShort: 'Nuxt Headless',
  appTitle: 'Nuxt Headless',
  appTitleShort: 'Nuxt-headless',
  appDescription: 'Nuxt Headless with Wordpress REST API',
  appThemeColor: '#ffffff ',
  appBgColor: '#00172c ',
  appIcon: 'assets/icon.png',
  wpDomain: 'https://[api.wordpress_site.com]/wp-json',
  client: 'https://[api.wordpress_site.com]',
  url: '[http://your-url.com]',
  loadDbName: 'starter_wp',
  api: {
    homePage: '/wp/v2/pages/[page_id]',
    // this url will hit an endpoint for contact form 7 plugin
    postFormContact: '/contact-form-7/v1/contact-forms/{form_id}/feedback'
  }
}
