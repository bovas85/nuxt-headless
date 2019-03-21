'use strict'

const apiEndpoint = 'api.moustachedesign.xyz' // change api.moustachedesign.xyz to your wordpress url

export default {
  appTitleShort: 'Nuxt Headless',
  appTitle: 'Nuxt Headless boilerplate',
  appTitleShort: 'Nuxt-headless',
  appDescription: 'Nuxt Headless with Wordpress REST API',
  appThemeColor: '#ffffff ',
  appBgColor: '#00172c ',
  appIcon: 'assets/icon.png',
  // these are the rest api endpoints and your wordpress url 
  client: `https://${apiEndpoint}`, 
  wpDomain: `https://${apiEndpoint}/wp-json`,
  url: 'https://nuxt-headless-lvllzsprpb.now.sh', // your website url
  loadDbName: '[starter_wp]', // db name if needed
  api: {
    homePage: '/wp/v2/pages/8', // the [page_id] from WordPress
    // this url will hit an endpoint for contact form 7 plugin
    postFormContact: '/contact-form-7/v1/contact-forms/[form_id]/feedback' // change {form_id} with the contact form 7 id provided
  }
}
