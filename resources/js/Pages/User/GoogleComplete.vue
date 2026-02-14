<template>
    <v-app class="bg-user-gradient-light">
        <v-main class="registration-viewport">
            <v-container fluid class="fill-height pa-0">
                <v-row no-gutters class="fill-height">
                    <!-- Left Panel - Desktop/Laptop Only -->
                    <v-col cols="12" lg="6" class="d-none d-lg-flex">
                        <div class="w-100 h-100 d-flex flex-column justify-center align-center left-panel">
                            <div class="logo-container">
                                <v-img
                                    src="/images/logos/pinpointme.png"
                                    max-height="160"
                                    max-width="160"
                                    contain
                                    class="logo-image"
                                />
                            </div>
                            <h1 class="text-h4 text-white mt-6 font-weight-bold brand-title">
                                PinPointMe
                            </h1>
                            <p class="text-h6 text-white-darken-1 text-center mt-2 brand-subtitle">
                                Complete Your Profile
                            </p>
                            <p class="text-body-2 text-white-darken-2 text-center mt-4 px-8" style="max-width: 400px;">
                                Just a few more details to activate your account and start using PinPointMe rescue services.
                            </p>
                        </div>
                    </v-col>

                    <!-- Right Panel - Form -->
                    <v-col cols="12" lg="6" class="d-flex align-center justify-center right-panel">
                        <div class="form-container">
                            <v-card class="registration-card pa-6 pa-sm-8" rounded="lg" elevation="8">
                                <!-- Mobile/Tablet Logo & Header -->
                                <div class="d-lg-none text-center mb-6">
                                    <v-img
                                        src="/images/logos/pinpointme.png"
                                        max-height="100"
                                        max-width="100"
                                        contain
                                        class="mx-auto mb-3"
                                    />
                                    <h2 class="text-h6 font-weight-bold text-primary">Complete Your Registration</h2>
                                </div>

                                <!-- Header with Google Profile -->
                                <div class="text-center mb-6 d-none d-lg-block">
                                    <v-avatar size="80" class="mb-4 elevation-4">
                                        <v-img 
                                            v-if="googleUser.profile_picture" 
                                            :src="googleUser.profile_picture"
                                            alt="Profile"
                                        />
                                        <v-icon v-else size="48" color="primary">mdi-account</v-icon>
                                    </v-avatar>
                                    <h2 class="text-h5 font-weight-bold mb-1">Complete Your Registration</h2>
                                    <p class="text-body-2 text-grey">
                                        Welcome, {{ googleUser.first_name }}! Please provide additional information to complete your account.
                                    </p>
                                    <v-chip color="primary" variant="tonal" class="mt-2" size="small">
                                        <v-icon start size="16">mdi-google</v-icon>
                                        {{ googleUser.email }}
                                    </v-chip>
                                </div>

                                <!-- Mobile Google Profile -->
                                <div class="d-lg-none mb-6">
                                    <v-card variant="tonal" color="primary" class="pa-3">
                                        <div class="d-flex align-center">
                                            <v-avatar size="48" class="mr-3">
                                                <v-img 
                                                    v-if="googleUser.profile_picture" 
                                                    :src="googleUser.profile_picture"
                                                    alt="Profile"
                                                />
                                                <v-icon v-else size="32" color="primary">mdi-account</v-icon>
                                            </v-avatar>
                                            <div class="flex-grow-1">
                                                <div class="text-subtitle-2 font-weight-medium">{{ googleUser.first_name }} {{ googleUser.last_name }}</div>
                                                <div class="text-caption text-grey-darken-1">{{ googleUser.email }}</div>
                                            </div>
                                        </div>
                                    </v-card>
                                </div>

                            <!-- Error Alert -->
                            <v-alert
                                v-if="error"
                                type="error"
                                variant="tonal"
                                class="mb-4"
                                closable
                                @click:close="error = ''"
                            >
                                {{ error }}
                            </v-alert>

                            <!-- Registration Form -->
                            <v-form @submit.prevent="handleSubmit" ref="formRef">
                                <!-- ID Number Section -->
                                <div class="mb-5">
                                    <div class="d-flex align-center mb-2">
                                        <v-icon size="20" color="primary" class="mr-2">mdi-card-account-details</v-icon>
                                        <span class="text-subtitle-2 font-weight-medium">ID Number</span>
                                    </div>
                                    <v-text-field
                                        v-model="form.id_number"
                                        label="ID Number (9 digits)"
                                        variant="outlined"
                                        density="comfortable"
                                        placeholder="e.g., 201234567"
                                        :rules="[rules.required, rules.idNumber]"
                                        :disabled="isLoading"
                                        maxlength="9"
                                        @input="formatIdNumber"
                                        hint="If starts with 2 = Student, other digits = Faculty"
                                        persistent-hint
                                        prepend-inner-icon="mdi-identifier"
                                    />
                                    <v-chip 
                                        v-if="userRole" 
                                        :color="userRole === 'student' ? 'blue' : 'green'" 
                                        size="small" 
                                        class="mt-2"
                                        variant="flat"
                                    >
                                        <v-icon start size="16">{{ userRole === 'student' ? 'mdi-school' : 'mdi-account-tie' }}</v-icon>
                                        {{ userRole === 'student' ? 'Student' : 'Faculty' }}
                                    </v-chip>
                                </div>

                                <!-- Phone Number Section -->
                                <div class="mb-5">
                                    <div class="d-flex align-center mb-2">
                                        <v-icon size="20" color="primary" class="mr-2">mdi-phone</v-icon>
                                        <span class="text-subtitle-2 font-weight-medium">Phone Number</span>
                                    </div>
                                    <v-text-field
                                        v-model="form.phone_number"
                                        label="Mobile Number"
                                        variant="outlined"
                                        density="comfortable"
                                        placeholder="e.g., 09171234567"
                                        :rules="[rules.required, rules.phoneNumber]"
                                        :disabled="isLoading"
                                        maxlength="13"
                                        @input="formatPhoneNumber"
                                        hint="11-digit mobile number format"
                                        persistent-hint
                                        prepend-inner-icon="mdi-cellphone"
                                    />
                                </div>

                              
                                <!-- Submit Button -->
                                <v-btn
                                    type="submit"
                                    color="primary"
                                    size="large"
                                    block
                                    :loading="isLoading"
                                    class="mb-4 registration-btn"
                                >
                                    <v-icon start>mdi-check</v-icon>
                                    Complete Registration
                                </v-btn>

                                <!-- Cancel Link -->
                                <div class="text-center">
                                    <v-btn variant="text" color="grey" size="small" @click="handleCancel" :disabled="isLoading">
                                        Cancel and go back to login
                                    </v-btn>
                                </div>
                            </v-form>
                        </v-card>
                    </div>
                </v-col>
            </v-row>
        </v-container>

        <!-- Success Snackbar -->
        <v-snackbar v-model="showSuccess" color="success" location="top">
            {{ successMessage }}
        </v-snackbar>
    </v-main>
