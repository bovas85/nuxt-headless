<template>
  <div class="contact-form">
    <form key="notSent" :class="{'sending': $root.sent}" @submit.prevent class="go-bottom is-flex-column">
        <div class="name">
            <label :class="{'selected': nameFocused || saved}" for="name">Full Name</label>
            <input autocomplete="given-name" @focus="nameFocused = true" @blur="nameClicked = true" :class="{'is-danger': $v.form.yourName.$invalid && sending || $v.form.yourName.$invalid && nameClicked}" v-model="form.yourName" id="name" name="name" type="text" required>
            <i :class="{'is-danger': $v.form.yourName.$invalid && sending || $v.form.yourName.$invalid && nameClicked}"><img src="/images/error.svg" alt="error icon"></i>
            <span :class="{'is-visible': $v.form.yourName.$invalid && sending || $v.form.yourName.$invalid && nameClicked}" class="has-text-danger">Please type your full name</span>
        </div>


        <div class="email">
            <label :class="{'selected': emailFocused || saved}" for="email">Email</label>
            <input autocomplete="email" @focus="emailFocused = true" @blur="emailClicked = true" :class="{'is-danger': $v.form.yourEmail.$invalid && sending || $v.form.yourEmail.$invalid && emailClicked}" v-model="form.yourEmail" id="email" name="email" type="email" required>
            <i :class="{'is-danger': $v.form.yourEmail.$invalid && sending || $v.form.yourEmail.$invalid && emailClicked}"><img src="/images/error.svg" alt="error icon"></i>
            <span :class="{'is-visible': $v.form.yourEmail.$invalid && sending || $v.form.yourEmail.$invalid && emailClicked}" class="has-text-danger">Please type an Email</span>
        </div>

        <div class="message">
            <label :class="{'selected': messageFocused || saved}" @focus="messageFocused = true" for="message">Message</label>
            <textarea class="hidden-mobile" rows="5" v-model="form.yourMessage" id="message" name="message"></textarea>
            <textarea class="is-hidden-mobile-large" rows="1" placeholder="Write your message here ..." v-model="form.yourMessage" id="message_mobile" name="message_mobile"></textarea>
        </div>

        <div class="check">
          <input id="check1" v-model="form.youAgree" type="checkbox" required>
          <label :class="{'opacity': form.youAgree, 'is-danger': form.youAgree && sending}" class="checkbox label" for="check1">
              <p>You agree to the <a href="/privacy-policy" target="_blank">Privacy Policy</a></p>
              <span :class="{'is-visible': !form.youAgree && sending}" class="has-text-danger">Please agree with the Privacy Policy</span>
          </label>

        </div>

        <div class="field is-grouped">
            <div class="control">
                <input type="text" hidden autocomplete="off" v-model="honeypot" class="hidden honeypot" />
                <button v-scroll-to="{element:'.go-bottom'}" :disabled="disabled || saved" :class="{'is-disabled': disabled || saved}" @click.prevent="sendForm()" class="button button--contact">{{ $store.state.connection !== 'none' ? 'Submit' : 'Save'}}</button>
            </div>
        </div>

        <p class="is-danger col--12 is-left send-error" v-show="sendError">There was an error sending the form.</p>
        <p class="is-danger col--12 is-left send-error" v-show="checkValidation">There is an error in one of the fields. Please review your previous answers</p>
    </form>

    <div class="sent" :class="{'is-visible': $root.sent}">
      <h4>Submitted</h4>
      <p>
        Your message has been successfully submitted, we will get in touch with you shortly.
      </p>
      <div class="field is-grouped">
        <div class="control">
          <nuxt-link class="button button--sent" to="/">Go Home</nuxt-link>
        </div>
      </div>
    </div>

    <div class="sent" :class="{'is-visible': showSaveConfirmation}">
      <h4>Saved</h4>
      <p>
        Your message has been successfully saved, if you come back here it will be prefilled.
      </p>
    </div>
  </div>
</template>

