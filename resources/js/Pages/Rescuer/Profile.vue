<template>
    <v-app class="bg-user-gradient-light">
        <!-- Header -->
        <div class="profile-page-header">
            <div class="header-content">
                <v-btn icon variant="text" @click="drawer = !drawer" class="menu-btn desktop-only">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>
                <div class="header-title">
                    <div class="title-with-icon">
                        <v-icon size="24" class="mr-2">mdi-account-circle</v-icon>
                        <h1>Profile</h1>
                    </div>
                    <p>Manage your account</p>
                </div>
                <v-btn icon variant="text" @click="refreshStatus" class="placeholder-btn desktop-only" :loading="refreshingStatus">
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </div>
        </div>

        <!-- Navigation Drawer -->
        <RescuerMenu v-model="drawer" />

        <v-main class="profile-main">
            <!-- Loading State -->
            <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
            </div>

            <v-container v-else fluid class="profile-container">
                <!-- Profile Header Card -->
                <v-card 
                    class="mb-4 rounded-xl overflow-hidden" 
                    elevation="0"
                    color="white"
                >
                    <div class="profile-header-bg pa-6 text-center">
                        <!-- Avatar with Edit Button -->
                        <div class="position-relative d-inline-block">
                            <v-avatar size="100" class="elevation-4 avatar-ring">
                                <v-img 
                                    v-if="profilePictureUrl" 
                                    :src="profilePictureUrl" 
                                    cover
                                ></v-img>
                                <span v-else class="text-h4 font-weight-bold text-white">
                                    {{ getInitials }}
                                </span>
                            </v-avatar>
                            <v-btn
                                icon
                                size="small"
                                color="primary"
                                class="position-absolute edit-avatar-btn"
                                @click="openPhotoDialog"
                            >
                                <v-icon size="16">mdi-camera</v-icon>
                            </v-btn>
                        </div>
                        
                        <h2 class="text-h5 font-weight-bold mt-3 text-white">{{ fullName }}</h2>
                        <p class="text-body-2 mb-0" style="color: #ffffff !important;">
                            {{ profile.email }}
                        </p>
                        
                        <!-- Status Display -->
                        <div class="d-flex flex-column align-center gap-2 mt-3">
                            <v-chip 
                                :color="getStatusChipColor(profile.status)"
                                variant="flat"
                                size="small"
                            >
                                <v-icon start size="14">
                                    {{ getStatusIcon(profile.status) }}
                                </v-icon>
                                {{ getStatusLabel(profile.status) }}
                            </v-chip>
                            
                            <!-- Admin Restriction Notice -->
                            <v-alert
                                v-if="profile.admin_restricted"
                                type="warning"
                                density="compact"
                                variant="tonal"
                                class="mt-2 text-caption"
                            >
                                <v-icon size="16" class="mr-1">mdi-lock</v-icon>
                                Status set by administrator. You cannot accept rescue requests until your status is changed to "Available".
                            </v-alert>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <v-row no-gutters class="text-center py-3 stats-row">
                        <v-col cols="4">
                            <div class="text-h5 font-weight-bold text-primary">{{ stats.completed }}</div>
                            <div class="text-caption text-grey">Completed</div>
                        </v-col>
                        <v-divider vertical></v-divider>
                        <v-col cols="4">
                            <div class="text-h5 font-weight-bold text-warning">{{ stats.inProgress }}</div>
                            <div class="text-caption text-grey">In Progress</div>
                        </v-col>
                        <v-divider vertical></v-divider>
                        <v-col cols="4">
                            <div class="text-h5 font-weight-bold text-success">{{ stats.avgTime }}</div>
                            <div class="text-caption text-grey">Avg Time</div>
                        </v-col>
                    </v-row>
                </v-card>

                <!-- Personal Information -->
                <v-card class="mb-4 rounded-xl" elevation="0">
                    <div class="d-flex align-center py-3 px-4">
                        <v-icon color="primary" class="mr-2" size="20">mdi-account</v-icon>
                        <span class="text-subtitle-1 font-weight-bold">Personal Information</span>
                    </div>
                    <v-divider></v-divider>
                    <div class="pa-4">
                        <v-form ref="formRef" v-model="formValid">
                            <v-row dense>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="profile.first_name"
                                        label="First Name"
                                        :readonly="!isEditing"
                                        :rules="[rules.required, rules.nameOnly]"
                                        variant="outlined"
                                        density="compact"
                                        hide-details="auto"
                                        class="mb-3"
                                        @keypress="preventInvalidNameChars"
                                        @input="sanitizeNameField('first_name')"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="profile.last_name"
                                        label="Last Name"
                                        :readonly="!isEditing"
                                        :rules="[rules.required, rules.nameOnly]"
                                        variant="outlined"
                                        density="compact"
                                        hide-details="auto"
                                        class="mb-3"
                                        @keypress="preventInvalidNameChars"
                                        @input="sanitizeNameField('last_name')"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="profile.email"
                                        label="Email"
                                        readonly
                                        disabled
                                        variant="outlined"
                                        density="compact"
                                        hide-details="auto"
                                        class="mb-3 non-editable-field"
                                        prepend-inner-icon="mdi-email-outline"
                                        bg-color="grey-lighten-3"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="profile.contact_number"
                                        label="Contact Number"
                                        :readonly="!isEditing"
                                        variant="outlined"
                                        density="compact"
                                        :rules="isEditing ? [rules.phoneNumber] : []"
                                        hint="Mobile number (11 digits starting with 09)"
                                        :persistent-hint="isEditing"
                                        placeholder="09171234567"
                                        class="mb-3"
                                        prepend-inner-icon="mdi-phone-outline"
                                        type="tel"
                                        inputmode="numeric"
                                        maxlength="11"
                                        @input="profile.contact_number = profile.contact_number.replace(/\\D/g, '')"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="profile.employee_id"
                                        label="Responder ID"
                                        readonly
                                        disabled
                                        variant="outlined"
                                        density="compact"
                                        hide-details="auto"
                                        class="non-editable-field"
                                        prepend-inner-icon="mdi-badge-account-outline"
                                        bg-color="grey-lighten-3"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-form>
                        
                        <div class="profile-action-buttons mt-3">
                            <v-btn
                                v-if="!isEditing"
                                block
                                color="primary"
                                variant="tonal"
                                @click="isEditing = true"
                                class="rounded-lg"
                            >
                                <v-icon start>mdi-pencil</v-icon>
                                Edit Information
                            </v-btn>
                            <v-row v-else dense>
                                <v-col cols="6">
                                    <v-btn
                                        block
                                        color="grey"
                                        variant="outlined"
                                        @click="cancelEdit"
                                        class="rounded-lg"
                                    >
                                        Cancel
                                    </v-btn>
                                </v-col>
                                <v-col cols="6">
                                    <v-btn
                                        block
                                        color="primary"
                                        variant="flat"
                                        :loading="saving"
                                        :disabled="!formValid"
                                        @click="saveProfile"
                                        class="rounded-lg"
                                    >
                                        Save Changes
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </div>
                    </div>
                </v-card>

                <!-- Settings Section -->
                <v-card class="mb-4 rounded-xl" elevation="0">
                    <v-expansion-panels flat v-model="settingsPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="py-3 px-4">
                                <div class="d-flex align-center">
                                    <v-icon color="primary" class="mr-2" size="20">mdi-cog</v-icon>
                                    <span class="text-subtitle-1 font-weight-bold">Settings</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text>
                                <v-list class="bg-transparent pa-0 settings-list">
                                    <!-- Security Dropdown -->
                                    <v-list-item class="px-2 py-3 rounded-lg mb-2 setting-item">
                                        <template v-slot:prepend>
                                            <v-avatar color="red" variant="tonal" size="36" class="mr-3">
                                                <v-icon size="20">mdi-shield-lock</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="text-body-2 font-weight-medium">Security</v-list-item-title>
                                        <v-list-item-subtitle class="text-caption">Manage your account security</v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-btn
                                                size="small"
                                                color="primary"
                                                variant="tonal"
                                                @click="openChangePasswordDialog"
                                                class="text-caption"
                                            >
                                                <v-icon size="16" start>mdi-lock-outline</v-icon>
                                                Change Password
                                            </v-btn>
                                        </template>
                                    </v-list-item>
                                    <v-list-item class="px-2 py-3 rounded-lg setting-item">
                                        <template v-slot:prepend>
                                            <v-avatar color="grey-darken-1" variant="tonal" size="36" class="mr-3">
                                                <v-icon size="20">mdi-theme-light-dark</v-icon>
                                            </v-avatar>
                                        </template>
                                        <v-list-item-title class="text-body-2 font-weight-medium">Dark Mode</v-list-item-title>
                                        <v-list-item-subtitle class="text-caption">Easier on eyes at night</v-list-item-subtitle>
                                        <template v-slot:append>
                                            <v-switch
                                                v-model="settings.darkMode"
                                                color="primary"
                                                hide-details
                                                inset
                                                @change="updateSetting('DarkMode')"
                                            />
                                        </template>
                                    </v-list-item>
                                </v-list>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Help Improve PinPointMe Section -->
                <v-card class="mb-4 rounded-xl section-card" elevation="0">
                    <v-expansion-panels flat v-model="systemFeedbackPanel">
                        <v-expansion-panel>
                            <v-expansion-panel-title class="panel-title-mobile">
                                <div class="d-flex align-center">
                                    <v-avatar color="deep-orange" size="32" class="mr-3">
                                        <v-icon color="white" size="18">mdi-lightbulb-on-outline</v-icon>
                                    </v-avatar>
                                    <span class="text-body-1 text-sm-subtitle-1 font-weight-bold">Help Improve</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="panel-content-mobile">
                                <p class="text-body-2 text-grey-darken-1 mb-3">Report a bug or suggest an improvement to help us enhance the rescuer experience.</p>

                                <!-- Previous submissions -->
                                <div v-if="userSystemFeedbacks.length > 0" class="mb-3">
                                    <p class="text-caption font-weight-bold text-grey-darken-2 mb-2">Your Reports</p>
                                    <div
                                        v-for="fb in userSystemFeedbacks.slice(0, 3)"
                                        :key="fb.id"
                                        class="sf-history-item mb-2 pa-3 rounded-lg"
                                    >
                                        <div class="d-flex align-center justify-space-between">
                                            <div class="d-flex align-center">
                                                <v-icon size="16" :color="fb.category === 'bug' ? '#C62828' : '#1565C0'" class="mr-2">
                                                    {{ fb.category === 'bug' ? 'mdi-bug' : 'mdi-lightbulb-on-outline' }}
                                                </v-icon>
                                                <span class="text-caption font-weight-medium text-truncate" style="max-width: 180px;">{{ fb.area || fb.category }}</span>
                                            </div>
                                            <v-chip
                                                size="x-small"
                                                :color="getSfStatusColor(fb.status)"
                                                variant="tonal"
                                            >{{ getSfStatusLabel(fb.status) }}</v-chip>
                                        </div>
                                        <p class="text-caption text-grey mt-1 text-truncate">{{ fb.description }}</p>
                                    </div>
                                </div>

                                <v-btn
                                    block
                                    color="#3674B5"
                                    variant="tonal"
                                    @click="openSystemFeedbackDialog"
                                    class="rounded-lg mobile-btn"
                                    size="large"
                                >
                                    <v-icon start>mdi-send-outline</v-icon>
                                    Submit a Report
                                </v-btn>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <!-- Logout Button -->
                <v-btn
                    block
                    color="#D32F2F"
                    variant="flat"
                    size="large"
                    class="rounded-xl mb-4 text-white"
                    @click="showLogoutDialog = true"
                >
                    <v-icon start color="white">mdi-logout</v-icon>
                    <span style="color: white !important;">Logout</span>
                </v-btn>
            </v-container>
        </v-main>

        <!-- Bottom Navigation -->
        <RescuerBottomNav :notification-count="0" :message-count="unreadMessageCount" />

        <!-- Logout Confirmation Dialog -->
        <v-dialog v-model="showLogoutDialog" max-width="340" persistent>
            <v-card class="rounded-xl">
                <v-card-text class="text-center pa-6">
                    <v-icon size="64" color="error" class="mb-4">mdi-logout-variant</v-icon>
                    <h3 class="text-h6 font-weight-bold mb-2">Logout</h3>
                    <p class="text-body-2 text-grey mb-0">Are you sure you want to logout?</p>
                </v-card-text>
                <v-card-actions class="pa-4 pt-0">
                    <v-row dense>
                        <v-col cols="6">
                            <v-btn
                                block
                                variant="outlined"
                                :disabled="loggingOut"
                                @click="showLogoutDialog = false"
                                class="rounded-lg"
                            >
                                Cancel
                            </v-btn>
                        </v-col>
                        <v-col cols="6">
                            <v-btn
                                block
                                color="error"
                                variant="flat"
                                :loading="loggingOut"
                                @click="handleLogout"
                                class="rounded-lg"
                            >
                                Logout
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Photo Upload Dialog -->
        <v-dialog v-model="photoDialog" max-width="340" persistent>
            <v-card class="rounded-xl">
                <v-card-title class="d-flex align-center pa-4">
                    <span class="text-subtitle-1 font-weight-bold">Profile Photo</span>
                    <v-spacer></v-spacer>
                    <v-btn icon size="small" variant="text" @click="cancelPhotoUpload">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-divider></v-divider>
                
                <v-card-text class="text-center pa-4">
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        style="display: none;"
                        @change="handleFileSelect"
                    />
                    
                    <v-avatar size="120" class="mb-4 elevation-2">
                        <v-img 
                            v-if="previewUrl || profilePictureUrl" 
                            :src="previewUrl || profilePictureUrl" 
                            cover
                        ></v-img>
                        <span v-else class="text-h3 font-weight-bold">{{ getInitials }}</span>
                    </v-avatar>
                    
                    <v-btn
                        block
                        variant="outlined"
                        color="primary"
                        class="mb-2 rounded-lg"
                        @click="triggerFileInput"
                    >
                        <v-icon start>mdi-camera</v-icon>
                        {{ previewUrl ? 'Change Photo' : 'Select Photo' }}
                    </v-btn>
                    
                    <v-btn
                        v-if="profilePictureUrl && !previewUrl"
                        block
                        variant="text"
                        color="error"
                        class="rounded-lg"
                        @click="confirmDeletePhoto"
                    >
                        <v-icon start>mdi-delete</v-icon>
                        Remove Photo
                    </v-btn>
                </v-card-text>
                
                <v-card-actions v-if="selectedFile" class="pa-4 pt-0">
                    <v-btn
                        block
                        color="primary"
                        variant="flat"
                        :loading="uploadingPhoto"
                        @click="uploadPhoto"
                        class="rounded-lg"
                    >
                        Save Photo
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Photo Confirmation -->
        <v-dialog v-model="deletePhotoDialog" max-width="320" persistent>
            <v-card class="rounded-xl">
                <v-card-text class="text-center pa-6">
                    <v-icon size="48" color="error" class="mb-3">mdi-delete-alert</v-icon>
                    <h3 class="text-subtitle-1 font-weight-bold mb-2">Remove Photo?</h3>
                    <p class="text-body-2 text-grey mb-0">This action cannot be undone.</p>
                </v-card-text>
                <v-card-actions class="pa-4 pt-0">
                    <v-row dense>
                        <v-col cols="6">
                            <v-btn
                                block
                                variant="outlined"
                                @click="deletePhotoDialog = false"
                                class="rounded-lg"
                            >
                                Cancel
                            </v-btn>
                        </v-col>
                        <v-col cols="6">
                            <v-btn
                                block
                                color="error"
                                variant="flat"
                                :loading="deletingPhoto"
                                @click="deletePhoto"
                                class="rounded-lg"
                            >
                                Remove
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Change Password Dialog - OTP Based (ChangePassword.vue style) -->
        <v-dialog v-model="passwordDialog" max-width="440" persistent>
            <v-card rounded="xl" class="pwd-dialog-card" elevation="8">
                <!-- Gradient Header -->
                <div class="pwd-card-header">
                    <div class="pwd-header-icon-wrap">
                        <v-icon size="36" color="white">mdi-shield-lock</v-icon>
                    </div>
                    <h1 class="pwd-header-title">Change Password</h1>
                    <p class="pwd-header-subtitle">Verify your identity to update your password</p>
                    <v-btn icon variant="text" size="small" class="pwd-close-btn" @click="closePasswordDialog" :disabled="sendingOtp || verifyingOtp || changingPassword">
                        <v-icon color="white" size="20">mdi-close</v-icon>
                    </v-btn>
                </div>

                <v-card-text class="pwd-card-body pa-6 pa-sm-8">
                    <!-- Success State -->
                    <div v-if="passwordComplete" class="text-center py-4">
                        <div class="pwd-success-check-wrap mb-4">
                            <div class="pwd-success-check-circle">
                                <v-icon size="48" color="white">mdi-check</v-icon>
                            </div>
                        </div>
                        <h2 class="text-h5 font-weight-bold mb-2" style="color: #13294B;">Password Updated!</h2>
                        <p class="text-body-2 text-medium-emphasis mb-6">
                            Your password has been changed successfully.
                        </p>
                        <v-btn
                            size="large"
                            color="#3674B5"
                            @click="closePasswordDialog"
                            class="pwd-action-btn px-10"
                            rounded="lg"
                            block
                        >
                            <v-icon start>mdi-check-circle</v-icon>
                            Done
                        </v-btn>
                    </div>

                    <!-- Step 1: Request OTP -->
                    <div v-else-if="passwordStep === 1" class="text-center">
                        <v-icon size="56" color="primary" class="mb-3">mdi-email-send-outline</v-icon>
                        <p class="text-body-2 text-medium-emphasis mb-1">We'll send a verification code to</p>
                        <p class="text-body-2 font-weight-bold mb-6" style="color: #3674B5;">{{ profile.email }}</p>

                        <v-alert v-if="passwordError" type="error" variant="tonal" class="mb-4 text-left" closable @click:close="passwordError = ''">
                            {{ passwordError }}
                        </v-alert>

                        <v-btn
                            block
                            size="large"
                            color="#3674B5"
                            :loading="sendingOtp"
                            @click="sendPasswordOtp"
                            class="pwd-action-btn"
                            rounded="lg"
                        >
                            <v-icon start>mdi-send</v-icon>
                            Send Verification Code
                        </v-btn>
                        <v-btn variant="text" color="grey" block size="small" class="mt-3 text-none" @click="closePasswordDialog">
                            Cancel
                        </v-btn>
                    </div>

                    <!-- Step 2: Enter OTP -->
                    <div v-else-if="passwordStep === 2">
                        <div class="text-center mb-5">
                            <v-icon size="48" color="primary" class="mb-2">mdi-email-check-outline</v-icon>
                            <p class="text-body-2 text-medium-emphasis">Enter the 6-digit code sent to</p>
                            <p class="text-body-2 font-weight-bold" style="color: #3674B5;">{{ profile.email }}</p>
                        </div>

                        <v-alert v-if="passwordError" type="error" variant="tonal" class="mb-4 text-left" closable @click:close="passwordError = ''">
                            {{ passwordError }}
                        </v-alert>

                        <div class="pwd-otp-container mb-4">
                            <v-otp-input
                                v-model="otpCode"
                                length="6"
                                variant="outlined"
                                :disabled="verifyingOtp"
                                class="pwd-otp-input-custom"
                            />
                        </div>

                        <div class="text-center mb-5">
                            <span class="text-caption text-medium-emphasis">Didn't receive it? </span>
                            <v-btn
                                variant="text"
                                color="#3674B5"
                                size="small"
                                density="compact"
                                @click="sendPasswordOtp"
                                :disabled="resendCountdown > 0 || sendingOtp"
                                class="text-none"
                            >
                                {{ resendCountdown > 0 ? `Resend in ${resendCountdown}s` : 'Resend Code' }}
                            </v-btn>
                        </div>

                        <v-btn
                            block
                            size="large"
                            color="#3674B5"
                            :loading="verifyingOtp"
                            :disabled="otpCode.length !== 6"
                            @click="verifyPasswordOtp"
                            class="pwd-action-btn"
                            rounded="lg"
                        >
                            <v-icon start>mdi-check-circle</v-icon>
                            Verify Code
                        </v-btn>
                        <v-btn variant="text" color="grey" block size="small" class="mt-3 text-none" @click="passwordStep = 1; otpCode = ''">
                            <v-icon start size="16">mdi-arrow-left</v-icon>
                            Go Back
                        </v-btn>
                    </div>

                    <!-- Step 3: Enter New Password -->
                    <div v-else-if="passwordStep === 3">
                        <v-alert v-if="passwordError" type="error" variant="tonal" class="mb-4" closable @click:close="passwordError = ''">
                            {{ passwordError }}
                        </v-alert>

                        <v-form ref="passwordFormRef" v-model="passwordFormValid">
                            <label class="pwd-field-label">New Password</label>
                            <v-text-field
                                v-model="passwordForm.new_password"
                                :type="showNewPassword ? 'text' : 'password'"
                                placeholder="Enter new password"
                                variant="outlined"
                                :append-inner-icon="showNewPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                @click:append-inner="showNewPassword = !showNewPassword"
                                class="mb-1 pwd-password-field"
                                hide-details
                                density="comfortable"
                                rounded="lg"
                                :disabled="changingPassword"
                            />

                            <!-- Password Strength Bar -->
                            <div class="pwd-strength-bar-wrap mt-2 mb-4" v-if="passwordForm.new_password.length > 0">
                                <div class="pwd-strength-bar-track">
                                    <div
                                        class="pwd-strength-bar-fill"
                                        :style="{ width: pwdStrength + '%', background: pwdStrengthGradient }"
                                    ></div>
                                </div>
                                <div class="d-flex justify-space-between align-center mt-1">
                                    <span class="text-caption" :style="{ color: pwdStrengthColor }">{{ pwdStrengthText }}</span>
                                    <span class="text-caption text-medium-emphasis">{{ pwdStrength }}%</span>
                                </div>
                            </div>

                            <!-- Password Requirements (2-col grid) -->
                            <div class="pwd-requirements-list mb-5" v-if="passwordForm.new_password.length > 0">
                                <div class="pwd-req-item" :class="{ met: passwordChecks.length }">
                                    <v-icon size="16" :color="passwordChecks.length ? '#3674B5' : '#ccc'">
                                        {{ passwordChecks.length ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                    <span>At least 8 characters</span>
                                </div>
                                <div class="pwd-req-item" :class="{ met: passwordChecks.uppercase }">
                                    <v-icon size="16" :color="passwordChecks.uppercase ? '#3674B5' : '#ccc'">
                                        {{ passwordChecks.uppercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                    <span>One uppercase letter</span>
                                </div>
                                <div class="pwd-req-item" :class="{ met: passwordChecks.lowercase }">
                                    <v-icon size="16" :color="passwordChecks.lowercase ? '#3674B5' : '#ccc'">
                                        {{ passwordChecks.lowercase ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                    <span>One lowercase letter</span>
                                </div>
                                <div class="pwd-req-item" :class="{ met: passwordChecks.number }">
                                    <v-icon size="16" :color="passwordChecks.number ? '#3674B5' : '#ccc'">
                                        {{ passwordChecks.number ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                    <span>One number</span>
                                </div>
                                <div class="pwd-req-item" :class="{ met: passwordChecks.special }">
                                    <v-icon size="16" :color="passwordChecks.special ? '#3674B5' : '#ccc'">
                                        {{ passwordChecks.special ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                    </v-icon>
                                    <span>One special character</span>
                                </div>
                            </div>

                            <label class="pwd-field-label">Confirm Password</label>
                            <v-text-field
                                v-model="passwordForm.confirm_password"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                placeholder="Confirm new password"
                                variant="outlined"
                                :append-inner-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                @click:append-inner="showConfirmPassword = !showConfirmPassword"
                                class="pwd-password-field"
                                hide-details
                                density="comfortable"
                                rounded="lg"
                                :disabled="changingPassword"
                            />

                            <!-- Password match indicator -->
                            <div v-if="passwordForm.confirm_password && passwordForm.confirm_password.length > 0" class="mt-2 mb-5">
                                <div class="pwd-req-item" :class="{ met: passwordForm.confirm_password === passwordForm.new_password }">
                                    <v-icon size="16" :color="passwordForm.confirm_password === passwordForm.new_password ? '#3674B5' : '#ef5350'">
                                        {{ passwordForm.confirm_password === passwordForm.new_password ? 'mdi-check-circle' : 'mdi-close-circle' }}
                                    </v-icon>
                                    <span :style="{ color: passwordForm.confirm_password === passwordForm.new_password ? '#3674B5' : '#ef5350' }">
                                        {{ passwordForm.confirm_password === passwordForm.new_password ? 'Passwords match' : 'Passwords do not match' }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="mb-5"></div>

                            <v-btn
                                block
                                size="large"
                                color="#3674B5"
                                :loading="changingPassword"
                                :disabled="!isPasswordValid || !passwordForm.confirm_password || passwordForm.confirm_password !== passwordForm.new_password"
                                @click="changePassword"
                                class="pwd-action-btn"
                                rounded="lg"
                            >
                                <v-icon start>mdi-lock-check</v-icon>
                                Update Password
                            </v-btn>
                            <v-btn variant="text" color="grey" block size="small" class="mt-3 text-none" @click="passwordStep = 2; otpCode = ''" :disabled="changingPassword">
                                <v-icon start size="16">mdi-arrow-left</v-icon>
                                Go Back
                            </v-btn>
                        </v-form>
                    </div>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- System Feedback Dialog -->
        <v-dialog v-model="systemFeedbackDialog" max-width="460" persistent>
            <v-card class="sf-dialog-card" elevation="4">
                <!-- Header -->
                <div class="sf-dialog-header">
                    <div class="sf-dialog-header-content">
                        <v-icon size="22" color="white" class="mr-2">mdi-lightbulb-on-outline</v-icon>
                        <div>
                            <h3 class="sf-dialog-title">Report &amp; Feedback</h3>
                            <p class="sf-dialog-subtitle">Help us improve the rescuer experience</p>
                        </div>
                    </div>
                    <v-btn icon variant="text" size="x-small" @click="closeSystemFeedbackDialog">
                        <v-icon size="18" color="rgba(255,255,255,0.8)">mdi-close</v-icon>
                    </v-btn>
                </div>

                <div class="sf-dialog-body">
                    <v-form ref="sfFormRef" v-model="sfFormValid">
                        <!-- Category Toggle -->
                        <p class="sf-field-label">What would you like to do?</p>
                        <v-btn-toggle
                            v-model="sfForm.category"
                            mandatory
                            color="#3674B5"
                            rounded="lg"
                            density="comfortable"
                            class="sf-category-toggle mb-4"
                        >
                            <v-btn value="bug" class="sf-cat-btn">
                                <v-icon start size="18">mdi-bug</v-icon>
                                Report a Bug
                            </v-btn>
                            <v-btn value="improvement" class="sf-cat-btn">
                                <v-icon start size="18">mdi-lightbulb-on-outline</v-icon>
                                Suggest Improvement
                            </v-btn>
                        </v-btn-toggle>

                        <!-- Area Dropdown -->
                        <p class="sf-field-label">Related rescuer feature</p>
                        <v-select
                            v-model="sfForm.area"
                            :items="sfAreaOptions"
                            item-title="label"
                            item-value="value"
                            :placeholder="sfForm.category === 'bug' ? 'Where did the issue occur?' : 'Which feature?'"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            color="#3674B5"
                            hide-details="auto"
                            class="mb-4"
                            clearable
                        >
                            <template v-slot:item="{ item, props }">
                                <v-list-item v-bind="props">
                                    <template v-slot:prepend>
                                        <v-icon size="18" color="#3674B5">{{ getSfAreaIcon(item.value) }}</v-icon>
                                    </template>
                                </v-list-item>
                            </template>
                        </v-select>

                        <!-- Subject -->
                        <p class="sf-field-label">{{ sfForm.category === 'bug' ? 'Brief title for the issue' : 'Brief title for your suggestion' }}</p>
                        <v-text-field
                            v-model="sfForm.subject"
                            :placeholder="sfForm.category === 'bug' ? 'e.g. Map not loading properly' : 'e.g. Add quick response templates'"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            color="#3674B5"
                            hide-details="auto"
                            :rules="[v => !!v || 'Title is required', v => (v && v.length >= 5) || 'Min 5 characters', v => (v && v.length <= 100) || 'Max 100 characters']"
                            counter="100"
                            maxlength="100"
                            class="mb-4"
                        />

                        <!-- Description -->
                        <p class="sf-field-label">{{ sfForm.category === 'bug' ? 'Describe the issue' : 'Describe your suggestion' }}</p>
                        <v-textarea
                            v-model="sfForm.description"
                            :placeholder="sfForm.category === 'bug' ? 'What happened? What did you expect to happen instead?' : 'How would your suggestion improve the rescuer experience?'"
                            variant="outlined"
                            density="comfortable"
                            rows="4"
                            auto-grow
                            max-rows="8"
                            maxlength="3000"
                            counter="3000"
                            rounded="lg"
                            color="#3674B5"
                            hide-details="auto"
                            :rules="[v => !!v || 'Description is required', v => (v && v.length >= 10) || 'Min 10 characters']"
                            class="mb-4"
                        />

                        <!-- Upload Media -->
                        <p class="sf-field-label">Attach screenshot or proof <span class="text-grey">(optional)</span></p>
                        <div
                            class="sf-upload-area mb-4"
                            @click="triggerSfFileInput"
                            @dragover.prevent
                            @drop.prevent="handleSfFileDrop"
                        >
                            <input
                                ref="sfFileInputRef"
                                type="file"
                                accept="image/*,video/mp4,video/mov,application/pdf"
                                style="display: none;"
                                @change="handleSfFileSelect"
                            />
                            <div v-if="!sfPreviewUrl" class="sf-upload-placeholder">
                                <v-icon size="36" color="#9E9E9E">mdi-cloud-upload-outline</v-icon>
                                <p class="text-caption text-grey mt-1">Tap to upload or drag & drop</p>
                                <p class="text-caption text-grey-lighten-1" style="font-size: 0.7rem;">JPG, PNG, GIF, MP4, PDF — max 10MB</p>
                            </div>
                            <div v-else class="sf-upload-preview">
                                <v-img v-if="sfPreviewIsImage" :src="sfPreviewUrl" max-height="120" contain class="rounded-lg" />
                                <div v-else class="d-flex align-center">
                                    <v-icon size="24" color="#3674B5" class="mr-2">mdi-file-check</v-icon>
                                    <span class="text-caption font-weight-medium">{{ sfSelectedFile?.name }}</span>
                                </div>
                                <v-btn
                                    icon
                                    size="x-small"
                                    color="error"
                                    variant="tonal"
                                    class="sf-upload-remove"
                                    @click.stop="removeSfFile"
                                >
                                    <v-icon size="14">mdi-close</v-icon>
                                </v-btn>
                            </div>
                        </div>

                        <!-- Submit -->
                        <v-btn
                            block
                            variant="flat"
                            color="#3674B5"
                            size="large"
                            rounded="lg"
                            :loading="submittingSf"
                            :disabled="!sfFormValidComputed"
                            class="sf-submit-btn"
                            @click="submitSystemFeedbackForm"
                        >
                            <v-icon start>mdi-send</v-icon>
                            Submit Report
                        </v-btn>
                    </v-form>
                </div>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar
            v-model="snackbar.show"
            :color="snackbar.color"
            :timeout="3000"
            location="top"
        >
            {{ snackbar.message }}
        </v-snackbar>


    </v-app>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';
import { apiFetch, getProfilePictureUrl, updateUser, uploadProfilePicture, deleteProfilePicture, getUnreadMessageCount, submitSystemFeedback, getUserSystemFeedbacks } from '@/Composables/useApi';
import { useDisplay } from 'vuetify';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import { useDarkMode } from '@/Composables/useDarkMode';
import { setUserActiveStatus } from '@/Utilities/firebase';


// Auth check
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// Dark mode composable
const { isDark, set: setDarkMode } = useDarkMode();

// Mobile detection
const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

// Notification Alert System
const { playNotificationSound } = useNotificationAlert();

// Refs
const formRef = ref(null);
const passwordFormRef = ref(null);
const fileInput = ref(null);

// State
const drawer = ref(false);
const loading = ref(true);
const saving = ref(false);
const changingPassword = ref(false);
const isEditing = ref(false);
const formValid = ref(true);
const passwordFormValid = ref(true);
const showLogoutDialog = ref(false);
const photoDialog = ref(false);
const deletePhotoDialog = ref(false);
const uploadingPhoto = ref(false);
const deletingPhoto = ref(false);
const loggingOut = ref(false);
const unreadMessageCount = ref(0);
const refreshingStatus = ref(false);

// Help Improve Feedback (Mobile)
const feedbackFormRef = ref(null);
const feedbackFormValid = ref(true);
const submittingFeedback = ref(false);
const feedbackCategoryError = ref(false);
const feedbackForm = ref({
    category: '',
    area: '',
    description: '',
});

// System Feedback Dialog States
const systemFeedbackDialog = ref(false);
const sfFormRef = ref(null);
const sfFileInputRef = ref(null);
const submittingSf = ref(false);
const sfFormValid = ref(true);
const systemFeedbacks = ref([]);
const loadingSystemFeedbacks = ref(false);
const sfSelectedFile = ref(null);
const sfPreviewUrl = ref(null);
const sfPreviewIsImage = ref(false);
const sfForm = reactive({
    category: 'issue',
    area: '',
    subject: '',
    description: '',
});

// Panel states
const securityPanel = ref(null);
const settingsPanel = ref(null);
const systemFeedbackPanel = ref(null);

// Password change OTP flow
const passwordDialog = ref(false);
const passwordStep = ref(1); // 1, 2, 3 for request, otp, password
const passwordComplete = ref(false);
const passwordError = ref('');
const otpCode = ref('');
const verificationToken = ref('');
const sendingOtp = ref(false);
const verifyingOtp = ref(false);
const resendCountdown = ref(0);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
let countdownInterval = null;

// Photo upload
const selectedFile = ref(null);
const previewUrl = ref(null);

const profile = ref({
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    contact_number: '',
    employee_id: '',
    avatar: null,
    is_active: true,
    status: 'available',
    admin_restricted: false,
});

const originalProfile = ref({});

const passwordForm = ref({
    current_password: '',
    new_password: '',
    confirm_password: '',
});

const settings = ref({
    notifications: true,
    sound: true,
    darkMode: false,
});

const stats = ref({
    completed: 0,
    inProgress: 0,
    avgTime: '0m',
});

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
});

// Validation rules
const rules = {
    required: v => !!v || 'Required',
    email: v => /.+@.+\..+/.test(v) || 'Invalid email',
    minLength: v => (v && v.length >= 8) || 'Min 8 characters',
    hasUppercase: v => /[A-Z]/.test(v) || 'Must contain uppercase letter',
    hasLowercase: v => /[a-z]/.test(v) || 'Must contain lowercase letter',
    hasNumber: v => /[0-9]/.test(v) || 'Must contain a number',
    hasSpecial: v => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v) || 'Must contain special character',
    passwordMatch: v => v === passwordForm.value.new_password || 'Passwords do not match',
    // Name validation - only letters and spaces allowed (no numbers, special chars, emojis)
    nameOnly: (v) => {
        if (!v) return true; // Allow empty (use 'required' rule separately if needed)
        if (!/^[a-zA-Z\s]+$/.test(v)) {
            return 'Only letters and spaces are allowed';
        }
        return true;
    },
    // Phone number validation - only accepts 11 numeric digits
    phoneNumber: (v) => {
        if (!v) return true; // Optional field
        
        // Remove all non-digit characters
        const cleaned = v.replace(/\D/g, '');
        
        // Must be exactly 11 digits and start with 09
        if (cleaned.length !== 11) {
            return 'Must be exactly 11 digits';
        }
        
        if (!cleaned.startsWith('09')) {
            return 'Must start with 09';
        }
        
        // Ensure it's purely numeric (no letters)
        if (!/^\d{11}$/.test(cleaned)) {
            return 'Must contain only numbers';
        }
        
        return true;
    }
};

// Password validation checks for visual feedback
const passwordChecks = computed(() => {
    const pwd = passwordForm.value.new_password || '';
    return {
        length: pwd.length >= 8,
        uppercase: /[A-Z]/.test(pwd),
        lowercase: /[a-z]/.test(pwd),
        number: /[0-9]/.test(pwd),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd),
    };
});

// Real-time prevention - blocks special chars, numbers, and emojis in name fields
const preventInvalidNameChars = (event) => {
    const char = event.key || String.fromCharCode(event.keyCode || event.which);
    // Allow control keys (Backspace, Delete, Tab, arrows, etc.)
    if (char.length > 1) return;
    // Only allow letters and spaces
    if (!/^[a-zA-Z\s]$/.test(char)) {
        event.preventDefault();
        showSnackbar('Special characters are not allowed in this field', 'warning');
    }
};

// Sanitize pasted/input content - strips invalid characters from name fields
const sanitizeNameField = (field) => {
    const val = profile.value[field];
    if (!val) return;
    const sanitized = val.replace(/[^a-zA-Z\s]/g, '');
    if (sanitized !== val) {
        profile.value[field] = sanitized;
        showSnackbar('Special characters are not allowed in this field', 'warning');
    }
};

// Computed
const fullName = computed(() => {
    return `${profile.value.first_name} ${profile.value.last_name}`.trim() || 'Rescuer';
});

const getInitials = computed(() => {
    const first = profile.value.first_name?.[0] || '';
    const last = profile.value.last_name?.[0] || '';
    return (first + last).toUpperCase() || 'R';
});

const profilePictureUrl = computed(() => {
    const picturePath = profile.value.avatar || profile.value.profile_picture;
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

// Password validation
const isPasswordValid = computed(() => {
    const checks = passwordChecks.value;
    return checks.length && checks.uppercase && checks.lowercase && checks.number && checks.special;
});

const pwdStrength = computed(() => {
    const checks = passwordChecks.value;
    let score = 0;
    if (checks.length) score += 20;
    if (checks.uppercase) score += 20;
    if (checks.lowercase) score += 20;
    if (checks.number) score += 20;
    if (checks.special) score += 20;
    return score;
});

const pwdStrengthColor = computed(() => {
    const s = pwdStrength.value;
    if (s <= 20) return '#ef5350';
    if (s <= 40) return '#ff9800';
    if (s <= 60) return '#fdd835';
    if (s <= 80) return '#66bb6a';
    return '#43a047';
});

const pwdStrengthGradient = computed(() => {
    const s = pwdStrength.value;
    if (s <= 20) return '#ef5350';
    if (s <= 40) return 'linear-gradient(90deg, #ef5350, #ff9800)';
    if (s <= 60) return 'linear-gradient(90deg, #ef5350, #ff9800, #fdd835)';
    if (s <= 80) return 'linear-gradient(90deg, #ff9800, #fdd835, #66bb6a)';
    return 'linear-gradient(90deg, #66bb6a, #43a047)';
});

const pwdStrengthText = computed(() => {
    const s = pwdStrength.value;
    if (s <= 20) return 'Weak';
    if (s <= 40) return 'Fair';
    if (s <= 60) return 'Good';
    if (s <= 80) return 'Strong';
    return 'Very Strong';
});

// System feedback form validation
const sfFormValidComputed = computed(() => {
    return sfForm.subject?.trim() && 
           sfForm.description?.trim() && 
           sfFormValid.value;
});

// System feedback area options for rescuers
const sfAreaOptions = computed(() => [
    { label: 'Rescue Dashboard', value: 'dashboard', icon: 'mdi-view-dashboard' },
    { label: 'Active Rescue Management', value: 'rescue_management', icon: 'mdi-alert-octagon' },
    { label: 'Chat with Users', value: 'chat', icon: 'mdi-chat' },
    { label: 'Profile & Settings', value: 'profile', icon: 'mdi-account-cog' },
    { label: 'Status Management', value: 'status', icon: 'mdi-toggle-switch' },
    { label: 'General App Experience', value: 'general', icon: 'mdi-application' }
]);

// Get icon for system feedback area
const getSfAreaIcon = (area) => {
    const option = sfAreaOptions.value.find(opt => opt.value === area);
    return option?.icon || 'mdi-help-circle';
};

// System feedback history helpers
const userSystemFeedbacks = computed(() => systemFeedbacks.value || []);

const getSfStatusColor = (status) => {
    switch (status) {
        case 'pending': return 'orange';
        case 'in_progress': return 'blue';
        case 'resolved': return 'green';
        case 'closed': return 'grey';
        default: return 'grey';
    }
};

const getSfStatusLabel = (status) => {
    switch (status) {
        case 'pending': return 'Pending';
        case 'in_progress': return 'In Progress';
        case 'resolved': return 'Resolved';
        case 'closed': return 'Closed';
        default: return 'Unknown';
    }
};

// Methods
const refreshStatus = async () => {
    refreshingStatus.value = true;
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (!userData.id) return;

        const response = await apiFetch(`/api/users/${userData.id}`, { method: 'GET' });
        console.log('[Profile] Refresh status API Response:', response);
        
        if (response && response.success && response.data) {
            const serverData = response.data;
            console.log('[Profile] Server data:', serverData);
            
            // Update profile status
            profile.value.status = serverData.status || 'available';
            profile.value.admin_restricted = ['off_duty', 'unavailable'].includes(serverData.status);
            
            // Update local storage
            const updatedUserData = {
                ...userData,
                status: serverData.status,
            };
            localStorage.setItem('userData', JSON.stringify(updatedUserData));
            
            showSnackbar('Status refreshed', 'success');
        }
    } catch (error) {
        console.error('Error refreshing status:', error);
        showSnackbar('Failed to refresh status', 'error');
    } finally {
        refreshingStatus.value = false;
    }
};

// Help Improve Feedback Methods
const submitFeedback = async () => {
    // Validate category
    if (!feedbackForm.value.category) {
        feedbackCategoryError.value = true;
        return;
    }
    feedbackCategoryError.value = false;

    // Validate form
    const { valid } = await feedbackFormRef.value.validate();
    if (!valid) return;

    submittingFeedback.value = true;
    try {
        const formData = new FormData();
        formData.append('user_id', authUser.value?.id);
        formData.append('category', feedbackForm.value.category);
        formData.append('description', feedbackForm.value.description);
        if (feedbackForm.value.area) formData.append('area', feedbackForm.value.area);

        const response = await submitSystemFeedback(formData);

        if (response?.success) {
            showSnackbar('Thank you! Your feedback has been submitted successfully.', 'success');
            resetFeedbackForm();
        } else {
            showSnackbar(response?.message || 'Failed to submit. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Failed to submit feedback:', error);
        showSnackbar('Something went wrong. Please try again later.', 'error');
    } finally {
        submittingFeedback.value = false;
    }
};

const resetFeedbackForm = () => {
    feedbackForm.value = { category: '', area: '', description: '' };
    feedbackFormRef.value?.resetValidation();
    feedbackCategoryError.value = false;
};

// System Feedback Dialog Methods
const openSystemFeedbackDialog = () => {
    systemFeedbackDialog.value = true;
    resetSfForm();
};

const closeSystemFeedbackDialog = () => {
    systemFeedbackDialog.value = false;
    resetSfForm();
};

const resetSfForm = () => {
    Object.assign(sfForm, {
        category: 'issue',
        area: '',
        subject: '',
        description: ''
    });
    sfSelectedFile.value = null;
    sfPreviewUrl.value = null;
    sfPreviewIsImage.value = false;
    sfFormRef.value?.resetValidation();
};

const loadUserSystemFeedbacks = async () => {
    if (!authUser.value?.id) return;
    
    loadingSystemFeedbacks.value = true;
    try {
        const response = await getUserSystemFeedbacks(authUser.value.id);
        if (response?.success) {
            systemFeedbacks.value = response.data || [];
        }
    } catch (error) {
        console.error('Error loading system feedbacks:', error);
    } finally {
        loadingSystemFeedbacks.value = false;
    }
};

const handleSfFileSelect = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    
    // File size validation (10MB max)
    if (file.size > 10 * 1024 * 1024) {
        showSnackbar('File size must be less than 10MB', 'error');
        return;
    }
    
    // File type validation
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'application/pdf'];
    if (!allowedTypes.includes(file.type)) {
        showSnackbar('Only JPG, PNG, GIF, MP4, and PDF files are allowed', 'error');
        return;
    }
    
    sfSelectedFile.value = file;
    sfPreviewIsImage.value = file.type.startsWith('image/');
    
    // Create preview URL
    if (sfPreviewUrl.value) {
        URL.revokeObjectURL(sfPreviewUrl.value);
    }
    sfPreviewUrl.value = URL.createObjectURL(file);
};

const removeSfFile = () => {
    if (sfPreviewUrl.value) {
        URL.revokeObjectURL(sfPreviewUrl.value);
    }
    sfSelectedFile.value = null;
    sfPreviewUrl.value = null;
    sfPreviewIsImage.value = false;
};

const triggerSfFileInput = () => {
    const fileInput = sfFileInputRef.value;
    if (fileInput) fileInput.click();
};

const handleSfFileDrop = (event) => {
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        const fakeEvent = { target: { files: files } };
        handleSfFileSelect(fakeEvent);
    }
};

const submitSystemFeedbackForm = async () => {
    const { valid } = await sfFormRef.value.validate();
    if (!valid) return;
    
    submittingSf.value = true;
    try {
        const formData = new FormData();
        formData.append('user_id', authUser.value.id);
        formData.append('category', sfForm.category);
        formData.append('area', sfForm.area || '');
        formData.append('subject', sfForm.subject);
        formData.append('description', sfForm.description);
        
        if (sfSelectedFile.value) {
            formData.append('attachment', sfSelectedFile.value);
        }
        
        const response = await submitSystemFeedback(formData);
        
        if (response?.success) {
            showSnackbar('System feedback submitted successfully!', 'success');
            closeSystemFeedbackDialog();
            await loadUserSystemFeedbacks(); // Refresh the list
        } else {
            showSnackbar(response?.message || 'Failed to submit feedback', 'error');
        }
    } catch (error) {
        console.error('Error submitting system feedback:', error);
        showSnackbar('Error submitting feedback. Please try again.', 'error');
    } finally {
        submittingSf.value = false;
    }
};

const fetchProfile = async () => {
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (!userData.id) {
            router.visit('/login');
            return;
        }

        // Fetch latest user data from server to get current status
        try {
            const response = await apiFetch(`/api/users/${userData.id}`, { method: 'GET' });
            console.log('[Profile] Fetch profile API Response:', response);
            
            if (response && response.success && response.data) {
                const serverData = response.data;
                console.log('[Profile] Server data:', serverData);
                
                // Update profile with fresh server data
                profile.value = {
                    id: serverData.id,
                    first_name: serverData.first_name || '',
                    last_name: serverData.last_name || '',
                    email: serverData.email || '',
                    contact_number: serverData.phone || serverData.contact_number || '',
                    employee_id: serverData.rescuer_id || serverData.username || serverData.id,
                    avatar: serverData.profile_picture || serverData.avatar || null,
                    profile_picture: serverData.profile_picture || serverData.avatar || null,
                    is_active: serverData.is_active !== false,
                    status: serverData.status || 'available',
                    admin_restricted: ['off_duty', 'unavailable'].includes(serverData.status),
                };
                
                // Update local storage with latest data
                const updatedUserData = {
                    ...userData,
                    status: serverData.status,
                    phone: serverData.phone,
                    contact_number: serverData.phone,
                    profile_picture: serverData.profile_picture || serverData.avatar,
                    first_name: serverData.first_name,
                    last_name: serverData.last_name,
                };
                localStorage.setItem('userData', JSON.stringify(updatedUserData));
                
                console.log('[Profile] Profile updated with status:', profile.value.status);
            } else {
                // Fallback to local storage if API fails
                profile.value = {
                    id: userData.id,
                    first_name: userData.firstName || userData.first_name || '',
                    last_name: userData.lastName || userData.last_name || '',
                    email: userData.email || '',
                    contact_number: userData.phone || userData.contact_number || '',
                    employee_id: userData.rescuer_id || userData.username || userData.id,
                    avatar: userData.profile_picture || userData.avatar || null,
                    profile_picture: userData.profile_picture || userData.avatar || null,
                    is_active: userData.is_active !== false,
                    status: userData.status || 'available',
                    admin_restricted: ['off_duty', 'unavailable'].includes(userData.status),
                };
            }
        } catch (apiError) {
            console.error('Error fetching user from server:', apiError);
            // Use local storage data if API call fails
            profile.value = {
                id: userData.id,
                first_name: userData.firstName || userData.first_name || '',
                last_name: userData.lastName || userData.last_name || '',
                email: userData.email || '',
                contact_number: userData.phone || userData.contact_number || '',
                employee_id: userData.rescuer_id || userData.username || userData.id,
                avatar: userData.profile_picture || userData.avatar || null,
                profile_picture: userData.profile_picture || userData.avatar || null,
                is_active: userData.is_active !== false,
                status: userData.status || 'available',
                admin_restricted: ['off_duty', 'unavailable'].includes(userData.status),
            };
        }
        
        originalProfile.value = { ...profile.value };

        await fetchStats(userData.id);

        const savedSettings = localStorage.getItem('rescuerSettings');
        if (savedSettings) {
            settings.value = JSON.parse(savedSettings);
        }
        // Sync dark mode composable with loaded setting
        setDarkMode(settings.value.darkMode);
    } catch (error) {
        console.error('Error fetching profile:', error);
        showSnackbar('Failed to load profile', 'error');
    } finally {
        loading.value = false;
    }
};

const fetchStats = async (userId) => {
    try {
        const response = await apiFetch(`/api/rescue-requests/rescuer/${userId}`, { method: 'GET' });
        
        if (response && response.data) {
            const rescues = Array.isArray(response.data) ? response.data : [];
            
            // Count completed rescues
            const completedRescues = rescues.filter(r => 
                r.status === 'rescued' || r.status === 'completed' || r.status === 'safe'
            );
            const completed = completedRescues.length;
            
            // Count in-progress rescues
            const inProgress = rescues.filter(r => 
                ['assigned', 'in_progress', 'en_route', 'on_scene'].includes(r.status)
            ).length;
            
            // Calculate average response time
            let avgTime = '0:00';
            if (completed > 0) {
                let totalMinutes = 0;
                let validCount = 0;
                
                completedRescues.forEach(rescue => {
                    const created = new Date(rescue.created_at);
                    const updated = new Date(rescue.updated_at);
                    const diffMinutes = Math.floor((updated - created) / 60000);
                    
                    if (diffMinutes > 0 && diffMinutes < 1440) { // Valid if less than 24 hours
                        totalMinutes += diffMinutes;
                        validCount++;
                    }
                });
                
                if (validCount > 0) {
                    const avgMinutes = Math.floor(totalMinutes / validCount);
                    const hours = Math.floor(avgMinutes / 60);
                    const mins = avgMinutes % 60;
                    avgTime = `${hours}:${String(mins).padStart(2, '0')}`;
                }
            }
            
            stats.value = {
                completed,
                inProgress,
                avgTime,
            };
        }
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
};

const saveProfile = async () => {
    if (!formRef.value) return;
    
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    // Validate phone number before saving
    if (profile.value.contact_number) {
        const phoneValidation = rules.phoneNumber(profile.value.contact_number);
        if (phoneValidation !== true) {
            showSnackbar(phoneValidation, 'error');
            return;
        }
    }

    saving.value = true;
    try {
        const updateData = {
            first_name: profile.value.first_name,
            last_name: profile.value.last_name,
            phone: profile.value.contact_number,
        };
        
        console.log('Updating profile with data:', updateData);
        
        const response = await updateUser(profile.value.id, updateData);
        console.log('Update response:', response);
        
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const updatedData = {
            ...userData,
            firstName: profile.value.first_name,
            lastName: profile.value.last_name,
            first_name: profile.value.first_name,
            last_name: profile.value.last_name,
            email: profile.value.email,
            phone: profile.value.contact_number,
            contact_number: profile.value.contact_number,
        };
        
        localStorage.setItem('userData', JSON.stringify(updatedData));
        originalProfile.value = { ...profile.value };
        
        isEditing.value = false;
        showSnackbar('Profile updated successfully', 'success');
    } catch (error) {
        console.error('Error saving profile:', error);
        // Show more detailed error message
        const errorMessage = error.data?.message || error.message || 'Failed to update profile';
        showSnackbar(errorMessage, 'error');
    } finally {
        saving.value = false;
    }
};

const cancelEdit = () => {
    profile.value = { ...originalProfile.value };
    isEditing.value = false;
};

// Password Change OTP Flow
const startResendCountdown = () => {
    resendCountdown.value = 60;
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
        if (resendCountdown.value > 0) {
            resendCountdown.value--;
        } else {
            clearInterval(countdownInterval);
        }
    }, 1000);
};

const sendPasswordOtp = async () => {
    sendingOtp.value = true;
    passwordError.value = '';
    try {
        const response = await fetch('/api/auth/send-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ email: profile.value.email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            passwordStep.value = 2;
            startResendCountdown();
            showSnackbar('Verification code sent to your email', 'success');
        } else {
            passwordError.value = data.message || 'Failed to send code';
        }
    } catch (error) {
        console.error('Error sending OTP:', error);
        passwordError.value = 'Failed to send verification code';
    } finally {
        sendingOtp.value = false;
    }
};

const verifyPasswordOtp = async () => {
    const otpValue = Array.isArray(otpCode.value) 
        ? otpCode.value.join('') 
        : String(otpCode.value).trim();
    
    if (otpValue.length !== 6) return;
    
    verifyingOtp.value = true;
    passwordError.value = '';
    try {
        const response = await fetch('/api/auth/verify-password-change-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                email: profile.value.email,
                otp: otpValue
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            verificationToken.value = data.token;
            passwordStep.value = 3;
            showSnackbar('Code verified! Set your new password.', 'success');
        } else {
            passwordError.value = data.message || 'Invalid code';
        }
    } catch (error) {
        console.error('Error verifying OTP:', error);
        passwordError.value = 'Failed to verify code';
    } finally {
        verifyingOtp.value = false;
    }
};

const changePassword = async () => {
    if (!isPasswordValid.value) return;

    changingPassword.value = true;
    passwordError.value = '';
    try {
        const response = await fetch('/api/auth/complete-password-change', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                email: profile.value.email,
                token: verificationToken.value,
                password: passwordForm.value.new_password,
                password_confirmation: passwordForm.value.confirm_password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            passwordComplete.value = true;
            showSnackbar('Password changed successfully!', 'success');
            playNotificationSound('success');
        } else {
            passwordError.value = data.message || 'Failed to change password';
        }
    } catch (error) {
        console.error('Error changing password:', error);
        passwordError.value = 'Failed to change password';
    } finally {
        changingPassword.value = false;
    }
};

const resetPasswordFlow = () => {
    passwordStep.value = 1;
    passwordComplete.value = false;
    passwordError.value = '';
    otpCode.value = '';
    verificationToken.value = '';
    passwordForm.value = { current_password: '', new_password: '', confirm_password: '' };
    if (passwordFormRef.value) passwordFormRef.value.reset();
};

const openChangePasswordDialog = () => {
    resetPasswordFlow();
    passwordDialog.value = true;
};

const closePasswordDialog = () => {
    passwordDialog.value = false;
    resetPasswordFlow();
};

const toggleAvailability = async () => {
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        userData.is_active = profile.value.is_active;
        localStorage.setItem('userData', JSON.stringify(userData));
        
        showSnackbar(
            profile.value.is_active ? 'You are now available' : 'You are now unavailable',
            profile.value.is_active ? 'success' : 'warning'
        );
    } catch (error) {
        console.error('Error toggling availability:', error);
        profile.value.is_active = !profile.value.is_active;
        showSnackbar('Failed to update', 'error');
    }
};

const updateSetting = async (setting) => {
    localStorage.setItem('rescuerSettings', JSON.stringify(settings.value));
    
    // Handle Push Notifications
    if (setting === 'Notifications') {
        if (settings.value.notifications) {
            // Request notification permission
            if ('Notification' in window) {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    showSnackbar('Push notifications enabled', 'success');
                    // Show test notification
                    new Notification('PinPointMe', {
                        body: 'Push notifications are now enabled!',
                        icon: '/images/logo.png',
                        tag: 'test-notification'
                    });
                } else if (permission === 'denied') {
                    settings.value.notifications = false;
                    localStorage.setItem('rescuerSettings', JSON.stringify(settings.value));
                    showSnackbar('Notification permission denied. Please enable in browser settings.', 'warning');
                }
            } else {
                showSnackbar('Notifications not supported in this browser', 'warning');
            }
        } else {
            showSnackbar('Push notifications disabled', 'info');
        }
    }
    
    // Handle Sound Alerts
    else if (setting === 'Sound') {
        if (settings.value.sound) {
            // Play test sound
            playNotificationSound('notification');
            showSnackbar('Sound alerts enabled', 'success');
        } else {
            showSnackbar('Sound alerts disabled', 'info');
        }
    }
    
    // Handle Dark Mode
    else if (setting === 'DarkMode') {
        setDarkMode(settings.value.darkMode);
        showSnackbar(settings.value.darkMode ? 'Dark mode enabled' : 'Dark mode disabled',
            settings.value.darkMode ? 'success' : 'info');
    }
    
    else {
        showSnackbar(`${setting} updated`, 'success');
    }
};

// Photo handling
const openPhotoDialog = () => {
    selectedFile.value = null;
    previewUrl.value = null;
    photoDialog.value = true;
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    
    if (file.size > 5 * 1024 * 1024) {
        showSnackbar('Max file size is 5MB', 'error');
        return;
    }
    
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        showSnackbar('Invalid file type', 'error');
        return;
    }
    
    selectedFile.value = file;
    previewUrl.value = URL.createObjectURL(file);
};

const cancelPhotoUpload = () => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    selectedFile.value = null;
    previewUrl.value = null;
    photoDialog.value = false;
};