</v-app>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const googleUser = computed(() => page.props.googleUser || {});

// Form state
const formRef = ref(null);
const form = ref({
    id_number: '',
    phone_number: '',
});

// UI state
const isLoading = ref(false);
const error = ref('');
const showSuccess = ref(false);
const successMessage = ref('');

// Computed role based on ID number first digit
const userRole = computed(() => {
    if (!form.value.id_number || form.value.id_number.length === 0) return null;
    const firstDigit = form.value.id_number.charAt(0);
    return firstDigit === '2' ? 'student' : 'faculty';
});

// Validation rules
const rules = {
    required: (v) => !!v || 'This field is required',
    idNumber: (v) => {
        if (!v) return 'ID number is required';
        if (!/^[0-9]+$/.test(v)) return 'ID number must contain only numbers';
        if (v.length !== 9) return 'ID number must be exactly 9 digits';
        return true;
    },
    phoneNumber: (v) => {
        if (!v) return 'Phone number is required';
        
        // Normalize the phone number
        let normalized = v.replace(/[^0-9+]/g, '');
        if (normalized.startsWith('+63')) {
            normalized = '0' + normalized.substring(3);
        } else if (normalized.startsWith('63')) {
            normalized = '0' + normalized.substring(2);
        } else if (normalized.startsWith('9') && normalized.length === 10) {
            normalized = '0' + normalized;
        }
        
        // Must start with 09 and have exactly 11 digits
        if (!/^09[0-9]{9}$/.test(normalized)) {
            return 'Please enter a valid number';
        }
        
        return true;
    },
};

