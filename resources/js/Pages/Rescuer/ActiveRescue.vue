<template>
    <v-app class="bg-user-gradient-light">
        <!-- Custom Header -->
        <div class="rescue-header">
            <div class="header-content">
                <v-btn icon variant="text" class="back-btn" @click="goBack">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <div class="header-title">
                    <h1>Active Rescue</h1>
                    <p v-if="rescueRequest?.rescue_code">Code: {{ rescueRequest.rescue_code }}</p>
                </div>
                <v-chip
                    v-if="currentStatus"
                    :color="getStatusChipColor(currentStatus)"
                    variant="flat"
                    size="small"
                    class="status-chip"
                >
                    <v-icon start size="14">{{ getStatusIcon(currentStatus) }}</v-icon>
                    {{ formatStatus(currentStatus) }}
                </v-chip>
            </div>
        </div>

        <!-- Main Content -->
        <v-main class="rescue-main">
            <!-- Loading State -->
            <div v-if="loading" class="loading-container">
                <v-progress-circular indeterminate color="primary" size="64" />
                <p class="mt-4 text-grey">Loading rescue details...</p>
            </div>

            <!-- No Rescue Found -->
            <div v-else-if="!rescueRequest" class="empty-state">
                <v-icon size="64" color="grey-lighten-1">mdi-alert-circle-outline</v-icon>
                <h3>No Active Rescue</h3>
                <p>You will be redirected to the dashboard.</p>
            </div>

            <!-- Rescue Details -->
            <div v-else class="rescue-content">
                <!-- Completion Banner (if completed) - HelpComing Style -->
                <div v-if="currentStatus === 'rescued' || currentStatus === 'safe' || currentStatus === 'completed'" class="text-center py-4 mb-4">
                    <v-icon size="56" color="success" class="mb-2">mdi-check-circle</v-icon>
                    <h3 class="text-h6 mb-1">Rescue Complete</h3>
                    
                    <p class="text-grey mb-3">Person has been successfully rescued and marked as safe.</p>
                </div>

                <!-- Urgency Banner -->
                <div :class="['urgency-banner', `urgency-${rescueRequest.urgency_level?.toLowerCase() || 'medium'}`]">
                    <div class="urgency-icon">
                        <v-icon size="28" color="white">{{ getUrgencyIcon(rescueRequest.urgency_level) }}</v-icon>
                    </div>
                    <div class="urgency-info">
                        <span class="urgency-label">{{ rescueRequest.urgency_level || 'Emergency' }} Priority</span>
                        <span class="urgency-time">{{ getElapsedTime(rescueRequest.created_at) }}</span>
                    </div>
                </div>

                <!-- Person Card -->
                <div class="info-card person-card">
                    <div class="card-header">
                        <v-icon size="20" color="primary">mdi-account-alert</v-icon>
                        <span>Person in Need</span>
                    </div>
                    <div class="person-info">
                        <v-avatar 
                            size="56" 
                            :color="requesterProfilePicture ? 'transparent' : 'primary'"
                            class="person-avatar"
                            @click="requesterProfilePicture && openPhotoViewer(requesterProfilePicture, getRequesterFullName())"
                        >
                            <v-img 
                                v-if="requesterProfilePicture" 
                                :src="requesterProfilePicture" 
                                cover 
                            />
                            <span v-else class="text-h6 text-white">{{ getPersonInNeedInitials() }}</span>
                        </v-avatar>
                        <div class="person-details">
                            <h3 class="person-name">{{ getPersonInNeedName() }}</h3>
                            <p v-if="isReportingForOthers" class="person-reporter">
                                Reported by: {{ rescueRequest.requester?.first_name }} {{ rescueRequest.requester?.last_name }}
                            </p>
                            <p v-else class="person-contact">
                                <v-icon size="14">mdi-phone</v-icon>
                                {{ rescueRequest.requester?.phone || rescueRequest.contact_number || 'No contact' }}
                            </p>
                        </div>
                        <v-btn
                            v-if="rescueRequest.requester || rescueRequest.user_id"
                            icon
                            size="small"
                            variant="tonal"
                            color="primary"
                            @click="showUserProfile = true"
                        >
                            <v-icon size="20">mdi-information</v-icon>
                        </v-btn>
                    </div>
                </div>

                <!-- Location Card -->
                <div class="info-card location-card">
                    <div class="card-header">
                        <v-icon size="20" color="error">mdi-map-marker</v-icon>
                        <span>Location</span>
                    </div>
                    <div class="location-info">
                        <div class="location-primary">
                            <span class="room-name">{{ rescueRequest.room?.room_name || 'Unknown Room' }}</span>
                        </div>
                        <div class="location-secondary">
                            <span>{{ rescueRequest.floor?.floor_name || 'Unknown Floor' }}</span>
                            <span class="separator">•</span>
                            <span>{{ rescueRequest.building?.name || 'Unknown Building' }}</span>
                        </div>
                    </div>
                    <v-btn
                        variant="flat"
                        color="#3674B5"
                        size="large"
                        class="map-btn"
                        block
                        elevation="4"
                        @click="viewMap"
                    >
                        <v-icon start size="20">mdi-map-marker-radius</v-icon>
                        View Floor Map
                    </v-btn>
                </div>

                <!-- Description Card (if exists) -->
                <div v-if="rescueRequest.description" class="info-card description-card">
                    <div class="card-header">
                        <v-icon size="20" color="orange">mdi-text-box</v-icon>
                        <span>Situation Details</span>
                        <v-chip v-if="rescueRequest.original_description" size="x-small" color="info" variant="tonal" class="ml-auto">
                            <v-icon start size="12">mdi-translate</v-icon>
                            Translated
                        </v-chip>
                    </div>
                    <p class="description-text">{{ rescueRequest.description }}</p>
                    <!-- Translate button if non-English and not yet translated -->
                    <div v-if="rescueRequest.is_translated && !rescueRequest.original_description" class="mt-2">
                        <v-btn
                            size="small"
                            variant="tonal"
                            color="info"
                            :loading="isTranslating"
                            @click="handleTranslate"
                            prepend-icon="mdi-translate"
                            class="rounded-lg"
                        >
                            Translate to English
                        </v-btn>
                    </div>
                    <!-- Show original text if translated -->
                    <div v-if="rescueRequest.original_description" class="mt-2" style="padding: 8px 12px; background: #f5f5f5; border-radius: 8px; border-left: 3px solid #42A5F5;">
                        <div class="text-caption text-grey-darken-1 mb-1 d-flex align-center">
                            <v-icon size="12" class="mr-1">mdi-translate</v-icon>
                            Original text
                        </div>
                        <p class="text-caption" style="font-style: italic; margin: 0; color: #616161;">{{ rescueRequest.original_description }}</p>
                    </div>
                </div>

                <!-- Additional Info Card -->
                <div v-if="rescueRequest.people_count || rescueRequest.mobility_status || rescueRequest.injuries || hasMediaAttachments || hasMedicalInfo || hasEmergencyContact" class="info-card additional-card">
                    <div class="card-header">
                        <v-icon size="20" color="info">mdi-clipboard-list</v-icon>
                        <span>Additional Information</span>
                    </div>
                    <div class="chips-container">
                        <v-chip v-if="rescueRequest.people_count" size="small" variant="tonal" color="info">
                            <v-icon start size="16">mdi-account-group</v-icon>
                            {{ rescueRequest.people_count }} {{ rescueRequest.people_count > 1 ? 'people' : 'person' }}
                        </v-chip>
                        <v-chip v-if="rescueRequest.mobility_status" size="small" variant="tonal" :color="getMobilityColor(rescueRequest.mobility_status)">
                            <v-icon start size="16">mdi-wheelchair-accessibility</v-icon>
                            {{ formatMobility(rescueRequest.mobility_status) }}
                        </v-chip>
                        <v-chip v-if="rescueRequest.injuries" size="small" variant="tonal" color="error">
                            <v-icon start size="16">mdi-bandage</v-icon>
                            {{ rescueRequest.injuries }}
                        </v-chip>
                        <v-chip v-if="rescueRequest.original_injuries" size="x-small" variant="tonal" color="info">
                            <v-icon start size="12">mdi-translate</v-icon>
                            Original: {{ rescueRequest.original_injuries }}
                        </v-chip>
                    </div>

                    <!-- Medical Information Section -->
                    <div v-if="hasMedicalInfo" class="medical-section">
                        <div class="medical-section-header">
                            <v-icon size="16" color="red">mdi-medical-bag</v-icon>
                            <span>Medical Information</span>
                        </div>
                        <div class="medical-info">
                            <div v-if="rescueRequest.requester?.blood_type" class="medical-item">
                                <div class="medical-icon blood">
                                    <v-icon size="18" color="white">mdi-blood-bag</v-icon>
                                </div>
                                <div class="medical-details">
                                    <span class="medical-label">Blood Type</span>
                                    <span class="medical-value">{{ rescueRequest.requester.blood_type }}</span>
                                </div>
                            </div>
                            <div v-if="rescueRequest.requester?.allergies" class="medical-item">
                                <div class="medical-icon allergy">
                                    <v-icon size="18" color="white">mdi-allergy</v-icon>
                                </div>
                                <div class="medical-details">
                                    <span class="medical-label">Allergies</span>
                                    <span class="medical-value warning">{{ rescueRequest.requester.allergies }}</span>
                                </div>
                            </div>
                            <div v-if="rescueRequest.requester?.medical_conditions" class="medical-item">
                                <div class="medical-icon condition">
                                    <v-icon size="18" color="white">mdi-pill</v-icon>
                                </div>
                                <div class="medical-details">
                                    <span class="medical-label">Medical Conditions</span>
                                    <span class="medical-value">{{ rescueRequest.requester.medical_conditions }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Emergency Contact Section -->
                    <div v-if="hasEmergencyContact" class="emergency-section">
                        <div class="emergency-section-header">
                            <v-icon size="16" color="orange-darken-2">mdi-phone-alert</v-icon>
                            <span>Emergency Contact</span>
                        </div>
                        <div class="emergency-info">
                            <div class="emergency-item">
                                <div class="medical-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                    <v-icon size="18" color="white">mdi-account-alert</v-icon>
                                </div>
                                <div class="medical-details">
                                    <span class="medical-label">Contact Name</span>
                                    <span class="medical-value">
                                        {{ rescueRequest.requester?.emergency_contact_name || 'N/A' }}
                                        <span v-if="rescueRequest.requester?.emergency_contact_relation" class="text-grey text-caption">
                                            ({{ rescueRequest.requester.emergency_contact_relation }})
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div v-if="rescueRequest.requester?.emergency_contact_phone" class="emergency-item">
                                <div class="medical-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                                    <v-icon size="18" color="white">mdi-phone</v-icon>
                                </div>
                                <div class="medical-details">
                                    <span class="medical-label">Phone Number</span>
                                    <span class="medical-value">{{ rescueRequest.requester.emergency_contact_phone }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Emergency Contact Action Button -->
                        <div v-if="rescueRequest.requester?.emergency_contact_phone" class="emergency-action mt-3">
                            <v-btn
                                variant="flat"
                                color="success"
                                size="large"
                                rounded="xl"
                                @click="callEmergencyContact"
                                elevation="4"
                                block
                            >
                                <v-icon start size="20">mdi-phone</v-icon>
                                Call Emergency Contact
                            </v-btn>
                        </div>
                    </div>
                </div>

                <!-- Media Attachments Section -->
                <div v-if="hasMediaAttachments" class="media-section">

                    <div v-if="hasMediaAttachments" class="media-section">
                        <div class="media-section-header">
                            <v-icon size="16" color="purple">mdi-image-multiple</v-icon>
                            <span>Attached Media ({{ mediaAttachments.length }})</span>
                        </div>
                        <div class="media-grid">
                            <div 
                                v-for="(media, index) in mediaAttachments" 
                                :key="index" 
                                class="media-item"
                                @click="openMediaViewer(media, index)"
                            >
                                <!-- Image Thumbnail -->
                                <template v-if="media.type === 'image'">
                                    <v-img
                                        :src="media.url"
                                        :alt="media.original_name || 'Attachment'"
                                        cover
                                        class="media-thumbnail"
                                    >
                                        <template v-slot:placeholder>
                                            <div class="d-flex align-center justify-center fill-height">
                                                <v-progress-circular indeterminate color="primary" size="24" />
                                            </div>
                                        </template>
                                    </v-img>
                                    <div class="media-overlay">
                                        <v-icon color="white" size="20">mdi-magnify-expand</v-icon>
                                    </div>
                                </template>
                                <!-- Video Thumbnail -->
                                <template v-else-if="media.type === 'video'">
                                    <div class="video-thumbnail">
                                        <video :src="media.url" muted preload="metadata" />
                                        <div class="video-play-overlay">
                                            <v-icon color="white" size="32">mdi-play-circle</v-icon>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-section">
                    <!-- Pending or Assigned Status - Accept & Start in one action -->
                    <div v-if="currentStatus === 'pending' || currentStatus === 'assigned'" class="action-container">
                        <div class="primary-action">
                            <!-- Cancel In Progress Warning -->
                            <v-alert 
                                v-if="rescueRequest?.cancel_in_progress_at"
                                type="warning"
                                variant="tonal"
                                density="compact"
                                class="mb-3"
                                icon="mdi-clock-alert-outline"
                            >
                                <div class="text-subtitle-2 font-weight-bold">User Processing Cancellation</div>
                                <div class="text-body-2">You cannot accept this request while the user is going through cancellation steps.</div>
                            </v-alert>

                            <!-- Marking Safe In Progress Warning -->
                            <v-alert 
                                v-if="rescueRequest?.marking_safe_in_progress_at"
                                type="info"
                                variant="tonal"
                                density="compact"
                                class="mb-3"
                                icon="mdi-shield-check-outline"
                            >
                                <div class="text-subtitle-2 font-weight-bold">User Considering Marking Self Safe</div>
                                <div class="text-body-2">You cannot accept this request while the user is considering marking themselves as safe.</div>
                            </v-alert>

                            <v-btn
                                color="success"
                                size="x-large"
                                block
                                rounded="xl"
                                @click="acceptRescue"
                                :loading="updating"
                                :disabled="!!rescueRequest?.cancel_in_progress_at || !!rescueRequest?.marking_safe_in_progress_at"
                                class="main-action-btn accept-btn"
                                elevation="3"
                            >
                                <v-icon start size="22">mdi-run-fast</v-icon>
                                <span class="btn-text">Accept & Start Rescue</span>
                            </v-btn>
                        </div>
                        
                        <!-- Cancel option for assigned status -->
                        <div v-if="currentStatus === 'assigned'" class="secondary-actions">
                            <v-btn
                                variant="outlined"
                                color="error"
                                size="large"
                                rounded="xl"
                                @click="showCancelDialog = true; cancellationReason = ''"
                                class="secondary-btn cancel-btn"
                            >
                                <v-icon start size="18">mdi-close-circle-outline</v-icon>
                                <span>Cancel Assignment</span>
                            </v-btn>
                        </div>
                    </div>

                    <!-- In Progress Status -->
                    <div v-else-if="currentStatus === 'in_progress'" class="action-container">
                        <!-- Pending Safe Approval Banner -->
                        <div 
                            v-if="rescueRequest?.safe_approval_requested && rescueRequest?.safe_approval_status === 'pending'" 
                            class="pending-approval-banner"
                            @click="showSafeApprovalDialog = true"
                        >
                            <div class="banner-pulse"></div>
                            <v-icon color="info" size="24" class="mr-3">mdi-account-clock</v-icon>
                            <div class="banner-content">
                                <span class="banner-title">User Requesting Safe Status</span>
                                <span class="banner-subtitle">Tap to review and respond</span>
                            </div>
                            <v-icon color="grey" size="20">mdi-chevron-right</v-icon>
                        </div>

                        <!-- Cancel In Progress Banner -->
                        <div 
                            v-if="rescueRequest?.cancel_in_progress_at && !(rescueRequest?.cancel_approval_requested && rescueRequest?.cancel_approval_status === 'pending')" 
                            class="pending-approval-banner cancel-in-progress-banner"
                        >
                            <div class="banner-pulse-orange"></div>
                            <v-icon color="orange" size="24" class="mr-3">mdi-clock-outline</v-icon>
                            <div class="banner-content">
                                <span class="banner-title">User Considering Cancellation</span>
                                <span class="banner-subtitle">Started {{ getElapsedTime(rescueRequest.cancel_in_progress_at) }}</span>
                            </div>
                            <v-icon color="grey" size="20">mdi-information</v-icon>
                        </div>

                        <!-- Marking Safe In Progress Banner -->
                        <!-- <div 
                            v-if="rescueRequest?.marking_safe_in_progress_at && !(rescueRequest?.safe_approval_requested && rescueRequest?.safe_approval_status === 'pending')" 
                            class="pending-approval-banner marking-safe-in-progress-banner"
                        >
                            <div class="banner-pulse-blue"></div>
                            <v-icon color="info" size="24" class="mr-3">mdi-shield-check-outline</v-icon>
                            <div class="banner-content">
                                <span class="banner-title">User Considering Marking Self Safe</span>
                                <span class="banner-subtitle">Started {{ getElapsedTime(rescueRequest.marking_safe_in_progress_at) }}</span>
                            </div>
                            <v-icon color="grey" size="20">mdi-information</v-icon>
                        </div> -->

                        <!-- Pending Cancel Approval Banner -->
                        <div 
                            v-if="rescueRequest?.cancel_approval_requested && rescueRequest?.cancel_approval_status === 'pending'" 
                            class="pending-approval-banner cancel-approval-banner"
                            @click="showCancelApprovalDialog = true"
                        >
                            <div class="banner-pulse"></div>
                            <v-icon color="warning" size="24" class="mr-3">mdi-cancel</v-icon>
                            <div class="banner-content">
                                <span class="banner-title">User Requesting Cancellation</span>
                                <span class="banner-subtitle">Tap to review and respond</span>
                            </div>
                            <v-icon color="grey" size="20">mdi-chevron-right</v-icon>
                        </div>
                        
                        <div class="primary-action">
                            <!-- Slide to confirm Mark as Safe -->
                            <div class="slide-to-confirm" @mousedown="startSlide" @touchstart="startSlide">
                                <div class="slide-track">
                                    <div class="slide-progress" :style="{ width: slideProgress + '%' }"></div>
                                    <div 
                                        class="slide-thumb" 
                                        :class="{ 'slide-complete': isSlideComplete, 'slide-active': isSliding }"
                                        :style="{ transform: `translateX(${slidePosition}px) translateY(-50%)` }"
                                    >
                                        <v-icon size="24" color="white">
                                            {{ isSlideComplete ? 'mdi-check' : 'mdi-shield-check' }}
                                        </v-icon>
                                    </div>
                                    <div class="slide-text">
                                        <span v-if="!isSlideComplete" class="slide-instruction">
                                            {{ isSliding ? 'Keep sliding...' : 'Slide to mark as safe' }}
                                        </span>
                                        <span v-else class="slide-success">
                                            Person marked as safe!
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="secondary-actions">
                            <div class="action-row">
                                <v-btn
                                    variant="flat"
                                    color="#3674B5"
                                    size="large"
                                    rounded="xl"
                                    @click="openChat"
                                    class="secondary-btn flex-btn"
                                    flex
                                >
                                    <v-icon start size="18">mdi-message-text-outline</v-icon>
                                    <span>Message</span>
                                </v-btn>
                                
                                <v-btn
                                    variant="outlined"
                                    color="error"
                                    size="large"
                                    rounded="xl"
                                    @click="showCancelDialog = true; cancellationReason = ''"
                                    class="secondary-btn cancel-btn flex-btn"
                                    flex
                                >
                                    <v-icon start size="18">mdi-close-circle-outline</v-icon>
                                    <span>Cancel</span>
                                </v-btn>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Status - HelpComing Style -->
                    <div v-else-if="currentStatus === 'rescued' || currentStatus === 'safe' || currentStatus === 'completed'" class="completed-actions">
                        <!-- Summary Toggle Button -->
                        <v-btn
                            variant="flat"
                            color="#3674B5"
                            rounded="xl"
                            size="large"
                            class="mt-2 mb-2"
                            @click="showSummary = !showSummary"
                        >
                            <v-icon start>mdi-clipboard-text</v-icon>
                            {{ showSummary ? 'Hide' : 'View' }} Rescue Summary
                        </v-btn>

                        <!-- Rescue Summary Card (toggleable) -->
                        <v-expand-transition>
                            <div v-show="showSummary" class="rescue-summary-card mt-4">
                                <div class="summary-header">
                                    <v-icon size="20" color="primary">mdi-clipboard-text</v-icon>
                                    <span class="summary-header-text">Rescue Summary</span>
                                </div>

                                <div class="summary-details">
                                    <div class="summary-row">
                                        <v-icon size="16" color="grey">mdi-account</v-icon>
                                        <span class="summary-label">Person Rescued</span>
                                        <span class="summary-value">{{ rescueRequest?.firstName }} {{ rescueRequest?.lastName }}</span>
                                    </div>
                                    <div class="summary-row">
                                        <v-icon size="16" color="grey">mdi-map-marker</v-icon>
                                        <span class="summary-label">Location</span>
                                        <span class="summary-value">{{ rescueRequest?.room?.room_name || rescueRequest?.floor?.floor_name || 'N/A' }}</span>
                                    </div>
                                    <div class="summary-row">
                                        <v-icon size="16" color="grey">mdi-alert-circle</v-icon>
                                        <span class="summary-label">Urgency</span>
                                        <v-chip :color="getUrgencyColor(rescueRequest?.urgency_level)" size="x-small" class="text-capitalize">
                                            {{ rescueRequest?.urgency_level || 'Medium' }}
                                        </v-chip>
                                    </div>
                                    <div class="summary-row">
                                        <v-icon size="16" color="grey">mdi-clock-outline</v-icon>
                                        <span class="summary-label">Duration</span>
                                        <span class="summary-value">{{ rescueDuration }}</span>
                                    </div>
                                    <div v-if="rescueRequest?.completion_notes" class="summary-row summary-row-block">
                                        <v-icon size="16" color="grey">mdi-note-text</v-icon>
                                        <span class="summary-label">Notes</span>
                                        <p class="summary-notes">{{ rescueRequest.completion_notes }}</p>
                                    </div>
                                </div>

                                <!-- Completion Photo -->
                                <div v-if="rescueRequest?.completion_photo" class="summary-photo-section">
                                    <div class="summary-photo-label">
                                        <v-icon size="16" color="grey">mdi-camera</v-icon>
                                        <span>Documentation Photo</span>
                                    </div>
                                    <div class="summary-photo-wrap" @click="openPhotoViewer(rescueRequest.completion_photo, 'Rescue Completion Photo')">
                                        <img :src="rescueRequest.completion_photo" alt="Rescue completion photo" class="summary-photo" />
                                        <div class="summary-photo-overlay">
                                            <v-icon color="white" size="24">mdi-magnify-plus</v-icon>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </v-expand-transition>
                    </div>
                </div>
            </div>
        </v-main>

        <!-- Mark as Safe Dialog -->
        <v-dialog v-model="showCompleteDialog" max-width="480">
            <v-card rounded="xl">
                <div class="complete-dialog-header">
                    <v-icon size="28" color="white">mdi-shield-check</v-icon>
                    <span class="complete-dialog-title">Mark as Safe</span>
                </div>
                <v-card-text class="pa-5">
                    <p class="text-body-2 text-medium-emphasis mb-4">
                        Confirm the person is rescued and safe. You may add notes and a photo of the rescue for documentation.
                    </p>

                    <v-textarea
                        v-model="completionNotes"
                        label="Rescue Notes (optional)"
                        placeholder="Describe what you did, condition of the person, etc."
                        rows="3"
                        variant="outlined"
                        density="comfortable"
                        class="mb-3"
                    />

                    <!-- Photo Upload -->
                    <div class="completion-photo-section">
                        <label class="photo-upload-label">
                            <v-icon start size="18">mdi-camera</v-icon>
                            Completion Photo <span style="color: #C62828;">*</span>
                        </label>
                        <p class="text-caption text-medium-emphasis mb-2">
                            Take or upload a photo as proof that the patient is okay. <strong>Required.</strong>
                        </p>

                        <div v-if="completionPhotoPreview" class="completion-photo-preview">
                            <img :src="completionPhotoPreview" alt="Completion photo preview" />
                            <v-btn
                                icon
                                size="x-small"
                                color="error"
                                class="remove-photo-btn"
                                @click="removeCompletionPhoto"
                            >
                                <v-icon size="16">mdi-close</v-icon>
                            </v-btn>
                        </div>

                        <v-btn
                            v-else
                            variant="outlined"
                            color="primary"
                            block
                            rounded="lg"
                            @click="triggerCompletionPhotoInput"
                            class="photo-upload-btn"
                        >
                            <v-icon start>mdi-camera-plus</v-icon>
                            Take or Upload Photo
                        </v-btn>

                        <input
                            ref="completionPhotoInputRef"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            capture="environment"
                            style="display: none"
                            @change="onCompletionPhotoSelected"
                        />
                    </div>
                </v-card-text>
                <v-card-actions class="px-5 pb-4">
                    <v-btn variant="text" rounded="lg" @click="showCompleteDialog = false; resetSlide()">Cancel</v-btn>
                    <v-spacer />
                    <v-btn
                        color="success"
                        variant="flat"
                        rounded="lg"
                        @click="completeRescue"
                        :loading="updating"
                        :disabled="!completionPhotoFile"
                        class="px-6"
                    >
                        <v-icon start size="18">mdi-check-circle</v-icon>
                        Confirm Safe
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Cancel Dialog -->
        <v-dialog v-model="showCancelDialog" max-width="440">
            <v-card rounded="xl">
                <v-card-title class="d-flex align-center pa-4 bg-primary">
                    <v-icon color="white" class="mr-2">mdi-alert-circle</v-icon>
                    <span class="text-white text-subtitle-1 font-weight-bold">
                        {{ currentStatus === 'assigned' ? 'Cancel Assignment' : 'Cancel Rescue' }}
                    </span>
                    <v-spacer />
                    <v-btn icon variant="text" size="small" @click="showCancelDialog = false; cancellationReason = ''; cancelType = 'valid'">
                        <v-icon color="white">mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pa-5">
                    <!-- Cancel Type Selection -->
                    <p class="text-body-2 text-grey-darken-1 mb-3">What is the reason for cancellation?</p>
                    
                    <div class="cancel-type-options mb-4">
                        <div 
                            :class="['cancel-option', cancelType === 'valid' ? 'cancel-option-active' : '']"
                            @click="cancelType = 'valid'"
                        >
                            <v-icon :color="cancelType === 'valid' ? 'warning' : 'grey'" size="24" class="mb-1">mdi-arrow-u-left-top</v-icon>
                            <span class="cancel-option-title">Valid Cancel</span>
                            <span class="cancel-option-desc">Return request to Need Help queue</span>
                        </div>
                        <div 
                            :class="['cancel-option', cancelType === 'invalid' ? 'cancel-option-active cancel-option-danger' : '']"
                            @click="cancelType = 'invalid'"
                        >
                            <v-icon :color="cancelType === 'invalid' ? 'error' : 'grey'" size="24" class="mb-1">mdi-delete-alert</v-icon>
                            <span class="cancel-option-title">False Report</span>
                            <span class="cancel-option-desc">Delete & report to admin</span>
                        </div>
                    </div>

                    <v-alert v-if="cancelType === 'invalid'" type="warning" variant="tonal" density="compact" class="mb-4">
                        <span class="text-caption">This will permanently delete the request and notify the administrator about the false report.</span>
                    </v-alert>

                    <v-textarea
                        v-model="cancellationReason"
                        :label="cancelType === 'invalid' ? 'Reason for reporting (required)' : 'Reason for cancellation (required)'"
                        rows="3"
                        variant="outlined"
                        density="comfortable"
                        :rules="[v => !!v || 'Reason is required']"
                    />
                </v-card-text>
                <v-card-actions class="pa-4 pt-0">
                    <v-btn variant="outlined" color="grey" @click="showCancelDialog = false; cancellationReason = ''; cancelType = 'valid'" class="rounded-lg">
                        {{ currentStatus === 'assigned' ? 'Keep Assignment' : 'Keep Rescue' }}
                    </v-btn>
                    <v-spacer />
                    <v-btn
                        :color="cancelType === 'invalid' ? 'error' : 'warning'"
                        variant="flat"
                        @click="cancelRescue"
                        :loading="updating"
                        :disabled="!cancellationReason"
                        class="rounded-lg"
                    >
                        <v-icon start size="18">{{ cancelType === 'invalid' ? 'mdi-delete' : 'mdi-close-circle' }}</v-icon>
                        {{ cancelType === 'invalid' ? 'Delete & Report' : 'Cancel Request' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Safe Approval Request Dialog -->
        <v-dialog v-model="showSafeApprovalDialog" max-width="440" persistent>
            <v-card rounded="xl" class="safe-approval-dialog">
                <v-card-title class="d-flex align-center pa-4 bg-info">
                    <div class="pulse-icon mr-3">
                        <v-icon color="white" size="28">mdi-account-check</v-icon>
                    </div>
                    <div class="flex-grow-1">
                        <span class="text-white text-subtitle-1 font-weight-bold d-block">Safe Request Received</span>
                        <span class="text-white-darken-1 text-caption">User wants to mark themselves as safe</span>
                    </div>
                </v-card-title>
                
                <v-card-text class="pa-5">
                    <div class="request-info-card mb-4">
                        <div class="d-flex align-center mb-3">
                            <v-avatar 
                                size="48"
                                :color="requesterProfilePicture ? 'transparent' : 'info'"
                                class="mr-3"
                            >
                                <v-img v-if="requesterProfilePicture" :src="requesterProfilePicture" cover />
                                <span v-else class="text-white text-subtitle-1">{{ getPersonInNeedInitials() }}</span>
                            </v-avatar>
                            <div>
                                <h4 class="text-subtitle-1 font-weight-bold mb-0">{{ getPersonInNeedName() }}</h4>
                                <p class="text-caption text-grey mb-0">is requesting to be marked as safe</p>
                            </div>
                        </div>
                        
                        <v-alert type="info" variant="tonal" density="compact" class="mb-0">
                            <div class="text-caption">
                                <strong>Before approving:</strong> Verify the user is actually safe. 
                                If you're unsure, deny the request and assess in person.
                            </div>
                        </v-alert>
                    </div>
                    
                    <!-- Safe Proof Photo and Reason -->
                    <div v-if="rescueRequest?.safe_proof_photo || rescueRequest?.safe_proof_reason" class="proof-section mb-4">
                        <div class="d-flex align-center mb-2">
                            <v-icon size="18" color="success" class="mr-2">mdi-shield-check</v-icon>
                            <span class="text-subtitle-2 text-grey-darken-2">Safety Proof Submitted</span>
                        </div>
                        
                        <!-- Proof Photo -->
                        <div v-if="rescueRequest?.safe_proof_photo" class="proof-photo-container mb-3">
                            <p class="text-caption text-grey-darken-1 mb-2">
                                <v-icon size="16" class="mr-1">mdi-camera</v-icon>
                                Photo Proof
                            </p>
                            <v-img 
                                :src="getStorageUrl(rescueRequest.safe_proof_photo)"
                                max-height="150"
                                cover
                                class="rounded-lg proof-photo cursor-pointer"
                                style="border: 2px solid #4CAF50;"
                                @click="viewSafeProofPhoto"
                            />
                        </div>
                        
                        <!-- Proof Reason -->
                        <div v-if="rescueRequest?.safe_proof_reason" class="proof-reason-container">
                            <p class="text-caption text-grey-darken-1 mb-2">
                                <v-icon size="16" class="mr-1">mdi-text-box</v-icon>
                                Reason for Safety
                            </p>
                            <v-card variant="outlined" class="pa-3 rounded-lg" style="border-color: #4CAF50;">
                                <p class="text-body-2 mb-0">{{ rescueRequest.safe_proof_reason }}</p>
                            </v-card>
                        </div>
                    </div>
                    
                    <v-tabs v-model="safeApprovalTab" density="compact" class="mb-4" color="info">
                        <v-tab value="approve" prepend-icon="mdi-check-circle">
                            <span class="text-caption">Approve</span>
                        </v-tab>
                        <v-tab value="deny" prepend-icon="mdi-close-circle">
                            <span class="text-caption">Deny</span>
                        </v-tab>
                    </v-tabs>
                    
                    <v-window v-model="safeApprovalTab">
                        <v-window-item value="approve">
                            <div class="text-center py-4">
                                <v-icon size="48" color="success" class="mb-3">mdi-shield-check</v-icon>
                                <p class="text-body-2 text-grey-darken-1">
                                    Confirming this means you verify that the user is safe and no longer requires assistance.
                                    This will complete the rescue operation.
                                </p>
                            </div>
                        </v-window-item>
                        
                        <v-window-item value="deny">
                            <v-textarea
                                v-model="safeApprovalDenyReason"
                                label="Reason for denying (required)"
                                placeholder="e.g., Still need to verify in person, user may be disoriented..."
                                rows="3"
                                variant="outlined"
                                density="comfortable"
                                prepend-inner-icon="mdi-text"
                                :rules="[v => !!v || 'Reason is required']"
                            />
                            <v-alert type="warning" variant="tonal" density="compact" class="mt-2">
                                <div class="text-caption">
                                    The user will be notified that their request was denied and will need to wait for your assessment.
                                </div>
                            </v-alert>
                        </v-window-item>
                    </v-window>
                </v-card-text>
                
                <v-card-actions class="pa-3 pt-0">
                    <!-- Mobile-optimized horizontal button layout -->
                    <div class="d-flex flex-row gap-2 w-100">
                        <v-btn 
                            variant="outlined" 
                            color="grey" 
                            @click="closeSafeApprovalDialog" 
                            class="rounded-lg flex-grow-1"
                            size="small"
                            :disabled="safeApprovalProcessing"
                            style="min-height: 36px;"
                        >
                            <span class="text-overline text-sm-caption font-weight-medium">Decide Later</span>
                        </v-btn>
                        
                        <v-btn
                            v-if="safeApprovalTab === 'approve'"
                            color="success"
                            variant="flat"
                            @click="handleApproveSafeRequest"
                            :loading="safeApprovalProcessing"
                            class="rounded-lg flex-grow-1"
                            size="small"
                            style="min-height: 36px;"
                        >
                            <v-icon start size="14">mdi-check-circle</v-icon>
                            <span class="text-overline text-sm-caption font-weight-medium">Approve</span>
                        </v-btn>
                        
                        <v-btn
                            v-else
                            color="warning"
                            variant="flat"
                            @click="handleDenySafeRequest"
                            :loading="safeApprovalProcessing"
                            :disabled="!safeApprovalDenyReason.trim()"
                            class="rounded-lg flex-grow-1"
                            size="small"
                            style="min-height: 36px;"
                        >
                            <v-icon start size="14">mdi-close-circle</v-icon>
                            <span class="text-overline text-sm-caption font-weight-medium">Deny</span>
                        </v-btn>
                    </div>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Cancel Approval Request Dialog -->
        <v-dialog v-model="showCancelApprovalDialog" max-width="440" persistent>
            <v-card rounded="xl" class="cancel-approval-dialog">
                <v-card-title class="d-flex align-center pa-4 bg-warning">
                    <div class="pulse-icon mr-3">
                        <v-icon color="white" size="28">mdi-cancel</v-icon>
                    </div>
                    <div class="flex-grow-1">
                        <span class="text-white text-subtitle-1 font-weight-bold d-block">Cancel Request Received</span>
                        <span class="text-white-darken-1 text-caption">User wants to cancel the rescue</span>
                    </div>
                </v-card-title>
                
                <v-card-text class="pa-5">
                    <div class="request-info-card mb-4">
                        <div class="d-flex align-center mb-3">
                            <v-avatar 
                                size="48"
                                :color="requesterProfilePicture ? 'transparent' : 'warning'"
                                class="mr-3"
                            >
                                <v-img v-if="requesterProfilePicture" :src="requesterProfilePicture" cover />
                                <span v-else class="text-white text-subtitle-1">{{ getPersonInNeedInitials() }}</span>
                            </v-avatar>
                            <div>
                                <h4 class="text-subtitle-1 font-weight-bold mb-0">{{ getPersonInNeedName() }}</h4>
                                <p class="text-caption text-grey mb-0">is requesting to cancel the rescue</p>
                            </div>
                        </div>
                        
                        <!-- Show cancel reason -->
                        <v-alert type="info" variant="tonal" density="compact" class="mb-3">
                            <div class="text-caption"><strong>Reason:</strong></div>
                            <div class="text-body-2">{{ rescueRequest?.cancel_approval_reason || 'No reason provided' }}</div>
                        </v-alert>
                        
                        <!-- Show proof details if provided -->
                        <v-alert v-if="rescueRequest?.cancel_proof_details" type="info" variant="tonal" density="compact" class="mb-3">
                            <div class="text-caption"><strong>Proof of safety:</strong></div>
                            <div class="text-body-2">{{ rescueRequest.cancel_proof_details }}</div>
                        </v-alert>

                        <!-- Show proof photo if provided -->
                        <div v-if="rescueRequest?.cancel_proof_photo" class="proof-photo-container mb-3">
                            <div class="text-caption font-weight-bold mb-2">
                                <v-icon size="14" class="mr-1">mdi-camera</v-icon>
                                Photo proof:
                            </div>
                            <v-img
                                :src="`/storage/${rescueRequest.cancel_proof_photo}`"
                                aspect-ratio="16/9"
                                class="rounded-lg proof-photo"
                                cover
                                @click="viewProofPhoto"
                            >
                                <template v-slot:placeholder>
                                    <v-row class="fill-height ma-0" align="center" justify="center">
                                        <v-progress-circular indeterminate color="grey-lighten-5"></v-progress-circular>
                                    </v-row>
                                </template>
                            </v-img>
                        </div>
                    </div>
                    
                    <v-tabs v-model="cancelApprovalTab" density="compact" class="mb-4" color="warning">
                        <v-tab value="approve" prepend-icon="mdi-check-circle">
                            <span class="text-caption">Approve</span>
                        </v-tab>
                        <v-tab value="deny" prepend-icon="mdi-close-circle">
                            <span class="text-caption">Deny</span>
                        </v-tab>
                    </v-tabs>
                    
                    <v-window v-model="cancelApprovalTab">
                        <v-window-item value="approve">
                            <div class="text-center py-4">
                                <v-icon size="48" color="success" class="mb-3">mdi-check-circle</v-icon>
                                <p class="text-body-2 text-grey-darken-1">
                                    Confirming this means you verify that the user is safe and no longer requires assistance.
                                    This will cancel the rescue request.
                                </p>
                                <v-alert type="success" variant="tonal" density="compact" class="mt-3">
                                    <div class="text-caption">
                                        <strong>Approving will:</strong> Cancel the rescue request, free you for other assignments, and mark as resolved.
                                    </div>
                                </v-alert>
                            </div>
                        </v-window-item>
                        
                        <v-window-item value="deny">
                            <v-textarea
                                v-model="cancelApprovalDenyReason"
                                label="Reason for denying cancellation (required)"
                                placeholder="e.g., Based on our conversation, I believe you still need assistance..."
                                rows="3"
                                variant="outlined"
                                density="comfortable"
                                prepend-inner-icon="mdi-text"
                                :rules="[v => !!v || 'Reason is required']"
                            />
                            <v-alert type="warning" variant="tonal" density="compact" class="mt-2">
                                <div class="text-caption">
                                    The user will be notified that their cancellation was denied and can continue to communicate via chat.
                                </div>
                            </v-alert>
                        </v-window-item>
                    </v-window>
                </v-card-text>
                
                <v-card-actions class="pa-3 pt-0">
                    <!-- Mobile-optimized horizontal button layout -->
                    <div class="d-flex flex-row gap-2 w-100">
                        <v-btn 
                            variant="outlined" 
                            color="grey" 
                            @click="closeCancelApprovalDialog" 
                            class="rounded-lg flex-grow-1"
                            size="small"
                            :disabled="cancelApprovalProcessing"
                            style="min-height: 36px;"
                        >
                            <span class="text-overline text-sm-caption font-weight-medium">Decide Later</span>
                        </v-btn>
                        
                        <v-btn
                            v-if="cancelApprovalTab === 'approve'"
                            color="success"
                            variant="flat"
                            @click="handleApproveCancelRequest"
                            :loading="cancelApprovalProcessing"
                            class="rounded-lg flex-grow-1"
                            size="small"
                            style="min-height: 36px;"
                        >
                            <v-icon start size="14">mdi-check-circle</v-icon>
                            <span class="text-overline text-sm-caption font-weight-medium">Approve</span>
                        </v-btn>
                        
                        <v-btn
                            v-else
                            color="warning"
                            variant="flat"
                            @click="handleDenyCancelRequest"
                            :loading="cancelApprovalProcessing"
                            :disabled="!cancelApprovalDenyReason.trim()"
                            class="rounded-lg flex-grow-1"
                            size="small"
                            style="min-height: 36px;"
                        >
                            <v-icon start size="14">mdi-close-circle</v-icon>
                            <span class="text-overline text-sm-caption font-weight-medium">Deny</span>
                        </v-btn>
                    </div>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- User Profile Dialog -->
        <v-dialog v-model="showUserProfile" max-width="400">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2">mdi-account</v-icon>
                    User Profile
                </v-card-title>
                <v-divider />
                <v-card-text class="text-center py-4">
                    <v-avatar 
                        size="80" 
                        color="primary" 
                        class="mb-3"
                        :style="requesterProfilePicture ? 'cursor: pointer' : ''"
                        @click="requesterProfilePicture && openPhotoViewer(requesterProfilePicture, getRequesterFullName())"
                    >
                        <v-img v-if="requesterProfilePicture" :src="requesterProfilePicture" cover />
                        <span v-else class="text-h5 text-white">
                            {{ getRequesterInitials() }}
                        </span>
                    </v-avatar>
                    <p v-if="requesterProfilePicture" class="text-caption text-grey mb-2">Tap photo to enlarge</p>
                    <h3 class="text-h6 mb-1">
                        {{ rescueRequest?.requester?.first_name || rescueRequest?.first_name }} {{ rescueRequest?.requester?.last_name || rescueRequest?.last_name }}
                    </h3>
                    <p class="text-grey text-body-2 mb-3">
                        {{ rescueRequest?.requester?.email || rescueRequest?.email || 'No email' }}
                    </p>
                    
                    <v-list density="compact" class="text-left">
                        <v-list-item v-if="rescueRequest?.requester?.phone || rescueRequest?.contact_number">
                            <template v-slot:prepend>
                                <v-icon color="grey" size="20">mdi-phone</v-icon>
                            </template>
                            <v-list-item-title class="text-body-2">Phone</v-list-item-title>
                            <v-list-item-subtitle>{{ rescueRequest?.requester?.phone || rescueRequest?.contact_number }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-divider />
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showUserProfile = false">Close</v-btn>
                    <v-btn
                        v-if="rescueRequest?.requester?.phone || rescueRequest?.contact_number"
                        color="success"
                        variant="flat"
                        prepend-icon="mdi-phone"
                        @click="callUser"
                    >
                        Call
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Photo Viewer Dialog -->
        <v-dialog v-model="showPhotoViewer" max-width="500" content-class="photo-viewer-dialog">
            <v-card class="bg-black">
                <v-card-title class="d-flex align-center justify-space-between text-white">
                    <span>{{ photoViewerName }}</span>
                    <v-btn icon variant="text" color="white" @click="showPhotoViewer = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pa-0 d-flex justify-center align-center" style="min-height: 300px;">
                    <v-img
                        :src="photoViewerUrl"
                        max-height="400"
                        contain
                        class="bg-black"
                    />
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Media Viewer Dialog (for photos/videos from rescue request) -->
        <v-dialog v-model="showMediaViewer" max-width="600" content-class="media-viewer-dialog">
            <v-card class="bg-black rounded-xl">
                <v-card-title class="d-flex align-center justify-space-between text-white pa-3">
                    <div class="d-flex align-center">
                        <v-icon :color="currentMediaItem?.type === 'video' ? 'red' : 'blue'" class="mr-2">
                            {{ currentMediaItem?.type === 'video' ? 'mdi-video' : 'mdi-image' }}
                        </v-icon>
                        <span class="text-body-1">{{ currentMediaItem?.original_name || 'Media' }}</span>
                    </div>
                    <div class="d-flex align-center">
                        <span class="text-caption text-grey mr-3">{{ currentMediaIndex + 1 }} / {{ mediaAttachments.length }}</span>
                        <v-btn icon variant="text" color="white" size="small" @click="showMediaViewer = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </div>
                </v-card-title>
                <v-divider color="grey-darken-3" />
                <v-card-text class="pa-0 d-flex justify-center align-center media-viewer-content">
                    <!-- Image Viewer -->
                    <template v-if="currentMediaItem?.type === 'image'">
                        <v-img
                            :src="currentMediaItem?.url"
                            max-height="500"
                            contain
                            class="bg-black"
                        >
                            <template v-slot:placeholder>
                                <div class="d-flex align-center justify-center fill-height">
                                    <v-progress-circular indeterminate color="primary" size="48" />
                                </div>
                            </template>
                        </v-img>
                    </template>
                    <!-- Video Viewer -->
                    <template v-else-if="currentMediaItem?.type === 'video'">
                        <video
                            ref="mediaVideoPlayer"
                            :src="currentMediaItem?.url"
                            controls
                            class="media-video-player"
                            preload="metadata"
                        />
                    </template>
                </v-card-text>
                <!-- Navigation Arrows -->
                <div v-if="mediaAttachments.length > 1" class="media-nav-arrows">
                    <v-btn 
                        icon 
                        variant="flat" 
                        color="white" 
                        size="small"
                        class="nav-arrow left"
                        :disabled="currentMediaIndex === 0"
                        @click="navigateMedia(-1)"
                    >
                        <v-icon>mdi-chevron-left</v-icon>
                    </v-btn>
                    <v-btn 
                        icon 
                        variant="flat" 
                        color="white" 
                        size="small"
                        class="nav-arrow right"
                        :disabled="currentMediaIndex === mediaAttachments.length - 1"
                        @click="navigateMedia(1)"
                    >
                        <v-icon>mdi-chevron-right</v-icon>
                    </v-btn>
                </div>
            </v-card>
        </v-dialog>

        <!-- Proof Photo Viewer Dialog -->
        <v-dialog v-model="showProofPhotoViewer" max-width="600" content-class="proof-photo-viewer-dialog">
            <v-card class="bg-black rounded-xl">
                <v-card-title class="d-flex align-center justify-space-between text-white pa-3">
                    <span class="text-subtitle-1">Proof Photo</span>
                    <v-btn icon variant="text" color="white" size="small" @click="showProofPhotoViewer = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pa-0">
                    <v-img
                        :src="proofPhotoUrl"
                        contain
                        class="bg-black"
                        style="max-height: 60vh;"
                    />
                </v-card-text>
                <v-card-actions class="pa-3">
                    <v-btn variant="outlined" color="white" prepend-icon="mdi-download" @click="downloadProofPhoto">
                        Download
                    </v-btn>
                    <v-spacer />
                    <v-btn variant="text" color="white" @click="showProofPhotoViewer = false">
                        Close
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
            {{ snackbar.message }}
        </v-snackbar>
        
        <!-- Bottom Navigation (Mobile/Tablet only) -->
        <RescuerBottomNav :notification-count="0" :message-count="unreadMessageCount" />
        

    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { apiFetch, getProfilePictureUrl, getUnreadMessageCount, translateRescueRequest, approveSafeRequest, denySafeRequest, approveCancelRequest, denyCancelRequest } from '@/Composables/useApi';
import { useDarkMode } from '@/Composables/useDarkMode';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';


const { isDark } = useDarkMode();
const { playNotificationSound, vibrate, notify, stopEmergencySound } = useNotificationAlert();

const props = defineProps({
    rescueId: {
        type: [String, Number],
        default: null,
    },
});

// Using apiFetch directly instead of useApi composable

// State
const loading = ref(true);
const updating = ref(false);
const isTranslating = ref(false);
const rescueRequest = ref(null);
const currentStatus = ref('');
const showSummary = ref(false);
const statusTimestamps = ref({});
const showCompleteDialog = ref(false);
const showCancelDialog = ref(false);
const showUserProfile = ref(false);
const completionNotes = ref('');
const cancellationReason = ref('');
const cancelType = ref('valid'); // 'valid' = return to pending, 'invalid' = delete + report
const pollingInterval = ref(null);
const unreadMessageCount = ref(0);
const completionPhotoFile = ref(null);
const completionPhotoPreview = ref(null);
const completionPhotoInputRef = ref(null);

// Safe approval state
const showSafeApprovalDialog = ref(false);
const safeApprovalProcessing = ref(false);
const safeApprovalDenyReason = ref('');
const safeApprovalTab = ref('approve'); // 'approve' or 'deny'
const previousSafeApprovalRequested = ref(false);

// Cancel approval state
const showCancelApprovalDialog = ref(false);
const cancelApprovalProcessing = ref(false);
const cancelApprovalDenyReason = ref('');
const cancelApprovalTab = ref('approve'); // 'approve' or 'deny'
const previousCancelApprovalRequested = ref(false);
const isInitialFetch = ref(true);
const showProofPhotoViewer = ref(false);
const proofPhotoUrl = ref('');
const isSliding = ref(false);
const slidePosition = ref(0);
const slideProgress = ref(0);
const isSlideComplete = ref(false);
const slideStartX = ref(0);
const maxSlideDistance = ref(0);
const completionTimer = ref(null);

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
});

// Photo Viewer State
const showPhotoViewer = ref(false);
const photoViewerUrl = ref('');
const photoViewerName = ref('');

const openPhotoViewer = (url, name) => {
    photoViewerUrl.value = url;
    photoViewerName.value = name || 'Profile Photo';
    showPhotoViewer.value = true;
};

// Media Viewer State (for rescue request attachments)
const showMediaViewer = ref(false);
const currentMediaIndex = ref(0);
const mediaVideoPlayer = ref(null);

// Computed property for media attachments
const mediaAttachments = computed(() => {
    const attachments = rescueRequest.value?.media_attachments;
    if (!attachments) return [];
    // Handle both array and JSON string formats
    if (typeof attachments === 'string') {
        try {
            return JSON.parse(attachments);
        } catch (e) {
            return [];
        }
    }
    return Array.isArray(attachments) ? attachments : [];
});

// Check if there are media attachments
const hasMediaAttachments = computed(() => {
    return mediaAttachments.value && mediaAttachments.value.length > 0;
});

// Get current media item being viewed
const currentMediaItem = computed(() => {
    return mediaAttachments.value[currentMediaIndex.value] || null;
});

// Open media viewer
const openMediaViewer = (media, index) => {
    currentMediaIndex.value = index;
    showMediaViewer.value = true;
};

// Navigate through media
const navigateMedia = (direction) => {
    const newIndex = currentMediaIndex.value + direction;
    if (newIndex >= 0 && newIndex < mediaAttachments.value.length) {
        // Pause video if playing
        if (mediaVideoPlayer.value) {
            mediaVideoPlayer.value.pause();
        }
        currentMediaIndex.value = newIndex;
    }
};

// Computed property for requester's profile picture
const requesterProfilePicture = computed(() => {
    const requester = rescueRequest.value?.requester;
    const picturePath = requester?.profile_picture || requester?.profile_photo;
    if (!picturePath) return null;
    return getProfilePictureUrl(picturePath);
});

// Check if there's medical information available
const hasMedicalInfo = computed(() => {
    const requester = rescueRequest.value?.requester;
    return requester?.blood_type || requester?.allergies || requester?.medical_conditions;
});

// Check if there's emergency contact information available
const hasEmergencyContact = computed(() => {
    const requester = rescueRequest.value?.requester;
    return requester?.emergency_contact_name || requester?.emergency_contact_phone;
});

// Check if the user is reporting for someone else (form has name filled in)
const isReportingForOthers = computed(() => {
    // If firstName or lastName is filled in the form, it means reporting for others
    // Check both camelCase (as stored in DB) and snake_case (for API normalization)
    const formFirstName = rescueRequest.value?.firstName || rescueRequest.value?.first_name;
    const formLastName = rescueRequest.value?.lastName || rescueRequest.value?.last_name;
    return !!(formFirstName || formLastName);
});

// Get the person in need name (form name if reporting for others, requester name if self)
const getPersonInNeedName = () => {
    if (isReportingForOthers.value) {
        // Use the name from the form (reporting for someone else)
        const firstName = rescueRequest.value?.firstName || rescueRequest.value?.first_name || '';
        const lastName = rescueRequest.value?.lastName || rescueRequest.value?.last_name || '';
        return `${firstName} ${lastName}`.trim() || 'Unknown Person';
    } else {
        // Use the requester's name (reporting for self)
        const firstName = rescueRequest.value?.requester?.first_name || '';
        const lastName = rescueRequest.value?.requester?.last_name || '';
        return `${firstName} ${lastName}`.trim() || 'Unknown User';
    }
};

// Get initials for person in need
const getPersonInNeedInitials = () => {
    if (isReportingForOthers.value) {
        const firstName = rescueRequest.value?.firstName || rescueRequest.value?.first_name || '';
        const lastName = rescueRequest.value?.lastName || rescueRequest.value?.last_name || '';
        if (firstName && lastName) {
            return `${firstName[0]}${lastName[0]}`.toUpperCase();
        }
        if (firstName) return firstName.substring(0, 2).toUpperCase();
        return '?';
    } else {
        return getRequesterInitials();
    }
};

// Get requester initials for avatar fallback
const getRequesterInitials = () => {
    const firstName = rescueRequest.value?.requester?.first_name || rescueRequest.value?.first_name || '';
    const lastName = rescueRequest.value?.requester?.last_name || rescueRequest.value?.last_name || '';
    if (firstName && lastName) {
        return `${firstName[0]}${lastName[0]}`.toUpperCase();
    }
    if (firstName) return firstName.substring(0, 2).toUpperCase();
    return '?';
};

// Get requester full name
const getRequesterFullName = () => {
    const firstName = rescueRequest.value?.requester?.first_name || rescueRequest.value?.first_name || '';
    const lastName = rescueRequest.value?.requester?.last_name || rescueRequest.value?.last_name || '';
    return `${firstName} ${lastName}`.trim() || 'User';
};

const statusSteps = [
    { value: 'pending', label: 'Request Created' },
    { value: 'assigned', label: 'Rescuer Assigned' },
    { value: 'in_progress', label: 'Rescue In Progress' },
    { value: 'rescued', label: 'Rescue Completed' },
];

const statusOrder = ['pending', 'assigned', 'in_progress', 'rescued'];

// Methods
const fetchRescueDetails = async () => {
    try {
        const id = props.rescueId || localStorage.getItem('lastRescueRequestId');
        if (!id) {
            showSnackbar('No rescue ID found', 'error');
            setTimeout(() => router.visit('/rescuer/dashboard'), 2000);
            return;
        }

        const response = await apiFetch(`/api/rescue-requests/${id}`, { method: 'GET' });
        console.log('Rescue details response:', response); // Debug log
        
        // Handle both wrapped and direct response formats
        const data = response.data || response;
        
        if (data) {
            // Detect new safe approval request
            const wasPreviouslySafeApprovalRequested = previousSafeApprovalRequested.value;
            const isNowSafeApprovalRequested = data.safe_approval_requested && data.safe_approval_status === 'pending';
            
            if (!wasPreviouslySafeApprovalRequested && isNowSafeApprovalRequested) {
                // New safe approval request detected - show dialog
                showSafeApprovalDialog.value = true;
                
                // Only play sound/vibrate/notify on subsequent polls (not on initial page load)
                if (!isInitialFetch.value) {
                    playNotificationSound('message');
                    
                    try { vibrate([200, 100, 200, 100, 200]); } catch (e) { /* blocked before user interaction */ }
                    
                    // Send browser notification to alert rescuer even if in another tab
                    if ('Notification' in window && Notification.permission === 'granted') {
                        try {
                            new Notification('🆘 Safe Request Received', {
                                body: `${data.firstName || 'User'} wants to mark themselves as safe and needs your approval.`,
                                icon: '/images/logo.png',
                                badge: '/images/logo.png',
                                tag: 'safe-approval-request',
                                requireInteraction: true,
                            });
                        } catch (e) {
                            console.warn('Browser notification failed:', e);
                        }
                    } else if ('Notification' in window && Notification.permission !== 'denied') {
                        Notification.requestPermission();
                    }
                    
                    showSnackbar('User is requesting to mark themselves as safe', 'info');
                }
            }
            
            // Update the tracking ref
            previousSafeApprovalRequested.value = isNowSafeApprovalRequested;
            
            // Detect new cancel approval request
            const wasPreviouslyCancelApprovalRequested = previousCancelApprovalRequested.value;
            const isNowCancelApprovalRequested = data.cancel_approval_requested && data.cancel_approval_status === 'pending';
            
            if (!wasPreviouslyCancelApprovalRequested && isNowCancelApprovalRequested) {
                // New cancel approval request detected - show dialog
                showCancelApprovalDialog.value = true;
                
                // Only play sound/vibrate on subsequent polls (not on initial page load)
                if (!isInitialFetch.value) {
                    playNotificationSound('notification');
                    try { vibrate([200, 100, 200, 100, 200]); } catch (e) { /* blocked before user interaction */ }
                    showSnackbar('User is requesting to cancel the rescue', 'warning');
                }
            }
            
            // Update the tracking ref
            previousCancelApprovalRequested.value = isNowCancelApprovalRequested;
            
            // Mark initial fetch as done after first load
            if (isInitialFetch.value) {
                isInitialFetch.value = false;
            }
            
            rescueRequest.value = data;
            currentStatus.value = data.status || 'pending';
            
            // Build status timestamps
            statusTimestamps.value = {
                pending: data.created_at,
                accepted: data.accepted_at,
                en_route: data.en_route_at,
                on_scene: data.on_scene_at,
                rescued: data.rescued_at,
            };
            
            console.log('Rescue request loaded:', rescueRequest.value); // Debug log
        }
    } catch (error) {
        console.error('Error fetching rescue details:', error);
        showSnackbar('Failed to load rescue details', 'error');
    } finally {
        loading.value = false;
    }
};

const handleTranslate = async () => {
    if (!rescueRequest.value?.id) return;
    isTranslating.value = true;
    try {
        const result = await translateRescueRequest(rescueRequest.value.id);
        if (result.success && result.data) {
            rescueRequest.value = result.data;
        }
        showSnackbar('Translation completed', 'success');
    } catch (err) {
        console.error('Translation failed:', err);
        showSnackbar('Translation failed. Please try again.', 'error');
    } finally {
        isTranslating.value = false;
    }
};

const acceptRescue = async () => {
    updating.value = true;
    try {
        // Block if user is currently processing cancellation
        if (rescueRequest.value?.cancel_in_progress_at) {
            showSnackbar('User is currently processing a cancellation. You cannot accept this request.', 'warning');
            updating.value = false;
            return;
        }

        // Block if user is currently considering marking themselves safe
        if (rescueRequest.value?.marking_safe_in_progress_at) {
            showSnackbar('User is currently considering marking themselves safe. Please wait.', 'warning');
            updating.value = false;
            return;
        }

        // Get rescuer ID from localStorage
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const rescuerId = userData.id;
        
        if (!rescuerId) {
            showSnackbar('Rescuer ID not found', 'error');
            return;
        }

        // Check if rescuer already has an active assignment
        const checkResponse = await apiFetch(`/api/rescue-requests/rescuer/${rescuerId}`, { method: 'GET' });
        const checkData = checkResponse.data || checkResponse;
        const activeRequests = Array.isArray(checkData) ? checkData : [];
        const hasOtherActiveAssignment = activeRequests.some((r) => 
            (r.status === 'assigned' || r.status === 'in_progress') &&
            r.id !== rescueRequest.value?.id &&
            (String(r.assigned_rescuer) === String(rescuerId) || String(r.rescuer_id) === String(rescuerId))
        );

        if (hasOtherActiveAssignment) {
            showSnackbar('You are only allowed to accept requests one at a time.', 'warning');
            updating.value = false;
            return;
        }

        // Accept and start rescue in one action - go directly to in_progress
        const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                status: 'in_progress',
                assigned_rescuer: rescuerId 
            }),
        });

        const data = response.data || response;
        console.log('Accept rescue response:', data); // Debug log
        
        if (data) {
            // Set status to 'in_progress' - combining accept and start
            currentStatus.value = 'in_progress';
            rescueRequest.value = { ...rescueRequest.value, ...data, status: 'in_progress' };
            statusTimestamps.value['in_progress'] = new Date().toISOString();
            showSnackbar('Rescue accepted and started!', 'success');
            
            // Store the rescue ID for later reference
            localStorage.setItem('lastRescueRequestId', rescueRequest.value.id.toString());
        }
    } catch (error) {
        console.error('Error accepting rescue:', error);
        if (error?.status === 409 && error?.data?.cancel_in_progress) {
            showSnackbar('User is currently processing a cancellation. Please wait.', 'warning');
            // Refresh data to update UI
            await fetchActiveRescue();
        } else if (error?.status === 409 && error?.data?.marking_safe_in_progress) {
            showSnackbar('User is currently considering marking themselves safe. Please wait.', 'warning');
            await fetchActiveRescue();
        } else if (error?.status === 409 && error?.data?.already_accepted) {
            showSnackbar(error.data?.message || 'Already accepted by another rescuer.', 'warning');
        } else {
            showSnackbar('Failed to accept rescue', 'error');
        }
    } finally {
        updating.value = false;
    }
};