const uploadPhoto = async () => {
    if (!selectedFile.value || !profile.value.id) return;
    
    uploadingPhoto.value = true;
    try {
        const result = await uploadProfilePicture(profile.value.id, selectedFile.value);
        
        profile.value.avatar = result.profile_picture;
        profile.value.profile_picture = result.profile_picture;
        
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        userData.profile_picture = result.profile_picture;
        userData.avatar = result.profile_picture;
        localStorage.setItem('userData', JSON.stringify(userData));
        
        showSnackbar('Photo updated', 'success');
        
        if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
        selectedFile.value = null;
        previewUrl.value = null;
        photoDialog.value = false;
    } catch (err) {
        console.error('Error uploading photo:', err);
        showSnackbar('Failed to upload', 'error');
    } finally {
        uploadingPhoto.value = false;
    }
};

const confirmDeletePhoto = () => {
    deletePhotoDialog.value = true;
};

const deletePhoto = async () => {
    if (!profile.value.id) return;
    
    deletingPhoto.value = true;
    try {
        await deleteProfilePicture(profile.value.id);
        
        profile.value.avatar = null;
        profile.value.profile_picture = null;
        
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        userData.profile_picture = null;
        userData.avatar = null;
        localStorage.setItem('userData', JSON.stringify(userData));
        
        showSnackbar('Photo removed', 'success');
        deletePhotoDialog.value = false;
        photoDialog.value = false;
    } catch (err) {
        console.error('Error deleting photo:', err);
        showSnackbar('Failed to remove', 'error');
    } finally {
        deletingPhoto.value = false;
    }
};

