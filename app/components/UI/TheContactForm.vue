<template>
  <div class="contact-form">
    <form v-if="!sent" class="go-bottom is-flex-column" @submit.prevent="sendForm">
      <div class="name">
        <label :class="{ selected: nameFocused || saved }" for="name">Full Name</label>
        <input
          id="name"
          v-model="form.yourName"
          autocomplete="given-name"
          :class="{ 'is-danger': (v$.yourName.$invalid && sending) || (v$.yourName.$invalid && nameClicked) }"
          name="name"
          type="text"
          required
          @focus="nameFocused = true"
          @blur="nameClicked = true"
        />
        <i :class="{ 'is-danger': (v$.yourName.$invalid && sending) || (v$.yourName.$invalid && nameClicked) }">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef664f" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
        </i>
        <span
          :class="{ 'is-visible': (v$.yourName.$invalid && sending) || (v$.yourName.$invalid && nameClicked) }"
          class="has-text-danger"
        >
          Please type your full name
        </span>
      </div>

      <div class="email">
        <label :class="{ selected: emailFocused || saved }" for="email">Email</label>
        <input
          id="email"
          v-model="form.yourEmail"
          autocomplete="email"
          :class="{ 'is-danger': (v$.yourEmail.$invalid && sending) || (v$.yourEmail.$invalid && emailClicked) }"
          name="email"
          type="email"
          required
          @focus="emailFocused = true"
          @blur="emailClicked = true"
        />
        <i :class="{ 'is-danger': (v$.yourEmail.$invalid && sending) || (v$.yourEmail.$invalid && emailClicked) }">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef664f" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
        </i>
        <span
          :class="{ 'is-visible': (v$.yourEmail.$invalid && sending) || (v$.yourEmail.$invalid && emailClicked) }"
          class="has-text-danger"
        >
          Please type an Email
        </span>
      </div>

      <div class="message">
        <label :class="{ selected: messageFocused || saved }" for="message" @focus="messageFocused = true">Message</label>
        <textarea id="message" v-model="form.yourMessage" class="hidden-mobile" rows="5" name="message" />
        <textarea
          id="message_mobile"
          v-model="form.yourMessage"
          class="is-hidden-mobile-large"
          rows="1"
          placeholder="Write your message here ..."
          name="message_mobile"
        />
      </div>

      <div class="check">
        <input id="check1" v-model="form.youAgree" type="checkbox" required />
        <label :class="{ opacity: form.youAgree, 'is-danger': !form.youAgree && sending }" class="checkbox label" for="check1">
          <p>You agree to the <NuxtLink to="/privacy-policy" target="_blank">Privacy Policy</NuxtLink></p>
          <span :class="{ 'is-visible': !form.youAgree && sending }" class="has-text-danger">
            Please agree with the Privacy Policy
          </span>
        </label>
      </div>

      <div class="field is-grouped">
        <div class="control">
          <input v-model="honeypot" type="text" hidden autocomplete="off" class="hidden honeypot" />
          <button
            :disabled="disabled || saved"
            :class="{ 'is-disabled': disabled || saved }"
            class="button button--contact"
            type="submit"
          >
            {{ appStore.connection !== 'none' ? 'Submit' : 'Save' }}
          </button>
        </div>
      </div>

      <p v-show="sendError" class="is-danger col--12 is-left send-error">There was an error sending the form.</p>
      <p v-show="checkValidation" class="is-danger col--12 is-left send-error">
        There is an error in one of the fields. Please review your previous answers
      </p>
    </form>

    <div class="sent" :class="{ 'is-visible': sent }">
      <h4>Submitted</h4>
      <p>Your message has been successfully submitted, we will get in touch with you shortly.</p>
      <div class="field is-grouped">
        <div class="control">
          <NuxtLink class="button button--sent" to="/">Go Home</NuxtLink>
        </div>
      </div>
    </div>

    <div class="sent" :class="{ 'is-visible': showSaveConfirmation }">
      <h4>Saved</h4>
      <p>Your message has been successfully saved, if you come back here it will be prefilled.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import useVuelidate from '@vuelidate/core'
import { required, email } from '@vuelidate/validators'

const appStore = useAppStore()
const config = useRuntimeConfig()

const sending = ref(false)
const saved = ref(false)
const sent = ref(false)
const nameClicked = ref(false)
const emailClicked = ref(false)
const nameFocused = ref(false)
const emailFocused = ref(false)
const messageFocused = ref(false)
const honeypot = ref('')
const disabled = ref(false)
const sendError = ref(false)
const showSaveConfirmation = ref(false)

const form = reactive({
  yourName: '',
  yourEmail: '',
  yourMessage: '',
  youAgree: false
})

const rules = {
  yourName: { required },
  yourEmail: { required, email },
  youAgree: { required: (val: boolean) => val === true }
}

const v$ = useVuelidate(rules, form)

const storedFormData = useLocalStorage('formData', '')

onMounted(() => {
  if (storedFormData.value) {
    try {
      const parsed = JSON.parse(storedFormData.value)
      if (parsed) {
        Object.assign(form, parsed)
        saved.value = true
        disabled.value = false
      }
    } catch (e) {
      console.warn('Could not parse stored form data', e)
    }
  }
})

const checkValidation = computed(() => {
  if (!sending.value) return false
  if (v$.value.yourEmail.$invalid && emailClicked.value) return true
  if (v$.value.yourName.$invalid && nameClicked.value) return true
  if (!form.youAgree) return true
  return false
})

const sendForm = async () => {
  if (appStore.connection === 'none') {
    storedFormData.value = JSON.stringify(form)
    showSaveConfirmation.value = true
    setTimeout(() => {
      showSaveConfirmation.value = false
    }, 2000)
    return
  }

  if (honeypot.value) return

  sending.value = true
  sendError.value = false

  const isValid = await v$.value.$validate()
  if (!isValid || !form.youAgree) {
    setTimeout(() => {
      sending.value = false
    }, 4000)
    return
  }

  sent.value = true
  disabled.value = true

  const formData = new FormData()
  formData.append('your-name', form.yourName)
  formData.append('your-email', form.yourEmail)
  formData.append('your-message', form.yourMessage)

  try {
    await $fetch(`${config.public.wpDomain}/contact-form-7/v1/contact-forms/feedback`, {
      method: 'POST',
      body: formData
    })
    disabled.value = false
    sending.value = false
  } catch (err) {
    console.warn('Contact send error:', err)
    disabled.value = false
    sending.value = false
    sendError.value = true
    sent.value = false
  }
}
</script>

<style lang="scss" scoped>
.contact-form {
  background: white;
  padding: calc($gap * 1.5) $gap calc($gap / 2);
  max-width: 100%;
  width: 100%;
  margin: 0;

  @include media(lg) {
    border: 8px solid $yellow;
    padding: $gap ($gap * 2);
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
  .check {
    display: block;
    margin: 20px 0 0;
    p {
      margin-bottom: 0;
      padding-bottom: 0;
      font-size: 14px;
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
    &:focus ~ label:before {
      border: 2px solid $secondary;
    }
  }

  label {
    &.checkbox {
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
      &.is-danger:before {
        border: solid 2px #ffd2d2;
      }
      &:hover:before {
        border: solid 2px $secondary;
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

  &.is-danger {
    border-bottom: 2px solid $red;
  }
}

.name,
.email {
  position: relative;

  label {
    transform: translate(0px, 32px);
    display: block;
    position: absolute;
    transition: transform 0.3s ease-in-out;

    &.selected {
      transform: translate(0, -15px);
    }
  }

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
</style>
