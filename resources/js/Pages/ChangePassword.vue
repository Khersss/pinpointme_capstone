<template>
  <v-app>
    <v-main class="change-password-bg">
      <v-container fluid class="change-password-container pa-0">
        <v-row align="center" justify="center" class="change-password-row ma-0">
          <v-col cols="12" sm="10" md="6" lg="5" xl="4" class="d-flex align-center justify-center">

            <!-- Main Card -->
            <v-card class="password-card" rounded="xl" elevation="8" width="100%" max-width="440">
              <!-- Header -->
              <div class="card-header">
                <div class="header-icon-wrap">
                  <v-icon size="36" color="white">mdi-shield-lock</v-icon>
                </div>
                <h1 class="header-title">{{ isNewUser ? 'Set Your Password' : 'Change Password' }}</h1>
                <p class="header-subtitle">
                  {{ isNewUser ? 'Create a secure password for your account' : 'Verify your identity to change your password' }}
                </p>
              </div>

              <v-card-text class="card-body pa-6 pa-sm-8">
                <!-- OTP FLOW: Only for existing users changing password (NOT new users) -->
                <template v-if="!isNewUser">
                  <!-- Step 1: Send OTP -->
                  <div v-if="step === 1" class="text-center">
                    <v-icon size="56" color="primary" class="mb-3">mdi-email-send-outline</v-icon>
                    <p class="text-body-2 text-medium-emphasis mb-1">We'll send a verification code to</p>
                    <p class="text-body-2 font-weight-bold mb-6" style="color: #3674B5;">{{ userEmail }}</p>
                    <v-btn
                      block
                      size="large"
                      color="#3674B5"
                      :loading="sendingOtp"
                      @click="sendOtp"
                      class="action-btn"
                      rounded="lg"
                    >
                      <v-icon start>mdi-send</v-icon>
                      Send Verification Code
                    </v-btn>
                  </div>

                  <!-- Step 2: Enter OTP -->
                  <div v-else-if="step === 2">
                    <div class="text-center mb-5">
                      <v-icon size="48" color="primary" class="mb-2">mdi-email-check-outline</v-icon>
                      <p class="text-body-2 text-medium-emphasis">
                        Enter the 6-digit code sent to
                      </p>
                      <p class="text-body-2 font-weight-bold" style="color: #3674B5;">{{ userEmail }}</p>
                    </div>

                    <v-form @submit.prevent="verifyOtp" ref="otpFormRef">
                      <div class="otp-container mb-4">
                        <v-otp-input
                          v-model="otpCode"
                          :length="6"
                          variant="outlined"
                          class="otp-input-custom"
                          @finish="verifyOtp"
                        />
                      </div>

                      <div class="text-center mb-5">
                        <span class="text-caption text-medium-emphasis">Didn't receive it? </span>
                        <v-btn 
                          variant="text" 
                          color="#3674B5" 
                          size="small"
                          density="compact"
                          @click="resendOtp"
                          :disabled="resendCooldown > 0 || sendingOtp"
                          class="text-none"
                        >
                          {{ resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend Code' }}
                        </v-btn>
                      </div>

                      <v-btn
                        block
                        size="large"
                        color="#3674B5"
                        :loading="verifyingOtp"
                        :disabled="getOtpLength() !== 6"
                        @click="verifyOtp"
                        class="action-btn"
                        rounded="lg"
                      >
                        <v-icon start>mdi-check-circle</v-icon>
                        Verify Code
                      </v-btn>

                      <v-btn
                        variant="text"
                        color="grey"
                        block
                        size="small"
                        class="mt-3 text-none"
                        @click="step = 1; otpCode = ''"
                      >
                        <v-icon start size="16">mdi-arrow-left</v-icon>
                        Go Back
                      </v-btn>
                    </v-form>
                  </div>

                  <!-- Step 3: Password Form for verified users -->
                  <div v-else-if="step === 3">
                    <v-form ref="passwordForm" @submit.prevent="changePassword">
                      <!-- New Password -->
                      <label class="field-label">New Password</label>
                      <v-text-field
                        v-model="newPassword"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="Enter new password"
                        variant="outlined"
                        :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                        @click:append-inner="showPassword = !showPassword"
                        class="mb-1 password-field"
                        hide-details
                        density="comfortable"
                        rounded="lg"
                      />

                      <!-- Password Strength Bar -->
                      <div class="strength-bar-wrap mt-2 mb-4" v-if="newPassword.length > 0">
                        <div class="strength-bar-track">
                          <div 
                            class="strength-bar-fill"
                            :style="{ width: passwordStrength + '%', background: strengthBarGradient }"
                          ></div>
                        </div>
                        <div class="d-flex justify-space-between align-center mt-1">
                          <span class="text-caption" :style="{ color: strengthColor }">{{ passwordStrengthText }}</span>
                          <span class="text-caption text-medium-emphasis">{{ passwordStrength }}%</span>
                        </div>
                      </div>

                      <!-- Password Requirements -->
                      <div class="requirements-list mb-5" v-if="newPassword.length > 0">
                        <div class="req-item" :class="{ met: hasMinLength }">
                          <v-icon size="16" :color="hasMinLength ? '#3674B5' : '#ccc'">
                            {{ hasMinLength ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                          </v-icon>
                          <span>At least 8 characters</span>
                        </div>
                        <div class="req-item" :class="{ met: hasUppercase }">
                          <v-icon size="16" :color="hasUppercase ? '#3674B5' : '#ccc'">
                            {{ hasUppercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                          </v-icon>
                          <span>One uppercase letter</span>
                        </div>
                        <div class="req-item" :class="{ met: hasLowercase }">
                          <v-icon size="16" :color="hasLowercase ? '#3674B5' : '#ccc'">
                            {{ hasLowercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                          </v-icon>
                          <span>One lowercase letter</span>
                        </div>
                        <div class="req-item" :class="{ met: hasNumber }">
                          <v-icon size="16" :color="hasNumber ? '#3674B5' : '#ccc'">
                            {{ hasNumber ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                          </v-icon>
                          <span>One number</span>
                        </div>
                      </div>

                      <!-- Confirm Password -->
                      <label class="field-label">Confirm Password</label>
                      <v-text-field
                        v-model="confirmPassword"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        placeholder="Confirm new password"
                        variant="outlined"
                        :append-inner-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
                        @click:append-inner="showConfirmPassword = !showConfirmPassword"
                        class="password-field"
                        hide-details
                        density="comfortable"
                        rounded="lg"
                      />

                      <!-- Password match indicator -->
                      <div v-if="confirmPassword.length > 0" class="mt-2 mb-5">
                        <div class="req-item" :class="{ met: passwordsMatch }">
                          <v-icon size="16" :color="passwordsMatch ? '#3674B5' : '#ef5350'">
                            {{ passwordsMatch ? 'mdi-check-circle' : 'mdi-close-circle' }}
                          </v-icon>
                          <span :style="{ color: passwordsMatch ? '#3674B5' : '#ef5350' }">
                            {{ passwordsMatch ? 'Passwords match' : 'Passwords do not match' }}
                          </span>
                        </div>
                      </div>
                      <div v-else class="mb-5"></div>

                      <!-- Submit Button -->
                      <v-btn
                        block
                        type="submit"
                        size="large"
                        color="#3674B5"
                        :loading="changingPassword"
                        :disabled="!isPasswordValid"
                        class="action-btn"
                        rounded="lg"
                      >
                        <v-icon start>mdi-lock-check</v-icon>
                        Update Password
                      </v-btn>

                      <!-- Back button for OTP flow -->
                      <v-btn
                        variant="text"
                        color="grey"
                        block
                        size="small"
                        class="mt-3 text-none"
                        @click="step = 2"
                      >
                        <v-icon start size="16">mdi-arrow-left</v-icon>
                        Go Back
                      </v-btn>
                    </v-form>
                  </div>
                </template>

                <!-- DIRECT PASSWORD FORM: For new users with temporary password -->
                <template v-else>
                  <!-- Greeting for new users -->
                  <div class="text-center mb-5">
                    <v-icon size="48" color="#DFA92C" class="mb-2">mdi-hand-wave</v-icon>
                    <p class="text-body-2 text-medium-emphasis">
                      Welcome! Set a secure password to get started.
                    </p>
                  </div>

                  <v-form ref="passwordForm" @submit.prevent="changePassword">
                    <!-- New Password -->
                    <label class="field-label">New Password</label>
                    <v-text-field
                      v-model="newPassword"
                      :type="showPassword ? 'text' : 'password'"
                      placeholder="Enter new password"
                      variant="outlined"
                      :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                      @click:append-inner="showPassword = !showPassword"
                      class="mb-1 password-field"
                      hide-details
                      density="comfortable"
                      rounded="lg"
                    />

                    <!-- Password Strength Bar -->
                    <div class="strength-bar-wrap mt-2 mb-4" v-if="newPassword.length > 0">
                      <div class="strength-bar-track">
                        <div 
                          class="strength-bar-fill"
                          :style="{ width: passwordStrength + '%', background: strengthBarGradient }"
                        ></div>
                      </div>
                      <div class="d-flex justify-space-between align-center mt-1">
                        <span class="text-caption" :style="{ color: strengthColor }">{{ passwordStrengthText }}</span>
                        <span class="text-caption text-medium-emphasis">{{ passwordStrength }}%</span>
                      </div>
                    </div>

                    <!-- Password Requirements -->
                    <div class="requirements-list mb-5" v-if="newPassword.length > 0">
                      <div class="req-item" :class="{ met: hasMinLength }">
                        <v-icon size="16" :color="hasMinLength ? '#3674B5' : '#ccc'">
                          {{ hasMinLength ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                        </v-icon>
                        <span>At least 8 characters</span>
                      </div>
                      <div class="req-item" :class="{ met: hasUppercase }">
                        <v-icon size="16" :color="hasUppercase ? '#3674B5' : '#ccc'">
                          {{ hasUppercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                        </v-icon>
                        <span>One uppercase letter</span>
                      </div>
                      <div class="req-item" :class="{ met: hasLowercase }">
                        <v-icon size="16" :color="hasLowercase ? '#3674B5' : '#ccc'">
                          {{ hasLowercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                        </v-icon>
                        <span>One lowercase letter</span>
                      </div>
                      <div class="req-item" :class="{ met: hasNumber }">
                        <v-icon size="16" :color="hasNumber ? '#3674B5' : '#ccc'">
                          {{ hasNumber ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                        </v-icon>
                        <span>One number</span>
                      </div>
                    </div>

                    <!-- Confirm Password -->
                    <label class="field-label">Confirm Password</label>
                    <v-text-field
                      v-model="confirmPassword"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      placeholder="Confirm new password"
                      variant="outlined"
                      :append-inner-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
                      @click:append-inner="showConfirmPassword = !showConfirmPassword"
                      class="password-field"
                      hide-details
                      density="comfortable"
                      rounded="lg"
                    />

                    <!-- Password match indicator -->
                    <div v-if="confirmPassword.length > 0" class="mt-2 mb-5">
                      <div class="req-item" :class="{ met: passwordsMatch }">
                        <v-icon size="16" :color="passwordsMatch ? '#3674B5' : '#ef5350'">
                          {{ passwordsMatch ? 'mdi-check-circle' : 'mdi-close-circle' }}
                        </v-icon>
                        <span :style="{ color: passwordsMatch ? '#3674B5' : '#ef5350' }">
                          {{ passwordsMatch ? 'Passwords match' : 'Passwords do not match' }}
                        </span>
                      </div>
                    </div>
                    <div v-else class="mb-5"></div>

                    <!-- Submit Button -->
                    <v-btn
                      block
                      type="submit"
                      size="large"
                      color="#3674B5"
                      :loading="changingPassword"
                      :disabled="!isPasswordValid"
                      class="action-btn"
                      rounded="lg"
                    >
                      <v-icon start>mdi-lock-check</v-icon>
                      Set Password
                    </v-btn>
                  </v-form>
                </template>
              </v-card-text>
            </v-card>

          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Success Dialog -->
    <v-dialog v-model="successDialog" persistent max-width="400">
      <v-card rounded="xl" class="text-center pa-6">
        <div class="success-check-wrap mb-4">
          <div class="success-check-circle">
            <v-icon size="48" color="white">mdi-check</v-icon>
          </div>
        </div>
        <h2 class="text-h5 font-weight-bold mb-2" style="color: #13294B;">Password Updated!</h2>
        <p class="text-body-2 text-medium-emphasis mb-6">
          Your password has been set successfully.<br>
          You're all set to use PinPointMe.
        </p>
        <v-btn
          size="large"
          color="#3674B5"
          @click="redirectToDashboard"
          class="action-btn px-10"
          rounded="lg"
          block
        >
          <v-icon start>mdi-arrow-right</v-icon>
          Continue
        </v-btn>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      :timeout="3000"
      location="top"
    >
      {{ snackbarMessage }}
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const userEmail = ref(page.props.email || '');
const userRole = ref(page.props.role || 'student');
const isNewUser = ref(page.props.isNewUser || false);

// For existing users changing password, start at step 1 (send OTP)
// For new users with temp password, template handles it directly (no steps needed)
const step = ref(1);

const otpCode = ref('');
const verificationToken = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const sendingOtp = ref(false);
const verifyingOtp = ref(false);
const changingPassword = ref(false);

const otpTimer = ref(300);
const resendCooldown = ref(0);
let timerInterval = null;
let cooldownInterval = null;

const snackbar = ref(false);
const snackbarMessage = ref('');
const snackbarColor = ref('success');
const successDialog = ref(false);

// Password validation
const hasMinLength = computed(() => newPassword.value.length >= 8);
const hasUppercase = computed(() => /[A-Z]/.test(newPassword.value));
const hasLowercase = computed(() => /[a-z]/.test(newPassword.value));
const hasNumber = computed(() => /[0-9]/.test(newPassword.value));
const passwordsMatch = computed(() => newPassword.value === confirmPassword.value && confirmPassword.value.length > 0);

const isPasswordValid = computed(() => 
  hasMinLength.value && 
  hasUppercase.value && 
  hasLowercase.value && 
  hasNumber.value && 
  newPassword.value === confirmPassword.value &&
  confirmPassword.value.length > 0
);

const passwordStrength = computed(() => {
  let strength = 0;
  if (hasMinLength.value) strength += 25;
  if (hasUppercase.value) strength += 25;
  if (hasLowercase.value) strength += 25;
  if (hasNumber.value) strength += 25;
  return strength;
});

const strengthColor = computed(() => {
  if (passwordStrength.value <= 25) return '#ef5350';
  if (passwordStrength.value <= 50) return '#FFA726';
  if (passwordStrength.value <= 75) return '#DFA92C';
  return '#4CAF50';
});

const strengthBarGradient = computed(() => {
  if (passwordStrength.value <= 25) return '#ef5350';
  if (passwordStrength.value <= 50) return 'linear-gradient(90deg, #ef5350, #FFA726)';
  if (passwordStrength.value <= 75) return 'linear-gradient(90deg, #FFA726, #DFA92C)';
  return 'linear-gradient(90deg, #DFA92C, #4CAF50)';
});

const passwordStrengthText = computed(() => {
  if (passwordStrength.value <= 25) return 'Weak';
  if (passwordStrength.value <= 50) return 'Fair';
  if (passwordStrength.value <= 75) return 'Good';
  return 'Strong';
});

const passwordRules = [
  v => !!v || 'Password is required',
  v => v.length >= 8 || 'Password must be at least 8 characters',
];

const confirmPasswordRules = [
  v => !!v || 'Please confirm your password',
  v => v === newPassword.value || 'Passwords do not match',
];

function getOtpLength() {
  if (Array.isArray(otpCode.value)) {
    return otpCode.value.join('').length;
  }
  return String(otpCode.value || '').length;
}

function startOtpTimer() {
  otpTimer.value = 300;
  if (timerInterval) clearInterval(timerInterval);
  timerInterval = setInterval(() => {
    if (otpTimer.value > 0) {
      otpTimer.value--;
    } else {
      clearInterval(timerInterval);
      showMessage('Code expired. Please request a new one.', 'error');
      step.value = 1;
    }
  }, 1000);
}

function startResendCooldown() {
  resendCooldown.value = 60;
  if (cooldownInterval) clearInterval(cooldownInterval);
  cooldownInterval = setInterval(() => {
    if (resendCooldown.value > 0) {
      resendCooldown.value--;
    } else {
      clearInterval(cooldownInterval);
    }
  }, 1000);
}

async function sendOtp() {
  sendingOtp.value = true;
  try {
    const response = await axios.post('/api/auth/send-password-change-otp', {
      email: userEmail.value,
    });
    if (response.data.success) {
      showMessage('Verification code sent!', 'success');
      step.value = 2;
      startOtpTimer();
      startResendCooldown();
    } else {
      showMessage(response.data.message || 'Failed to send code', 'error');
    }
  } catch (error) {
    showMessage(error.response?.data?.message || 'Failed to send code', 'error');
  } finally {
    sendingOtp.value = false;
  }
}

async function resendOtp() {
  await sendOtp();
}

async function verifyOtp() {
  const otpValue = Array.isArray(otpCode.value) 
    ? otpCode.value.join('') 
    : String(otpCode.value).trim();
  if (otpValue.length !== 6) return;
  
  verifyingOtp.value = true;
  try {
    const response = await axios.post('/api/auth/verify-password-change-otp', {
      email: userEmail.value,
      otp: otpValue,
    });
    if (response.data.success) {
      verificationToken.value = response.data.token;
      showMessage('Verified!', 'success');
      step.value = 3;
      if (timerInterval) clearInterval(timerInterval);
    } else {
      showMessage(response.data.message || 'Invalid code', 'error');
    }
  } catch (error) {
    showMessage(error.response?.data?.message || 'Verification failed', 'error');
  } finally {
    verifyingOtp.value = false;
  }
}

async function changePassword() {
  if (!isPasswordValid.value) return;
  
  changingPassword.value = true;
  try {
    const payload = {
      email: userEmail.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    };
    // Include token only for OTP-verified flow
    if (!isNewUser.value) {
      payload.token = verificationToken.value;
    }

    const response = await axios.post('/api/auth/complete-password-change', payload);
    if (response.data.success) {
      successDialog.value = true;
    } else {
      showMessage(response.data.message || 'Failed to update password', 'error');
    }
  } catch (error) {
    showMessage(error.response?.data?.message || 'Failed to update password', 'error');
  } finally {
    changingPassword.value = false;
  }
}

function redirectToDashboard() {
  if (userRole.value === 'admin') {
    window.location.href = '/admin/dashboard';
  } else if (userRole.value === 'rescuer') {
    window.location.href = '/rescuer/dashboard';
  } else {
    window.location.href = '/user/scanner';
  }
}

function showMessage(message, color = 'success') {
  snackbarMessage.value = message;
  snackbarColor.value = color;
  snackbar.value = true;
}

onMounted(() => {
  if (page.props.auth?.user?.email) {
    userEmail.value = page.props.auth.user.email;
    userRole.value = page.props.auth.user.role;
  }
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
  if (cooldownInterval) clearInterval(cooldownInterval);
});
</script>

<style scoped>
.change-password-bg {
  background: linear-gradient(to bottom, #F8F0F0 0%, #FFFFFF 40%, #f8f8f8 50%, #D1F8EF 80%, #A1E3F9 100%);
  min-height: 100vh;
  position: relative;
}

.change-password-container {
  min-height: 100vh;
  padding: 16px !important;
}

.change-password-row {
  min-height: 100vh;
}

.change-password-bg::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(ellipse at 20% 20%, rgba(161,227,249,0.15) 0%, transparent 50%),
              radial-gradient(ellipse at 80% 80%, rgba(209,248,239,0.12) 0%, transparent 50%);
  pointer-events: none;
}

/* Card */
.password-card {
  overflow: hidden;
  border: none;
  position: relative;
  z-index: 1;
}

/* Header */
.card-header {
  background: linear-gradient(135deg, #3674B5 0%, #2D5F96 100%);
  padding: 28px 32px 24px;
  text-align: center;
  position: relative;
}

.card-header::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #DFA92C 0%, #f0c040 50%, #DFA92C 100%);
}

.header-icon-wrap {
  width: 64px;
  height: 64px;
  margin: 0 auto 14px;
  background: rgba(255,255,255,0.15);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
}

.header-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: white;
  margin-bottom: 6px;
  letter-spacing: -0.3px;
}

.header-subtitle {
  font-size: 0.82rem;
  color: rgba(255,255,255,0.75);
  margin: 0;
  line-height: 1.5;
}

/* Card Body */
.card-body {
  background: #fafbfc;
}

/* Field Labels */
.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: #13294B;
  margin-bottom: 6px;
  letter-spacing: 0.3px;
}

/* Password Fields */
.password-field :deep(.v-field) {
  background: white;
  border: 1.5px solid #e0e4ea;
  transition: border-color 0.2s;
}

.password-field :deep(.v-field:hover) {
  border-color: #3674B5;
}

.password-field :deep(.v-field--focused) {
  border-color: #3674B5 !important;
}

.password-field :deep(input) {
  color: #13294B;
  font-size: 0.95rem;
}

.password-field :deep(input::placeholder) {
  color: #adb5bd;
}

/* Strength Bar */
.strength-bar-wrap {
  padding: 0 2px;
}

.strength-bar-track {
  height: 6px;
  background: #e9ecef;
  border-radius: 3px;
  overflow: hidden;
}

.strength-bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.4s ease, background 0.4s ease;
}

/* Requirements List */
.requirements-list {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px 12px;
}

.req-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  color: #999;
  transition: color 0.2s;
}

.req-item.met {
  color: #3674B5;
}

/* OTP Input */
.otp-container {
  max-width: 300px;
  margin: 0 auto;
}

.otp-input-custom :deep(.v-otp-input__content) {
  justify-content: center;
  gap: 8px;
}

.otp-input-custom :deep(.v-field) {
  border-radius: 10px;
  background: white;
  border: 1.5px solid #e0e4ea;
}

.otp-input-custom :deep(.v-field--focused) {
  border-color: #3674B5 !important;
}

.otp-input-custom :deep(input) {
  font-size: 20px;
  font-weight: 600;
  text-align: center;
  color: #13294B;
}

/* Action Button */
.action-btn {
  font-weight: 600;
  font-size: 0.95rem;
  letter-spacing: 0;
  text-transform: none;
  height: 48px !important;
  box-shadow: 0 4px 14px rgba(54, 116, 181, 0.3);
  transition: box-shadow 0.2s, transform 0.1s;
}

.action-btn:hover {
  box-shadow: 0 6px 20px rgba(54, 116, 181, 0.4);
  transform: translateY(-1px);
}

.action-btn:active {
  transform: translateY(0);
}

/* Success Dialog */
.success-check-wrap {
  display: flex;
  justify-content: center;
}

.success-check-circle {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #3674B5, #4a8fd4);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(54, 116, 181, 0.3);
  animation: successPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes successPop {
  0% { transform: scale(0); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

/* Responsive */
@media (max-width: 600px) {
  .change-password-container {
    min-height: auto;
    padding: 12px !important;
    padding-top: 20px !important;
    padding-bottom: 20px !important;
  }
  
  .change-password-row {
    min-height: auto;
    align-items: flex-start !important;
  }

  .card-header {
    padding: 18px 16px 16px;
  }
  
  .header-title {
    font-size: 1.1rem;
    margin-bottom: 4px;
  }

  .header-subtitle {
    font-size: 0.78rem;
  }

  .header-icon-wrap {
    width: 52px;
    height: 52px;
    margin-bottom: 10px;
  }

  .requirements-list {
    grid-template-columns: 1fr;
    gap: 4px;
    margin-bottom: 16px !important;
  }
  
  .card-body {
    padding: 16px !important;
  }

  .password-card {
    margin-top: 8px;
    margin-bottom: 8px;
  }

  .strength-bar-wrap {
    margin-bottom: 16px !important;
  }

  .action-btn {
    height: 44px !important;
    font-size: 0.9rem;
  }
}
</style>