const handleLogout = async () => {
    loggingOut.value = true;

    // 1. Clear ALL local storage & session data FIRST to ensure clean state
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    localStorage.removeItem('lastRescueRequestId');
    localStorage.removeItem('rescuerSettings');
    localStorage.removeItem('activeRescue');
    localStorage.removeItem('conversationId');
    localStorage.removeItem('chatId');
    sessionStorage.clear();

    // 2. Fire-and-forget: set user inactive in Firebase (don't block logout)
    if (userData.id) {
        setUserActiveStatus(userData.id, false).catch(e => 
            console.error('[Logout] Firebase inactive error:', e)
        );
    }

    // 3. Call backend logout with timeout - don't let it block the redirect
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000);

        await fetch('/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
            },
            credentials: 'include',
            signal: controller.signal
        });
        clearTimeout(timeoutId);
    } catch (error) {
        console.warn('[Logout] Backend logout error (proceeding anyway):', error.message);
    }

    // 4. Clear cookies manually as fallback
    document.cookie.split(';').forEach(c => {
        document.cookie = c.replace(/^ +/, '').replace(/=.*/, '=;expires=' + new Date().toUTCString() + ';path=/');
    });

    // 5. Force hard redirect to login
    window.location.replace('/login');
};

// Status display helpers
const getStatusChipColor = (status) => {
    const colors = {
        'available': 'success',
        'on_rescue': 'warning',
        'off_duty': 'grey',
        'unavailable': 'error',
        'pending': 'info'
    };
    return colors[status] || 'grey';
};

