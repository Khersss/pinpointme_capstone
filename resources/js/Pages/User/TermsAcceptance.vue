<template>
    <v-app class="bg-user-gradient-light">
        <!-- Main Content -->
        <v-main>
            <div class="terms-container">
                <!-- Header Section -->
                <div class="terms-header">
                    <div class="header-content">
                        <div class="logo-section">
                            <img src="/images/Icons/ppm_logo.png" alt="PinPointMe" class="logo" />
                            <div class="logo-text">
                                <h1>PinPointMe</h1>
                                <p>St. Dominic Medical Center Incident Response System</p>
                            </div>
                        </div>
                        
                        <div class="welcome-text mt-5">

                            <div class="welcome-highlight">
                                
                                <v-icon size="16" color="rgba(255, 255, 255, 0.8)" class="mr-2">mdi-information-outline</v-icon>
                                <span class="highlight-text">Please review and accept our Terms & Conditions and Privacy Policy</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms Content Card -->
                <v-card class="terms-card mx-4 mt-4" elevation="2" rounded="xl">
                    <!-- Tab Navigation -->
                    <v-tabs v-model="activeTab" color="#3674B5" grow class="terms-tabs">
                        <v-tab value="terms">
                            <v-icon start size="20">mdi-file-document</v-icon>
                            <span class="d-none d-sm-inline">Terms & Conditions</span>
                            <span class="d-sm-none">Terms</span>
                        </v-tab>
                        <v-tab value="privacy">
                            <v-icon start size="20">mdi-shield-lock</v-icon>
                            <span class="d-none d-sm-inline">Privacy Policy</span>
                            <span class="d-sm-none">Privacy</span>
                        </v-tab>
                    </v-tabs>

                    <v-divider />

                    <!-- Scrollable Content -->
                    <div class="terms-scroll-area" ref="scrollArea" @scroll="handleScroll">
                        <v-window v-model="activeTab">
                            <!-- Terms & Conditions Tab -->
                            <v-window-item value="terms">
                                <div class="terms-text pa-4">
                                    <div class="terms-header-badge mb-4">
                                        <p class="version-text">
                                            <v-icon size="16" color="#3674B5">mdi-update</v-icon>
                                            Version {{ TERMS_VERSION }} | February 2026
                                        </p>
                                    </div>

                                    <h3 class="section-title">1. Acceptance of Terms</h3>
                                    <p>
                                        By accessing or using PinPointMe, you agree to be bound by these Terms and Conditions.
                                        PinPointMe is deployed as a hospital incident tracking and response platform for St. Dominic Medical Center.
                                        If you do not agree with any part of these terms, you may not use the service.
                                    </p>

                                    <h3 class="section-title">2. Project Title and Objective</h3>
                                    <p>
                                        Project title: <strong>PinPointMe: A QR Code-Based Incident Tracking and Response Application for St. Dominic Medical Center</strong>.
                                    </p>
                                    <p>
                                        Objective: to enhance patient safety by facilitating immediate communication between inpatients and ward responders.
                                    </p>

                                    <h3 class="section-title">3. Service Description</h3>
                                    <p>PinPointMe provides incident reporting and response coordination services within hospital wards, including:</p>
                                    <ul class="terms-list">
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> QR-based ward location detection for faster reporting</li>
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Immediate incident details sent to ward responders</li>
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Real-time communication between patients/companions and clinical personnel</li>
                                    </ul>

                                    <h3 class="section-title">4. Scope and User Roles</h3>
                                    <p>PinPointMe is intended for the following hospital users:</p>
                                    <ul class="terms-list">
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Primary Users: Patients and Visitors</li>
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Responders: Clinical Personnel</li>
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Administrators: Information and Communications Technology (ICT) Department of the Hospital</li>
                                    </ul>

                                    <h3 class="section-title">5. User Eligibility</h3>
                                    <p>
                                        The App is available to authorized hospital users.
                                        A valid email address is required to access the service. External email addresses are supported.
                                    </p>

                                    <h3 class="section-title">6. User Responsibilities</h3>
                                    <p>Users agree to:</p>
                                    <ul class="terms-list">
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Provide accurate and truthful information during registration</li>
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Use incident reporting features strictly for real patient or ward incidents</li>
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Refrain from misusing or abusing the incident request system</li>
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Maintain the security and confidentiality of their login credentials</li>
                                    </ul>

                                    <h3 class="section-title">7. Prohibited Use</h3>
                                    <p>Users are prohibited from:</p>
                                    <ul class="terms-list warning">
                                        <li><v-icon size="16" color="#C62828">mdi-close-circle</v-icon> Submitting false incident reports</li>
                                        <li><v-icon size="16" color="#C62828">mdi-close-circle</v-icon> Attempting to gain unauthorized access to the system</li>
                                        <li><v-icon size="16" color="#C62828">mdi-close-circle</v-icon> Interfering with the proper functioning of the service</li>
                                        <li><v-icon size="16" color="#C62828">mdi-close-circle</v-icon> Using the service for any unlawful purpose</li>
                                    </ul>

                                    <h3 class="section-title">8. Limitation of Liability</h3>
                                    <p>
                                        PinPointMe and St. Dominic Medical Center shall not be liable for any damages arising from the use or inability
                                        to use the service. This App is a supplementary academic prototype and does not replace 
                                        official emergency services. Its performance is subject to mobile hardware specifications 
                                        and network connectivity.
                                    </p>

                                    <h3 class="section-title">9. Governing Law</h3>
                                    <p>
                                        These terms shall be governed by the laws of the Republic of the Philippines, 
                                        including the Data Privacy Act of 2012 (RA 10173).
                                    </p>

                                    <div class="contact-box mt-4">
                                        <v-icon color="#3674B5" class="mr-2">mdi-email-outline</v-icon>
                                        <div>
                                            <strong>Questions?</strong><br>
                                            <a href="mailto:pinpointme@gmail.com" class="text-grey-darken-1">pinpointme@gmail.com</a>
                                        </div>
                                    </div>
                                </div>
                            </v-window-item>

                            <!-- Privacy Policy Tab -->
                            <v-window-item value="privacy">
                                <div class="terms-text pa-4">
                                    <div class="terms-header-badge mb-4">
                                        <p class="compliance-text">
                                            <v-icon size="16" color="#3674B5">mdi-shield-check</v-icon>
                                            RA 10173 Compliant
                                        </p>
                                    </div>

                                    <h3 class="section-title">1. Introduction</h3>
                                    <p>
                                        St. Dominic Medical Center is committed to protecting your privacy.
                                        This Privacy Policy explains how we collect, use, and protect your personal information
                                        through PinPointMe in compliance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>.
                                    </p>

                                    <h3 class="section-title">2. Information We Collect</h3>
                                    
                                    <div class="info-category">
                                        <v-icon color="#3674B5" size="20">mdi-account</v-icon>
                                        <strong>Personal Information:</strong>
                                    </div>
                                    <ul class="terms-list compact">
                                        <li>Full name and email address</li>
                                        <li>Patient/visitor or personnel identification details (when applicable)</li>
                                        <li>User role in the hospital incident workflow</li>
                                    </ul>

                                    <div class="info-category">
                                        <v-icon color="#C62828" size="20">mdi-medical-bag</v-icon>
                                        <strong>Emergency Information (Optional):</strong>
                                    </div>
                                    <ul class="terms-list compact">
                                        <li>Emergency contact details</li>
                                        <li>Blood type, allergies, and relevant medical conditions</li>
                                    </ul>

                                    <div class="info-category">
                                        <v-icon color="#3674B5" size="20">mdi-map-marker</v-icon>
                                        <strong>Usage Data:</strong>
                                    </div>
                                    <ul class="terms-list compact">
                                        <li>Location data recorded during active QR code scans</li>
                                        <li>Incident request history</li>
                                    </ul>

                                    <h3 class="section-title">3. How We Use Your Information</h3>
                                    <p>Your information is used to:</p>
                                    <ul class="terms-list">
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Facilitate ward incident response coordination</li>
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Enable location identification via QR code logs during incidents</li>
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Allow direct communication between users and responders</li>
                                        <li><v-icon size="16" color="#185D33">mdi-check-circle</v-icon> Comply with hospital safety and response protocols</li>
                                    </ul>

                                    <h3 class="section-title">4. Data Sharing and Disclosure</h3>
                                    <p>Your information may be shared with:</p>
                                    <ul class="terms-list compact">
                                        <li>Authorized clinical personnel and ward responders</li>
                                        <li>Hospital ICT administrators for system operations and audit trails</li>
                                        <li>Relevant hospital emergency units during active incidents</li>
                                    </ul>

                                    <h3 class="section-title">5. Data Retention</h3>
                                    <p>
                                        Personal data is retained based on hospital policy and legal retention requirements.
                                        Incident records may be kept for patient safety review, quality assurance, and incident documentation.
                                    </p>

                                    <h3 class="section-title">6. Your Rights</h3>
                                    <p>
                                        Under the Data Privacy Act, you have the right to correct, or request the deletion of 
                                        your data, subject to legal and institutional retention requirements.
                                    </p>

                                    <h3 class="section-title">7. Data Security</h3>
                                    <p>
                                        We implement technical measures, authentication and secure access controls, to protect 
                                        your data from unauthorized access.
                                    </p>

                                    <h3 class="section-title">8. Data Protection Officer</h3>
                                    <div class="contact-box">
                                        <v-icon color="#3674B5" class="mr-2">mdi-shield-account</v-icon>
                                        <div>
                                            <strong>Data Protection Officer</strong><br>
                                            <span class="text-grey-darken-1">pinpointme@gmail.com</span>
                                        </div>
                                    </div>
                                </div>
                            </v-window-item>
                        </v-window>
                    </div>

                    <!-- Scroll Indicator -->
                    <div v-if="!hasScrolledToBottom" class="scroll-indicator">
                        <v-icon color="#3674B5" size="18">mdi-chevron-double-down</v-icon>
                        <span>Scroll down to read more</span>
                    </div>
                </v-card>

                <!-- Consent Card -->
                <v-card class="consent-card mx-4 mt-2 mb-15" elevation="2" rounded="xl">
                    <v-card-text class="pa-3">
                        <v-checkbox
                            v-model="agreedToTerms"
                            color="#3674B5"
                            hide-details
                            class="consent-checkbox"
                        >
                            <template v-slot:label>
                                <span class="consent-label">
                                    I have read, understood, and agree to the 
                                    <strong>Terms & Conditions</strong> and <strong>Privacy Policy</strong>.
                                    I consent to the collection and processing of my personal data as described.
                                </span>
                            </template>
                        </v-checkbox>

                        <v-btn
                            :disabled="!agreedToTerms || isSubmitting"
                            :loading="isSubmitting"
                            color="#3674B5"
                            size="large"
                            block
                            rounded="lg"
                            class="continue-btn mt-3"
                            @click="acceptTerms"
                        >
                            <v-icon start>mdi-check-circle</v-icon>
                            Continue to PinPointMe
                        </v-btn>

                        <p class="legal-note mt-2 text-center">
                            <v-icon size="14" color="grey">mdi-information-outline</v-icon>
                            By clicking "Continue", you create a legally binding agreement. 
                            Your consent will be recorded for compliance purposes.
                        </p>
                    </v-card-text>
                </v-card>
            </div>
        </v-main>

        <!-- Success Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000" location="top">
            {{ snackbar.message }}
            <template v-slot:actions>
                <v-btn variant="text" @click="snackbar.show = false">Close</v-btn>
            </template>
        </v-snackbar>
        

    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axiosInstance from '@/Utilities/axios';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Current terms version - update this when terms change
