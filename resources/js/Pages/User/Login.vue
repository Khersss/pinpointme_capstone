<template>
    <v-app class="bg-user-gradient-light">
        <v-main class="min-h-screen">
            <v-container fluid class="fill-height pa-0">
                <v-row no-gutters class="fill-height">
                    <!-- Left Panel - Laptop/Desktop Only -->
                    <v-col cols="12" lg="6" class="d-none d-lg-flex">
                        <div
                            class="w-100 h-100 d-flex flex-column justify-center align-center position-relative left-panel"
                        >
                            <div class="logo-container">
                                <v-img
                                    src="/images/logos/pinpointme.png"
                                    max-height="200"
                                    max-width="200"
                                    contain
                                    class="logo-image"
                                />
                            </div>
                            <h1 class="text-h3 text-white mt-6 font-weight-bold brand-title">
                                PinPointMe
                            </h1>
                            <p class="text-h6 text-white-darken-1 text-center mt-2 brand-subtitle">
                                COMING YOUR WAY.
                            </p>
                            <p class="text-body-2 text-white-darken-2 text-center mt-4 px-8" style="max-width: 400px;">
                                Your trusted ward incident response system. Fast, reliable, and ready to support patient safety.
                            </p>
                        </div>
                    </v-col>

                    <!-- Right Panel - Login Form -->
                    <v-col cols="12" lg="6" class="d-flex align-center justify-center">
                        <v-card
                            class="pa-8 mx-4"
                            max-width="450"
                            width="100%"
                            elevation="8"
                            rounded="lg"
                        >
                            <!-- Mobile/Tablet Logo -->
                            <div class="d-lg-none text-center mb-6">
                                <v-img
                                    src="/images/logos/pinpointme.png"
                                    max-height="120"
                                    max-width="120"
                                    contain
                                    class="mx-auto"
                                />
                                <h2 class="text-h5 font-weight-bold text-primary mt-2">PinPointMe</h2>
                            </div>

                         

                            <v-form @submit.prevent="handleLogin" ref="formRef">
                                <v-alert
                                    v-if="error"
                                    type="error"
                                    variant="elevated"
                                    class="mb-4 text-body-1 font-weight-bold login-error-alert"
                                    color="error"
                                    border="start"
                                    prominent
                                    closable
                                    @click:close="error = ''"
                                >
                                    <span>{{ error }}</span>
                                </v-alert>

                                <!-- Google OAuth Error -->
                                <v-alert
                                    v-if="googleError"
                                    type="error"
                                    variant="tonal"
                                    class="mb-4"
                                    closable
                                    @click:close="googleError = ''"
                                >
                                    {{ googleError }}
                                </v-alert>

                                <!-- Google Sign-In Button -->
                                <v-btn
                                    variant="outlined"
                                    size="large"
                                    block
                                    :loading="isGoogleLoading"
                                    :disabled="isLoading"
                                    class="mb-4 google-btn"
                                    @click="handleGoogleLogin"
                                >
                                    <template v-slot:prepend>
                                        <img 
                                            src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" 
                                            alt="Google" 
                                            class="google-icon"
                                        >
                                    </template>
                                    <span class="google-btn-text">Continue with Google</span>
                                </v-btn>

                                <!-- Divider -->
                                <div class="divider-container">
                                    <v-divider class="divider-line"></v-divider>
                                    <span class="divider-text">or</span>
                                    <v-divider class="divider-line"></v-divider>
                                </div>

                                <v-text-field
                                    v-model="form.email"
                                    label="Email"
                                    type="email"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-email"
                                    :rules="[rules.required, rules.email]"
                                    :disabled="isLoading"
                                    @input="filterLoginEmailInput"
                                    class="mb-1"
                                />

                                <v-text-field
                                    v-model="form.password"
                                    label="Password"
                                    :type="showPassword ? 'text' : 'password'"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-lock"
                                    :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                    @click:append-inner="showPassword = !showPassword"
                                    :rules="[rules.required]"
                                    :disabled="isLoading"
                                    @input="filterLoginPasswordInput"
                                />
                                <div class="d-flex justify-end mb-2">
                                    <v-btn variant="text" color="primary" size="small" @click="showForgotPassword = true" class="pa-0">
                                        Forgot Password?
                                    </v-btn>
                                </div>

                                <v-btn
                                    type="submit"
                                    color="primary"
                                    size="large"
                                    block
                                    :loading="isLoading"
                                    class="mb-4"
                                >
                                    Sign In
                                </v-btn>
                            </v-form>

                            <div class="text-center mt-2">
                                <span class="text-grey-darken-1 text-caption" >Don't have an account?</span>
                                <v-btn variant="text" color="primary" size="x-small" @click="showRolePicker = true" class="ml-1">
                                    Register here
                                </v-btn>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>

            <!-- Forgot Password Dialog - OTP Based -->
            <v-dialog v-model="showForgotPassword" max-width="450" persistent>
                <v-card rounded="lg">
                    <v-card-title class="d-flex align-center pa-4 bg-primary">
                        <v-icon color="white" class="mr-2">mdi-lock-reset</v-icon>
                        <span class="text-white">Reset Password</span>
                        <v-spacer />
                        <v-btn icon variant="text" @click="closeForgotPassword" :disabled="forgotPasswordLoading">
                            <v-icon color="white">mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>

                    <v-card-text class="pa-6">
                        <!-- Step Indicator -->
                        <div class="d-flex justify-center mb-4">
                            <div class="d-flex align-center">
                                <v-avatar :color="resetStep >= 1 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span class="text-white text-caption font-weight-bold">1</span>
                                </v-avatar>
                                <div class="step-line" :class="{ 'active': resetStep >= 2 }"></div>
                                <v-avatar :color="resetStep >= 2 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span :class="resetStep >= 2 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">2</span>
                                </v-avatar>
                                <div class="step-line" :class="{ 'active': resetStep >= 3 }"></div>
                                <v-avatar :color="resetStep >= 3 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span :class="resetStep >= 3 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">3</span>
                                </v-avatar>
                            </div>
                        </div>

                        <!-- Success State -->
                        <div v-if="resetComplete" class="text-center py-4">
                            <v-icon size="64" color="success" class="mb-4">mdi-check-circle</v-icon>
                            <h3 class="text-h6 font-weight-bold mb-2">Password Reset Successful!</h3>
                            <p class="text-body-2 text-grey mb-4">
                                Your password has been changed successfully. You can now log in with your new password.
                            </p>
                        </div>

                        <!-- Step 1: Enter Email -->
                        <div v-else-if="resetStep === 1">
                            <p class="text-body-2 text-grey mb-4">
                                Enter your email address and we'll send you a verification code.
                            </p>

                            <v-alert
                                v-if="forgotPasswordError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="forgotPasswordError = ''"
                            >
                                {{ forgotPasswordError }}
                            </v-alert>

                            <v-form @submit.prevent="sendResetOtp" ref="forgotFormRef">
                                <v-text-field
                                    v-model="forgotPasswordEmail"
                                    label="Email Address"
                                    type="email"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-email"
                                    :rules="[rules.required, rules.email]"
                                    :disabled="forgotPasswordLoading"
                                    @input="filterForgotEmailInput"
                                    autofocus
                                />
                            </v-form>
                        </div>

                        <!-- Step 2: Enter OTP -->
                        <div v-else-if="resetStep === 2">
                            <div class="text-center mb-4">
                                <v-icon size="48" color="primary" class="mb-2">mdi-email-check</v-icon>
                                <p class="text-body-2 text-grey">
                                    We've sent a 6-digit code to <strong>{{ forgotPasswordEmail }}</strong>
                                </p>
                            </div>

                            <v-alert
                                v-if="forgotPasswordError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="forgotPasswordError = ''"
                            >
                                {{ forgotPasswordError }}
                            </v-alert>

                            <v-form @submit.prevent="verifyResetOtp" ref="otpFormRef">
                                <v-otp-input
                                    v-model="resetOtp"
                                    length="6"
                                    variant="outlined"
                                    :disabled="forgotPasswordLoading"
                                    class="mb-4"
                                />
                            </v-form>

                            <p class="text-caption text-grey text-center">
                                Didn't receive the code? 
                                <v-btn 
                                    variant="text" 
                                    color="primary" 
                                    size="small" 
                                    @click="resendResetOtp"
                                    :disabled="resendCooldown > 0 || forgotPasswordLoading"
                                >
                                    {{ resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend Code' }}
                                </v-btn>
                            </p>
                        </div>

                        <!-- Step 3: Enter New Password -->
                        <div v-else-if="resetStep === 3">
                            <p class="text-body-2 text-grey mb-4 text-center">
                                Create a new password for your account.
                            </p>

                            <v-alert
                                v-if="forgotPasswordError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="forgotPasswordError = ''"
                            >
                                {{ forgotPasswordError }}
                            </v-alert>

                            <v-form @submit.prevent="completePasswordReset" ref="newPasswordFormRef">
                                <v-text-field
                                    v-model="newPassword"
                                    label="New Password"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-lock"
                                    :append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                    @click:append-inner="showNewPassword = !showNewPassword"
                                    :rules="[rules.required, rules.minLength, rules.hasUppercase, rules.hasLowercase, rules.hasNumber, rules.hasSpecial]"
                                    :disabled="forgotPasswordLoading"
                                    class="mb-1"
                                />

                                <!-- Password Requirements Checklist -->
                                <div class="password-requirements mb-4 px-2">
                                    <p class="text-caption text-grey-darken-1 mb-2">Password must contain:</p>
                                    <div class="requirements-grid">
                                        <div class="requirement-item" :class="{ 'met': passwordChecks.length }">
                                            <v-icon size="14" :color="passwordChecks.length ? 'success' : 'grey-lighten-1'">
                                                {{ passwordChecks.length ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                            </v-icon>
                                            <span>At least 8 characters</span>
                                        </div>
                                        <div class="requirement-item" :class="{ 'met': passwordChecks.uppercase }">
                                            <v-icon size="14" :color="passwordChecks.uppercase ? 'success' : 'grey-lighten-1'">
                                                {{ passwordChecks.uppercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                            </v-icon>
                                            <span>One uppercase letter (A-Z)</span>
                                        </div>
                                        <div class="requirement-item" :class="{ 'met': passwordChecks.lowercase }">
                                            <v-icon size="14" :color="passwordChecks.lowercase ? 'success' : 'grey-lighten-1'">
                                                {{ passwordChecks.lowercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                            </v-icon>
                                            <span>One lowercase letter (a-z)</span>
                                        </div>
                                        <div class="requirement-item" :class="{ 'met': passwordChecks.number }">
                                            <v-icon size="14" :color="passwordChecks.number ? 'success' : 'grey-lighten-1'">
                                                {{ passwordChecks.number ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                            </v-icon>
                                            <span>One number (0-9)</span>
                                        </div>
                                        <div class="requirement-item" :class="{ 'met': passwordChecks.special }">
                                            <v-icon size="14" :color="passwordChecks.special ? 'success' : 'grey-lighten-1'">
                                                {{ passwordChecks.special ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                            </v-icon>
                                            <span>One special character (!@#$%...)</span>
                                        </div>
                                    </div>
                                </div>

                                <v-text-field
                                    v-model="confirmNewPassword"
                                    label="Confirm New Password"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-lock-check"
                                    :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                    @click:append-inner="showConfirmPassword = !showConfirmPassword"
                                    :rules="[rules.required, rules.passwordMatch]"
                                    :disabled="forgotPasswordLoading"
                                    :error="confirmNewPassword && confirmNewPassword !== newPassword"
                                />
                                <p v-if="confirmNewPassword && confirmNewPassword === newPassword" class="text-caption text-success mt-1 px-2">
                                    <v-icon size="14" color="success">mdi-check-circle</v-icon>
                                    Passwords match
                                </p>
                            </v-form>
                        </div>
                    </v-card-text>

                    <v-card-actions class="pa-4 pt-0">
                        <!-- Success State Actions -->
                        <template v-if="resetComplete">
                            <v-spacer />
                            <v-btn color="primary" @click="closeForgotPassword">
                                Back to Login
                            </v-btn>
                        </template>

                        <!-- Step 1 Actions -->
                        <template v-else-if="resetStep === 1">
                            <v-btn variant="text" @click="closeForgotPassword" :disabled="forgotPasswordLoading">
                                Cancel
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="sendResetOtp"
                                :loading="forgotPasswordLoading"
                            >
                                Send Code
                            </v-btn>
                        </template>

                        <!-- Step 2 Actions -->
                        <template v-else-if="resetStep === 2">
                            <v-btn variant="text" @click="resetStep = 1" :disabled="forgotPasswordLoading">
                                Back
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="verifyResetOtp"
                                :loading="forgotPasswordLoading"
                                :disabled="resetOtp.length !== 6"
                            >
                                Verify Code
                            </v-btn>
                        </template>

                        <!-- Step 3 Actions -->
                        <template v-else-if="resetStep === 3">
                            <v-btn variant="text" @click="resetStep = 2; resetOtp = ''" :disabled="forgotPasswordLoading">
                                Back
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="completePasswordReset"
                                :loading="forgotPasswordLoading"
                                :disabled="!isPasswordValid || !confirmNewPassword || confirmNewPassword !== newPassword"
                            >
                                Reset Password
                            </v-btn>
                        </template>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Registration Dialog -->
            <v-dialog v-model="showRegister" max-width="450" persistent>
                <v-card rounded="lg">
                    <v-card-title class="d-flex align-center pa-4 bg-primary">
                        <v-icon color="white" class="mr-2">mdi-account-plus</v-icon>
                        <span class="text-white">Register New Account</span>
                        <v-spacer />
                        <v-btn icon variant="text" @click="closeRegister" :disabled="registerLoading">
                            <v-icon color="white">mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>

                    <v-card-text class="pa-6">
                        <!-- Step Indicator -->
                        <div class="d-flex justify-center mb-4">
                            <div class="d-flex align-center">
                                <v-avatar :color="registerStep >= 1 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span class="text-white text-caption font-weight-bold">1</span>
                                </v-avatar>
                                <div class="step-line" :class="{ 'active': registerStep >= 2 }"></div>
                                <v-avatar :color="registerStep >= 2 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span :class="registerStep >= 2 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">2</span>
                                </v-avatar>
                                <div class="step-line" :class="{ 'active': registerStep >= 3 }"></div>
                                <v-avatar :color="registerStep >= 3 ? 'primary' : 'grey-lighten-2'" size="28">
                                    <span :class="registerStep >= 3 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">3</span>
                                </v-avatar>
                            </div>
                        </div>

                        <!-- Step 1: Enter Email -->
                        <div v-if="registerStep === 1">
                            <p class="text-body-2 text-grey mb-4">
                                Enter your email address to register. We'll send a verification code to confirm your email.
                            </p>

                            <v-alert
                                v-if="registerError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="registerError = ''"
                            >
                                {{ registerError }}
                            </v-alert>

                            <v-form @submit.prevent="sendRegisterOtp" ref="registerFormRef">
                                <v-text-field
                                    v-model="registerEmail"
                                    label="Email Address"
                                    type="email"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-email"
                                    :rules="[rules.required, rules.email]"
                                    :disabled="registerLoading"
                                    @input="filterRegisterEmailInput"
                                />
                            </v-form>
                        </div>

                        <!-- Step 2: Enter OTP -->
                        <div v-else-if="registerStep === 2">
                            <div class="text-center mb-4">
                                <v-icon size="48" color="primary" class="mb-2">mdi-email-check</v-icon>
                                <p class="text-body-2 text-grey">
                                    We've sent a <strong>6-digit verification code</strong> to:
                                </p>
                                <p class="text-body-2 font-weight-bold text-primary">{{ registerEmail }}</p>
                            </div>

                            <v-alert
                                v-if="registerError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="registerError = ''"
                            >
                                {{ registerError }}
                            </v-alert>

                            <v-form @submit.prevent="verifyRegisterOtp" ref="registerOtpFormRef">
                                <label class="text-caption font-weight-medium text-grey-darken-1 d-block mb-2 text-center">Enter Verification Code</label>
                                <v-otp-input
                                    v-model="registerOtp"
                                    length="6"
                                    variant="outlined"
                                    :disabled="registerLoading"
                                    class="mb-4"
                                />
                            </v-form>

                            <p class="text-caption text-grey text-center">
                                Didn't receive the code? 
                                <v-btn 
                                    variant="text" 
                                    color="primary" 
                                    size="small" 
                                    @click="resendRegisterOtp"
                                    :disabled="registerResendCooldown > 0 || registerLoading"
                                >
                                    {{ registerResendCooldown > 0 ? `Resend in ${registerResendCooldown}s` : 'Resend Code' }}
                                </v-btn>
                            </p>
                        </div>

                        <!-- Success State -->
                        <div v-else-if="registerStep === 3" class="text-center py-4 success-state">
                            <v-icon size="64" color="success" class="mb-4">mdi-check-circle</v-icon>
                            <h3 class="text-h6 font-weight-bold mb-2">Email Verified!</h3>
                            <p class="text-body-2 text-grey mb-4">
                                Your email has been verified. We've sent a <strong>temporary password</strong> to your email.
                            </p>
                          
                                <div class="text-caption">
                                    <v-icon size="16" class="mr-1">mdi-information</v-icon>
                                    <strong>Next steps:</strong>
                                    <ol class="mt-1 ml-2" style="line-height: 1.8;">
                                        <li>Check your email for the temporary password</li>
                                        <li>Log in using your email and temporary password</li>
                                        <li>You'll be asked to set a new password</li>
                                    </ol>
                                </div>
                           
                        </div>
                    </v-card-text>

                    <v-card-actions class="pa-4 pt-0">
                        <!-- Success State Actions -->
                        <template v-if="registerStep === 3">
                            <v-spacer />
                            <v-btn color="primary" @click="closeRegister">
                                Back to Login
                            </v-btn>
                        </template>

                        <!-- Step 1 Actions -->
                        <template v-else-if="registerStep === 1">
                            <v-btn variant="text" @click="closeRegister" :disabled="registerLoading">
                                Cancel
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="sendRegisterOtp"
                                :loading="registerLoading"
                            >
                                Send Code
                            </v-btn>
                        </template>

                        <!-- Step 2 Actions -->
                        <template v-else-if="registerStep === 2">
                            <v-btn variant="text" @click="registerStep = 1" :disabled="registerLoading">
                                Back
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="primary" 
                                @click="verifyRegisterOtp"
                                :loading="registerLoading"
                                :disabled="registerOtp.length !== 6"
                            >
                                Verify Email
                            </v-btn>
                        </template>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Role Picker Dialog -->
            <v-dialog v-model="showRolePicker" max-width="420" persistent>
                <v-card rounded="lg">
                    <v-card-title class="d-flex align-center pa-4 bg-primary">
                        <v-icon color="white" class="mr-2">mdi-account-plus</v-icon>
                        <span class="text-white">Create an Account</span>
                        <v-spacer />
                        <v-btn icon variant="text" @click="showRolePicker = false">
                            <v-icon color="white">mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>

                    <v-card-text class="pa-6">
                        <p class="text-body-2 text-grey-darken-1 mb-5 text-center">
                            Choose the type of account you'd like to create.
                        </p>

                        <!-- Patient / Visitor Option -->
                        <v-card
                            variant="outlined"
                            rounded="lg"
                            class="mb-4 role-option-card"
                            @click="handleStudentStaffRegister"
                            hover
                        >
                            <v-card-text class="d-flex align-center pa-4">
                                <v-avatar color="primary" size="48" class="mr-4">
                                    <v-icon color="white" size="24">mdi-hospital-box</v-icon>
                                </v-avatar>
                                <div class="flex-grow-1">
                                    <div class="text-subtitle-1 font-weight-bold">Patient / Visitor</div>
                                    <div class="text-caption text-grey">Register with any valid email address</div>
                                </div>
                                <v-icon color="grey" size="20">mdi-chevron-right</v-icon>
                            </v-card-text>
                        </v-card>

                        <!-- Responder Option -->
                        <v-card
                            variant="outlined"
                            rounded="lg"
                            class="role-option-card"
                            @click="handleRescuerRegister"
                            hover
                        >
                            <v-card-text class="d-flex align-center pa-4">
                                <v-avatar color="red-darken-1" size="48" class="mr-4">
                                    <v-icon color="white" size="24">mdi-shield-account</v-icon>
                                </v-avatar>
                                <div class="flex-grow-1">
                                    <div class="text-subtitle-1 font-weight-bold">Clinical Responder</div>
                                    <div class="text-caption text-grey">Register as clinical personnel (subject to approval)</div>
                                </div>
                                <v-icon color="grey" size="20">mdi-chevron-right</v-icon>
                            </v-card-text>
                        </v-card>

                        <p class="text-caption text-grey text-center mt-4">
                            <v-icon size="14" class="mr-1">mdi-information-outline</v-icon>
                            Sign-in and registration support external email addresses (not limited to school accounts).
                        </p>
                    </v-card-text>
                </v-card>
            </v-dialog>

            <!-- Clinical Responder Registration Dialog -->
            <v-dialog v-model="showRescuerRegister" max-width="450" persistent>
                <v-card rounded="lg">
                    <v-card-title class="d-flex align-center pa-4" style="background: #C62828;">
                        <v-icon color="white" class="mr-2">mdi-shield-account</v-icon>
                        <span class="text-white">Clinical Responder Registration</span>
                        <v-spacer />
                        <v-btn icon variant="text" @click="closeRescuerRegister" :disabled="rescuerLoading">
                            <v-icon color="white">mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>

                    <v-card-text class="pa-6">
                        <!-- Step Indicator -->
                        <div class="d-flex justify-center mb-4">
                            <div class="d-flex align-center">
                                <v-avatar :color="rescuerStep >= 1 ? 'red-darken-2' : 'grey-lighten-2'" size="28">
                                    <span class="text-white text-caption font-weight-bold">1</span>
                                </v-avatar>
                                <div class="step-line" :class="{ 'active': rescuerStep >= 2 }" style="--active-color: #C62828;"></div>
                                <v-avatar :color="rescuerStep >= 2 ? 'red-darken-2' : 'grey-lighten-2'" size="28">
                                    <span :class="rescuerStep >= 2 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">2</span>
                                </v-avatar>
                                <div class="step-line" :class="{ 'active': rescuerStep >= 3 }" style="--active-color: #C62828;"></div>
                                <v-avatar :color="rescuerStep >= 3 ? 'red-darken-2' : 'grey-lighten-2'" size="28">
                                    <span :class="rescuerStep >= 3 ? 'text-white' : 'text-grey'" class="text-caption font-weight-bold">3</span>
                                </v-avatar>
                            </div>
                        </div>

                        <!-- Step 1: Clinical Responder Details -->
                        <div v-if="rescuerStep === 1">
                            <p class="text-body-2 text-grey mb-4">
                                Fill in your details to register as clinical personnel. Your registration will require admin approval.
                            </p>

                            <v-alert
                                v-if="rescuerError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="rescuerError = ''"
                            >
                                {{ rescuerError }}
                            </v-alert>

                            <v-form ref="rescuerFormRef">
                                <v-text-field
                                    v-model="rescuerForm.first_name"
                                    label="First Name"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-account"
                                    :rules="[rules.required, rules.nameOnly]"
                                    :disabled="rescuerLoading"
                                    @input="filterNameInput('first_name')"
                                    class="mb-1"
                                />
                                <v-text-field
                                    v-model="rescuerForm.last_name"
                                    label="Last Name"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-account"
                                    :rules="[rules.required, rules.nameOnly]"
                                    :disabled="rescuerLoading"
                                    @input="filterNameInput('last_name')"
                                    class="mb-1"
                                />
                                <v-text-field
                                    v-model="rescuerForm.email"
                                    label="Email Address"
                                    type="email"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-email"
                                    :rules="[rules.required, rules.email]"
                                    :disabled="rescuerLoading"
                                    @input="filterRescuerEmailInput"
                                    hint="Any valid email address is accepted"
                                    persistent-hint
                                    class="mb-1"
                                />
                                <v-text-field
                                    v-model="rescuerForm.phone"
                                    label="Mobile Number"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-phone"
                                    placeholder="09XX XXX XXXX"
                                    :rules="[rules.required, rules.mobileNumber]"
                                    :disabled="rescuerLoading"
                                    @input="filterPhoneInput"
                                    maxlength="11"
                                    hint="Enter 11-digit number starting with 09"
                                    persistent-hint
                                    class="mb-1"
                                />
                                <v-text-field
                                    v-model="rescuerForm.organization"
                                    label="Organization / Affiliation (Optional)"
                                    variant="outlined"
                                    density="comfortable"
                                    prepend-inner-icon="mdi-domain"
                                    :disabled="rescuerLoading"
                                    @input="filterRescuerOrgInput"
                                    hint="e.g. BFP, Red Cross, MDRRMO, LGU"
                                    persistent-hint
                                />
                            </v-form>
                        </div>

                        <!-- Step 2: OTP Verification -->
                        <div v-else-if="rescuerStep === 2">
                            <div class="text-center mb-4">
                                <v-icon size="48" color="red-darken-2" class="mb-2">mdi-email-check</v-icon>
                                <p class="text-body-2 text-grey">
                                    We've sent a <strong>6-digit verification code</strong> to:
                                </p>
                                <p class="text-body-2 font-weight-bold" style="color: #C62828;">{{ rescuerForm.email }}</p>
                            </div>

                            <v-alert
                                v-if="rescuerError"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="rescuerError = ''"
                            >
                                {{ rescuerError }}
                            </v-alert>

                            <v-form @submit.prevent="verifyRescuerOtp" ref="rescuerOtpFormRef">
                                <label class="text-caption font-weight-medium text-grey-darken-1 d-block mb-2 text-center">Enter Verification Code</label>
                                <v-otp-input
                                    v-model="rescuerOtp"
                                    length="6"
                                    variant="outlined"
                                    :disabled="rescuerLoading"
                                    class="mb-4"
                                />
                            </v-form>

                            <p class="text-caption text-grey text-center">
                                Didn't receive the code? 
                                <v-btn 
                                    variant="text" 
                                    color="red-darken-2" 
                                    size="small" 
                                    @click="resendRescuerOtp"
                                    :disabled="rescuerResendCooldown > 0 || rescuerLoading"
                                >
                                    {{ rescuerResendCooldown > 0 ? `Resend in ${rescuerResendCooldown}s` : 'Resend Code' }}
                                </v-btn>
                            </p>
                        </div>

                        <!-- Step 3: Success -->
                        <div v-else-if="rescuerStep === 3" class="text-center py-4 success-state">
                            <h3 class="text-h6 font-weight-bold mb-2">Registration Submitted!</h3>
                            <p class="text-body-2 text-grey mb-4">
                                Your email has been verified. We've sent a <strong>temporary password</strong> to your email.
                            </p>
                            <v-alert
                                type="info"
                                variant="tonal"
                                class="mb-4 text-left"
                                density="compact"
                            >
                                <div class="text-caption">
                                    <v-icon size="16" class="mr-1">mdi-information</v-icon>
                                    <strong>Next steps:</strong>
                                    <ol class="mt-1 ml-2" style="line-height: 1.8;">
                                        <li>Check your email for the temporary password</li>
                                        <li>Wait for an admin to approve your account</li>
                                        <li>Once approved, log in with your email and temporary password</li>
                                        <li>You'll be asked to set a new password on first login</li>
                                    </ol>
                                </div>
                            </v-alert>
                            <v-alert
                                type="warning"
                                variant="tonal"
                                class="text-left"
                                density="compact"
                            >
                                <div class="text-caption">
                                    Your account requires <strong>admin approval</strong> before you can log in. You will receive an email notification once approved.
                                </div>
                            </v-alert>
                        </div>
                    </v-card-text>

                    <v-card-actions class="pa-4 pt-0">
                        <!-- Success State Actions -->
                        <template v-if="rescuerStep === 3">
                            <v-spacer />
                            <v-btn color="red-darken-2" @click="closeRescuerRegister">
                                Back to Login
                            </v-btn>
                        </template>

                        <!-- Step 1 Actions -->
                        <template v-else-if="rescuerStep === 1">
                            <v-btn variant="text" @click="closeRescuerRegister" :disabled="rescuerLoading">
                                Cancel
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="red-darken-2" 
                                @click="sendRescuerOtp"
                                :loading="rescuerLoading"
                            >
                                Send Code
                            </v-btn>
                        </template>

                        <!-- Step 2 Actions -->
                        <template v-else-if="rescuerStep === 2">
                            <v-btn variant="text" @click="rescuerStep = 1" :disabled="rescuerLoading">
                                Back
                            </v-btn>
                            <v-spacer />
                            <v-btn 
                                color="red-darken-2" 
                                @click="verifyRescuerOtp"
                                :loading="rescuerLoading"
                                :disabled="rescuerOtp.length !== 6"
                            >
                                Verify Email
                            </v-btn>
                        </template>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Toast Notification -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">
                        Close
                    </v-btn>
                </template>
            </v-snackbar>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { initializePushNotifications, isPushSupported, getNotificationPermission } from '@/Utilities/pushNotifications';
import { initializeFCMForUser } from '@/Utilities/firebase';

// Capacitor & Google Auth
import { GoogleAuth } from '@codetrix-studio/capacitor-google-auth';
import { Capacitor } from '@capacitor/core';

const page = usePage();

// Check if user is already authenticated from Inertia shared data
const authUser = computed(() => page.props?.auth?.user);

// Form state
const formRef = ref(null);
const form = ref({
    email: '',
    password: '',
});

// Inertia form for session-based login
const inertiaForm = useForm({
    email: '',
    password: '',
});

// UI state
const isLoading = ref(false);
const error = ref('');
const showPassword = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Google Auth state
const isGoogleLoading = ref(false);
const googleError = ref('');

// Forgot Password state
const showForgotPassword = ref(false);
const forgotPasswordEmail = ref('');
const forgotPasswordError = ref('');
const forgotPasswordLoading = ref(false);
const forgotFormRef = ref(null);
const newPasswordFormRef = ref(null);
const resetStep = ref(1);
const resetOtp = ref('');
const resetToken = ref('');
const resetComplete = ref(false);
const newPassword = ref('');
const confirmNewPassword = ref('');
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const resendCooldown = ref(0);
let cooldownInterval = null;

// Register state
const showRegister = ref(false);
const registerStep = ref(1);
const registerEmail = ref('');
const registerOtp = ref('');
const registerToken = ref('');
const registerPassword = ref('');
const registerConfirmPassword = ref('');
const registerLoading = ref(false);

// Role Picker state
const showRolePicker = ref(false);

// Rescuer Registration state
const showRescuerRegister = ref(false);
const rescuerStep = ref(1);
const rescuerForm = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    organization: '',
});
const rescuerOtp = ref('');
const rescuerLoading = ref(false);
const rescuerError = ref('');
const rescuerFormRef = ref(null);
const rescuerOtpFormRef = ref(null);
const rescuerResendCooldown = ref(0);
let rescuerCooldownInterval = null;

// Handle Google OAuth login
const handleGoogleLogin = async () => {
    isGoogleLoading.value = true;
    googleError.value = '';

    // Check if the app is running as a Native APK
    if (Capacitor.isNativePlatform()) {
        try {
            // 1. Trigger the Native Android Google Picker
            const result = await GoogleAuth.signIn();
            console.log('Google Sign-In result:', JSON.stringify(result));
            // 2. Send the ID token and email to our Laravel backend via fetch
            const apiUrl = 'https://pinpointme.app/auth/google/callback/native';
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    token: result.authentication.idToken,
                    email: result.email
                })
            });
            const data = await response.json();
            console.log('Native Google response:', JSON.stringify(data));
            if (data.success) {
                // Initialize push notifications before redirecting (native Google login)
                try {
                    const pushResult = await initializePushNotifications();
                    console.log('[Login/Google] Push notifications:', pushResult.success ? 'enabled' : pushResult.reason);
                } catch (pushError) {
                    console.warn('[Login/Google] Push init error:', pushError);
                }
                window.location.href = data.redirect || 'https://pinpointme.app/user/scanner';
            } else if (data.needs_verification) {
                isGoogleLoading.value = false;
                googleError.value = data.message || 'Please verify your email first. Check your inbox for the verification link.';
            } else {
                isGoogleLoading.value = false;
                googleError.value = data.message || 'Google sign-in failed. Please try again.';
            }
        } catch (error) {
            console.error('Google Auth Error:', error);
            isGoogleLoading.value = false;
            googleError.value = 'Google sign-in was cancelled or failed. ' + error.message;
        }
    } else {
        // Standard Web Redirect for browser users
        window.location.href = '/auth/google';
    }
};
const registerError = ref('');
const registerFormRef = ref(null);
const registerOtpFormRef = ref(null);
const registerPasswordFormRef = ref(null);
const registerResendCooldown = ref(0);
let registerCooldownInterval = null;

// Validation rules
const rules = {
    required: (v) => !!v || 'This field is required',
    email: (v) => /.+@.+\..+/.test(v) || 'Please enter a valid email',
    minLength: (v) => (v && v.length >= 8) || 'Password must be at least 8 characters',
    hasUppercase: (v) => /[A-Z]/.test(v) || 'Must contain at least one uppercase letter',
    hasLowercase: (v) => /[a-z]/.test(v) || 'Must contain at least one lowercase letter',
    hasNumber: (v) => /[0-9]/.test(v) || 'Must contain at least one number',
    hasSpecial: (v) => /[!@#$%^&*(),.?":{}|<>]/.test(v) || 'Must contain at least one special character',
    passwordMatch: (v) => v === newPassword.value || 'Passwords do not match',
    nameOnly: (v) => !v || /^[a-zA-Z\s.,'\-ñÑ]+$/.test(v) || 'Name must contain letters only (no numbers or special characters)',
    mobileNumber: (v) => {
        if (!v) return 'Mobile number is required';
        const cleaned = v.replace(/[^0-9]/g, '');
        if (cleaned.length !== 11) return 'Enter exactly 11 digits (e.g., 09171234567)';
        if (!cleaned.startsWith('09')) return 'Number must start with 09';
        return true;
    },
};

// Password strength checker
const passwordChecks = computed(() => ({
    length: newPassword.value.length >= 8,
    uppercase: /[A-Z]/.test(newPassword.value),
    lowercase: /[a-z]/.test(newPassword.value),
    number: /[0-9]/.test(newPassword.value),
    special: /[!@#$%^&*(),.?":{}|<>]/.test(newPassword.value),
}));

const isPasswordValid = computed(() => {
    const checks = passwordChecks.value;
    return checks.length && checks.uppercase && checks.lowercase && checks.number && checks.special;
});

// Registration password strength checker
const registerPasswordChecks = computed(() => ({
    length: registerPassword.value.length >= 8,
    uppercase: /[A-Z]/.test(registerPassword.value),
    lowercase: /[a-z]/.test(registerPassword.value),
    number: /[0-9]/.test(registerPassword.value),
    special: /[!@#$%^&*(),.?":{}|<>]/.test(registerPassword.value),
}));

const isRegisterPasswordValid = computed(() => {
    const checks = registerPasswordChecks.value;
    return checks.length && checks.uppercase && checks.lowercase && checks.number && checks.special;
});

// Keep registration open to any valid email address.
rules.sdcaEmail = (v) => {
    if (!v) return true;
    return true;
};

// Add phone number validation rule
rules.phone = (v) => {
    if (!v) return 'Phone number is required';
    const cleaned = v.replace(/[^0-9]/g, '');
    return /^09[0-9]{9}$/.test(cleaned) || 'Enter a valid 11-digit phone number (e.g., 09171234567)';
};

// Check for existing session on mount
onMounted(() => {
    // Initialize GoogleAuth for native platforms to prevent white screen crashes
    if (Capacitor.isNativePlatform()) {
        try {
            GoogleAuth.initialize();
        } catch (error) {
            console.error('GoogleAuth initialization error:', error);
        }
    }

    const urlParams = new URLSearchParams(window.location.search);
    const logout = urlParams.get('logout');
    // Check for Google OAuth errors from backend
    const errors = page.props?.errors;
    if (errors?.google) {
        googleError.value = errors.google;
    }

    if (logout === 'true') {
        handleLogout();
    } else {
        // First check Inertia auth (server-side session)
        if (authUser.value) {
            const user = authUser.value;
            // Store user data in localStorage for components that need it
            const userData = {
                id: user.id,
                email: user.email,
                firstName: user.first_name || '',
                lastName: user.last_name || '',
                role: user.role || 'student',
                isAdmin: user.isAdmin === true || user.isAdmin === 1 || user.role === 'admin',
                profile_picture: user.profile_picture || null,
                contact_number: user.contact_number || '',
            };
            localStorage.setItem('userData', JSON.stringify(userData));
            
            // Redirect based on user role
            if (user.isAdmin === true || user.isAdmin === 1 || user.role === 'admin') {
                window.location.href = '/admin/dashboard';
            } else if (user.role === 'rescuer') {
                window.location.href = '/rescuer/dashboard';
            } else {
                window.location.href = '/user/scanner';
            }
            return;
        }
        
        // Clear any stale localStorage data if not authenticated on server
        localStorage.removeItem('userData');
        localStorage.removeItem('authToken');
        localStorage.removeItem('token');
    }
});

const handleLogout = () => {
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    localStorage.removeItem('lastRescueCode');
    localStorage.removeItem('lastRescueRequestId');
    localStorage.removeItem('lastRescueRequestTime');
    localStorage.removeItem('conversationId');
    localStorage.removeItem('chatId');
    window.history.replaceState({}, document.title, window.location.pathname);
};

const handleLogin = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    error.value = '';
    isLoading.value = true;

    // Update inertia form with current values
    inertiaForm.email = form.value.email.trim();
    inertiaForm.password = form.value.password;

    // Use Inertia form submission for session-based authentication
    inertiaForm.post('/login', {
        onSuccess: (page) => {
            // Get user data from the response
            const user = page.props?.auth?.user;
            
            if (user) {
                const userData = {
                    id: user.id,
                    email: user.email,
                    firstName: user.first_name || '',
                    lastName: user.last_name || '',
                    role: user.role || 'student',
                    isAdmin: user.isAdmin === true || user.isAdmin === 1 || user.role === 'admin',
                    profile_picture: user.profile_picture || null,
                    contact_number: user.contact_number || '',
                };

                localStorage.setItem('userData', JSON.stringify(userData));

                // Initialize push notifications after successful login
                initializePushNotifications().then(result => {
                    if (result.success) {
                        console.log('[Login] Push notifications enabled');
                    } else {
                        console.log('[Login] Push notifications not enabled:', result.reason);
                    }
                });

                // Initialize Firebase FCM for web users only
                // On native, FCM token is already stored in Firestore by initializeNativePush()
                if (!Capacitor.isNativePlatform()) {
                    initializeFCMForUser(user.id).then(result => {
                        if (result.success) {
                            console.log('[Login] Firebase FCM initialized, user marked as active');
                        } else {
                            console.log('[Login] Firebase FCM not initialized:', result.error);
                        }
                    });
                }

                toastMessage.value = 'Login successful!';
                toastColor.value = 'success';
                showToast.value = true;
            }
            
            isLoading.value = false;
            // The redirect is handled by the backend AuthController
        },
        onError: (errors) => {
            isLoading.value = false;
            if (errors.email) {
                error.value = errors.email;
            } else if (errors.password) {
                error.value = errors.password;
            } else {
                error.value = 'Login failed. Please check your credentials.';
            }
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

// Forgot Password handlers - OTP Based
const sendResetOtp = async () => {
    if (forgotFormRef.value) {
        const { valid } = await forgotFormRef.value.validate();
        if (!valid) return;
    }

    forgotPasswordError.value = '';
    forgotPasswordLoading.value = true;

    try {
        const response = await fetch('/api/auth/send-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: forgotPasswordEmail.value.trim(),
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            resetStep.value = 2;
            startResendCooldown();
            toastMessage.value = 'Verification code sent!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            forgotPasswordError.value = data.message || 'Failed to send verification code. Please try again.';
        }
    } catch (err) {
        console.error('Send OTP error:', err);
        forgotPasswordError.value = 'An error occurred. Please try again later.';
    } finally {
        forgotPasswordLoading.value = false;
    }
};

const verifyResetOtp = async () => {
    if (resetOtp.value.length !== 6) {
        forgotPasswordError.value = 'Please enter the 6-digit code';
        return;
    }

    forgotPasswordError.value = '';
    forgotPasswordLoading.value = true;

    try {
        const response = await fetch('/api/auth/verify-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: forgotPasswordEmail.value.trim(),
                otp: resetOtp.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            resetToken.value = data.token;
            resetStep.value = 3;
            toastMessage.value = 'Code verified!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            forgotPasswordError.value = data.message || 'Invalid verification code. Please try again.';
        }
    } catch (err) {
        console.error('Verify OTP error:', err);
        forgotPasswordError.value = 'An error occurred. Please try again later.';
    } finally {
        forgotPasswordLoading.value = false;
    }
};

const completePasswordReset = async () => {
    if (newPasswordFormRef.value) {
        const { valid } = await newPasswordFormRef.value.validate();
        if (!valid) return;
    }

    if (newPassword.value !== confirmNewPassword.value) {
        forgotPasswordError.value = 'Passwords do not match';
        return;
    }

    if (!isPasswordValid.value) {
        forgotPasswordError.value = 'Password does not meet all requirements';
        return;
    }

    forgotPasswordError.value = '';
    forgotPasswordLoading.value = true;

    try {
        const response = await fetch('/api/auth/complete-password-change', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: forgotPasswordEmail.value.trim(),
                token: resetToken.value,
                password: newPassword.value,
                password_confirmation: confirmNewPassword.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            resetComplete.value = true;
            toastMessage.value = 'Password reset successful!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            forgotPasswordError.value = data.message || 'Failed to reset password. Please try again.';
        }
    } catch (err) {
        console.error('Password reset error:', err);
        forgotPasswordError.value = 'An error occurred. Please try again later.';
    } finally {
        forgotPasswordLoading.value = false;
    }
};

const resendResetOtp = async () => {
    if (resendCooldown.value > 0) return;
    await sendResetOtp();
};

const startResendCooldown = () => {
    resendCooldown.value = 60;
    if (cooldownInterval) clearInterval(cooldownInterval);
    cooldownInterval = setInterval(() => {
        resendCooldown.value--;
        if (resendCooldown.value <= 0) {
            clearInterval(cooldownInterval);
            cooldownInterval = null;
        }
    }, 1000);
};

const closeForgotPassword = () => {
    showForgotPassword.value = false;
    forgotPasswordEmail.value = '';
    forgotPasswordError.value = '';
    resetStep.value = 1;
    resetOtp.value = '';
    resetToken.value = '';
    newPassword.value = '';
    confirmNewPassword.value = '';
    resetComplete.value = false;
    resendCooldown.value = 0;
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
        cooldownInterval = null;
    }
};

// Registration handlers
const sendRegisterOtp = async () => {
    if (registerFormRef.value) {
        const { valid } = await registerFormRef.value.validate();
        if (!valid) return;
    }

    registerError.value = '';
    registerLoading.value = true;

    try {
        const response = await fetch('/api/auth/register-send-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: registerEmail.value.trim(),
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            registerStep.value = 2;
            startRegisterResendCooldown();
            toastMessage.value = 'Verification code sent to your email!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            registerError.value = data.message || 'Failed to send verification code. Please try again.';
        }
    } catch (err) {
        console.error('Send register OTP error:', err);
        registerError.value = 'An error occurred. Please try again later.';
    } finally {
        registerLoading.value = false;
    }
};

const verifyRegisterOtp = async () => {
    if (registerOtp.value.length !== 6) {
        registerError.value = 'Please enter the 6-digit code';
        return;
    }

    registerError.value = '';
    registerLoading.value = true;

    try {
        const response = await fetch('/api/auth/register-verify-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: registerEmail.value.trim(),
                otp: registerOtp.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            registerToken.value = data.token;
            registerStep.value = 3;
            toastMessage.value = 'Email verified! Check your email for the temporary password.';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            registerError.value = data.message || 'Invalid verification code. Please try again.';
        }
    } catch (err) {
        console.error('Verify register OTP error:', err);
        registerError.value = 'An error occurred. Please try again later.';
    } finally {
        registerLoading.value = false;
    }
};

const completeRegister = async () => {
    if (registerPasswordFormRef.value) {
        const { valid } = await registerPasswordFormRef.value.validate();
        if (!valid) return;
    }

    if (registerPassword.value !== registerConfirmPassword.value) {
        registerError.value = 'Passwords do not match';
        return;
    }

    if (!isRegisterPasswordValid.value) {
        registerError.value = 'Password does not meet all requirements';
        return;
    }

    registerError.value = '';
    registerLoading.value = true;

    try {
        const response = await fetch('/api/auth/register-complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: registerEmail.value.trim(),
                token: registerToken.value,
                password: registerPassword.value,
                password_confirmation: registerConfirmPassword.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            registerStep.value = 4;
            toastMessage.value = 'Registration successful!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            registerError.value = data.message || 'Failed to complete registration. Please try again.';
        }
    } catch (err) {
        console.error('Complete register error:', err);
        registerError.value = 'An error occurred. Please try again later.';
    } finally {
        registerLoading.value = false;
    }
};

const resendRegisterOtp = async () => {
    if (registerResendCooldown.value > 0) return;
    await sendRegisterOtp();
};

const startRegisterResendCooldown = () => {
    registerResendCooldown.value = 60;
    if (registerCooldownInterval) clearInterval(registerCooldownInterval);
    registerCooldownInterval = setInterval(() => {
        registerResendCooldown.value--;
        if (registerResendCooldown.value <= 0) {
            clearInterval(registerCooldownInterval);
            registerCooldownInterval = null;
        }
    }, 1000);
};

const closeRegister = () => {
    showRegister.value = false;
    registerEmail.value = '';
    registerError.value = '';
    registerStep.value = 1;
    registerOtp.value = '';
    registerToken.value = '';
    registerPassword.value = '';
    registerConfirmPassword.value = '';
    registerResendCooldown.value = 0;
    if (registerCooldownInterval) {
        clearInterval(registerCooldownInterval);
        registerCooldownInterval = null;
    }
};

// Role Picker handlers
const handleStudentStaffRegister = () => {
    showRolePicker.value = false;
    showRegister.value = true;
};

const handleRescuerRegister = () => {
    showRolePicker.value = false;
    showRescuerRegister.value = true;
};

// Input filter: strip numbers from name fields in real-time
// Strip emoji and extended pictographic characters from a string
const stripEmojis = (str) => {
    if (!str) return str;
    return str.replace(/\p{Extended_Pictographic}|\p{Emoji_Presentation}/gu, '');
};

const filterNameInput = (field) => {
    if (rescuerForm.value[field]) {
        // Strip anything that is NOT a letter, space, period, comma, apostrophe, hyphen, or ñ/Ñ
        // This removes numbers, special characters, and emojis in real-time
        rescuerForm.value[field] = rescuerForm.value[field].replace(/[^a-zA-Z\s.,'\-ñÑ]/g, '');
    }
};

// Filter email: allow only printable ASCII (blocks emojis and non-ASCII)
// Strips characters that are not valid in an email address (allows a-z, 0-9, . _ % + - @)
const stripInvalidEmailChars = (val) => val.replace(/[^a-zA-Z0-9._%+\-@]/g, '');

const filterLoginEmailInput = () => {
    if (form.value.email) {
        form.value.email = stripInvalidEmailChars(form.value.email);
    }
};

const filterLoginPasswordInput = () => {
    if (form.value.password) {
        form.value.password = stripEmojis(form.value.password);
    }
};

const filterRescuerEmailInput = () => {
    if (rescuerForm.value.email) {
        rescuerForm.value.email = stripInvalidEmailChars(rescuerForm.value.email);
    }
};

// Org field: allow only letters, numbers, spaces, and basic punctuation (. , - / ( ) ' &)
const filterRescuerOrgInput = () => {
    if (rescuerForm.value.organization) {
        rescuerForm.value.organization = rescuerForm.value.organization.replace(/[^a-zA-Z0-9\s.,\-/()'&ñÑ]/g, '');
    }
};

const filterRegisterEmailInput = () => {
    if (registerEmail.value) {
        registerEmail.value = stripInvalidEmailChars(registerEmail.value);
    }
};

const filterForgotEmailInput = () => {
    if (forgotPasswordEmail.value) {
        forgotPasswordEmail.value = stripInvalidEmailChars(forgotPasswordEmail.value);
    }
};

// Input filter: strip non-digits from phone field in real-time
const filterPhoneInput = () => {
    if (rescuerForm.value.phone) {
        rescuerForm.value.phone = rescuerForm.value.phone.replace(/[^0-9]/g, '');
    }
};

// Rescuer Registration handlers
const sendRescuerOtp = async () => {
    if (rescuerFormRef.value) {
        const { valid } = await rescuerFormRef.value.validate();
        if (!valid) return;
    }

    rescuerError.value = '';
    rescuerLoading.value = true;

    try {
        const response = await fetch('/api/auth/rescuer-register-send-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                first_name: rescuerForm.value.first_name.trim(),
                last_name: rescuerForm.value.last_name.trim(),
                email: rescuerForm.value.email.trim(),
                phone: rescuerForm.value.phone.replace(/[^0-9]/g, ''),
                organization: (rescuerForm.value.organization || '').trim() || null,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            rescuerStep.value = 2;
            startRescuerResendCooldown();
            toastMessage.value = 'Verification code sent to your email!';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            if (data.errors) {
                const firstError = Object.values(data.errors)[0];
                rescuerError.value = Array.isArray(firstError) ? firstError[0] : firstError;
            } else {
                rescuerError.value = data.message || 'Failed to send verification code. Please try again.';
            }
        }
    } catch (err) {
        console.error('Send rescuer OTP error:', err);
        rescuerError.value = 'An error occurred. Please try again later.';
    } finally {
        rescuerLoading.value = false;
    }
};

const verifyRescuerOtp = async () => {
    if (rescuerOtp.value.length !== 6) {
        rescuerError.value = 'Please enter the 6-digit code';
        return;
    }

    rescuerError.value = '';
    rescuerLoading.value = true;

    try {
        const response = await fetch('/api/auth/rescuer-register-verify-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: rescuerForm.value.email.trim(),
                otp: rescuerOtp.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            rescuerStep.value = 3;
            toastMessage.value = 'Email verified! Check your email for the temporary password.';
            toastColor.value = 'success';
            showToast.value = true;
        } else {
            rescuerError.value = data.message || 'Invalid verification code. Please try again.';
        }
    } catch (err) {
        console.error('Verify rescuer OTP error:', err);
        rescuerError.value = 'An error occurred. Please try again later.';
    } finally {
        rescuerLoading.value = false;
    }
};

const resendRescuerOtp = async () => {
    if (rescuerResendCooldown.value > 0) return;
    await sendRescuerOtp();
};

const startRescuerResendCooldown = () => {
    rescuerResendCooldown.value = 60;
    if (rescuerCooldownInterval) clearInterval(rescuerCooldownInterval);
    rescuerCooldownInterval = setInterval(() => {
        rescuerResendCooldown.value--;
        if (rescuerResendCooldown.value <= 0) {
            clearInterval(rescuerCooldownInterval);
            rescuerCooldownInterval = null;
        }
    }, 1000);
};

const closeRescuerRegister = () => {
    showRescuerRegister.value = false;
    rescuerForm.value = { first_name: '', last_name: '', email: '', phone: '', organization: '' };
    rescuerError.value = '';
    rescuerStep.value = 1;
    rescuerOtp.value = '';
    rescuerResendCooldown.value = 0;
    if (rescuerCooldownInterval) {
        clearInterval(rescuerCooldownInterval);
        rescuerCooldownInterval = null;
    }
};
</script>

<style scoped>
/* Enhanced login error alert */
.login-error-alert {
    border-left: 6px solid #b71c1c !important;
    background: #fff5f5 !important;
    color: #b71c1c !important;
    box-shadow: 0 2px 12px 0 rgba(183,28,28,0.08);
    border-radius: 10px;
    padding: 14px 18px !important;
    align-items: center;
    display: flex;
}
/* Left Panel Styles */
.left-panel {
    background: linear-gradient(135deg, #3674B5 0%, #2D5F96 50%, #1E4A7A 100%);
    position: relative;
    overflow: hidden;
}

.left-panel::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
    animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.1) rotate(180deg); }
}

.logo-container {
    position: relative;
    z-index: 1;
}

.logo-image {
    width: 200px;
    height: 200px;
    object-fit: contain;
    filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.3));
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.brand-title {
    font-size: 3rem;
    font-weight: 800;
    letter-spacing: 4px;
    text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
}

.brand-subtitle {
    font-size: 1.1rem;
    letter-spacing: 8px;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

/* Right Panel (Form) Styles */
.right-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: linear-gradient(145deg, #f5f7fa 0%, #e4e8ec 100%);
}

@media (max-width: 1279px) {
    .right-panel {
        min-height: 100vh;
    }
}

/* Mobile Logo Styles */
.mobile-logo-section {
    padding-top: 40px;
    padding-bottom: 16px;
}

.mobile-logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

.mobile-brand-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #3674B5;
    letter-spacing: 2px;
}

/* Text color utilities */
.text-white-darken-1 {
    color: rgba(255, 255, 255, 0.95) !important;
}

.text-white-darken-2 {
    color: rgba(255, 255, 255, 0.85) !important;
}

/* Login Card Styles */
.login-card {
    width: 100%;
    max-width: 420px;
    border-radius: 16px !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1) !important;
}

.login-card .v-card-title {
    padding: 24px 24px 8px 24px;
}

.login-card .v-card-text {
    padding: 16px 24px 24px 24px;
}

/* Form field improvements */
:deep(.v-text-field .v-field) {
    border-radius: 10px;
}

:deep(.v-text-field .v-field__outline) {
    --v-field-border-opacity: 0.3;
}

/* Button styles */
.login-btn {
    text-transform: none;
    font-weight: 600;
    font-size: 1rem;
    letter-spacing: 0.5px;
}

/* Forgot password link */
.forgot-link {
    color: #3674B5;
    text-decoration: none;
    font-size: 0.875rem;
    cursor: pointer;
    transition: color 0.2s ease;
}

.forgot-link:hover {
    color: #2D5F96;
    text-decoration: underline;
}

/* Step indicator styles */
.step-line {
    width: 40px;
    height: 3px;
    background-color: #e0e0e0;
    margin: 0 8px;
    border-radius: 2px;
    transition: background-color 0.3s ease;
}

.step-line.active {
    background-color: #3674B5;
}

/* OTP Input styling */
:deep(.v-otp-input) {
    justify-content: center;
}

:deep(.v-otp-input input) {
    font-size: 1.5rem;
    font-weight: 600;
}

/* Password requirements styling */
.password-requirements {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
}

.requirements-grid {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.requirement-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    color: #9e9e9e;
    transition: color 0.2s ease;
}

.requirement-item.met {
    color: #4caf50;
}

.requirement-item span {
    line-height: 1.2;
}

/* Google Sign-In Button */
.google-btn {
    text-transform: none !important;
    font-weight: 500;
    letter-spacing: 0;
    border: 2px solid #dadce0 !important;
    color: #3c4043 !important;
    background-color: #fff !important;
    min-height: 48px;
    border-radius: 8px !important;
    transition: all 0.2s ease;
}

.google-btn:hover {
    background-color: #f8f9fa !important;
    border-color: #c4c4c4 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.google-btn .google-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.google-btn .google-btn-text {
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Mobile: Shorter text */
@media (max-width: 400px) {
    .google-btn .google-btn-text {
        font-size: 0.875rem;
    }
}

/* Divider Styles */
.divider-container {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
    gap: 12px;
}

.divider-line {
    flex: 1;
}

.divider-text {
    color: #9e9e9e;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Registration Success State */
.success-state {
    position: relative;
    z-index: 10;
    background-color: white;
    min-height: 200px;
}

/* Role Picker Option Cards */
.role-option-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border-color: #e0e0e0 !important;
}

.role-option-card:hover {
    border-color: #3674B5 !important;
    box-shadow: 0 4px 16px rgba(54, 116, 181, 0.15);
    transform: translateY(-1px);
}

/* Rescuer step line active color override */
.step-line.active[style*="--active-color"] {
    background-color: var(--active-color, #3674B5) !important;
}
</style>