const getStatusIcon = (status) => {
    const icons = {
        'available': 'mdi-check-circle',
        'on_rescue': 'mdi-account-alert',
        'off_duty': 'mdi-clock-outline',
        'unavailable': 'mdi-close-circle',
        'pending': 'mdi-clock-alert-outline'
    };
    return icons[status] || 'mdi-help-circle';
};

const getStatusLabel = (status) => {
    const labels = {
        'available': 'Available',
        'on_rescue': 'On Rescue',
        'off_duty': 'Off Duty',
        'unavailable': 'Unavailable',
        'pending': 'Pending Activation'
    };
    return labels[status] || status?.replace(/_/g, ' ').toUpperCase() || 'Unknown';
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Fetch unread message count
const fetchUnreadMessageCount = async () => {
    const userId = authUser.value?.id;
    if (!userId) return;
    try {
        unreadMessageCount.value = await getUnreadMessageCount(userId);
    } catch (error) {
        console.error('Failed to fetch unread message count:', error);
    }
};

// Watch settings changes and save to localStorage
watch(settings, (newSettings) => {
    localStorage.setItem('rescuerSettings', JSON.stringify(newSettings));
}, { deep: true });

// Lifecycle
onMounted(async () => {
    if (!authUser.value) {
        router.visit('/login');
        return;
    }
    fetchProfile();
    await fetchUnreadMessageCount();
    await loadUserSystemFeedbacks();
    
    // Add visibility change listener to refresh status when user returns to page
    const handleVisibilityChange = () => {
        if (!document.hidden) {
            console.log('[Profile] Page became visible, refreshing status...');
            refreshStatus();
        }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);
    
    // Store the cleanup function
    window._profileVisibilityHandler = handleVisibilityChange;
});

onUnmounted(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    // Remove visibility change listener
    if (window._profileVisibilityHandler) {
        document.removeEventListener('visibilitychange', window._profileVisibilityHandler);
        delete window._profileVisibilityHandler;
    }
});
</script>