// Format ID number (numbers only)
const formatIdNumber = () => {
    form.value.id_number = form.value.id_number.replace(/[^0-9]/g, '').substring(0, 9);
};

// Format phone number
const formatPhoneNumber = () => {
    let value = form.value.phone_number.replace(/[^0-9+]/g, '');
    
    // Handle various formats
    if (value.startsWith('+63')) {
        value = '0' + value.substring(3);
    } else if (value.startsWith('63') && !value.startsWith('639')) {
        value = '0' + value.substring(2);
    } else if (value.startsWith('9') && value.length <= 10) {
        value = '0' + value;
    }
    
    form.value.phone_number = value.substring(0, 11);
};

// Handle form submission
const handleSubmit = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;
    
    isLoading.value = true;
    error.value = '';
    
    try {
        const response = await fetch('/auth/google/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                id_number: form.value.id_number,
                phone_number: form.value.phone_number,
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            successMessage.value = data.message || 'Registration completed successfully!';
            showSuccess.value = true;
            
            // Redirect to the appropriate page
            setTimeout(() => {
                window.location.href = data.redirect || '/user/scanner';
            }, 1500);
        } else {
            if (data.errors) {
                // Handle validation errors
                const errorMessages = Object.values(data.errors).flat();
                error.value = errorMessages.join(' ');
            } else {
                error.value = data.message || 'Failed to complete registration.';
            }
        }
    } catch (err) {
        console.error('Registration error:', err);
        error.value = 'An unexpected error occurred. Please try again.';
    } finally {
        isLoading.value = false;
    }
};

// Handle cancel
const handleCancel = () => {
    window.location.href = '/login';
};
</script>

<style scoped>
/* =================================================================
   Viewport & Layout - Full 100vh, Responsive Design
   ================================================================= */
.registration-viewport {
    min-height: 100vh;
    height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    align-items: center;
}

.fill-height {
    min-height: 100%;
}

/* =================================================================
   Left Panel - Desktop Branding Section
   ================================================================= */
.left-panel {
    background: linear-gradient(135deg, #13294B 0%, #185D33 100%);
    position: relative;
    overflow: hidden;
    min-height: 100vh;
}

.left-panel::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(223, 169, 44, 0.15) 0%, transparent 60%);
    animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.3; }
    50% { transform: scale(1.1) rotate(180deg); opacity: 0.5; }
}

.logo-container {
    position: relative;
    z-index: 1;
}

.logo-image {
    filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.3));
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}

.brand-title {
    font-size: 2.5rem;
    font-weight: 800;
    letter-spacing: 3px;
    text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
}

.brand-subtitle {
    font-size: 1rem;
    letter-spacing: 6px;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

.text-white-darken-1 {
    color: rgba(255, 255, 255, 0.95) !important;
}

.text-white-darken-2 {
    color: rgba(255, 255, 255, 0.85) !important;
}

/* =================================================================
   Right Panel - Form Section
   ================================================================= */
.right-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
    min-height: 100vh;
    overflow-y: auto;
}

