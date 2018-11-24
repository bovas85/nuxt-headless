import Vue from 'vue'

// disable dev mode message in console
Vue.config.productionTip = false

Vue.mixin({
  data () {
    return { sent: false, showForm: false }
  },
  mounted () {
    // avoid mounted as it runs every component load (not page load)
  },
  computed: {
    Splitting () {
      if (process.client) {
        let Splitting = require('splitting')
        return Splitting
      }
    },
    scrollama () {
      if (process.browser) {
        let scrollama = require('scrollama')
        return scrollama
      }
    }
  },
  methods: {
    getTimeNow () {
      return `${new Date().getHours()}:${new Date().getMinutes()}:${new Date().getSeconds()}`
    },
    breakIt (text) {
      return text.replace(/\n\r/g, '<br /><br />')
    },
    validateEmail (email) {
      var validEmail = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:[A-Z]{2}|co|it|xyz|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum)\b/i // email regex
      if (validEmail.test(email)) {
        return true
      } else return false
    },
    getDate (string) {
      var date = new Date(string)
      var monthNames = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
      ]
      let nth = n => {
        return ['st', 'nd', 'rd'][((n + 90) % 100 - 10) % 10 - 1] || 'th'
      }

      var day = date.getDate()
      day += nth(day) // add suffix to day
      var monthIndex = date.getMonth()
      return monthNames[monthIndex] + ' ' + day
    },
    markdown (text) {
      // markdown converter for bold text
      return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
    },
    capitalizeFirstLetter (string) {
      return string.charAt(0).toUpperCase() + string.slice(1)
    }
  }
})