<style scoped>
/* System Feedback Dialog Styles */
.sf-dialog-card {
    overflow: hidden;
}

.sf-dialog-header {
    background: linear-gradient(135deg, #3674B5, #2D5F96);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sf-dialog-header-content {
    display: flex;
    align-items: center;
}

.sf-dialog-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}

.sf-dialog-subtitle {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.8);
    margin: 0;
}

.sf-dialog-body {
    padding: 24px;
}

.sf-field-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #444;
    margin-bottom: 8px;
}

.sf-category-toggle {
    width: 100%;
}

.sf-cat-btn {
    flex: 1;
    text-transform: none;
    font-weight: 600;
}

.sf-upload-area {
    border: 2px dashed #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s;
}

.sf-upload-area:hover {
    border-color: #3674B5;
}

.sf-upload-placeholder {
    color: #666;
}

.sf-upload-preview {
    position: relative;
}

.sf-upload-remove {
    position: absolute;
    top: 8px;
    right: 8px;
}

.sf-submit-btn {
    text-transform: none;
    font-weight: 600;
}

.sf-history-item {
    background: rgba(54, 116, 181, 0.05);
    border: 1px solid rgba(54, 116, 181, 0.1);
}

/* Panel titles - mobile-friendly with larger touch targets */
.panel-title-mobile {
    padding: 14px 12px !important;
    min-height: 60px !important;
}

