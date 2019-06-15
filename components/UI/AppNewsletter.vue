<template>
  <div class="newsletter-container">
    <div :class="{'footer-newsletter': footer}">
        <no-ssr>
            <fade-transition group tag="div">
                <div key="notSuccess" v-if="!success" class="field">
                    <form name="newsletterForm" @submit.prevent :class="{'form-wrapper': !footer}">
                      <input type="hidden" v-model="honeypot" name="honeypot" class="honeypot" value="" hidden>
                      <input type="hidden" v-model="token" name="_token" class="x-csrf-token" value="" token>
                      <input @keyup="checkInput" placeholder="Email" type="text" name="newsletterL" :class="{ 'is-error': error || empty }" v-model="input" class="newsletterInput">
                      <button @click.prevent="sendIt()" type="submit" class="newsletterSubmit">Submit</button>
                  </form>
                </div>
                <div key="success" v-else class="field successful">
                    <div class="columns"> 
                        <div class="column is-2">
                            <img src="/images/sent.svg" alt="form sent">
                        </div>
                        <div class="column is-10">
                            <p>
                              Thanks for your interest
                            </p>
                        </div>
                    </div>
                </div>
            </fade-transition>
        </no-ssr>

        <div class="validation">
            <p v-show="error" class="help is-danger">You have entered an invalid email address.
                                                    <br>Ex: myemail@email.com</p>
            <p v-show="empty" class="help has-text-danger">Please type an e-mail</p>
        </div>
    </div>
  </div>
</template>

<script>
  import Config from "~/assets/config";
  export default {
    name: "AppNewsletter",
    props: {
      footer: {
        type: Boolean,
        default: false
      }
    },
    data () {
      return {
        input: "",
        honeypot: "",
        error: false,
        success: false,
        token: "",
        empty: true
      };
    },
    methods: {
      sendIt () {
        if (this.honeypot || this.honeypot !== "") {
          // bot prevention
          return false;
        }
        if (this.input.length) {
          this.empty = false;
          var validEmail = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:[A-Z]{2}|co|it|xyz|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum)\b/i; // email regex
          if (validEmail.test(this.input)) {
            this.success = true;
            this.error = false;
            let email = this.input ? this.input.toLowerCase() : "";

            // Sends to WP contact form 7 form. The Campaign Monitor submission is handled in PHP through custom WP plugin 'Contact Form 7 - Send to Campaign Monitor List'
            // The CM ID is stored within the CF7 Form 'Additional settings' tab. (details in WP plugin)
            try {
              var formData = new FormData();
              formData.append("your-email", email);
              formData.append("token", this.token);
              this.$http
                .post(
                  `${Config.client}${Config.api.postFormNewsletter}`,
                  formData
                )
                .then(res => {
                  // console.log("newsletter signup sent");
                })
                .catch(err => {
                  // console.log('newsletter send error', err);
                });
            } catch (e) {
              // console.log('newsletter send error', e);
            }
          } else {
            this.success = false;
            this.error = true;
          }
        } else {
          this.empty = true;
        }
      },
      checkInput () {
        if (this.input.length === 0) {
          this.error = false;
          this.success = false;
        }
      }
    },
    mounted () {
      this.empty = false;
    }
  };
</script>