const TERMS_VERSION = '1.0';

// State
const activeTab = ref('terms');
const agreedToTerms = ref(false);
const isSubmitting = ref(false);
const hasScrolledToBottom = ref(false);
const scrollArea = ref(null);
const snackbar = ref({
    show: false,
    message: '',
    color: 'success'
});

// Handle scroll to track if user has read the content
const handleScroll = () => {
    if (scrollArea.value) {
        const { scrollTop, scrollHeight, clientHeight } = scrollArea.value;
        if (scrollTop + clientHeight >= scrollHeight - 50) {
            hasScrolledToBottom.value = true;
        }
    }
};

// Accept terms and continue
const acceptTerms = async () => {
    if (!agreedToTerms.value) return;
    
    isSubmitting.value = true;
    
    try {
        const response = await axiosInstance.post('/accept-terms', {
            version: TERMS_VERSION
        });
        
        if (response.data.success) {
            snackbar.value = {
                show: true,
                message: 'Terms accepted successfully! Redirecting...',
                color: 'success'
            };
            
            // Redirect to the appropriate dashboard
            setTimeout(() => {
                // Use window.location.href for reliable redirect on all platforms (mobile/tablet/desktop)
                window.location.href = response.data.redirect || '/user/scanner';
            }, 1000);
        }
    } catch (error) {
        console.error('Error accepting terms:', error);
        snackbar.value = {
            show: true,
            message: error.response?.data?.message || 'Failed to accept terms. Please try again.',
            color: 'error'
        };
        isSubmitting.value = false;
    }
};