.form-container {
    width: 100%;
    max-width: 500px;
    padding: 0 8px;
}

/* =================================================================
   Registration Card
   ================================================================= */
.registration-card {
    width: 100%;
    border-radius: 16px !important;
    box-shadow: 0 8px 32px rgba(19, 41, 75, 0.12) !important;
    background: rgba(255, 255, 255, 0.98) !important;
}

/* Form Field Enhancements */
:deep(.v-text-field .v-field) {
    border-radius: 10px;
    background: #fafafa;
}

:deep(.v-text-field .v-field--focused) {
    background: #fff;
}

:deep(.v-text-field .v-field__outline) {
    --v-field-border-opacity: 0.3;
}

:deep(.v-text-field .v-field--focused .v-field__outline) {
    --v-field-border-opacity: 1;
}

/* Button Styling */
.registration-btn {
    text-transform: none !important;
    font-weight: 600;
    font-size: 1rem;
    letter-spacing: 0.5px;
    border-radius: 10px !important;
    box-shadow: 0 4px 12px rgba(19, 41, 75, 0.15) !important;
    transition: all 0.3s ease;
}

.registration-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(19, 41, 75, 0.25) !important;
}

/* =================================================================
   Responsive Design - Mobile & Tablet
   ================================================================= */
@media (max-width: 1279px) {
    .registration-viewport {
        height: auto;
        min-height: 100vh;
    }
    
    .right-panel {
        min-height: 100vh;
        padding: 32px 16px;
    }
    
    .form-container {
        max-width: 480px;
    }
    
    .registration-card {
        box-shadow: 0 4px 20px rgba(19, 41, 75, 0.1) !important;
    }
}

@media (max-width: 599px) {
    .registration-viewport {
        overflow-y: auto;
    }
    
    .right-panel {
        padding: 24px 12px;
        min-height: 100vh;
    }
    
    .form-container {
        padding: 0;
    }
    
    .registration-card {
        border-radius: 12px !important;
    }
    
    .brand-title {
        font-size: 2rem;
        letter-spacing: 2px;
    }
    
    .brand-subtitle {
        font-size: 0.9rem;
        letter-spacing: 4px;
    }
}

/* Very small devices */
@media (max-width: 374px) {
    .registration-card {
        padding: 20px 16px !important;
    }
}

/* Landscape mobile */
@media (max-height: 600px) and (orientation: landscape) {
    .registration-viewport {
        height: auto;
    }
    
    .right-panel {
        padding: 16px;
        min-height: auto;
    }
    
    .registration-card {
        margin: 16px 0;
    }
    
    .left-panel {
        min-height: auto;
    }
}

/* =================================================================
   Role Chip Enhancement
   ================================================================= */
:deep(.v-chip) {
    font-weight: 500;
    border-radius: 8px;
}

/* =================================================================
   Alert Styling
   ================================================================= */
:deep(.v-alert) {
    border-radius: 10px;
}

/* =================================================================
   Scrollbar Styling for Overflow Areas
   ================================================================= */
.registration-viewport::-webkit-scrollbar {
    width: 8px;
}

.registration-viewport::-webkit-scrollbar-track {
    background: transparent;
}

.registration-viewport::-webkit-scrollbar-thumb {
    background: rgba(19, 41, 75, 0.2);
    border-radius: 4px;
}

.registration-viewport::-webkit-scrollbar-thumb:hover {
    background: rgba(19, 41, 75, 0.3);
}

/* =================================================================
   Vuetify Component Overrides for Better Spacing
   ================================================================= */
.v-expansion-panel-title {
    font-size: 0.95rem;
}

.v-expansion-panel-text {
    padding-top: 16px;
}

/* Field hints styling */
:deep(.v-messages__message) {
    color: #546E7A;
    font-size: 0.75rem;
}

/* Focus states */
:deep(.v-field--focused .v-label) {
    color: #13294B !important;
}
</style>