@media (min-width: 600px) {
    .panel-title-mobile {
        padding: 16px !important;
        min-height: auto !important;
    }
}

/* Panel content - mobile-friendly padding */
.panel-content-mobile {
    padding: 4px 0 !important;
}

:deep(.v-expansion-panel-text__wrapper) {
    padding: 0 12px 16px !important;
}

@media (min-width: 600px) {
    :deep(.v-expansion-panel-text__wrapper) {
        padding: 0 16px 16px !important;
    }
}

/* Category card styles (kept for consistency) */
.profile-page-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: var(--ppm-header-bg, #3674B5);
    padding: env(safe-area-inset-top, 0) 0 0 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    gap: 12px;
}

.menu-btn, .placeholder-btn {
    color: white;
}

.header-title {
    flex: 1;
    text-align: left;
}

.title-with-icon {
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.title-with-icon .v-icon {
    color: white;
}

.header-title h1 {
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
    margin: 0;
}

.header-title p {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

.profile-header-bg {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%);
}

.avatar-ring {
    border: 4px solid rgba(255, 255, 255, 0.3);
    background: linear-gradient(135deg, #42A5F5, #1E88E5);
}

.edit-avatar-btn {
    bottom: 0;
    right: 0;
}

.stats-row {
    background: rgba(25, 118, 210, 0.03);
}

.position-relative {
    position: relative;
}

.position-absolute {
    position: absolute;
}

.d-inline-block {
    display: inline-block;
}

/* Profile main layout */
.profile-main {
    min-height: 100vh;
    min-height: 100dvh;
}

/* Profile Container - mobile-first padding */
.profile-container {
    padding: 12px !important;
    padding-bottom: 180px !important;
}

@media (min-width: 600px) {
    .profile-container {
        padding: 16px !important;
        padding-bottom: 180px !important;
    }
    
    .header-title h1 {
        font-size: 1.25rem;
    }
    
    .header-title p {
        font-size: 0.75rem;
    }
}

/* Desktop: remove extra padding since no bottom nav */
@media (min-width: 1024px) {
    .profile-container {
        padding-bottom: 0 !important;
    }
}

/* Mobile avatar size adjustment */
.avatar-mobile {
    width: 80px !important;
    height: 80px !important;
}

@media (min-width: 600px) {
    .avatar-mobile {
        width: 120px !important;
        height: 120px !important;
    }
}

/* Section Cards - mobile-friendly */
.section-card {
    border: 1px solid rgba(0, 0, 0, 0.05);
}

/* Panel titles - mobile-friendly with larger touch targets */
.panel-title-mobile {
    padding: 14px 12px !important;
    min-height: 60px !important;
}

@media (min-width: 600px) {
    .panel-title-mobile {
        padding: 16px 24px !important;
        min-height: auto !important;
    }
}

/* Panel content - mobile-friendly padding */
.panel-content-mobile {
    padding: 4px 0 !important;
}

:deep(.v-expansion-panel-text__wrapper) {
    padding: 0 12px 16px !important;
}

@media (min-width: 600px) {
    :deep(.v-expansion-panel-text__wrapper) {
        padding: 0 24px 24px !important;
    }
}

/* Mobile input fields - larger touch targets */
.mobile-input :deep(.v-field) {
    min-height: 48px !important;
}

.mobile-input :deep(.v-field__input) {
    padding-top: 12px !important;
    padding-bottom: 12px !important;
    font-size: 0.9375rem !important;
}

/* Mobile buttons - better touch targets */
.mobile-btn {
    min-height: 48px !important;
    font-weight: 600 !important;
}

/* Logout button styling */
.logout-btn {
    min-height: 52px !important;
    font-weight: 600 !important;
    letter-spacing: 0.5px;
}

/* Settings list mobile styling */
.settings-list {
    margin: 0 -4px;
}

.setting-item {
    background: rgba(0, 0, 0, 0.02);
    min-height: 64px !important;
}

.setting-item:active {
    background: rgba(0, 0, 0, 0.05);
}

/* Desktop-only elements (hidden on mobile/tablet) */
.desktop-only {
    display: flex;
}

@media (max-width: 1023px) {
    .desktop-only {
        display: none !important;
    }
}

/* Mobile responsive adjustments */
@media (max-width: 600px) {
    .profile-main {
        padding-bottom: 180px !important;
    }
    
    /* Ensure container has proper spacing */
    .v-container {
        padding-left: 12px !important;
        padding-right: 12px !important;
    }
    
    /* Make cards more compact on mobile */
    .v-card {
        margin-bottom: 12px !important;
    }
    
    /* Adjust button sizing */
    .profile-action-buttons .v-btn {
        min-height: 44px;
    }
    
    /* Ensure buttons stack nicely on very small screens */
    .d-flex.flex-column.flex-sm-row .v-btn {
        margin-bottom: 8px !important;
    }
}

@media (max-width: 1024px) {
    .v-main {
        margin-left: 0 !important;
    }
    
    .v-main :deep(.v-container) {
        max-width: 100% !important;
    }
}

@media (max-width: 400px) {
    .profile-main {
        padding-bottom: 180px !important;
    }
    
    .header-content {
        padding: 6px 10px;
    }
    
    .v-container {
        padding-left: 8px !important;
        padding-right: 8px !important;
    }
    
    /* Make profile header more compact */
    .profile-header-bg {
        padding: 16px !important;
    }
}

/* Non-editable fields styling */
.non-editable-field {
    opacity: 0.7;
}

.non-editable-field :deep(.v-field) {
    background-color: #f5f5f5 !important;
}

.non-editable-field :deep(.v-field__input) {
    color: #666 !important;
}

/* ═══ Password Dialog (ChangePassword.vue style) ═══ */
.pwd-dialog-card {
    overflow: hidden;
}

.pwd-card-header {
    background: linear-gradient(135deg, #3674B5, #2D5F96);
    padding: 32px 24px 28px;
    text-align: center;
    position: relative;
}
.pwd-card-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--ppm-secondary, #DFA92C), transparent);
}

.pwd-header-icon-wrap {
    width: 64px;
    height: 64px;
    margin: 0 auto 12px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pwd-header-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 4px;
}

.pwd-header-subtitle {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.75);
    margin: 0;
}

.pwd-close-btn {
    position: absolute !important;
    top: 12px;
    right: 12px;
}

.pwd-card-body {
    background: #fff;
}

.pwd-action-btn {
    text-transform: none;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.pwd-otp-container {
    max-width: 300px;
    margin: 0 auto;
}

.pwd-otp-input-custom :deep(.v-otp-input__content) {
    gap: 8px;
}

.pwd-field-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: #444;
    margin-bottom: 6px;
}

.pwd-password-field :deep(.v-field) {
    border-radius: 12px !important;
    border: 1.5px solid #e0e0e0;
    transition: border-color 0.2s;
}
.pwd-password-field :deep(.v-field--focused) {
    border-color: #3674B5 !important;
}

.pwd-strength-bar-wrap {
    padding: 0 2px;
}
.pwd-strength-bar-track {
    height: 6px;
    background: #eee;
    border-radius: 3px;
    overflow: hidden;
}
.pwd-strength-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.4s ease, background 0.4s ease;
}

