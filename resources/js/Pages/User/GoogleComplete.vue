<template>
    <v-app class="bg-user-gradient-light">
        <v-main class="verify-viewport">
            <div class="verify-container">
                <!-- Brand Header -->
                <div class="brand-header">
                    <img src="/images/Icons/logo_w_word.png" alt="PinPointMe" class="brand-logo" />
                </div>

                <!-- Main Card -->
                <v-card class="verify-card" rounded="xl" elevation="3">
                    <!-- Google Profile Strip -->
                    <div class="profile-strip">
                        <v-avatar :size="profileAvatarSize" class="profile-avatar">
                            <v-img v-if="googleUser.profile_picture" :src="googleUser.profile_picture" />
                            <v-icon v-else color="white" :size="profileAvatarSize * 0.6">mdi-account-circle</v-icon>
                        </v-avatar>
                        <div class="profile-details">
                            <div class="profile-name">{{ googleUser.first_name }} {{ googleUser.last_name }}</div>
                            <div class="profile-email">{{ googleUser.email }}</div>
                        </div>
                        <v-icon color="#4CAF50" :size="checkSize">mdi-check-decagram</v-icon>
                    </div>

                    <!-- Content -->
                    <div class="verify-content">
                        <!-- Email Icon -->
                        <div class="icon-wrapper">
                            <div class="icon-bg">
                                <v-icon color="white" :size="emailIconSize">mdi-email-check-outline</v-icon>
                            </div>
                        </div>

                        <h2 class="verify-title">Verify Your SDCA Email</h2>

                        <p class="verify-description">
                            We've sent a verification link to your <strong>SDCA Gmail account</strong>.
                            Please check your inbox and click the link to activate your account.
                        </p>

                        <!-- Steps Info -->
                        <div class="steps-info">
                            <div class="step-item">
                                <div class="step-num">1</div>
                                <span>Open your <strong>SDCA Gmail</strong> inbox</span>
                            </div>
                            <div class="step-item">
                                <div class="step-num">2</div>
                                <span>Find the email from <strong>PinPointMe</strong></span>
                            </div>
                            <div class="step-item">
                                <div class="step-num">3</div>
                                <span>Click <strong>"Verify My Account"</strong></span>
                            </div>
                        </div>

                        <!-- Error Alert -->
                        <v-alert v-if="error" type="error" variant="tonal" class="mb-3" closable density="compact" rounded="lg" @click:close="error = ''">
                            {{ error }}
                        </v-alert>

                        <!-- Actions -->
                        <v-btn
                            color="#3674B5"
                            class="resend-btn"
                            :loading="isLoading"
                            @click="resendVerification"
                            variant="tonal"
                            block
                            rounded="lg"
                        >
                            <v-icon start size="18">mdi-email-sync-outline</v-icon>
                            Resend Verification Email
                        </v-btn>

                        <v-btn
                            color="grey-darken-1"
                            class="back-btn"
                            @click="handleCancel"
                            variant="text"
                            block
                            rounded="lg"
                        >
                            <v-icon start size="18">mdi-arrow-left</v-icon>
                            Back to Login
                        </v-btn>
                    </div>
                </v-card>

                <!-- Footer Note -->
                <p class="footer-note">
                    <v-icon size="14" color="#999">mdi-information-outline</v-icon>
                    Didn't receive the email? Check your spam folder.
                </p>
            </div>
        </v-main>
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

// Responsive sizes
const profileAvatarSize = computed(() => window.innerWidth < 360 ? 32 : 40);
const checkSize = computed(() => window.innerWidth < 360 ? 18 : 22);
const emailIconSize = computed(() => window.innerWidth < 360 ? 28 : 36);

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
   Viewport & Container
   ================================================================= */