<script>
  import Config from '~/assets/config'
  import { required, email } from 'vuelidate/lib/validators'

  export default {
    name: 'TheContactForm',
    data () {
      return {
        sending: false,
        saved: false,
        $v: null,
        nameClicked: false,
        surnameClicked: false,
        emailClicked: false,
        nameFocused: false,
        emailFocused: false,
        messageFocused: false,
        honeypot: '',
        form: {
          yourName: '',
          yourEmail: '',
          yourMessage: '',
          youAgree: false
        },
        disabled: false,
        error: false,
        sendError: false,
        sent: false,
        saved: false,
        notSent: true,
        showSaveConfirmation: false
      }
    },
    validations: {
      form: {
        yourName: {
          required
        },
        yourEmail: {
          required,
          email
        },
        youAgree: {
          required
        }
      }
    },
    mounted () {
      if (this.$localStorage.get('formData')) {
        this.saved = true
        const form = JSON.parse(this.$localStorage.get('formData'))
        if (form) {
          this.form = form
          this.disabled = false
        }
        return
      }
    },
    beforeDestroy () {
      this.resetForm()
    },
    methods: {
      resetForm () {
        let newForm = {
          yourName: '',
          yourEmail: '',
          yourMessage: '',
          youAgree: false
        }
        this.nameClicked = false
        this.emailClicked = false
        this.form = newForm
        this.$root.sent = false
        this.saved = false
      },
      sendForm () {
        if (this.$store.state.connection === 'none') {
          let formStorage = {
            yourName: this.form.yourName,
            yourEmail: this.form.yourEmail,
            yourMessage: this.form.yourMessage,
            youAgree: this.form.youAgree
          }
          this.$localStorage.set('formData', JSON.stringify(formStorage))
          this.showSaveConfirmation = true
          setTimeout(() => {
            this.showSaveConfirmation = false
          }, 2000)
          return false
        }
        if (this.honeypot || this.honeypot !== '') {
          // bot prevention
          return false
        }
        this.sending = true
        this.sendError = false
        this.error = false
        if (this.$v.form.yourName.$invalid) {
          // console.log('email not valid')
          this.form.yourEmail = ''
          setTimeout(() => {
            // this.error = false
            this.sending = false
            return false
          }, 5000)
          return false
        }
        if (this.$v.form.$invalid || !this.form.youAgree) {
          this.error = true
          setTimeout(() => {
            this.sending = false
            return false
          }, 5000)
        } else {
          this.$root.sent = true
          this.disabled = true
          var formData = new FormData()
          formData.append('your-name', this.form.yourName)
          formData.append('your-email', this.form.yourEmail)
          formData.append('your-message', this.form.yourMessage)
          this.$http
            .post(`${Config.wpDomain}${Config.api.postFormContact}`, formData)
            .then(res => {
              this.disabled = false
              this.sending = false
              try {
                this.$ga.event({
                  eventCategory: 'form',
                  eventAction: 'submit',
                  eventLabel: 'submission',
                  eventValue: 0
                })
              } catch (e) {}
            })
            .catch(err => {
              console.log('contact send error', err)
              this.disabled = false
              this.sending = false
              this.sendError = true
              this.error = true
              this.$root.sent = false
            })
        }
      }
    },
    computed: {
      checkValidation () {
        if (!this.sending) {
          return false
        } else if (this.sending) {
          if (this.$v.form.yourEmail.$invalid && this.emailClicked) {
            // console.log('invalid email')
            return true
          } else if (this.$v.form.yourName.$invalid && this.nameClicked) {
            // console.log('invalid name')
            return true
          } else if (!this.form.youAgree) {
            // console.log('invalid agree')
            return true
          } else return false
        }
      }
    }
  }
</script>