.pwd-requirements-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px 16px;
    padding: 12px 14px;
    background: #f8f9fb;
    border-radius: 10px;
}

.pwd-req-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: #999;
    transition: color 0.2s;
}
.pwd-req-item.met {
    color: #3674B5;
    font-weight: 500;
}

.pwd-success-check-wrap {
    display: flex;
    justify-content: center;
}

.pwd-success-check-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--ppm-header-bg, #3674B5), var(--ppm-header-bg-light, #4a8fd4));
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(54, 116, 181, 0.3);
    animation: pwdSuccessPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes pwdSuccessPop {
    0% { transform: scale(0); opacity: 0; }
    60% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

/* Settings list mobile styling */
.settings-list {
    margin: 0 -4px;
}

.setting-item {
    background: rgba(0, 0, 0, 0.02);
    min-height: 64px !important;
}

.setting-item:active {
    background: rgba(0, 0, 0, 0.05);
}

/* Dark Mode Overrides */
.dark-mode .profile-header-bg {
    background: var(--dm-bg-surface) !important; /* Remove gradient */
}

.dark-mode .profile-avatar {
    background: var(--dm-bg-surface) !important; /* Remove gradient */
    border: 2px solid var(--dm-border) !important;
}

/* Make all icons gray for balance in dark mode */
.dark-mode .v-icon {
    color: var(--dm-text-muted) !important;
}

/* Exception: Keep some icons with their intended colors when they're interactive or status indicators */
.dark-mode .v-snackbar .v-icon,
.dark-mode .v-alert .v-icon,
.dark-mode .status-chip .v-icon,
.dark-mode .v-btn--variant-flat .v-icon,
.dark-mode .v-btn[style*="background"] .v-icon {
    color: inherit !important;
}

/* Profile header specific dark mode */
.dark-mode .profile-page-header {
    background: var(--dm-bg-surface) !important;
    border-bottom: 1px solid var(--dm-border) !important;
}

.dark-mode .header-title h1 {
    color: var(--dm-text-primary) !important;
}

.dark-mode .header-title p {
    color: var(--dm-text-secondary) !important;
}

/* Profile card dark mode */
.dark-mode .profile-info {
    background: var(--dm-bg-surface) !important;
    border: 1px solid var(--dm-border) !important;
}

.dark-mode .profile-name {
    color: var(--dm-text-primary) !important;
}

.dark-mode .profile-contact,
.dark-mode .profile-status {
    color: var(--dm-text-secondary) !important;
}

/* Settings list dark mode */
.dark-mode .setting-item {
    background: var(--dm-hover) !important;
    border-bottom: 1px solid var(--dm-border) !important;
}

.dark-mode .setting-item:active {
    background: var(--dm-active) !important;
}

/* Remove gradients from buttons in dark mode */
.dark-mode .v-btn[style*="linear-gradient"] {
    background: var(--dm-bg-elevated) !important;
}

/* Stats cards dark mode */
.dark-mode .stats-card {
    background: var(--dm-bg-surface) !important;
    border: 1px solid var(--dm-border) !important;
}

/* Photo dialog dark mode */
.dark-mode .photo-preview {
    background: var(--dm-bg-surface) !important;
    border: 1px solid var(--dm-border) !important;
}

/* Form fields in dark mode */
.dark-mode .profile-form .v-text-field .v-field,
.dark-mode .profile-form .v-select .v-field,
.dark-mode .profile-form .v-textarea .v-field {
    background: var(--dm-bg-input) !important;
}

/* Password dialog dark mode */
.dark-mode .pwd-card-body {
    background: var(--dm-bg-surface) !important;
}

.dark-mode .pwd-field-label {
    color: var(--dm-text-secondary) !important;
}

.dark-mode .pwd-password-field :deep(.v-field) {
    border-color: var(--dm-border) !important;
    background: var(--dm-bg-input) !important;
}

.dark-mode .pwd-requirements-list {
    background: var(--dm-bg-input) !important;
    border: 1px solid var(--dm-border) !important;
}

.dark-mode .pwd-req-item {
    color: var(--dm-text-muted) !important;
}

.dark-mode .pwd-req-item.met {
    color: #5ba3e6 !important;
}

.dark-mode .pwd-strength-bar-track {
    background: var(--dm-border) !important;
}
</style>