// Start rescue - assigns rescuer and starts in one action
const startRescue = async () => {
    updating.value = true;
    try {
        // Get rescuer ID from localStorage
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const rescuerId = userData.id;
        
        if (!rescuerId) {
            showSnackbar('Rescuer ID not found', 'error');
            return;
        }

        const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                status: 'in_progress',
                assigned_rescuer: rescuerId 
            }),
        });

        const data = response.data || response;
        if (data) {
            currentStatus.value = 'in_progress';
            rescueRequest.value = data;
            statusTimestamps.value['in_progress'] = new Date().toISOString();
            
            // Store the rescue ID for later reference
            localStorage.setItem('lastRescueRequestId', rescueRequest.value.id.toString());
            
            showSnackbar('Rescue started! You can now proceed to the location.', 'success');
        }
    } catch (error) {
        console.error('Error starting rescue:', error);
        showSnackbar('Failed to start rescue', 'error');
    } finally {
        updating.value = false;
    }
};

const updateStatus = async (newStatus) => {
    updating.value = true;
    try {
        const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: newStatus }),
        });

        const data = response.data || response;
        if (data) {
            currentStatus.value = newStatus;
            rescueRequest.value = data;
            statusTimestamps.value[newStatus] = new Date().toISOString();
            showSnackbar(`Status updated to ${formatStatus(newStatus)}`, 'success');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showSnackbar('Failed to update status', 'error');
    } finally {
        updating.value = false;
    }
};