<style lang="scss" scoped>
  .contact-form {
    background: white;
    padding: $gap * 1.5 $gap $gap / 2;
    max-width: 100%;
    width: 100%;
    margin: 0;

    @include media(lg) {
      border: 8px solid $yellow;
      padding: $gap $gap * 2;
      width: $tablet;
      max-width: $tablet;
    }
  }
  p {
    margin: 0;
    padding: 0 0 10px 0;
    line-height: 2;
    color: $secondary;
    font-weight: 300;
    font-size: 16px;
    margin-bottom: 20px;
  }
  .sent {
    opacity: 0;
    position: fixed;
    display: flex;
    flex-direction: column;
    justify-content: center;
    height: 100%;
    max-height: 0%;
    transition: all 0.3s ease-in-out;
    min-height: 200px;
    z-index: -1;

    @include media(md) {
      min-height: 445px;
    }

    h4 {
      font-size: 18px;
      text-transform: uppercase;
    }
    &.is-visible {
      position: relative;
      opacity: 1;
      max-height: 100%;
      z-index: 1;
    }
    p {
      line-height: 1.2;
      font-weight: 600;
      font-size: $font-size;
    }
  }
  .is-danger {
    color: $red;
    margin-left: 0;
    text-align: left;
  }

  h2 {
    color: $secondary;
    text-transform: uppercase;
    font-size: 18px;
    font-weight: 400;
    text-align: left;
    letter-spacing: 1.8px;
    margin: 0 0 4px 0;
    @media (min-width: $tablet) {
      font-size: 24px;
    }
    span.red {
      color: $red;
      line-height: 1;
    }
  }
  h4 {
    font-size: 22px;
    line-height: 22px;
    color: $secondary;
    letter-spacing: 0.4px;
    margin: 0 0 24px 0;
    padding: 0;
    font-weight: 700;
  }
  form {
    grid-row-gap: 0;
    &.sending {
      display: none;
    }
    .check {
      display: block;
      margin: 20px 0 0;
      p {
        margin-bottom: 0;
        padding-bottom: 0;
        font-size: 14px;
        font-weight: normal;
        font-style: normal;
        font-stretch: normal;
        line-height: 1.43;
        letter-spacing: 0.3px;
        text-align: left;
        color: $secondary;
      }
    }
    .checkbox {
      font-size: 14px;
      font-weight: 300;
      line-height: 1.36;
      cursor: pointer;
      letter-spacing: 0.2px;
      text-align: left;
      color: $secondary;
      padding-top: 0;
      margin: 0;
      opacity: 1 !important;
      a {
        color: $secondary;
        font-weight: 600;
        &:hover {
          color: $secondary;
          text-decoration: underline;
        }
      }
      span {
        &.is-visible {
          margin-bottom: 0 !important;
        }
      }
    }

    input[type='checkbox'] {
      visibility: visible !important;
      position: absolute;
      opacity: 0;
      height: 0 !important;
      &:focus {
        ~ label:before {
          border: 2px solid $secondary;
        }
      }
    }

    .checkbox:after {
      background-image: url('/images/tick.svg');
      background-repeat: no-repeat;
      background-size: 70%;
      background-position: center;
      content: '';
      width: 32px;
      height: 32px;
      line-height: 1;
      font-size: 0px;
      position: absolute;
      top: -5px;
      left: 0px;
      opacity: 0;
      color: $secondary;

      @include media(md) {
        top: 1px;
        left: -1px;
      }
    }

    label {
      &.checkbox {
        &.opacity {
          &:after {
            opacity: 1 !important;
          }
        }
        height: 33px;
        position: relative;
        padding-left: 44px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        font-size: 12px;
        &:before {
          content: '';
          background-color: transparent;
          border: solid 1px $secondary;
          padding: 14px;
          display: block;
          border-radius: 2px;
          position: absolute;
          left: 0;
          top: -5px;

          @include media(md) {
            top: 3px;
          }
        }
        &.is-danger {
          &:before {
            border: solid 2px #ffd2d2;
          }
        }
        &:checked {
          &:before {
            border: solid 1px $secondary;
            color: $secondary;
          }
        }
        &:hover {
          &:before {
            border: solid 2px $secondary;
          }
        }
      }
    }

    input:checked + label:before {
      border: 1px solid $secondary;
      color: #99a1a7;
    }

    .honeypot {
      visibility: hidden;
      height: 0;
      font-size: 0px;
      width: 0;
      position: absolute;
      text-indent: 9999px;
    }
    span {
      visibility: hidden;
      display: block;
      &.is-visible {
        visibility: visible;
        margin-bottom: $gap;
      }
    }
    .email {
      margin-bottom: $gap;
    }
  }

  label {
    line-height: 1;
    color: $secondary;
    margin-bottom: 12px;
    text-transform: uppercase;
    font-weight: 600;
    font-size: 14px;
    &.error {
      color: $red;
    }
  }

  input {
    margin: 12px 0 20px 0;
    height: 50px;
    font-size: $font-size;
    border: 0 solid transparent;
    border-bottom: 1px solid $secondary;
    width: 100%;
    background-color: white;
    color: $secondary;
    box-shadow: unset;
    outline: none;
    padding: 10px;
    transition: all 0.3s ease-in-out;

    &.error {
      border-bottom-color: $red;
    }

    &:focus {
      border-bottom: 2px solid $secondary;
      outline: unset;
    }

    &.correct {
      background-image: url('/images/tick.svg');
      background-repeat: no-repeat;
      background-position: 98% 50%;
    }

    &.is-danger {
      border-bottom: 2px solid $red;
    }
  }
  .emailErrorMsg {
    color: $red;
    font-size: 16px;
    margin: 0 0 10px 0px;
    padding: 0;
    display: none;
  }
  .name,
  .email {
    label {
      transform: translate(0px, 32px);
      display: block;
      position: absolute;
      transition: transform 0.3s ease-in-out;

      &.selected {
        transform: translate(0, -15px);
      }
    }
  }

  .name,
  .email {
    position: relative;

    i {
      position: absolute;
      top: $gap;
      right: 8px;
      opacity: 0;
      transition: opacity 0.4s ease-in-out;

      &.is-danger {
        opacity: 1;
      }
    }
  }

  textarea {
    border: 0 solid $secondary;
    font-size: $font-size;
    font-family: $family-primary;
    border-bottom: 1px solid $secondary;
    background-color: white !important;
    height: 100px;
    width: 100%;
    outline: none !important;
    margin: 10px 0 0 0;
    padding: 10px;
    color: $grey;
    resize: vertical;
    &.error {
      border-bottom-color: $red;
    }
    &:focus {
      border-bottom: 2px solid $secondary;
    }
  }

  .container {
    width: 100%;
    max-width: 540px;
    padding-top: 54px;
  }

  .contact-modal {
    .container {
      max-width: 500px;
    }
    form {
      padding-top: 24px;
    }
    .is-flex {
      align-items: center;
      justify-content: space-between;
      i {
        color: $secondary;
        font-size: 12px;
        cursor: pointer;
      }
    }
  }
</style>
