<template>
    <v-app class="bg-user-gradient-light">
        <v-main class="registration-viewport">
            <div class="mobile-container">
                <div class="mobile-header">
                    <v-img
                        src="/images/logos/pinpointme.png"
                        max-height="64"
                        max-width="64"
                        contain
                        class="mx-auto"
                    />
                    <h1 class="text-h6 font-weight-bold text-primary mt-2 mb-0">PinPointMe</h1>
                </div>
                <v-card class="registration-card" rounded="xl" elevation="2">
                    <div class="google-profile-card mb-4 mt-2">
                        <v-avatar size="40" class="mr-3">
                            <v-img v-if="googleUser.profile_picture" :src="googleUser.profile_picture" />
                            <v-icon v-else color="primary">mdi-account-circle</v-icon>
                        </v-avatar>
                        <div class="profile-info">
                            <div class="profile-name">{{ googleUser.first_name }} {{ googleUser.last_name }}</div>
                            <div class="profile-email">{{ googleUser.email }}</div>
                        </div>
                        <v-icon color="success" size="20">mdi-check-circle</v-icon>
                    </div>
                    <div class="form-content text-center">
                        <v-icon size="56" color="primary" class="mb-3">mdi-email-check-outline</v-icon>
                        <h2 class="form-title" style="color:#3674B5;">Verify Your SDCA Email</h2>
                        <p class="form-subtitle mb-4">
                            We have sent a verification link to your SDCA Gmail account.<br>
                            <span style="color:#3674B5;font-weight:600">Please check your inbox and click the link to verify your account.</span>
                        </p>
                        <v-alert v-if="error" type="error" variant="tonal" class="mb-4 mx-4" closable density="compact" @click:close="error = ''">
                            {{ error }}
                        </v-alert>
                        <v-btn color="primary" class="submit-btn mb-2" :loading="isLoading" @click="resendVerification" variant="tonal">
                            Resend Verification Email
                        </v-btn>
                        <v-btn color="grey" class="submit-btn" @click="handleCancel" variant="text">
                            Back to Login
                        </v-btn>
                    </div>
                </v-card>
            </div>
        </v-main>
        <v-snackbar v-model="showToast" :color="toastColor" location="top" timeout="3000">
            {{ toastMessage }}
        </v-snackbar>
                                    type="tel"
                                    inputmode="numeric"
                                />
                            </div>

                            <v-btn
                                type="submit"
                                color="primary"
                                block
                                size="large"
                                :loading="isLoading"
                                class="submit-btn mt-4"
                            >
                                <v-icon start size="20">mdi-email-send</v-icon>
                                Send Verification Code
                            </v-btn>
                        </v-form>
                    </div>

                    <!-- Step 2: OTP Verification -->
                    <div v-else-if="step === 2" class="form-content">
                        <div class="text-center mb-4">
                            <v-icon size="48" color="primary" class="mb-2">mdi-email-check-outline</v-icon>
                            <h2 class="form-title">Check Your Email</h2>
                            <p class="form-subtitle">
                                We sent a 6-digit verification code to<br>
                                <strong>{{ googleUser.email }}</strong>
                            </p>
                        </div>

                        <v-form @submit.prevent="verifyOtp" ref="otpFormRef">
                            <div class="input-group">
                                <label class="input-label text-center d-block">Enter Verification Code</label>
                                <v-otp-input
                                    v-model="otp"
                                    length="6"
                                    variant="outlined"
                                    :disabled="isLoading"
                                    class="otp-input"
                                />
                            </div>

                            <v-btn
                                type="submit"
                                color="primary"
                                block
                                size="large"
                                :loading="isLoading"
                                :disabled="otp.length !== 6"
                                class="submit-btn"
                            >
                                <v-icon start size="20">mdi-check-circle</v-icon>
                                Verify Email
                            </v-btn>

                            <div class="text-center mt-3">
                                <span class="text-caption text-grey">Didn't receive it? </span>
                                <v-btn 
                                    variant="text" 
                                    color="primary" 
                                    size="small"
                                    density="compact"
                                    @click="resendOtp"
                                    :disabled="resendCooldown > 0 || isLoading"
                                >
                                    {{ resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend Code' }}
                                </v-btn>
                            </div>

                            <v-btn
                                variant="text"
                                color="grey"
                                block
                                size="small"
                                class="mt-2"
                                @click="step = 1; otp = ''"
                                :disabled="isLoading"
                            >
                                <v-icon start size="16">mdi-arrow-left</v-icon>
                                Back to Edit Details
                            </v-btn>
                        </v-form>
                    </div>

                    <!-- Step 3: Success -->
                    <div v-else-if="step === 3" class="form-content text-center">
                        <v-icon size="72" color="success" class="mb-4">mdi-check-circle</v-icon>
                        <h2 class="form-title text-success">Account Created!</h2>
                        <p class="form-subtitle">
                            Your account has been verified successfully. Now let's set up your password to secure your account.
                        </p>

                        <v-btn
                            color="primary"
                            block
                            size="large"
                            @click="redirectToSetPassword"
                            class="submit-btn"
                        >
                            <v-icon start size="20">mdi-lock-reset</v-icon>
                            Set a Password
                        </v-btn>
                    </div>

                    <!-- Cancel Button -->
                    <div v-if="step < 3" class="cancel-section">
                        <v-btn 
                            variant="text" 
                            color="grey" 
                            size="small"
                            @click="handleCancel" 
                            :disabled="isLoading"
                        >
                            Cancel Registration
                        </v-btn>
                    </div>
                </v-card>
            </div>
        </v-main>

        <!-- Toast Snackbar -->
        <v-snackbar v-model="showToast" :color="toastColor" location="top" timeout="3000">
            {{ toastMessage }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { GoogleAuth } from '@codetrix-studio/capacitor-google-auth';
import { Capacitor } from '@capacitor/core';

const page = usePage();
const googleUser = computed(() => page.props.googleUser || {});
const isLoading = ref(false);
const error = ref('');
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Resend verification email
const resendVerification = async () => {
    isLoading.value = true;
    error.value = '';
    try {
        const response = await fetch('/auth/google/resend-verification', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: googleUser.value.email,
            }),
        });
        const data = await response.json();
        if (response.ok && data.success) {
            toastMessage.value = 'Verification email resent! Please check your inbox.';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            error.value = data.message || 'Failed to resend verification email.';
        }
    } catch (err) {
        error.value = 'Connection error. Please try again.';
    } finally {
        isLoading.value = false;
    }
};