const completeRescue = async () => {
    updating.value = true;
    try {
        const formData = new FormData();
        if (completionNotes.value) {
            formData.append('completion_notes', completionNotes.value);
        }
        if (completionPhotoFile.value) {
            formData.append('completion_photo', completionPhotoFile.value);
        }

        const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}/complete`, {
            method: 'POST',
            body: formData,
        });

        const data = response.data || response;
        if (data) {
            // Update local rescue request with completion data
            rescueRequest.value = { ...rescueRequest.value, ...data, status: 'safe' };
            currentStatus.value = 'safe';
            showSnackbar('Person marked as safe!', 'success');
            showCompleteDialog.value = false;
            resetSlide();
            removeCompletionPhoto();
            localStorage.removeItem('lastRescueRequestId');

            // Update localStorage so Profile and other pages reflect 'available' status
            try {
                const stored = JSON.parse(localStorage.getItem('userData') || '{}');
                stored.status = 'available';
                localStorage.setItem('userData', JSON.stringify(stored));
            } catch (e) { /* ignore */ }
        }
    } catch (error) {
        console.error('Error completing rescue:', error);
        showSnackbar('Failed to mark as safe', 'error');
    } finally {
        updating.value = false;
    }
};

const cancelRescue = async () => {
    if (!cancellationReason.value) return;

    updating.value = true;
    try {
        if (cancelType.value === 'invalid') {
            // False/joke report — delete the request and report to admin
            const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}/report-false`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cancellation_reason: cancellationReason.value,
                    reported_by: JSON.parse(localStorage.getItem('userData') || '{}')?.id,
                }),
            });

            const data = response.data || response;
            if (data) {
                showSnackbar('False report deleted and admin notified', 'error');
                showCancelDialog.value = false;
                localStorage.removeItem('lastRescueRequestId');
                // Update localStorage so Profile reflects 'available' status
                try {
                    const stored = JSON.parse(localStorage.getItem('userData') || '{}');
                    stored.status = 'available';
                    localStorage.setItem('userData', JSON.stringify(stored));
                } catch (e) { /* ignore */ }
                setTimeout(() => router.visit('/rescuer/dashboard'), 1500);
            }
        } else {
            // Valid cancel — return request back to pending so other rescuers can pick it up
            const response = await apiFetch(`/api/rescue-requests/${rescueRequest.value.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    status: 'pending',
                    cancellation_reason: cancellationReason.value,
                    assigned_rescuer: null,
                }),
            });

            const data = response.data || response;
            if (data) {
                showSnackbar('Request returned to Need Help queue', 'warning');
                showCancelDialog.value = false;
                localStorage.removeItem('lastRescueRequestId');
                // Update localStorage so Profile reflects 'available' status
                try {
                    const stored = JSON.parse(localStorage.getItem('userData') || '{}');
                    stored.status = 'available';
                    localStorage.setItem('userData', JSON.stringify(stored));
                } catch (e) { /* ignore */ }
                setTimeout(() => router.visit('/rescuer/dashboard'), 1500);
            }
        }
    } catch (error) {
        console.error('Error cancelling rescue:', error);
        showSnackbar('Failed to cancel rescue', 'error');
    } finally {
        updating.value = false;
    }
};

const viewMap = () => {
    if (rescueRequest.value) {
        router.visit(`/rescuer/map/${rescueRequest.value.id}`);
    }
};

const openChat = () => {
    // Check if rescuer is restricted
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    const status = userData.status?.toLowerCase();
    if (status === 'off_duty' || status === 'unavailable') {
        showSnackbar('You cannot access messages while your status is restricted', 'warning');
        return;
    }
    
    // Use rescue-chat route which will get or create conversation
    if (rescueRequest.value?.id) {
        router.visit(`/rescuer/rescue-chat/${rescueRequest.value.id}?from=active-rescue`);
    } else {
        showSnackbar('No rescue request found', 'warning');
    }
};

const callUser = () => {
    if (rescueRequest.value?.user?.contact_number) {
        window.location.href = `tel:${rescueRequest.value.user.contact_number}`;
    } else if (rescueRequest.value?.requester?.phone) {
        window.location.href = `tel:${rescueRequest.value.requester.phone}`;
    }
};

const callEmergencyContact = () => {
    if (rescueRequest.value?.requester?.emergency_contact_phone) {
        window.location.href = `tel:${rescueRequest.value.requester.emergency_contact_phone}`;
    }
};

const goBack = () => {
    // Use currentStatus (always kept in sync) instead of rescueRequest.value?.status
    const status = currentStatus.value || rescueRequest.value?.status;
    if (['rescued', 'safe', 'completed'].includes(status)) {
        // After rescue is done, go to Need Help tab so rescuer can pick up new requests
        router.visit('/rescuer/dashboard?tab=pending');
    } else if (['assigned', 'in_progress', 'en_route', 'on_scene'].includes(status)) {
        router.visit('/rescuer/dashboard?tab=inProgress');
    } else {
        router.visit('/rescuer/dashboard?tab=pending');
    }
};

// Helper methods
const getUrgencyIcon = (level) => {
    const icons = {
        'low': 'mdi-alert-circle-outline',
        'medium': 'mdi-alert',
        'high': 'mdi-alert-octagon',
        'critical': 'mdi-fire-alert',
    };
    return icons[level] || 'mdi-alert-circle';
};

// Computed rescue duration (from created_at to updated_at / now)
const rescueDuration = computed(() => {
    if (!rescueRequest.value) return 'N/A';
    const start = new Date(rescueRequest.value.created_at);
    const end = rescueRequest.value.updated_at ? new Date(rescueRequest.value.updated_at) : new Date();
    const diffMs = end - start;
    const mins = Math.floor(diffMs / 60000);
    const hrs = Math.floor(mins / 60);
    const remainMins = mins % 60;
    if (hrs > 0) return `${hrs}h ${remainMins}m`;
    return `${mins}m`;
});

// Completion photo helpers
const triggerCompletionPhotoInput = () => {
    completionPhotoInputRef.value?.click();
};

const onCompletionPhotoSelected = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    completionPhotoFile.value = file;
    completionPhotoPreview.value = URL.createObjectURL(file);
};

const removeCompletionPhoto = () => {
    if (completionPhotoPreview.value) {
        URL.revokeObjectURL(completionPhotoPreview.value);
    }
    completionPhotoFile.value = null;
    completionPhotoPreview.value = null;
    if (completionPhotoInputRef.value) completionPhotoInputRef.value.value = '';
};

const getUrgencyColor = (level) => {
    const colors = {
        'low': 'success',
        'medium': 'warning',
        'high': 'orange',
        'critical': 'error',
    };
    return colors[level] || 'grey';
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'assigned': 'info',
        'in_progress': 'primary', 
        'rescued': 'success',
        'safe': 'success',
        'cancelled': 'error',
    };
    return colors[status] || 'grey';
};

const getStatusChipColor = (status) => {
    const colors = {
        'pending': 'orange',
        'assigned': 'blue',
        'in_progress': 'primary', 
        'rescued': 'success',
        'safe': 'success',
        'cancelled': 'error',
    };
    return colors[status] || 'grey';
};

const getStatusIcon = (status) => {
    const icons = {
        'pending': 'mdi-clock-outline',
        'assigned': 'mdi-account-check',
        'in_progress': 'mdi-run-fast', 
        'rescued': 'mdi-check-circle',
        'safe': 'mdi-shield-check',
        'cancelled': 'mdi-close-circle',
    };
    return icons[status] || 'mdi-help-circle';
};

const formatStatus = (status) => {
    const labels = {
        'pending': 'Pending',
        'assigned': 'Assigned',
        'in_progress': 'In Progress',
        'rescued': 'Rescued',
        'safe': 'Safe',
        'cancelled': 'Cancelled',
    };
    return labels[status] || status;
};

const getMobilityColor = (status) => {
    if (status === 'immobile' || status === 'injured') return 'error';
    if (status === 'limited') return 'warning';
    return 'success';
};

const formatMobility = (status) => {
    const labels = {
        'mobile': 'Mobile',
        'limited': 'Limited Mobility',
        'immobile': 'Immobile',
        'injured': 'Injured',
    };
    return labels[status] || status;
};

const getStepColor = (step) => {
    const stepIndex = statusOrder.indexOf(step);
    const currentIndex = statusOrder.indexOf(currentStatus.value);
    
    if (stepIndex < currentIndex) return 'success';
    if (stepIndex === currentIndex) return 'primary';
    return 'grey-lighten-1';
};

const getStepIcon = (step) => {
    const stepIndex = statusOrder.indexOf(step);
    const currentIndex = statusOrder.indexOf(currentStatus.value);
    
    if (stepIndex < currentIndex) return 'mdi-check';
    if (stepIndex === currentIndex) return 'mdi-circle';
    return 'mdi-circle-outline';
};

const isCurrentOrPastStatus = (status) => {
    const stepIndex = statusOrder.indexOf(status);
    const currentIndex = statusOrder.indexOf(currentStatus.value);
    return stepIndex <= currentIndex;
};

const getElapsedTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const totalMinutes = Math.floor(diffMs / 60000);
    
    if (totalMinutes < 1) return 'Just now';
    
    // Format as hours:minutes instead of minutes:seconds
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    
    if (hours === 0) {
        return `${minutes} min ago`;
    } else {
        return `${hours}:${minutes.toString().padStart(2, '0')}`;
    }
};

const formatTimestamp = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Safe Approval Handling
const handleApproveSafeRequest = async () => {
    if (!rescueRequest.value?.id) return;
    
    // Stop the emergency alarm sound immediately
    stopEmergencySound();
    
    safeApprovalProcessing.value = true;
    try {
        const response = await approveSafeRequest(rescueRequest.value.id, 'Confirmed safe by rescuer');
        
        // Update local state
        if (response.data) {
            rescueRequest.value = response.data;
            currentStatus.value = response.data.status;
        }

        // Update localStorage so Profile and other pages reflect 'available' status
        try {
            const stored = JSON.parse(localStorage.getItem('userData') || '{}');
            stored.status = 'available';
            localStorage.setItem('userData', JSON.stringify(stored));
        } catch (e) { /* ignore */ }
        
        showSafeApprovalDialog.value = false;
        showSnackbar('User marked as safe. Rescue completed.', 'success');
        
        // Vibrate for successful completion
        if (navigator.vibrate) {
            navigator.vibrate([100, 50, 200]);
        }
        
        // Redirect to dashboard after a short delay
        setTimeout(() => {
            router.visit('/rescuer/dashboard');
        }, 2000);
    } catch (error) {
        console.error('Failed to approve safe request:', error);
        showSnackbar('Failed to approve safe request. Please try again.', 'error');
    } finally {
        safeApprovalProcessing.value = false;
    }
};

const handleDenySafeRequest = async () => {
    if (!rescueRequest.value?.id || !safeApprovalDenyReason.value.trim()) {
        showSnackbar('Please provide a reason for denying the safe request.', 'warning');
        return;
    }
    
    // Stop the emergency alarm sound immediately
    stopEmergencySound();
    
    safeApprovalProcessing.value = true;
    try {
        const response = await denySafeRequest(rescueRequest.value.id, safeApprovalDenyReason.value.trim());
        
        // Update local state
        if (response.data) {
            rescueRequest.value = response.data;
        }
        
        showSafeApprovalDialog.value = false;
        safeApprovalDenyReason.value = '';
        safeApprovalTab.value = 'approve';
        showSnackbar('Safe request denied. User has been notified to wait for your assessment.', 'info');
    } catch (error) {
        console.error('Failed to deny safe request:', error);
        showSnackbar('Failed to deny safe request. Please try again.', 'error');
    } finally {
        safeApprovalProcessing.value = false;
    }
};

const closeSafeApprovalDialog = () => {
    // Stop the emergency alarm sound when dismissing
    stopEmergencySound();
    showSafeApprovalDialog.value = false;
    safeApprovalDenyReason.value = '';
    safeApprovalTab.value = 'approve';
};

// Cancel Approval handlers
const handleApproveCancelRequest = async () => {
    cancelApprovalProcessing.value = true;
    try {
        const result = await approveCancelRequest(rescueRequest.value.id);
        const data = result.data || result;
        if (data) {
            rescueRequest.value = data;
            showCancelApprovalDialog.value = false;
            showSnackbar('Cancellation approved. Request has been cancelled.', 'success');
            
            // Update localStorage so Profile reflects 'available' status
            try {
                const stored = JSON.parse(localStorage.getItem('userData') || '{}');
                stored.status = 'available';
                localStorage.setItem('userData', JSON.stringify(stored));
            } catch (e) { /* ignore */ }

            // Redirect to dashboard after successful approval
            setTimeout(() => {
                router.visit('/rescuer/dashboard');
            }, 1500);
        }
    } catch (error) {
        console.error('Error approving cancel request:', error);
        showSnackbar('Failed to approve cancellation', 'error');
    } finally {
        cancelApprovalProcessing.value = false;
    }
};

const handleDenyCancelRequest = async () => {
    if (!cancelApprovalDenyReason.value) return;
    
    cancelApprovalProcessing.value = true;
    try {
        const result = await denyCancelRequest(rescueRequest.value.id, cancelApprovalDenyReason.value);
        const data = result.data || result;
        if (data) {
            rescueRequest.value = data;
            showCancelApprovalDialog.value = false;
            showSnackbar('Cancellation denied. User has been notified.', 'warning');
        }
    } catch (error) {
        console.error('Error denying cancel request:', error);
        showSnackbar('Failed to deny cancellation', 'error');
    } finally {
        cancelApprovalProcessing.value = false;
    }
};

const closeCancelApprovalDialog = () => {
    showCancelApprovalDialog.value = false;
    cancelApprovalDenyReason.value = '';
    cancelApprovalTab.value = 'approve';
};

// View proof photo in fullscreen
const viewProofPhoto = () => {
    proofPhotoUrl.value = `/storage/${rescueRequest.value.cancel_proof_photo}`;
    showProofPhotoViewer.value = true;
};

// View safe proof photo in fullscreen
const viewSafeProofPhoto = () => {
    proofPhotoUrl.value = getStorageUrl(rescueRequest.value.safe_proof_photo);
    showProofPhotoViewer.value = true;
};

// Get storage URL for images
const getStorageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http') || path.startsWith('data:')) return path;
    return `/storage/${path}`;
};

// Download proof photo
const downloadProofPhoto = () => {
    const link = document.createElement('a');
    link.href = proofPhotoUrl.value;
    link.download = `cancel-proof-${rescueRequest.value.id}.jpg`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Slide to confirm functionality
const startSlide = (event) => {
    if (updating.value || isSlideComplete.value) return;
    
    event.preventDefault();
    isSliding.value = true;
    
    const clientX = event.type === 'touchstart' ? event.touches[0].clientX : event.clientX;
    slideStartX.value = clientX;
    
    // Calculate max slide distance (button width minus thumb width)
    const slideTrack = event.currentTarget.querySelector('.slide-track');
    maxSlideDistance.value = slideTrack.clientWidth - 80; // 80px is thumb width
    
    // Add event listeners
    document.addEventListener('mousemove', handleSlide);
    document.addEventListener('mouseup', endSlide);
    document.addEventListener('touchmove', handleSlide, { passive: false });
    document.addEventListener('touchend', endSlide);
};

const handleSlide = (event) => {
    if (!isSliding.value) return;
    
    event.preventDefault();
    const clientX = event.type === 'touchmove' ? event.touches[0].clientX : event.clientX;
    const deltaX = clientX - slideStartX.value;
    
    // Constrain slide position
    const newPosition = Math.max(0, Math.min(deltaX, maxSlideDistance.value));
    slidePosition.value = newPosition;
    slideProgress.value = (newPosition / maxSlideDistance.value) * 100;
    
    // Check if slide is complete (95% of the way)
    if (slideProgress.value >= 95 && !isSlideComplete.value) {
        completeSlide();
    }
};

const endSlide = () => {
    if (!isSliding.value) return;
    
    // Remove event listeners
    document.removeEventListener('mousemove', handleSlide);
    document.removeEventListener('mouseup', endSlide);
    document.removeEventListener('touchmove', handleSlide);
    document.removeEventListener('touchend', endSlide);
    
    if (!isSlideComplete.value) {
        // Reset slide if not complete
        isSliding.value = false;
        slidePosition.value = 0;
        slideProgress.value = 0;
    }
};

const completeSlide = () => {
    isSlideComplete.value = true;
    isSliding.value = false;
    slidePosition.value = maxSlideDistance.value;
    slideProgress.value = 100;
    
    // Add haptic feedback if available
    if (navigator.vibrate) {
        navigator.vibrate([100, 50, 100]);
    }
    
    // Open the Mark as Safe dialog so rescuer can upload proof photo
    completionTimer.value = setTimeout(() => {
        showCompleteDialog.value = true;
    }, 800);
};

const resetSlide = () => {
    if (completionTimer.value) {
        clearTimeout(completionTimer.value);
        completionTimer.value = null;
    }
    isSliding.value = false;
    slidePosition.value = 0;
    slideProgress.value = 0;
    isSlideComplete.value = false;
};

// Check if text appears to be in English (simple heuristic)
const isLikelyEnglish = (text) => {
    if (!text) return true;
    
    const commonWords = [
        'the', 'is', 'at', 'which', 'on', 'and', 'a', 'to', 'this', 'be', 
        'has', 'have', 'it', 'in', 'of', 'for', 'not', 'with', 'he', 'as', 
        'you', 'do', 'will', 'can', 'if', 'no', 'man', 'up', 'her', 'all', 
        'any', 'may', 'say', 'she', 'or', 'an', 'are', 'his', 'your', 'how',
        'help', 'need', 'emergency', 'hurt', 'pain', 'blood', 'injury', 'fire',
        'stuck', 'trapped', 'fell', 'broken', 'stairs', 'bathroom', 'room',
        'floor', 'building', 'urgent', 'please', 'quickly', 'fast', 'now'
    ];
    
    const words = text.toLowerCase().trim().split(/\s+/);
    const totalWords = words.length;
    
    if (totalWords === 0) return true;
    
    let englishWords = 0;
    words.forEach(word => {
        const cleanWord = word.replace(/[^a-z]/g, '');
        if (commonWords.includes(cleanWord)) {
            englishWords++;
        }
    });
    
    return (englishWords / totalWords) > 0.6; // 60% threshold
};

// Fetch unread message count
const fetchUnreadMessageCount = async () => {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    const userId = userData?.id;
    if (!userId) return;
    try {
        unreadMessageCount.value = await getUnreadMessageCount(userId);
    } catch (error) {
        console.error('Failed to fetch unread message count:', error);
    }
};

// Lifecycle
onMounted(async () => {
    fetchRescueDetails();
    await fetchUnreadMessageCount();
    
    // Request notification permission for real-time alerts
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Poll for updates every 10 seconds
    pollingInterval.value = setInterval(async () => {
        fetchRescueDetails();
        await fetchUnreadMessageCount();
    }, 10000);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
    // Clean up slide timer
    if (completionTimer.value) {
        clearTimeout(completionTimer.value);
    }
    // Remove any lingering event listeners
    document.removeEventListener('mousemove', handleSlide);
    document.removeEventListener('mouseup', endSlide);
    document.removeEventListener('touchmove', handleSlide);
    document.removeEventListener('touchend', endSlide);
});
</script>

<style scoped>
/* Header */
.rescue-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: linear-gradient(135deg, #3674B5 0%, #2d5f96 100%);
    padding: env(safe-area-inset-top, 0) 0 0 0;
    box-shadow: 0 2px 12px rgba(54, 116, 181, 0.3);
}

.header-content {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    gap: 12px;
}

.back-btn {
    color: white;
    transition: transform 0.2s ease;
}

.back-btn:hover {
    transform: translateX(-2px);
}

.header-title {
    flex: 1;
}

.header-title h1 {
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
    margin: 0;
    letter-spacing: 0.3px;
}

.header-title p {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.85);
    margin: 0;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.status-chip {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.65rem;
    letter-spacing: 0.5px;
}

/* Main Content */
.rescue-main {
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px);
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 100vh;
    min-height: 100dvh;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    text-align: center;
    padding: 24px;
}

.empty-state h3 {
    margin-top: 16px;
    color: #64748b;
    font-weight: 600;
}

.empty-state p {
    color: #94a3b8;
}

.rescue-content {
    padding: 16px;
    max-width: 600px;
    margin: 0 auto;
}

/* Urgency Banner */
.urgency-banner {
    display: flex;
    align-items: center;
    padding: 18px 20px;
    border-radius: 20px;
    margin-bottom: 16px;
    gap: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    position: relative;
    overflow: hidden;
}

.urgency-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%);
    pointer-events: none;
}

.urgency-banner.urgency-critical {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.urgency-banner.urgency-high {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.urgency-banner.urgency-medium {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.urgency-banner.urgency-low {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.urgency-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.urgency-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.urgency-label {
    font-size: 1.05rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.urgency-time {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

/* Info Cards */
.info-card {
    background: white;
    border-radius: 18px;
    padding: 18px;
    margin-bottom: 14px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.04);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.info-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

/* Person Card */
.person-card {
    border-left: 4px solid #3674B5;
}

.person-info {
    display: flex;
    align-items: center;
    gap: 14px;
}

.person-avatar {
    cursor: pointer;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(54, 116, 181, 0.2);
    transition: transform 0.2s ease;
}

.person-avatar:hover {
    transform: scale(1.05);
}

.person-details {
    flex: 1;
    min-width: 0;
}

.person-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 6px 0;
}

.person-contact, .person-reporter {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #64748b;
    margin: 0;
}

/* Location Card */
.location-card {
    border-left: 4px solid #ef4444;
}

.location-info {
    margin-bottom: 14px;
}

.location-primary {
    margin-bottom: 6px;
}

.room-name {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1e293b;
}

.location-secondary {
    font-size: 0.9rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.separator {
    color: #cbd5e1;
}

.map-btn {
    width: 100%;
    margin-top: 4px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* Description Card */
.description-card {
    border-left: 4px solid #f59e0b;
}

.description-text {
    font-size: 0.95rem;
    color: #475569;
    line-height: 1.6;
    margin: 0;
}

/* Additional Info Card */
.additional-card {
    border-left: 4px solid #3b82f6;
}

.chips-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

/* Media Section within Additional Info */
.media-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}

.media-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

/* Medical Section within Additional Info */
.medical-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}

.medical-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

/* Media Attachments */
.medical-card {
    border-left: 4px solid #e53935;
}

.medical-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.medical-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.medical-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.medical-icon.blood {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.medical-icon.allergy {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.medical-icon.condition {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.medical-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.medical-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.medical-value {
    font-size: 0.95rem;
    color: #1e293b;
    font-weight: 500;
}

.medical-value.warning {
    color: #ea580c;
}

/* Action Section */
.action-section {
    margin-top: 14px;
    padding: 0;
}

.action-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 20px;
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 20px);
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    margin-bottom: 100px;
}

.action-container:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

/* Pending Safe Approval Banner */
.pending-approval-banner {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    background: linear-gradient(135deg, rgba(33, 150, 243, 0.1) 0%, rgba(33, 150, 243, 0.15) 100%);
    border: 2px solid rgba(33, 150, 243, 0.3);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.pending-approval-banner:hover {
    background: linear-gradient(135deg, rgba(33, 150, 243, 0.15) 0%, rgba(33, 150, 243, 0.2) 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
}

.pending-approval-banner:active {
    transform: translateY(0);
}

.banner-pulse {
    position: absolute;
    top: 50%;
    left: -10px;
    transform: translateY(-50%);
    width: 8px;
    height: 8px;
    background: #2196F3;
    border-radius: 50%;
    animation: banner-pulse-anim 1.5s ease-in-out infinite;
}

@keyframes banner-pulse-anim {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.4);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(33, 150, 243, 0);
    }
}

.banner-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.banner-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
}

.banner-subtitle {
    font-size: 0.75rem;
    color: #64748b;
}

/* Cancel Approval Banner Variant */
.cancel-approval-banner {
    background: linear-gradient(135deg, rgba(255, 152, 0, 0.1) 0%, rgba(255, 152, 0, 0.15) 100%) !important;
    border-color: rgba(255, 152, 0, 0.3) !important;
}

.cancel-approval-banner:hover {
    background: linear-gradient(135deg, rgba(255, 152, 0, 0.15) 0%, rgba(255, 152, 0, 0.2) 100%) !important;
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.2) !important;
}

.cancel-approval-banner .banner-pulse {
    background: #FF9800 !important;
}

@keyframes cancel-banner-pulse-anim {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.4);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(255, 152, 0, 0);
    }
}

.cancel-approval-banner .banner-pulse {
    animation: cancel-banner-pulse-anim 1.5s ease-in-out infinite;
}

/* Cancel In Progress Banner */
.cancel-in-progress-banner {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.15) 100%) !important;
    border-color: rgba(255, 193, 7, 0.3) !important;
}

.cancel-in-progress-banner .banner-pulse-orange {
    position: absolute;
    top: 50%;
    left: 24px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(255, 193, 7, 0.8);
    transform: translateY(-50%);
    animation: cancel-in-progress-pulse-anim 2s ease-in-out infinite;
}

@keyframes cancel-in-progress-pulse-anim {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
    }
    50% {
        box-shadow: 0 0 0 8px rgba(255, 193, 7, 0);
    }
}

.marking-safe-in-progress-banner {
    background: linear-gradient(135deg, rgba(33, 150, 243, 0.1) 0%, rgba(33, 150, 243, 0.15) 100%) !important;
    border-color: rgba(33, 150, 243, 0.3) !important;
}

.marking-safe-in-progress-banner .banner-pulse-blue {
    position: absolute;
    top: 50%;
    left: 24px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(33, 150, 243, 0.8);
    transform: translateY(-50%);
    animation: marking-safe-pulse-anim 2s ease-in-out infinite;
}

@keyframes marking-safe-pulse-anim {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.4);
    }
    50% {
        box-shadow: 0 0 0 8px rgba(33, 150, 243, 0);
    }
}

/* Proof Photo Styles */
.proof-photo-container {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    background: #fafafa;
}

.proof-photo {
    cursor: pointer;
    transition: opacity 0.2s;
    border: 1px solid #ddd;
}

.proof-photo:hover {
    opacity: 0.8;
}

/* Safe Approval Dialog Styles */
.safe-approval-dialog .pulse-icon {
    animation: pulse-icon-anim 2s ease-in-out infinite;
}

@keyframes pulse-icon-anim {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.safe-approval-dialog .request-info-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    padding: 16px;
    border: 1px solid #e2e8f0;
}

/* Slide to Confirm Component */
.slide-to-confirm {
    width: 100%;
    cursor: grab;
    touch-action: none;
    user-select: none;
}

.slide-to-confirm:active {
    cursor: grabbing;
}

.slide-track {
    position: relative;
    width: 100%;
    height: 64px;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 32px;
    border: 2px solid #e2e8f0;
    overflow: hidden;
    display: flex;
    align-items: center;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
}

.slide-progress {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    transition: width 0.2s ease;
    border-radius: 30px;
}

.slide-thumb {
    position: absolute;
    left: 4px;
    top: 50%;
    transform: translateY(-50%);
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
    cursor: grab;
    flex-shrink: 0;
}

.slide-thumb .v-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1;
    pointer-events: none;
    margin: 0;
    padding: 0;
}

.slide-thumb:active {
    cursor: grabbing;
}

.slide-thumb.slide-active {
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
    transform: translateY(-50%) scale(1.05);
}

.slide-thumb.slide-complete {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
}

.slide-text {
    position: absolute;
    left: 70px;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    text-align: center;
    pointer-events: none;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.slide-instruction {
    font-size: 0.9rem;
    font-weight: 600;
    color: #64748b;
    letter-spacing: 0.3px;
}

.slide-thumb.slide-active ~ .slide-text .slide-instruction {
    color: rgb(12, 12, 12);
}
.slide-success {
    font-size: 0.9rem;
    font-weight: 700;
    color: white;
    letter-spacing: 0.3px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Slide animations */
@keyframes slideComplete {
    0% { transform: translateY(-50%) scale(1.05); }
    50% { transform: translateY(-50%) scale(1.1); }
    100% { transform: translateY(-50%) scale(1); }
}

.slide-thumb.slide-complete {
    animation: slideComplete 0.4s ease-out;
}

/* Primary Action */
.primary-action {
    width: 100%;
}

.main-action-btn {
    height: 64px !important;
    font-weight: 700;
    font-size: 1.1rem !important;
    letter-spacing: 0.5px;
    text-transform: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
}

.main-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.main-action-btn:active {
    transform: translateY(0);
    transition: transform 0.1s ease;
}

.main-action-btn[disabled] {
    opacity: 0.6;
    transform: none !important;
    box-shadow: none !important;
}

.accept-btn {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
}

.accept-btn:hover {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important;
}

.safe-btn {
    background: linear-gradient(135deg, #22c55e 0%, #059669 100%) !important;
}

.safe-btn:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
}

.btn-text {
    font-weight: 700;
}

/* Secondary Actions */
.secondary-actions {
    width: 100%;
}

.action-row {
    display: flex;
    gap: 12px;
    width: 100%;
}

.secondary-btn {
    height: 52px !important;
    font-weight: 600;
    font-size: 0.95rem !important;
    text-transform: none;
    letter-spacing: 0.3px;
    transition: all 0.2s ease;
    border-width: 2px !important;
}

.flex-btn {
    flex: 1;
}

.secondary-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.secondary-btn:active {
    transform: translateY(0);
    transition: transform 0.1s ease;
}

.cancel-btn {
    border-color: #ef4444 !important;
    color: #dc2626 !important;
}

.cancel-btn:hover {
    background: rgba(239, 68, 68, 0.04) !important;
    border-color: #dc2626 !important;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15) !important;
}

/* Completed Status */
.completed-container {
    text-align: center;
    padding: 32px 20px;
}

/* HelpComing-style completed actions */
.completed-actions {
    text-align: center;
    padding: 16px;
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 100px); /* Add bottom padding for mobile */
}

.completed-actions .v-btn {
    font-weight: 600;
    text-transform: none;
    box-shadow: 0 4px 12px rgba(54, 116, 181, 0.3);
}

.completed-actions .v-btn:hover {
    box-shadow: 0 6px 16px rgba(54, 116, 181, 0.4);
}

/* Emergency contact action button */
.emergency-action {
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    padding-top: 12px;
}

.emergency-action .v-btn {
    font-weight: 600;
    text-transform: none;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.emergency-action .v-btn:hover {
    box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4);
}

.back-btn {
    height: 56px !important;
    font-weight: 600;
    font-size: 1rem !important;
    letter-spacing: 0.3px;
}

/* Complete Dialog Header */
.complete-dialog-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    border-radius: 12px 12px 0 0;
}
.complete-dialog-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
}

/* Completion Photo Section */
.completion-photo-section {
    margin-top: 4px;
}
.photo-upload-label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 4px;
}
.completion-photo-preview {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e2e8f0;
    max-height: 200px;
}
.completion-photo-preview img {
    width: 100%;
    max-height: 196px;
    object-fit: cover;
    display: block;
}
.remove-photo-btn {
    position: absolute !important;
    top: 6px;
    right: 6px;
}
.photo-upload-btn {
    height: 48px !important;
    border-style: dashed !important;
    border-width: 2px !important;
}

/* Rescue Summary Card */
.rescue-summary-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 20px;
    text-align: left;
}
.summary-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e2e8f0;
}
.summary-header-text {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
}
.summary-details {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.summary-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
}
.summary-row-block {
    flex-wrap: wrap;
}
.summary-label {
    color: #64748b;
    font-weight: 500;
    min-width: 100px;
}
.summary-value {
    color: #1e293b;
    font-weight: 600;
    flex: 1;
}
.summary-notes {
    width: 100%;
    padding: 8px 12px;
    margin-top: 4px;
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size: 0.84rem;
    color: #334155;
    line-height: 1.5;
}

/* Summary Photo */
.summary-photo-section {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
}
.summary-photo-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 8px;
}
.summary-photo-wrap {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid #e2e8f0;
    transition: border-color 0.2s;
}
.summary-photo-wrap:hover {
    border-color: #3b82f6;
}
.summary-photo {
    width: 100%;
    max-height: 220px;
    object-fit: cover;
    display: block;
}
.summary-photo-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}
.summary-photo-wrap:hover .summary-photo-overlay {
    opacity: 1;
}

/* Emergency Contact Section */
.emergency-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}

.emergency-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.emergency-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.emergency-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

/* Media Attachments */
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 10px;
}

.media-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    background: #f5f5f5;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.media-item:hover {
    transform: scale(1.03);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.media-item:active {
    transform: scale(0.98);
}

.media-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.media-item:hover .media-overlay {
    opacity: 1;
}

.video-thumbnail {
    position: relative;
    width: 100%;
    height: 100%;
    background: #1a1a1a;
}

.video-thumbnail video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-play-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Media Viewer Dialog */
.media-viewer-content {
    min-height: 300px;
    max-height: 500px;
    background: #000;
}

.media-video-player {
    width: 100%;
    max-height: 500px;
    background: #000;
}

.media-nav-arrows {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    display: flex;
    justify-content: space-between;
    padding: 0 8px;
    pointer-events: none;
}

.nav-arrow {
    pointer-events: auto;
    opacity: 0.8;
    transition: opacity 0.2s ease;
}

.nav-arrow:hover {
    opacity: 1;
}

.nav-arrow:disabled {
    opacity: 0.3;
}

/* Responsive */
@media (max-width: 600px) {
    .rescue-main {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 130px);
    }
    
    .rescue-content {
        padding: 14px;
    }
    
    .urgency-banner {
        padding: 16px;
        border-radius: 16px;
    }
    
    .urgency-label {
        font-size: 0.95rem;
    }
    
    .info-card {
        padding: 16px;
        border-radius: 16px;
    }
    
    /* Mobile-optimized action buttons */
    .action-container {
        padding: 16px;
        gap: 14px;
        border-radius: 20px;
    }
    
    .slide-track {
        height: 60px;
        border-radius: 30px;
    }
    
    .slide-thumb {
        width: 52px;
        height: 52px;
        left: 4px;
    }
    
    .slide-instruction,
    .slide-success {
        font-size: 0.85rem;
    }
    
    .main-action-btn {
        height: 60px !important;
        font-size: 1.05rem !important;
    }
    
    .secondary-btn {
        height: 48px !important;
        font-size: 0.9rem !important;
    }
    
    .action-row {
        gap: 10px;
    }
    
    /* Stack buttons on very small screens */
    .action-row {
        flex-direction: row;
    }
    
    .flex-btn {
        min-height: 48px;
    }
    
    /* HelpComing-style responsive adjustments */
    .completed-actions {
        padding: 12px;
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px); /* Extra padding for mobile bottom nav */
    }
    
    .completed-actions .v-btn {
        width: 100%;
        max-width: 300px;
    }
    
    /* Emergency action responsive */
    .emergency-action .v-btn {
        width: 100%;
    }

    .rescue-summary-card {
        padding: 12px;
    }
    .summary-label {
        min-width: 80px;
        font-size: 0.78rem;
    }
    .summary-value {
        font-size: 0.82rem;
    }
    .summary-row {
        font-size: 0.82rem;
    }
    
    .media-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
}

@media (max-width: 400px) {
    /* Very small screens - stack secondary buttons */
    .action-row {
        flex-direction: column;
        gap: 8px;
    }
    
    .flex-btn {
        width: 100%;
    }
    
    .secondary-btn {
        height: 50px !important;
    }
    
    .main-action-btn {
        height: 58px !important;
        font-size: 1rem !important;
    }
    
    .slide-track {
        height: 56px;
        border-radius: 28px;
    }
    
    .slide-thumb {
        width: 48px;
        height: 48px;
    }
    
    .slide-instruction,
    .slide-success {
        font-size: 0.8rem;
    }
}

/* Desktop visibility */
@media (max-width: 1024px) {
    .rescue-main {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px);
    }
    
    .desktop-only {
        display: none !important;
    }
}

/* Smooth scrolling */
.rescue-main {
    scroll-behavior: smooth;
}

/* Focus states for accessibility */
.main-action-btn:focus-visible,
.secondary-btn:focus-visible {
    outline: 3px solid rgba(54, 116, 181, 0.5);
    outline-offset: 2px;
}

/* Loading states */
.main-action-btn.v-btn--loading {
    transform: none !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1) !important;
}

.secondary-btn.v-btn--loading {
    transform: none !important;
}

/* Improved action container on mobile */
@media (hover: none) and (pointer: coarse) {
    .slide-to-confirm {
        cursor: default;
    }
    
    .slide-thumb {
        cursor: default;
    }
    
    .slide-thumb:active {
        cursor: default;
    }
    
    .main-action-btn:hover,
    .secondary-btn:hover {
        transform: none;
    }
    
    .main-action-btn:active {
        transform: scale(0.98);
    }
    
    .secondary-btn:active {
        transform: scale(0.98);
    }
}

/* Dark Mode Overrides */
.dark-mode .rescue-header {
    background: var(--dm-bg-surface) !important;
    border-bottom: 1px solid var(--dm-border) !important;
}

.dark-mode .rescue-main {
    background: var(--dm-bg-base) !important;
}

.dark-mode .info-card {
    background: var(--dm-bg-surface) !important;
    border: 1px solid var(--dm-border) !important;
    color: var(--dm-text-primary) !important;
}

.dark-mode .info-card:hover {
    background: var(--dm-bg-surface) !important;
    box-shadow: 0 4px 16px var(--dm-shadow) !important;
}

.dark-mode .card-header {
    color: var(--dm-text-secondary) !important;
}

.dark-mode .person-name,
.dark-mode .room-name {
    color: var(--dm-text-primary) !important;
}

.dark-mode .person-contact,
.dark-mode .person-reporter,
.dark-mode .location-secondary {
    color: var(--dm-text-secondary) !important;
}

.dark-mode .separator {
    color: var(--dm-text-muted) !important;
}

.dark-mode .description-text {
    color: var(--dm-text-primary) !important;
}

.dark-mode .medical-value {
    color: var(--dm-text-primary) !important;
}

.dark-mode .medical-label {
    color: var(--dm-text-secondary) !important;
}

.dark-mode .urgency-time {
    color: rgba(255, 255, 255, 0.8) !important;
}

.dark-mode .action-container {
    background: var(--dm-bg-surface) !important;
    border: 1px solid var(--dm-border) !important;
    backdrop-filter: none;
}

.dark-mode .slide-track {
    background: var(--dm-bg-input) !important;
    border: 2px solid var(--dm-border-light) !important;
}

.dark-mode .slide-instruction {
    color: var(--dm-text-secondary) !important;
}

.dark-mode .empty-state h3 {
    color: var(--dm-text-secondary) !important;
}

.dark-mode .empty-state p {
    color: var(--dm-text-muted) !important;
}

.dark-mode .header-title h1,
.dark-mode .back-btn {
    color: var(--dm-text-primary) !important;
}

.dark-mode .header-title p {
    color: var(--dm-text-secondary) !important;
}

.dark-mode .media-section,
.dark-mode .medical-section,
.dark-mode .emergency-section {
    border-color: var(--dm-border) !important;
}

.dark-mode .media-section-header,
.dark-mode .medical-section-header,
.dark-mode .emergency-section-header {
    color: var(--dm-text-secondary) !important;
}

.dark-mode .media-overlay {
    background: var(--dm-bg-overlay) !important;
}

.dark-mode .nav-arrow {
    background: var(--dm-bg-surface) !important;
    color: var(--dm-text-primary) !important;
}

.dark-mode .nav-arrow:hover {
    background: var(--dm-bg-elevated) !important;
}

/* Dark mode styles for HelpComing-style completion */
.dark-mode .completed-actions .v-btn {
    background: #3674B5 !important;
    color: white !important;
}

/* Dark mode for rescue summary */
.dark-mode .rescue-summary-card {
    background: var(--dm-bg-surface) !important;
    border-color: var(--dm-border) !important;
}
.dark-mode .summary-header {
    border-color: var(--dm-border) !important;
}
.dark-mode .summary-header-text {
    color: var(--dm-text-primary) !important;
}
.dark-mode .summary-label {
    color: var(--dm-text-secondary) !important;
}
.dark-mode .summary-value {
    color: var(--dm-text-primary) !important;
}
.dark-mode .summary-notes {
    background: var(--dm-bg-input, #1e1e1e) !important;
    border-color: var(--dm-border) !important;
    color: var(--dm-text-primary) !important;
}
.dark-mode .summary-photo-section {
    border-color: var(--dm-border) !important;
}
.dark-mode .summary-photo-label {
    color: var(--dm-text-secondary) !important;
}
.dark-mode .summary-photo-wrap {
    border-color: var(--dm-border) !important;
}
.dark-mode .photo-upload-label {
    color: var(--dm-text-primary) !important;
}
.dark-mode .completion-photo-preview {
    border-color: var(--dm-border) !important;
}

/* Cancel Dialog Options */
.cancel-type-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.cancel-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 14px 10px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fafafa;
}

.cancel-option:hover {
    border-color: #bbb;
    background: #f5f5f5;
}

.cancel-option-active {
    border-color: #FFA726;
    background: #FFF8E1;
}

.cancel-option-active.cancel-option-danger {
    border-color: #ef5350;
    background: #FFEBEE;
}

.cancel-option-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: #333;
    margin-top: 2px;
}

.cancel-option-desc {
    font-size: 0.65rem;
    color: #888;
    margin-top: 2px;
    line-height: 1.3;
}

@media (max-width: 360px) {
    .cancel-type-options {
        grid-template-columns: 1fr;
    }
}

/* Safe Proof Section */
.proof-section {
    border: 1px solid #E8F5E9;
    border-radius: 12px;
    padding: 16px;
    background: #F9FBE7;
}

.proof-photo {
    cursor: pointer;
    transition: transform 0.2s ease;
}

.proof-photo:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.proof-reason-container {
    margin-top: 12px;
}

/* Enhanced Call Emergency Button */
.call-emergency-btn {
    position: relative;
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%) !important;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    animation: callPulse 2s infinite;
}

.call-emergency-btn:hover {
    transform: scale(1.1) !important;
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.6) !important;
    background: linear-gradient(135deg, #66BB6A 0%, #4CAF50 100%) !important;
}

.call-emergency-btn:active {
    transform: scale(0.95) !important;
}

.call-emergency-btn::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #4CAF50, #81C784, #4CAF50);
    border-radius: inherit;
    z-index: -1;
    animation: callGlow 2s ease-in-out infinite alternate;
    opacity: 0.7;
}

@keyframes callPulse {
    0% { 
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4), 0 0 0 0 rgba(76, 175, 80, 0.7); 
    }
    50% { 
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4), 0 0 0 8px rgba(76, 175, 80, 0.3); 
    }
    100% { 
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4), 0 0 0 0 rgba(76, 175, 80, 0); 
    }
}

@keyframes callGlow {
    from {
        opacity: 0.5;
    }
    to {
        opacity: 0.8;
    }
}

/* Mobile responsive for call button */
@media (max-width: 600px) {
    .call-emergency-btn {
        animation: callPulseMobile 3s infinite;
    }
}

@keyframes callPulseMobile {
    0%, 100% { 
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
    }
    50% { 
        transform: scale(1.05);
        box-shadow: 0 6px 16px rgba(76, 175, 80, 0.6);
    }
}

/* Summary Toggle Button */
.summary-toggle-btn {
    background: linear-gradient(135deg, #3674B5 0%, #2E5A94 100%) !important;
    box-shadow: 0 4px 12px rgba(54, 116, 181, 0.3) !important;
    transition: all 0.3s ease !important;
}

.summary-toggle-btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(54, 116, 181, 0.4) !important;
}

/* Summary toggle button styling */
.summary-toggle-btn .v-btn__content {
    justify-content: space-between !important;
    width: 100% !important;
}
</style>