.verify-viewport {
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(12px, 3vw, 24px);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.verify-container {
    width: 100%;
    max-width: clamp(340px, 85vw, 460px);
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: clamp(12px, 3vw, 20px);
}

/* =================================================================
   Brand Header
   ================================================================= */
.brand-header {
    text-align: center;
    flex-shrink: 0;
}

.brand-logo {
    max-width: clamp(180px, 50vw, 260px);
    height: auto;
    object-fit: contain;
    filter: drop-shadow(0 2px 8px rgba(54, 116, 181, 0.15));
}

/* =================================================================
   Main Card - Fits Content
   ================================================================= */
.verify-card {
    width: 100%;
    background: white !important;
    border: 1px solid rgba(161, 227, 249, 0.25);
    box-shadow: 0 8px 32px rgba(54, 116, 181, 0.1), 0 2px 8px rgba(0, 0, 0, 0.04) !important;
    overflow: hidden;
}

/* =================================================================
   Profile Strip
   ================================================================= */
.profile-strip {
    display: flex;
    align-items: center;
    padding: clamp(10px, 2.5vw, 14px) clamp(12px, 3vw, 20px);
    background: linear-gradient(135deg, #f0f7ff 0%, #e8f4f8 100%);
    border-bottom: 1px solid rgba(54, 116, 181, 0.1);
    gap: clamp(10px, 2.5vw, 14px);
    min-width: 0;
}

.profile-avatar {
    flex-shrink: 0;
    border: 2px solid rgba(54, 116, 181, 0.2);
}

.profile-details {
    flex: 1;
    min-width: 0;
}

.profile-name {
    font-size: clamp(12px, 3.2vw, 14px);
    font-weight: 600;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

.profile-email {
    font-size: clamp(10px, 2.8vw, 12px);
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

/* =================================================================
   Content Area
   ================================================================= */
.verify-content {
    padding: clamp(16px, 4vw, 28px) clamp(16px, 4vw, 24px) clamp(20px, 4vw, 28px);
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Email Icon */
.icon-wrapper {
    margin-bottom: clamp(12px, 3vw, 20px);
}

.icon-bg {
    width: clamp(56px, 14vw, 72px);
    height: clamp(56px, 14vw, 72px);
    border-radius: 50%;
    background: linear-gradient(135deg, #3674B5, #2563A7);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 24px rgba(54, 116, 181, 0.3);
}

/* Title */
.verify-title {
    font-size: clamp(1.1rem, 4.5vw, 1.4rem);
    font-weight: 700;
    color: #3674B5;
    margin: 0 0 clamp(8px, 2vw, 12px) 0;
    letter-spacing: -0.3px;
}

/* Description */
.verify-description {
    font-size: clamp(0.8rem, 2.8vw, 0.9rem);
    line-height: 1.6;
    color: #555;
    margin: 0 0 clamp(14px, 3.5vw, 20px) 0;
    max-width: 380px;
}

.verify-description strong {
    color: #3674B5;
}

/* =================================================================
   Steps Info
   ================================================================= */
.steps-info {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: clamp(6px, 1.5vw, 10px);
    margin-bottom: clamp(16px, 4vw, 24px);
    padding: clamp(10px, 2.5vw, 16px);
    background: rgba(54, 116, 181, 0.04);
    border-radius: clamp(10px, 2vw, 14px);
    border: 1px solid rgba(54, 116, 181, 0.08);
}

.step-item {
    display: flex;
    align-items: center;
    gap: clamp(8px, 2vw, 12px);
    text-align: left;
    font-size: clamp(0.75rem, 2.5vw, 0.85rem);
    color: #444;
    line-height: 1.4;
}

.step-item strong {
    color: #3674B5;
}

.step-num {
    width: clamp(22px, 5.5vw, 28px);
    height: clamp(22px, 5.5vw, 28px);
    border-radius: 50%;
    background: linear-gradient(135deg, #3674B5, #2563A7);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: clamp(10px, 2.5vw, 12px);
    font-weight: 700;
    flex-shrink: 0;
}

/* =================================================================
   Buttons
   ================================================================= */
.resend-btn {
    text-transform: none !important;
    font-weight: 600 !important;
    font-size: clamp(13px, 3.2vw, 14px) !important;
    letter-spacing: 0.2px !important;
    height: clamp(40px, 9vw, 46px) !important;
    margin-bottom: clamp(6px, 1.5vw, 10px);
    box-shadow: none !important;
}

.back-btn {
    text-transform: none !important;
    font-weight: 500 !important;
    font-size: clamp(12px, 3vw, 13px) !important;
    height: clamp(36px, 8vw, 42px) !important;
    color: #888 !important;
}

/* =================================================================
   Footer Note
   ================================================================= */
.footer-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: clamp(0.65rem, 2vw, 0.75rem);
    color: #999;
    text-align: center;
    flex-shrink: 0;
}

/* =================================================================
   Alerts
   ================================================================= */
:deep(.v-alert) {
    width: 100%;
    border-radius: 10px;
}

:deep(.v-alert .v-alert__content) {
    font-size: clamp(11px, 2.8vw, 12px);
}

/* =================================================================
   Responsive – Extra Small (< 360px)
   ================================================================= */
@media (max-width: 359px) {
    .verify-viewport {
        padding: 8px;
        align-items: flex-start;
        padding-top: 16px;
    }

    .verify-container {
        gap: 8px;
    }

    .brand-logo {
        max-width: 150px;
    }

    .profile-strip {
        padding: 8px 10px;
        gap: 8px;
    }

    .verify-content {
        padding: 12px 12px 16px;
    }

    .steps-info {
        padding: 8px;
        gap: 5px;
    }
}

/* =================================================================
   Responsive – Short Screens / Landscape
   ================================================================= */
@media (max-height: 640px) {
    .verify-viewport {
        align-items: flex-start;
        padding-top: 8px;
    }

    .brand-logo {
        max-width: 140px;
    }

    .verify-container {
        gap: 8px;
    }

    .icon-bg {
        width: 48px;
        height: 48px;
    }

    .icon-wrapper {
        margin-bottom: 8px;
    }

    .steps-info {
        padding: 8px;
        gap: 4px;
    }
}

@media (max-height: 500px) and (orientation: landscape) {
    .brand-header {
        display: none;
    }

    .verify-viewport {
        align-items: flex-start;
        padding: 6px;
    }

    .verify-content {
        padding: 10px 14px 14px;
    }

    .icon-wrapper {
        margin-bottom: 6px;
    }

    .icon-bg {
        width: 40px;
        height: 40px;
    }

    .verify-title {
        font-size: 1rem;
    }

    .verify-description {
        font-size: 0.75rem;
        margin-bottom: 10px;
    }

    .steps-info {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        gap: 6px;
        padding: 8px;
    }

    .step-item {
        font-size: 0.7rem;
    }

    .resend-btn {
        height: 38px !important;
    }

    .back-btn {
        height: 34px !important;
    }
}

/* =================================================================
   Responsive – Tablet+
   ================================================================= */
@media (min-width: 600px) {
    .brand-logo {
        max-width: 280px;
    }

    .verify-card {
        box-shadow: 0 12px 48px rgba(54, 116, 181, 0.12), 0 4px 16px rgba(0, 0, 0, 0.05) !important;
    }

    .verify-content {
        padding: 28px 32px 32px;
    }

    .profile-strip {
        padding: 14px 24px;
    }
}

/* =================================================================
   Safe Area Support (notched phones)
   ================================================================= */
@supports (padding-top: env(safe-area-inset-top)) {
    .verify-viewport {
        padding-top: max(12px, env(safe-area-inset-top));
        padding-bottom: max(12px, env(safe-area-inset-bottom));
        padding-left: max(12px, env(safe-area-inset-left));
        padding-right: max(12px, env(safe-area-inset-right));
    }
}
</style>
