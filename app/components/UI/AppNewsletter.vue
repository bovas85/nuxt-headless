<template>
  <div class="newsletter-container">
    <div :class="{ 'footer-newsletter': footer }">
      <ClientOnly>
        <TransitionGroup name="fade" tag="div">
          <div v-if="!success" key="notSuccess" class="field">
            <form name="newsletterForm" :class="{ 'form-wrapper': !footer }" @submit.prevent="sendIt">
              <input v-model="honeypot" type="hidden" name="honeypot" class="honeypot" value="" />
              <input v-model="token" type="hidden" name="_token" class="x-csrf-token" value="" />
              <input
                v-model="input"
                placeholder="Email"
                type="email"
                name="newsletterL"
                :class="{ 'is-error': error || empty }"
                class="newsletterInput"
                @keyup="checkInput"
              />
              <button type="submit" class="newsletterSubmit">Submit</button>
            </form>
          </div>
          <div v-else key="success" class="field successful">
            <div class="columns">
              <div class="column is-2">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
                </svg>
              </div>
              <div class="column is-10">
                <p>Thanks for your interest</p>
              </div>
            </div>
          </div>
        </TransitionGroup>
      </ClientOnly>

      <div class="validation">
        <p v-show="error" class="help is-danger">
          You have entered an invalid email address.<br />Ex: myemail@email.com
        </p>
        <p v-show="empty && input.length > 0" class="help has-text-danger">Please type an e-mail</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    footer?: boolean
  }>(),
  {
    footer: false
  }
)

const input = ref('')
const honeypot = ref('')
const error = ref(false)
const success = ref(false)
const token = ref('')
const empty = ref(false)

const config = useRuntimeConfig()

const sendIt = async () => {
  if (honeypot.value) return false

  if (input.value.length) {
    empty.value = false
    const validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (validEmail.test(input.value)) {
      success.value = true
      error.value = false
      const email = input.value.toLowerCase()

      try {
        const formData = new FormData()
        formData.append('your-email', email)
        formData.append('token', token.value)

        await $fetch(`${config.public.clientUrl}/contact-form-7/v1/contact-forms/newsletter/feedback`, {
          method: 'POST',
          body: formData
        })
      } catch (e) {
        // Handled silently or logged
        console.warn('Newsletter submission error:', e)
      }
    } else {
      success.value = false
      error.value = true
    }
  } else {
    empty.value = true
  }
}

const checkInput = () => {
  if (input.value.length === 0) {
    error.value = false
    success.value = false
  }
}
</script>

<style lang="scss" scoped>
.field {
  margin-bottom: 0;
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
    @media (min-width: $tablet) {
      width: 60px;
    }
  }
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
    padding: 0 60px 0 12px;
    margin-right: -60px;
    @media (min-width: $tablet) {
      padding: 0 60px 0 12px;
      margin-right: -60px;
    }
    &.is-error {
      box-shadow: 0 0 1px 1px $danger;
    }
    &:focus {
      outline: 0;
      background: #fff;
    }
  }
}

.validation {
  p {
    font-size: 12px;
    letter-spacing: 1px;
  }
}

.successful {
  width: 100%;
  svg {
    stroke: $yellow;
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

.footer-newsletter {
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
    padding-right: 70px;
    width: 100%;
    height: 50px;
    color: #ffffff !important;
    box-shadow: unset;
    outline: none;
    font-size: 14px;
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