onMounted(() => {
    // Check scroll position on mount
    if (scrollArea.value) {
        handleScroll();
    }
});
</script>

<style scoped>
/* Background gradient matching other user pages */
.bg-user-gradient-light {
    background: linear-gradient(180deg, #e8f0f8 0%, #f5f7fa 100%) !important;
    min-height: 100vh;
}

.terms-container {
    max-width: 600px;
    margin: 0 auto;
    padding-bottom: 24px;
}

/* Header Section */
.terms-header {
    background: #3674B5;
    padding: 32px 24px 16px;
    position: relative;
}

.terms-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.5;
}

.header-content {
    position: relative;
    z-index: 1;
}

.logo-section {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
}

.logo {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.logo-text h1 {
    color: white;
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: 0.5px;
}

.logo-text p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.8rem;
    margin: 0;
}

.welcome-text {
    text-align: center;
}

.welcome-icon {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    margin: 0 auto;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.welcome-title {
    color: white;
    font-size: 1.3rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.5px;
}

.welcome-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.9rem;
    line-height: 1.6;
    margin: 0;
    max-width: 420px;
    margin-left: auto;
    margin-right: auto;
}

.welcome-highlight {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 12px 20px;
    margin: 0 auto;
    max-width: 380px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.highlight-text {
    color: rgba(255, 255, 255, 0.95);
    font-size: 0.8rem;
    font-weight: 500;
    line-height: 1.4;
}

/* Terms Card */
.terms-card {
    overflow: hidden;
}

.terms-tabs {
    background: #fafafa;
}

.terms-tabs :deep(.v-tab) {
    text-transform: none;
    font-weight: 600;
    letter-spacing: 0;
    min-height: 48px;
}

/* Scrollable Area */
.terms-scroll-area {
    height: 420px;
    overflow-y: auto;
    scroll-behavior: smooth;
}

.terms-scroll-area::-webkit-scrollbar {
    width: 6px;
}

.terms-scroll-area::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.terms-scroll-area::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.terms-scroll-area::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Terms Text Styling */
.terms-text {
    color: #333;
    line-height: 1.6;
    font-size: 0.85rem;
}

.terms-header-badge {
    text-align: center;
}

.section-title {
    color: #3674B5;
    font-size: 0.95rem;
    font-weight: 600;
    margin-top: 20px;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 2px solid #DFA92C;
    display: inline-block;
}

.terms-text p {
    margin-bottom: 12px;
    text-align: justify;
}

.terms-list {
    list-style: none;
    padding-left: 0;
    margin-bottom: 16px;
}

.terms-list li {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 8px;
    padding: 6px 10px;
    background: #f8faf8;
    border-radius: 6px;
    font-size: 0.8rem;
}

.terms-list li .v-icon {
    flex-shrink: 0;
    margin-top: 2px;
}

.terms-list.warning li {
    background: #fef8f8;
}

.terms-list.compact li {
    background: transparent;
    padding: 4px 0;
}

/* Info Categories */
.info-category {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 16px;
    margin-bottom: 8px;
    color: #333;
}

/* Legal Basis Grid */
.legal-basis-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin: 16px 0;
}