<style lang="scss" scoped>
  @media (min-width: $mobile) {
    .form-wrapper button[data-v-e1ec8af4] {
      top: 5px;
      width: 42px;
    }
    .level img[data-v-e1ec8af4] {
      height: 40px;
      width: 40px;
      max-width: 40px;
    }
  }
  .field {
    margin-bottom: 0; // height of input
  }

  form {
    margin: 0 auto;
    width: 100%;
  }

  .form-wrapper {
    button {
      overflow: visible;
      position: relative;
      float: right;
      right: 0;
      height: 42px;
      top: 0;
      background: transparent;
      border: 0;
      margin: 0;
      text-transform: uppercase;
      font-size: $font-small;
      padding: 0;
      cursor: pointer;
      color: #fff;
      width: 60px;
      //   background-color: #66C0BE;
      @media (min-width: $tablet) {
        width: 60px;
      }
      &::-moz-focus-inner {
        /* remove extra button spacing for Mozilla Firefox */
        border: 0;
        padding: 0;
      }
    }
  }
  .form-wrapper,
  .form-wrapper-mobile {
    input {
      width: 100%;
      height: 42px;
      float: left;
      border: 0;
      border-radius: 1px;
      font-size: $font-size;
      color: #2d2d33;
      line-height: 23px;
      background-color: #ffffff;
      box-shadow: 0 0 1px 1px transparent;
      // border: 1px solid #64CCC9;
      padding: 0 60px 0 12px;
      margin-right: -60px; // button margin
      @media (min-width: $tablet) {
        padding: 0 60px 0 12px;
        margin-right: -60px; // button margin
      }
      &.is-error {
        box-shadow: 0 0 1px 1px $danger;
      }
      &:focus {
        outline: 0;
        background: #fff;
      }
      // &::-webkit-input-placeholder {
      //   color: #646362;
      // }
      &:-moz-placeholder {
        color: #646362;
      }
      &:-ms-input-placeholder {
        color: #646362;
      }
    }
    button {
      height: 42px;
      background: transparent;
      border: 0;
      margin: 0;
      text-transform: uppercase;
      font-size: $font-small;
      padding: 0;
      cursor: pointer;
      color: currentColor;
      &::-moz-focus-inner {
        /* remove extra button spacing for Mozilla Firefox */
        border: 0;
        padding: 0;
      }
    }
  }

  .form-wrapper-mobile {
    input {
      margin: ($gap / 1.5) 0 $gap;
      border-radius: 0;
    }
    button {
      float: none;
      display: block;
      clear: both;
      width: 100%;
    }
  }

  .validation {
    p {
      font-size: 12px;
      letter-spacing: 1px;
    }
  }

  .level {
    img {
      height: 40px;
      width: 40px;
      max-width: 40px;
    }
  }

  .successful {
    width: 100%;
    img {
      width: 40px;
      height: 40px;
    }
    .columns {
      margin-left: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      .column:not(:last-child) {
        margin-right: $gap;
      }
    }
    p {
      font-size: 14px;
      font-weight: 300;
      letter-spacing: 0.2px;
      text-align: left;
      color: #fff;
    }
  }

  @media (max-width: $tablet) {
    .form-wrapper {
      width: 100%;
      input {
        min-width: 100%;
        width: 100%;
        max-width: 100%;
      }
    }
  }

  .homepage {
    width: 100%;
    max-width: 500px;
  }

  .footer-newsletter {
    //disable yellow webkit autofill colour
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    input:-webkit-autofill:active {
      transition: background-color 5000s ease-in-out 0s;
      background-color: #00172c !important;
      border: 1px solid rgb(250, 255, 189);
      -webkit-text-fill-color: #ffffff !important;
    }
    p {
      margin-bottom: 0px;
      @media (min-width: $tablet) {
        max-width: 300px;
        margin: 0 auto;
        font-weight: 400;
        margin-top: 8px;
      }
    }
    form {
      position: relative;
      display: flex;
      width: 100%;
      margin: 0 auto;
      @media (min-width: $desktop) {
        width: 270px;
      }
    }
    input {
      background-color: #252d34;
      border: none;
      padding: 5px 10px;
      padding-right: 65px;
      width: 100%;
      height: 50px;
      .button {
        height: 50px;
      }
      @media (min-width: $desktop) {
        height: 40px;
        .button {
          height: 40px;
        }
      }
    }
    .button {
      position: absolute;
      right: 10px;
      background: transparent;
      padding-top: 0;
      font-size: 12px;
      letter-spacing: 1.6px;
      text-transform: capitalize;
      text-align: left;
    }
    input {
      background-color: #252d34;
      border: none;
      padding: 5px 10px;
      padding-right: 10px;
      padding-right: 65px;
      width: 100%;
      height: 50px;
      color: #ffffff !important;
      box-shadow: unset;
      outline: none;
      border: unset;
      font-size: 14px;
      padding-right: 70px;
      // &:-webkit-autofill {
      //   color: white !important;
      //   box-shadow: 0 0 0px 1000px #00172c inset;
      // }
      @media (min-width: $desktop) {
        background-color: #252d34;
        height: 40px;
        border: none;
      }
    }
    button {
      cursor: pointer;
      width: 60px;
      height: 50px;
      position: absolute;
      top: 0;
      right: 0;
      text-transform: capitalize;
      padding: 0 15px 0 0px;
      font-size: 12px;
      letter-spacing: 1.6px;
      background-color: transparent;
      border: none;
      color: #ffffff;
      outline: none;
      @media (min-width: $desktop) {
        height: 40px;
      }
      &:hover {
        color: #979797;
      }
    }
  }
</style>