const handleCancel = () => {
    window.location.href = '/login';
};

// Initialize GoogleAuth for native platforms
onMounted(() => {
    if (Capacitor.isNativePlatform()) {
        try {
            GoogleAuth.initialize();
        } catch (error) {
            console.error('GoogleAuth initialization error:', error);
        }
    }
});
</script>

<style scoped>
/* =================================================================
   Mobile-First Container Layout
   ================================================================= */
.registration-viewport {
    min-height: 100vh;
    min-height: 100dvh;
    padding: 0;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.mobile-container {
    width: 100%;
    max-width: 440px;
    margin: 0 auto;
    padding: 12px;
    padding-top: max(12px, env(safe-area-inset-top, 12px));
    padding-bottom: max(12px, env(safe-area-inset-bottom, 12px));
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    min-height: 100dvh;
    box-sizing: border-box;
}

/* =================================================================
   Mobile Header
   ================================================================= */
.mobile-header {
    text-align: center;
    padding: 4px 0 10px;
    flex-shrink: 0;
}

.mobile-header .v-img {
    max-height: 48px !important;
    max-width: 48px !important;
}

.mobile-header h1 {
    font-size: 1rem !important;
}

/* =================================================================
   Registration Card - Main Container
   ================================================================= */
.registration-card {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    background: white !important;
    border-radius: 16px !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
    overflow: hidden;
    min-height: 0;
}

/* =================================================================
   Step Indicator
   ================================================================= */
.step-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 16px;
    background: #f8f9fa;
    gap: 6px;
    flex-shrink: 0;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}

.step-circle {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    background: #e0e0e0;
    color: #9e9e9e;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.step.active .step-circle {
    background: #3674B5;
    color: white;
}

.step.completed .step-circle {
    background: #4caf50;
    color: white;
}

.step-label {
    font-size: 10px;
    color: #9e9e9e;
    font-weight: 500;
    white-space: nowrap;
}

.step.active .step-label,
.step.completed .step-label {
    color: #333;
}

.step-line {
    width: clamp(24px, 8vw, 48px);
    height: 2px;
    background: #e0e0e0;
    margin-bottom: 16px;
    transition: background 0.3s ease;
    flex-shrink: 0;
}

.step-line.active {
    background: #3674B5;
}

/* =================================================================
   Google Profile Card
   ================================================================= */