.legal-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    background: #fffbf0;
    border-radius: 8px;
    border: 1px solid #f0e6d0;
}

.legal-item span {
    font-size: 0.8rem;
    line-height: 1.4;
}

/* Version and Compliance Info */
.version-text, .compliance-text {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #3674B5;
    font-size: 0.85rem;
    text-align: center;
    justify-content: center;
}

.version-info, .compliance-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #3674B5;
    font-size: 0.85rem;
    text-align: center;
    justify-content: center;
}

/* Contact Box */
.contact-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f5f7fa;
    border-radius: 10px;
    margin-top: 12px;
}

.contact-box div {
    font-size: 0.8rem;
    line-height: 1.5;
}

/* Scroll Indicator */
.scroll-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    background: linear-gradient(to top, #f5f5f5, transparent);
    color: #3674B5;
    font-size: 0.75rem;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-4px); }
    60% { transform: translateY(-2px); }
}

/* Consent Card */
.consent-card {
    border: 2px solid #e0e0e0;
    transition: border-color 0.3s ease;
}

.consent-card:has(.v-checkbox--selected) {
    border-color: #3674B5;
}

.consent-checkbox :deep(.v-label) {
    opacity: 1;
}

.consent-label {
    color: #333;
    font-size: 0.85rem;
    line-height: 1.5;
}

.consent-label strong {
    color: #3674B5;
}

.continue-btn {
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: none;
    height: 48px;
}

.legal-note {
    color: #666;
    font-size: 0.7rem;
    line-height: 1.5;
}

/* Responsive Adjustments */
@media (max-width: 599px) {
    .terms-container {
        padding-bottom: 60px;
    }

    .consent-card {
        margin-bottom: 90px !important;
    }

    .terms-header {
        padding: 24px 16px 16px;
    }

    .logo {
        width: 48px;
        height: 48px;
    }

    .logo-text h1 {
        font-size: 1.4rem;
    }

    .welcome-icon {
        width: 50px;
        height: 50px;
    }

    .welcome-title {
        font-size: 1.1rem;
    }

    .welcome-subtitle {
        font-size: 0.85rem;
        max-width: 300px;
    }

    .welcome-highlight {
        padding: 10px 16px;
        max-width: 320px;
    }

    .highlight-text {
        font-size: 0.75rem;
    }

    .terms-scroll-area {
        height: 380px;
    }

    .legal-basis-grid {
        grid-template-columns: 1fr;
    }

    .section-title {
        font-size: 0.9rem;
    }

    .terms-text {
        font-size: 0.8rem;
    }
}

@media (min-width: 600px) {
    .terms-container {
        padding-top: 16px;
    }

    .terms-scroll-area {
        height: 460px;
    }
}
</style>
