'use strict'

// configure this file following the example below. 
// these are the rest api endpoints and your wordpress url 
const Config = {
  appTitle: 'Nuxt Headless',
  appTitleShort: 'Nuxt-headless',
  appDescription: 'Nuxt Headless with Wordpress REST API',
  appThemeColor: '#ffffff ',
  appBgColor: '#00172c ',
  appIcon: 'assets/app-icon.png',
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

module.exports = Config