.google-profile-card {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    margin: 0 12px 6px;
    background: linear-gradient(135deg, #f0f7ff 0%, #e8f4f8 100%);
    border-radius: 10px;
    border: 1px solid rgba(54, 116, 181, 0.15);
    flex-shrink: 0;
    min-width: 0;
}

.google-profile-card .v-avatar {
    flex-shrink: 0;
}

.profile-info {
    flex: 1;
    min-width: 0;
    margin-right: 8px;
}

.profile-name {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-email {
    font-size: 11px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* =================================================================
   Form Content Area
   ================================================================= */
.form-content {
    padding: 14px;
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    min-height: 0;
}

.form-title {
    font-size: clamp(16px, 4.5vw, 20px);
    font-weight: 700;
    color: #333;
    margin-bottom: 4px;
}

.form-subtitle {
    font-size: clamp(12px, 3.2vw, 14px);
    color: #666;
    margin-bottom: 16px;
    line-height: 1.4;
}

/* =================================================================
   Input Groups
   ================================================================= */
.input-group {
    margin-bottom: 12px;
}

.input-label {
    display: flex;
    align-items: center;
    font-size: 13px;
    font-weight: 600;
    color: #444;
    margin-bottom: 4px;
}

/* Text field styling */
:deep(.v-text-field .v-field) {
    border-radius: 10px;
    background: #fafafa;
    font-size: 15px;
    min-height: 44px;
}

:deep(.v-text-field .v-field--focused) {
    background: #fff;
}

:deep(.v-text-field .v-field__outline) {
    --v-field-border-opacity: 0.25;
}

:deep(.v-text-field .v-field--focused .v-field__outline) {
    --v-field-border-opacity: 1;
}

:deep(.v-text-field input) {
    font-size: 15px !important;
}

:deep(.v-text-field input::placeholder) {
    font-size: 14px !important;
    color: #aaa !important;
}

/* OTP Input */
.otp-input {
    justify-content: center;
}

:deep(.v-otp-input) {
    max-width: 100%;
    gap: 4px;
}

:deep(.v-otp-input .v-otp-input__content) {
    gap: clamp(4px, 1.5vw, 8px);
    justify-content: center;
}

:deep(.v-otp-input .v-field) {
    border-radius: 8px;
    min-width: 0;
    width: clamp(36px, 11vw, 48px);
    height: clamp(40px, 11vw, 48px);
}

:deep(.v-otp-input input) {
    font-size: clamp(16px, 4.5vw, 20px) !important;
    font-weight: 600;
    padding: 0 !important;
    text-align: center;
}

/* =================================================================
   Submit Button
   ================================================================= */
.submit-btn {
    text-transform: none !important;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 0.3px;
    border-radius: 12px !important;
    height: 46px !important;
    box-shadow: 0 4px 12px rgba(54, 116, 181, 0.25) !important;
    flex-shrink: 0;
}

/* =================================================================
   Cancel Section
   ================================================================= */
.cancel-section {
    padding: 10px 14px 14px;
    text-align: center;
    border-top: 1px solid #f0f0f0;
    margin-top: auto;
    flex-shrink: 0;
}

/* =================================================================
   Alerts
   ================================================================= */
:deep(.v-alert) {
    border-radius: 10px;
}

:deep(.v-alert .v-alert__content) {
    font-size: 12px;
}

/* =================================================================
   Chips
   ================================================================= */
:deep(.v-chip) {
    font-weight: 500;
    border-radius: 6px;
}

/* =================================================================
   Responsive – Extra-small phones (iPhone SE, Galaxy S3, 320px)
   ================================================================= */
@media (max-width: 359px) {
    .mobile-container {
        padding: 8px;
    }

    .mobile-header {
        padding: 2px 0 6px;
    }

    .mobile-header .v-img {
        max-height: 36px !important;
        max-width: 36px !important;
    }

    .mobile-header h1 {
        font-size: 0.85rem !important;
        margin-top: 4px !important;
    }

    .step-indicator {
        padding: 8px 10px;
        gap: 4px;
    }

    .step-circle {
        width: 22px;
        height: 22px;
        font-size: 10px;
    }

    .step-label {
        font-size: 9px;
    }

    .step-line {
        width: 20px;
        margin-bottom: 14px;
    }

    .google-profile-card {
        margin: 0 8px 4px;
        padding: 8px 10px;
    }

    .google-profile-card .v-avatar {
        width: 32px !important;
        height: 32px !important;
    }

    .profile-name {
        font-size: 12px;
    }

    .profile-email {
        font-size: 10px;
    }

    .form-content {
        padding: 10px;
    }

    .input-group {
        margin-bottom: 10px;
    }

    .input-label {
        font-size: 12px;
    }

    .submit-btn {
        height: 42px !important;
        font-size: 13px;
    }

    .cancel-section {
        padding: 8px 10px 10px;
    }

    :deep(.v-text-field input) {
        font-size: 14px !important;
    }

    :deep(.v-text-field input::placeholder) {
        font-size: 13px !important;
    }
}

/* =================================================================
   Responsive – Small phones (375px)
   ================================================================= */
@media (min-width: 360px) and (max-width: 399px) {
    .mobile-container {
        padding: 10px;
    }

    .form-content {
        padding: 12px;
    }

    .google-profile-card {
        margin: 0 10px 6px;
    }
}

/* =================================================================
   Responsive – Short screens / landscape
   ================================================================= */
@media (max-height: 640px) {
    .mobile-container {
        min-height: auto;
        padding-top: 6px;
        padding-bottom: 6px;
    }

    .mobile-header {
        padding: 2px 0 6px;
    }

    .mobile-header .v-img {
        max-height: 36px !important;
        max-width: 36px !important;
    }

    .mobile-header h1 {
        font-size: 0.85rem !important;
        margin-top: 2px !important;
    }

    .registration-card {
        flex: none;
    }

    .step-indicator {
        padding: 8px 14px;
    }

    .google-profile-card {
        padding: 8px 10px;
        margin-bottom: 4px;
    }

    .form-content {
        padding: 10px 14px;
    }

    .form-title {
        margin-bottom: 2px;
    }

    .form-subtitle {
        margin-bottom: 10px;
    }

    .input-group {
        margin-bottom: 8px;
    }

    .submit-btn {
        height: 42px !important;
    }

    .cancel-section {
        padding: 6px 14px 10px;
    }
}

@media (max-height: 500px) and (orientation: landscape) {
    .mobile-header {
        display: none;
    }

    .step-indicator {
        padding: 6px 12px;
    }

    .step-circle {
        width: 22px;
        height: 22px;
        font-size: 10px;
    }

    .step-label {
        display: none;
    }

    .step-line {
        margin-bottom: 0;
    }

    .google-profile-card {
        margin: 0 10px 2px;
        padding: 6px 10px;
    }

    .form-content {
        padding: 8px 12px;
        overflow-y: auto;
    }

    .form-title {
        font-size: 15px;
    }

    .form-subtitle {
        font-size: 11px;
        margin-bottom: 8px;
    }

    .input-group {
        margin-bottom: 6px;
    }

    .input-label {
        font-size: 11px;
        margin-bottom: 2px;
    }

    :deep(.v-text-field .v-field) {
        min-height: 38px;
    }

    .submit-btn {
        height: 38px !important;
        font-size: 13px;
    }

    .cancel-section {
        padding: 4px 12px 6px;
    }
}

/* =================================================================
   Responsive – Tablet and larger
   ================================================================= */
@media (min-width: 600px) {
    .mobile-container {
        padding: 24px;
        justify-content: center;
        min-height: 100vh;
        min-height: 100dvh;
    }

    .mobile-header {
        padding: 12px 0 20px;
    }

    .mobile-header .v-img {
        max-height: 72px !important;
        max-width: 72px !important;
    }

    .mobile-header h1 {
        font-size: 1.35rem !important;
    }

    .registration-card {
        flex: none;
        max-width: 100%;
    }

    .step-indicator {
        padding: 16px 20px;
    }

    .form-content {
        padding: 20px 24px;
    }

    .google-profile-card {
        margin: 0 20px 12px;
        padding: 12px 16px;
    }

    .profile-name {
        font-size: 14px;
    }

    .profile-email {
        font-size: 12px;
    }

    .form-title {
        font-size: 20px;
    }

    .form-subtitle {
        font-size: 14px;
        margin-bottom: 20px;
    }

    .input-group {
        margin-bottom: 16px;
    }

    .submit-btn {
        height: 48px !important;
        font-size: 15px;
    }
}

/* =================================================================
   Responsive – Desktop
   ================================================================= */
@media (min-width: 960px) {
    .mobile-container {
        padding: 32px;
    }

    .mobile-header {
        padding: 16px 0 24px;
    }

    .mobile-header .v-img {
        max-height: 80px !important;
        max-width: 80px !important;
    }

    .mobile-header h1 {
        font-size: 1.5rem !important;
    }

    .form-content {
        padding: 24px 28px;
    }

    .google-profile-card {
        margin: 0 24px 16px;
    }

    .form-title {
        font-size: 22px;
    }
}

/* =================================================================
   Safe Area Support (notched phones)
   ================================================================= */
@supports (padding-top: env(safe-area-inset-top)) {
    .mobile-container {
        padding-left: max(12px, env(safe-area-inset-left));
        padding-right: max(12px, env(safe-area-inset-right));
    }
}

/* =================================================================
   Focus states for accessibility
   ================================================================= */
:deep(.v-field--focused .v-label) {
    color: #3674B5 !important;
}

/* =================================================================
   Scrollbar (for overflow content)
   ================================================================= */
.form-content::-webkit-scrollbar {
    width: 3px;
}

.form-content::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 2px;
}

.form-content {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
}
</style>
