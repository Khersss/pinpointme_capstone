<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <UserAppBar 
            title="Help Status" 
            :subtitle="rescue ? rescue.rescue_code : ''"
            :show-back="true"
            :notification-count="0"
            @go-back="handleGoBack"
        >
        </UserAppBar>

        <!-- Navigation Drawer - handles its own visibility -->
        <UserMenu v-model="drawer" />

        <v-main>

            <v-container fluid class="pa-0">
                <!-- Loading State -->
                <div v-if="loading" class="d-flex flex-column justify-center align-center" style="min-height: 70vh;">
                    <v-progress-circular indeterminate color="primary" size="64" width="5" />
                    <p class="text-grey mt-4">Loading rescue status...</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="pa-4">
                    <v-alert type="error" variant="tonal" class="mb-4 rounded-xl">
                        {{ error }}
                        <template v-slot:append>
                            <v-btn variant="text" @click="fetchRescueData">Retry</v-btn>
                        </template>
                    </v-alert>
                </div>

                <!-- Rescue Status Display -->
                <template v-else-if="rescue">
                    <div class="status-hero" :class="'status-' + rescue.status">
                        <!-- Progress Steps -->
                        <div class="progress-steps-container">
                            <div class="progress-track">
                                <div class="progress-fill" :style="{ width: getProgressWidth(rescue.status) }"></div>
                            </div>
                            <div class="progress-steps">
                                <div class="progress-step" :class="{ active: isStepActive(rescue.status, 'pending'), completed: isStepCompleted(rescue.status, 'pending') }">
                                    <div class="step-dot">
                                        <v-icon v-if="isStepCompleted(rescue.status, 'pending')" size="14" color="white">mdi-check</v-icon>
                                        <v-icon v-else-if="isStepActive(rescue.status, 'pending')" size="12" color="white">mdi-clock-outline</v-icon>
                                    </div>
                                    <span>Pending</span>
                                </div>
                                <div class="progress-step" :class="{ active: isStepActive(rescue.status, 'in_progress'), completed: isStepCompleted(rescue.status, 'in_progress') }">
                                    <div class="step-dot">
                                        <v-icon v-if="isStepCompleted(rescue.status, 'in_progress')" size="14" color="white">mdi-check</v-icon>
                                        <v-icon v-else-if="isStepActive(rescue.status, 'in_progress')" size="12" color="white">mdi-run-fast</v-icon>
                                    </div>
                                    <span>In Progress</span>
                                </div>
                                <div class="progress-step" :class="{ active: isStepActive(rescue.status, 'rescued'), completed: isStepCompleted(rescue.status, 'rescued') }">
                                    <div class="step-dot">
                                        <v-icon v-if="isStepCompleted(rescue.status, 'rescued') || isStepActive(rescue.status, 'rescued')" size="14" color="white">mdi-check-circle</v-icon>
                                    </div>
                                    <span>Rescued</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Area -->
                    <div class="content-area pa-4">
                        <!-- Completed State Actions - Moved to top for immediate visibility -->
                        <div v-if="rescue.status === 'rescued' || rescue.status === 'safe'" class="text-center py-4 mb-4">
                            <v-icon size="56" color="success" class="mb-2">mdi-check-circle</v-icon>
                            <h3 class="text-h6 mb-1">Rescue Complete</h3>
                            <p v-if="rescue.first_name || rescue.last_name" class="text-grey mb-1" style="font-weight: 600;">
                                <v-icon size="16" class="mr-1">mdi-account-alert</v-icon>
                                {{ getReporterName() }}
                            </p>
                            <p class="text-grey mb-3">Thank you for using PinPointMe. Stay safe!</p>

                            <!-- Feedback Button -->
                            <v-btn
                                v-if="!feedbackSubmitted && rescue.assigned_rescuer"
                                variant="flat"
                                color="#3674B5"
                                rounded="xl"
                                size="large"
                                class="mt-2 mb-2"
                                @click="showFeedbackDialog = true"
                            >
                                <v-icon start>mdi-star-outline</v-icon>
                                Share Feedback
                            </v-btn>
                            <v-chip v-else-if="feedbackSubmitted" color="success" variant="tonal" class="mt-2 mb-2">
                                <v-icon start size="16">mdi-check-circle</v-icon>
                                Feedback Submitted — Thank you!
                            </v-chip>
                        </div>

                        <!-- Cancelled State - Moved to top for immediate visibility -->
                        <div v-else-if="rescue.status === 'cancelled'" class="text-center py-4 mb-4">
                            <v-icon size="56" color="grey" class="mb-2">mdi-close-circle</v-icon>
                            <h3 class="text-h6 mb-1">Request Cancelled</h3>
                            <v-alert 
                                v-if="rescue.cancellation_reason" 
                                type="warning" 
                                variant="tonal" 
                                density="compact" 
                                class="mx-4 mb-3 text-left"
                            >
                                <div class="text-caption font-weight-bold mb-1">Cancellation Reason:</div>
                                <div class="text-body-2">{{ rescue.cancellation_reason }}</div>
                            </v-alert>
                            <p class="text-grey mb-3">You can submit a new rescue request if needed.</p>

                        </div>

                        <!-- Location Card -->
                        <v-card class="mb-3 rounded-xl location-card" elevation="2">
                            <div class="card-header-icon">
                                <v-avatar color="primary" size="40">
                                    <v-icon color="white" size="20">mdi-map-marker</v-icon>
                                </v-avatar>
                                <div class="card-header-text">
                                    <h3>Your Location</h3>
                                    <p>{{ needsLocationUpdate ? 'Location not yet provided' : 'Where help is coming' }}</p>
                                </div>
                            </div>
                            <v-card-text class="pt-0">
                                <template v-if="!needsLocationUpdate">
                                    <div class="location-details">
                                        <div class="location-item">
                                            <v-icon color="primary" size="18">mdi-office-building</v-icon>
                                            <div>
                                                <span class="label">Building</span>
                                                <span class="value">{{ locationDetails.buildingName }}</span>
                                            </div>
                                        </div>
                                        <div class="location-item">
                                            <v-icon color="secondary" size="18">mdi-stairs</v-icon>
                                            <div>
                                                <span class="label">Floor</span>
                                                <span class="value">{{ locationDetails.floorName || 'Loading...' }}</span>
                                            </div>
                                        </div>
                                        <div class="location-item">
                                            <v-icon color="success" size="18">mdi-door</v-icon>
                                            <div>
                                                <span class="label">Room</span>
                                                <span class="value">{{ locationDetails.roomName || 'Loading...' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- View Map Button -->
                                    <v-btn
                                        variant="flat"
                                        color="#3674B5"
                                        size="large"
                                        class="mt-4 view-map-btn"
                                        block
                                        elevation="4"
                                        @click="viewMap"
                                    >
                                        <v-icon start size="20">mdi-map-marker-radius</v-icon>
                                        View Floor Map
                                    </v-btn>
                                </template>
                                <template v-else>
                                    <v-alert type="info" variant="tonal" density="compact" class="rounded-lg">
                                        <div class="text-caption">
                                            <template v-if="['rescued', 'safe', 'cancelled'].includes(rescue.status)">
                                                Location was not provided during this rescue request.
                                            </template>
                                            <template v-else>
                                                Location not yet provided. Tap "Update Details" below to add your location so rescuers can find you.
                                            </template>
                                        </div>
                                    </v-alert>
                                </template>
                            </v-card-text>
                            

                            
                        </v-card>
                         <!-- Update Details Card (when location or details are missing) -->
                        <v-card v-if="(needsLocationUpdate || needsDetailsUpdate) && rescue.status !== 'rescued' && rescue.status !== 'safe' && rescue.status !== 'cancelled'" class="mb-3 rounded-xl update-details-card" elevation="3">
                            <div class="card-header-icon update-details-header" style="cursor: pointer;" @click="toggleUpdateForm">
                                <v-avatar color="grey" size="40">
                                    <v-icon color="white" size="20">mdi-pencil</v-icon>
                                </v-avatar>
                                <div class="card-header-text">
                                    <h3>Update Details</h3>
                                    <p>Add missing information to help rescuers find you</p>
                                </div>
                                <v-icon class="ml-auto" color="black">{{ showUpdateForm ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                            </div>
                            
                            <v-expand-transition>
                                <v-card-text v-if="showUpdateForm" class="pt-0">
                                    <v-form @submit.prevent="handleUpdateDetails">
                                        <!-- Location Selection (if missing) -->
                                        <template v-if="needsLocationUpdate">
                                            <p class="text-subtitle-2 font-weight-bold mb-2 mt-5">
                                                <v-icon size="16" class="mr-1 mt">mdi-map-marker</v-icon>
                                                Location
                                            </p>
                                            <v-select
                                                v-model="updateForm.building_id"
                                                :items="buildings"
                                                item-title="name"
                                                item-value="id"
                                                label="Building"
                                                variant="outlined"
                                                density="comfortable"
                                                prepend-inner-icon="mdi-office-building"
                                                hide-details
                                                class="mb-3"
                                                @update:modelValue="updateForm.floor_id = null; updateForm.room_id = null"
                                            />
                                            <v-select
                                                v-model="updateForm.floor_id"
                                                :items="updateFloors"
                                                item-title="floor_name"
                                                item-value="id"
                                                label="Floor"
                                                variant="outlined"
                                                density="comfortable"
                                                prepend-inner-icon="mdi-stairs"
                                                hide-details
                                                class="mb-3"
                                                :disabled="!updateForm.building_id"
                                                @update:modelValue="updateForm.room_id = null"
                                            />
                                            <v-select
                                                v-model="updateForm.room_id"
                                                :items="updateRooms"
                                                item-title="room_name"
                                                item-value="id"
                                                label="Room"
                                                variant="outlined"
                                                density="comfortable"
                                                prepend-inner-icon="mdi-door"
                                                hide-details
                                                class="mb-3"
                                                :disabled="!updateForm.floor_id"
                                            />
                                            <v-divider class="mb-3" />
                                        </template>

                                        <!-- Emergency Details (if missing) -->
                                        <template v-if="needsDetailsUpdate">
                                            <p class="text-subtitle-2 font-weight-bold mb-2">
                                                <v-icon size="16" class="mr-1">mdi-alert-circle</v-icon>
                                                Emergency Details
                                            </p>
                                            <v-textarea
                                                v-model="updateForm.description"
                                                label="Describe your emergency"
                                                variant="outlined"
                                                rows="2"
                                                prepend-inner-icon="mdi-text"
                                                hide-details
                                                class="mb-3"
                                                placeholder="Briefly describe the situation"
                                            />
                                            <v-row dense class="mb-3">
                                                <v-col cols="6">
                                                    <v-select
                                                        v-model="updateForm.mobility_status"
                                                        :items="mobilityOptions"
                                                        item-title="title"
                                                        item-value="value"
                                                        label="Mobility Status"
                                                        variant="outlined"
                                                        density="comfortable"
                                                        prepend-inner-icon="mdi-walk"
                                                        hide-details
                                                        clearable
                                                    />
                                                </v-col>
                                                <v-col cols="6">
                                                    <v-select
                                                        v-model="updateForm.urgency_level"
                                                        :items="urgencyOptions"
                                                        item-title="title"
                                                        item-value="value"
                                                        label="Urgency"
                                                        variant="outlined"
                                                        density="comfortable"
                                                        prepend-inner-icon="mdi-speedometer"
                                                        hide-details
                                                        clearable
                                                    />
                                                </v-col>
                                            </v-row>
                                            <v-select
                                                v-model="updateForm.injuries"
                                                :items="injuryOptions"
                                                item-title="title"
                                                item-value="value"
                                                label="Injuries (if any)"
                                                variant="outlined"
                                                density="comfortable"
                                                prepend-inner-icon="mdi-medical-bag"
                                                multiple
                                                chips
                                                closable-chips
                                                hide-details
                                                class="mb-3"
                                            />
                                        </template>

                                        <v-btn
                                            color="info"
                                            variant="flat"
                                            block
                                            :loading="isUpdating"
                                            type="submit"
                                            class="rounded-xl mt-2 font-weight-bold"
                                            size="large"
                                        >
                                            Save Details
                                        </v-btn>
                                    </v-form>
                                </v-card-text>
                            </v-expand-transition>
                        </v-card>


                        <!-- Rescuer Card (if assigned) or Self-Safe Card -->
                        <v-card v-if="rescue.assigned_rescuer || rescue.rescuer || (rescue.status === 'rescued' || rescue.status === 'safe')" class="mb-3 rounded-xl rescuer-card" elevation="2">
                            <div class="card-header-icon">
                                <v-avatar color="success" size="40">
                                    <v-icon color="white" size="20">{{ (rescue.assigned_rescuer || rescue.rescuer) ? 'mdi-account-check' : 'mdi-shield-check' }}</v-icon>
                                </v-avatar>
                                <div class="card-header-text">
                                    <h3>{{ (rescue.assigned_rescuer || rescue.rescuer) ? (rescue.status === 'rescued' || rescue.status === 'safe' ? 'Your Rescuer' : 'Help is Coming') : (rescue.first_name || rescue.last_name ? 'Samaritan Report - Safe' : 'Marked Safe by Yourself') }}</h3>
                                    <p>{{ (rescue.assigned_rescuer || rescue.rescuer) ? (rescue.status === 'rescued' || rescue.status === 'safe' ? 'Rescue completed' : 'Rescuer assigned to help you') : (rescue.first_name || rescue.last_name ? 'Person marked as safe by reporter' : 'You have marked yourself as safe') }}</p>
                                </div>
                            </div>
                            <v-card-text class="pt-0">
                                <!-- Show rescuer info if rescuer is assigned -->
                                <div v-if="rescue.assigned_rescuer || rescue.rescuer" class="rescuer-info">
                                    <div class="rescuer-details">
                                        <h4>{{ getRescuerName() }}</h4>
                                        <p v-if="!rescuerProfilePicture" class="text-body-2 text-grey-darken-1"></p>
                                    </div>
                                    <v-icon 
                                        color="primary" 
                                        size="20" 
                                        style="cursor: pointer;"
                                        @click="showRescuerProfile = true"
                                    >
                                        mdi-information-outline
                                    </v-icon>
                                </div>
                                
                                <!-- Show self-safe message if no rescuer assigned -->
                                <div v-else class="self-safe-info">
                                    <!-- Status Card Design -->
                                  
                                        <!-- Card Content -->
                                        <div class="status-body">
                                            <div v-if="rescue.first_name || rescue.last_name" class="status-message">
                                                <p>This person has been marked as safe by the Samaritan reporter who initially reported the emergency.</p>
                                            </div>
                                            <div v-else class="status-message">
                                                <p>You have successfully resolved your emergency situation without requiring external rescue assistance.</p>
                                            </div>
                                            
                                            <!-- Timestamp -->
                                            <div class="status-timestamp">
                                                <strong>Resolved on {{ formatRescueDateTime(rescue.self_marked_safe_at || rescue.updated_at) }}</strong>
                                            </div>
                                            
                                            <!-- Additional Note -->
                                            <div class="status-note">
                                                <em>External assistance cancelled.</em>
                                            </div>
                                        </div>
                                   
                                </div>
                            </v-card-text>
                        </v-card>

                        <!-- Emergency Details -->
                        <v-card v-if="rescue.description || rescue.urgency_level || rescue.mobility_status || rescue.injuries || rescue.status === 'rescued' || rescue.status === 'safe'" class="mb-3 rounded-xl emergency-card" elevation="2">
                            <div class="card-header-icon">
                                <v-avatar color="error" size="40">
                                    <v-icon color="white" size="20">mdi-alert-circle</v-icon>
                                </v-avatar>
                                <div class="card-header-text">
                                    <h3>Emergency Details</h3>
                                    <p>Request information and status</p>
                                </div>
                            </div>
                            
                            <v-card-text class="pt-0">
                                <div class="emergency-details">
                                    <!-- Description -->
                                    <div v-if="rescue.description" class="detail-item description-item">
                                        <span class="detail-label">
                                            <v-icon size="14" class="mr-1">mdi-text-box</v-icon>
                                            Description
                                        </span>
                                        <div class="detail-value description-text">
                                            <p v-for="(paragraph, index) in rescue.description.split('\n').filter(p => p.trim())" :key="index" class="description-paragraph">
                                                {{ paragraph }}
                                            </p>
                                        </div>
                                        <!-- Show translate button if language is not English -->
                                        <div v-if="rescue.is_translated && !rescue.original_description" class="mt-2">
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
                                        <!-- Show translated text with original -->
                                        <div v-if="rescue.original_description" class="translation-info mt-1">
                                            <v-chip size="x-small" color="info" variant="tonal" class="mb-1">
                                                <v-icon start size="10">mdi-translate</v-icon>
                                                Translated to English
                                            </v-chip>
                                            <p class="original-text text-caption text-grey-darken-1" style="font-style: italic;">
                                                Original: "{{ rescue.original_description }}"
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Urgency & Mobility Row -->
                                    <div class="detail-row" v-if="rescue.urgency_level || rescue.mobility_status || rescue.emergency_type || rescue.status === 'rescued' || rescue.status === 'safe'">
                                        <div v-if="rescue.urgency_level || rescue.status === 'rescued' || rescue.status === 'safe'" class="detail-item half">
                                            <span class="detail-label">Urgency Level</span>
                                            <div class="detail-chip-value">
                                                <v-chip 
                                                    v-if="rescue.urgency_level"
                                                    :color="getUrgencyColor(rescue.urgency_level)" 
                                                    variant="flat" 
                                                    size="small"
                                                >
                                                    <v-icon start size="12">{{ getUrgencyIcon(rescue.urgency_level) }}</v-icon>
                                                    {{ getUrgencyTitle(rescue.urgency_level) }}
                                                </v-chip>
                                                <span v-else class="text-grey">Not specified</span>
                                            </div>
                                        </div>

                                        <div v-if="rescue.mobility_status || rescue.status === 'rescued' || rescue.status === 'safe'" class="detail-item half">
                                            <span class="detail-label">Mobility Status</span>
                                            <div class="detail-chip-value">
                                                <v-chip v-if="rescue.mobility_status" color="info" variant="flat" size="small">
                                                    <v-icon start size="12">mdi-wheelchair-accessibility</v-icon>
                                                    {{ getMobilityTitle(rescue.mobility_status) }}
                                                </v-chip>
                                                <span v-else class="text-grey">Not specified</span>
                                            </div>
                                        </div>

                                        <div v-if="rescue.emergency_type" class="detail-item half">
                                            <span class="detail-label">Emergency Type</span>
                                            <div class="detail-chip-value">
                                                <v-chip color="orange" variant="flat" size="small">
                                                    <v-icon start size="12">{{ getEmergencyTypeIcon(rescue.emergency_type) }}</v-icon>
                                                    {{ rescue.emergency_type }}
                                                </v-chip>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Injuries -->
                                    <div v-if="rescue.injuries || rescue.status === 'rescued' || rescue.status === 'safe'" class="detail-item">
                                        <span class="detail-label">Injuries Reported</span>
                                        <p v-if="rescue.injuries" class="detail-value">
                                            {{ getInjuryTitles(rescue.injuries) }}
                                        </p>
                                        <p v-else class="detail-value text-grey">
                                            No injuries reported
                                        </p>
                                        <div v-if="rescue.original_injuries" class="translation-info mt-1">
                                            <v-chip size="x-small" color="info" variant="tonal" class="mb-1">
                                                <v-icon start size="10">mdi-translate</v-icon>
                                                Translated
                                            </v-chip>
                                            <p class="original-text text-caption text-grey-darken-1" style="font-style: italic;">
                                                Original: "{{ rescue.original_injuries }}"
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Completion Time -->
                                    <div v-if="(rescue.status === 'rescued' || rescue.status === 'safe') && rescue.updated_at" class="detail-item">
                                        <span class="detail-label">Rescue Completed</span>
                                        <p class="detail-value">
                                            <v-icon color="success" size="16" class="mr-2">mdi-clock-check</v-icon>
                                            {{ formatRescueDateTime(rescue.updated_at) }}
                                        </p>
                                    </div>

                                    <!-- Rescuer's Completion Notes -->
                                    <div v-if="rescue.completion_notes && (rescue.status === 'rescued' || rescue.status === 'safe')" class="detail-item">
                                        <span class="detail-label">Rescuer Notes</span>
                                        <div class="completion-notes-box">
                                            <v-icon size="14" color="grey" class="mr-1" style="flex-shrink:0;">mdi-note-text</v-icon>
                                            <p class="detail-value" style="margin:0; white-space:pre-wrap;">{{ rescue.completion_notes }}</p>
                                        </div>
                                    </div>

                                    <!-- Completion Proof Photo -->
                                    <div v-if="rescue.completion_photo && (rescue.status === 'rescued' || rescue.status === 'safe')" class="detail-item">
                                        <span class="detail-label">
                                            <v-icon size="14" color="success" class="mr-1">mdi-camera-check</v-icon>
                                            Rescue Proof Photo
                                        </span>
                                        <div class="proof-photo-wrap" @click="showProofPhoto = true">
                                            <img :src="rescue.completion_photo" alt="Rescue completion proof" class="proof-photo-thumb" />
                                            <div class="proof-photo-badge">
                                                <v-icon size="12" color="white">mdi-magnify-plus</v-icon>
                                                <span>View</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </v-card-text>
                        </v-card>

                       
                        <!-- Action Container -->
                        <!-- Pending Safe Approval State -->
                        <div v-if="rescue.safe_approval_requested && rescue.safe_approval_status === 'pending'" class="action-container approval-pending-container">
                            <div class="approval-pending-card">
                                <div class="pulse-animation">
                                    <v-icon size="48" :color="isCriticalUrgency ? 'warning' : 'info'">{{ isCriticalUrgency ? 'mdi-shield-alert' : 'mdi-account-clock' }}</v-icon>
                                </div>
                                <h3 class="approval-title">{{ isCriticalUrgency ? 'Waiting for Admin Approval' : 'Waiting for Rescuer Approval' }}</h3>
                                <p class="approval-subtitle">
                                    <template v-if="isCriticalUrgency">
                                        Since your urgency level is <strong>Critical</strong>, an administrator must verify and approve your safety before you can be marked as safe. A rescuer may also be sent to check on you.
                                    </template>
                                    <template v-else>
                                        Your safe request has been sent to the rescuer.
                                        They will confirm once they verify your safety.
                                    </template>
                                </p>
                                <div class="approval-timestamp">
                                    <v-icon size="14" class="mr-1">mdi-clock-outline</v-icon>
                                    Requested {{ formatApprovalTime(rescue.safe_approval_requested_at) }}
                                </div>
                            </div>
                            
                            <div class="secondary-actions mt-4">
                                <div class="action-row">
                                    <v-btn
                                        v-if="!isCriticalUrgency"
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
                                        color="#3674B5"
                                        size="large"
                                        rounded="xl"
                                        @click="handleCancelSafeApproval"
                                        class="secondary-btn flex-btn"
                                        flex
                                    >
                                        <v-icon start size="18">mdi-undo</v-icon>
                                        <span>Cancel</span>
                                    </v-btn>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Denied Safe Approval State + Slide to Try Again -->
                        <div v-else-if="rescue.safe_approval_status === 'denied' && !['rescued', 'safe', 'cancelled'].includes(rescue.status)" class="action-container">
                            <v-alert type="warning" variant="tonal" class="mb-4 rounded-xl" density="comfortable">
                                <div class="d-flex align-center">
                                    <div>
                                        <div class="font-weight-bold">Safe Request Denied</div>
                                        <div class="text-caption">{{ rescue.safe_approval_reason || 'The rescuer believes you still need assistance.' }}</div>
                                    </div>
                                </div>
                            </v-alert>
                            
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
                                                {{ isSliding ? 'Keep sliding...' : 'Slide to request again' }}
                                            </span>
                                            <span v-else class="slide-success">
                                                Request sent!
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
                                    
                                    <!-- Simple Cancel Button with 3-second hold -->
                                    <v-btn
                                        v-if="canCancel"
                                        variant="flat"
                                        color="error"
                                        size="large"
                                        rounded="xl"
                                        class="secondary-btn flex-btn cancel-hold-btn"
                                        :class="{ 'holding': cancelHoldActive }"
                                        @mousedown="startCancelHold"
                                        @mouseup="endCancelHold"
                                        @mouseleave="endCancelHold"
                                        @touchstart.prevent="startCancelHold"
                                        @touchend="endCancelHold"
                                        @touchcancel="endCancelHold"
                                        flex
                                    >
                                        <v-icon start size="18">mdi-close-circle-outline</v-icon>
                                        {{ cancelHoldActive ? 'Hold to cancel' : 'Cancel Request' }}
                                    </v-btn>
                                    <v-btn
                                        v-else-if="isCancelBlockedByUrgency && !['rescued', 'safe', 'cancelled', 'completed'].includes(rescue.status)"
                                        variant="outlined"
                                        color="grey"
                                        size="large"
                                        rounded="xl"
                                        disabled
                                        class="secondary-btn cancel-btn flex-btn"
                                        flex
                                    >
                                        <v-icon start size="18">mdi-lock-outline</v-icon>
                                        <span>Cancel Locked</span>
                                    </v-btn>
                                </div>
                                <v-alert
                                    v-if="isCancelBlockedByUrgency && !['rescued', 'safe', 'cancelled', 'completed'].includes(rescue.status)"
                                    type="warning"
                                    variant="tonal"
                                    density="compact"
                                    class="mt-2 mx-2"
                                    icon="mdi-shield-lock-outline"
                                >
                                    <span class="text-caption">Cancellation is disabled for <strong>{{ rescue.urgency_level?.toUpperCase() }}</strong> urgency requests. A rescuer must verify your safety.</span>
                                </v-alert>
                            </div>
                        </div>
                        
                        <!-- Pending Cancel Approval State -->
                        <div v-else-if="rescue.cancel_approval_requested && rescue.cancel_approval_status === 'pending'" class="action-container approval-pending-container">
                            <div class="approval-pending-card cancel-approval-card">
                                <div class="pulse-animation">
                                    <v-icon size="48" color="warning">mdi-cancel</v-icon>
                                </div>
                                <h3 class="approval-title">Cancellation Pending Approval</h3>
                                <p class="approval-subtitle">
                                    Your cancellation request has been sent to the rescuer.
                                    Use chat to confirm you no longer need help.
                                </p>
                                <div class="approval-timestamp">
                                    <v-icon size="14" class="mr-1">mdi-clock-outline</v-icon>
                                    Requested {{ formatApprovalTime(rescue.cancel_approval_requested_at) }}
                                </div>
                                
                                <!-- Show the reason sent -->
                                <v-alert type="info" variant="tonal" density="compact" class="mt-3 rounded-lg text-left">
                                    <div class="text-caption"><strong>Your reason:</strong> {{ rescue.cancel_approval_reason || rescue.cancellation_reason }}</div>
                                </v-alert>
                            </div>
                            
                            <div class="secondary-actions mt-4">
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
                                        <span>Chat with Rescuer</span>
                                    </v-btn>
                                    
                                    <v-btn
                                        variant="outlined"
                                        color="grey"
                                        size="large"
                                        rounded="xl"
                                        @click="handleWithdrawCancelRequest"
                                        class="secondary-btn flex-btn"
                                        flex
                                    >
                                        <v-icon start size="18">mdi-undo</v-icon>
                                        <span>Withdraw</span>
                                    </v-btn>
                                </div>
                            </div>
                        </div>

                        <!-- Denied Cancel Approval State -->
                        <div v-else-if="rescue.cancel_approval_status === 'denied' && !['rescued', 'safe', 'cancelled'].includes(rescue.status)" class="action-container">
                            <v-alert type="error" variant="tonal" class="mb-4 rounded-xl" density="comfortable">
                                <div class="d-flex align-center">
                                    <v-icon start color="error">mdi-close-circle</v-icon>
                                    <div>
                                        <div class="font-weight-bold">Cancellation Denied</div>
                                        <div class="text-caption">{{ rescue.cancel_approval_reason || 'The rescuer believes you still need assistance.' }}</div>
                                    </div>
                                </div>
                            </v-alert>
                            
                            <v-alert type="info" variant="tonal" density="compact" class="mb-3 rounded-lg">
                                <div class="text-caption">
                                    <v-icon size="14" class="mr-1">mdi-chat-processing</v-icon>
                                    Please use chat to communicate with your rescuer if you no longer need help.
                                </div>
                            </v-alert>
                            
                            <div class="primary-action">
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
                                                {{ isSliding ? 'Keep sliding...' : 'Slide to mark as safe instead' }}
                                            </span>
                                            <span v-else class="slide-success">
                                                Safe request sent!
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
                                    
                                    <!-- Simple Cancel Button with 3-second hold -->
                                    <v-btn
                                        v-if="canCancel"
                                        variant="flat"
                                        color="error"
                                        size="large"
                                        rounded="xl"
                                        class="flex-btn cancel-hold-btn ma-1"
                                        :class="{ 'holding': cancelHoldActive }"
                                        @mousedown="startCancelHold"
                                        @mouseup="endCancelHold"
                                        @mouseleave="endCancelHold"
                                        @touchstart.prevent="startCancelHold"
                                        @touchend="endCancelHold"
                                        @touchcancel="endCancelHold"
                                        flex
                                    >
                                        <v-icon start>mdi-close-circle-outline</v-icon>
                                        {{ cancelHoldActive ? 'Hold...' : 'Cancel Request' }}
                                    </v-btn>
                                    <v-btn
                                        v-else-if="isCancelBlockedByUrgency && !['rescued', 'safe', 'cancelled', 'completed'].includes(rescue.status)"
                                        variant="outlined"
                                        color="grey"
                                        size="large"
                                        rounded="xl"
                                        disabled
                                        class="secondary-btn cancel-btn flex-btn"
                                        flex
                                    >
                                        <v-icon start size="18">mdi-lock-outline</v-icon>
                                        <span>Cancel Locked</span>
                                    </v-btn>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Normal Action Container (no pending approval) -->
                        <div v-else-if="rescue.status !== 'rescued' && rescue.status !== 'safe' && rescue.status !== 'cancelled'" class="action-container">
                            <div class="primary-action">
                                <!-- Locked Slide for High/Critical urgency without rescuer -->
                                <div v-if="isSlideLockedByUrgency" class="slide-to-confirm slide-locked">
                                    <div class="slide-track slide-track-locked">
                                        <div class="slide-thumb slide-thumb-locked">
                                            <v-icon size="24" color="white">mdi-lock</v-icon>
                                        </div>
                                        <div class="slide-text">
                                            <span class="slide-instruction slide-locked-text">
                                                Waiting for rescuer to accept...
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Normal Slide to confirm Mark as Safe -->
                                <div v-else class="slide-to-confirm" @mousedown="startSlide" @touchstart="startSlide">
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
                                                {{ isSliding ? 'Keep sliding...' : 'Slide to confirm you are safe' }}
                                            </span>
                                            <span v-else class="slide-success">
                                                Marked as safe!
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Combined urgency notice for High/Critical -->
                            <v-alert 
                                v-if="isHighOrCriticalUrgency"
                                type="warning" 
                                variant="tonal" 
                                density="compact" 
                                class="mb-3 rounded-lg"
                                icon="mdi-shield-lock-outline"
                            >
                                <div class="text-caption">
                                    Your urgency level is <strong>{{ rescue.urgency_level }}</strong>. A rescuer must accept and verify your safety before you can be marked as safe or cancel this request.
                                </div>
                            </v-alert>

                            <!-- Info hint if rescuer is assigned (non-high/critical) -->
                            <v-alert 
                                v-else-if="(rescue.assigned_rescuer || rescue.rescuer) && ['assigned', 'in_progress'].includes(rescue.status)"
                                type="info" 
                                variant="tonal" 
                                density="compact" 
                                class="mb-3 rounded-lg"
                            >
                                <div class="text-caption">
                                     Since a rescuer is assigned, they will need to approve your safe request.
                                </div>
                            </v-alert>
                            
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
                                    
                                    <!-- Simple Cancel Button with 3-second hold -->
                                    <v-btn
                                        v-if="canCancel"
                                        variant="flat"
                                        color="error"
                                        size="large"
                                        rounded="xl"
                                        class="flex-btn cancel-hold-btn ma-1"
                                        :class="{ 'holding': cancelHoldActive }"
                                        @mousedown="startCancelHold"
                                        @mouseup="endCancelHold"
                                        @mouseleave="endCancelHold"
                                        @touchstart.prevent="startCancelHold"
                                        @touchend="endCancelHold"
                                        @touchcancel="endCancelHold"
                                        flex
                                    >
                                        <v-icon start>mdi-close-circle-outline</v-icon>
                                        {{ cancelHoldActive ? 'Hold...' : 'Cancel  ' }}
                                    </v-btn>
                                    <v-btn
                                        v-else-if="isCancelBlockedByUrgency && !['rescued', 'safe', 'cancelled', 'completed'].includes(rescue.status)"
                                        variant="outlined"
                                        color="grey"
                                        size="large"
                                        rounded="xl"
                                        disabled
                                        class="secondary-btn cancel-btn flex-btn"
                                        flex
                                    >
                                        <v-icon start size="18">mdi-lock-outline</v-icon>
                                        <span>Cancel Locked</span>
                                    </v-btn>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- No Rescue Data -->
                <div v-else class="no-rescue-state">
                    <v-icon size="80" color="grey-lighten-1">mdi-alert-circle-outline</v-icon>
                    <h3>No Active Rescue Request</h3>
                    <p>You don't have an active rescue request</p>
                </div>
            </v-container>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- CANCEL CONFIRMATION MODAL — REMOVED (slide goes to reason) -->
            <!-- ═══════════════════════════════════════════════════════ -->

            <!-- Safe Proof Dialog (photo + reason for marking safe) -->
            <v-dialog v-model="showSafeProofDialog" max-width="480" persistent>
                <v-card class="rounded-xl">
                    <v-card-text class="pt-6 pb-2">
                        <div class="text-center mb-4">
                            <v-avatar color="success" size="64" class="mb-3">
                                <v-icon size="32" color="white">mdi-shield-check</v-icon>
                            </v-avatar>
                            <h3 class="text-h6 mb-1">Confirm You Are Safe</h3>
                            <p class="text-grey text-body-2">Please provide a photo and brief reason as proof that you are safe.</p>
                        </div>

                        <!-- Photo Section -->
                        <div class="mb-4">
                            <p class="text-subtitle-2 text-grey-darken-2 mb-2">
                                Photo Proof <span class="text-error">*</span>
                            </p>
                            
                            <!-- Camera Preview -->
                            <div v-if="isCaptureMode" class="camera-container rounded-lg overflow-hidden mb-2 position-relative" style="padding: 8px;">
                                <video 
                                    ref="videoRef" 
                                    autoplay 
                                    playsinline 
                                    class="w-100"
                                    style="max-height: 220px; object-fit: cover; border-radius: 8px;"
                                ></video>
                                
                                <!-- Cancel X Button - Top Right -->
                                <v-btn 
                                    icon
                                    size="small"
                                    variant="flat"
                                    color="rgba(0,0,0,0.6)"
                                    class="position-absolute"
                                    style="top: 16px; right: 16px; z-index: 10;"
                                    @click="stopCamera"
                                >
                                    <v-icon color="white" size="18">mdi-close</v-icon>
                                </v-btn>
                                
                                <!-- Capture Button - Centered -->
                                <div class="d-flex justify-center mt-2">
                                    <v-btn 
                                        icon
                                        size="large"
                                        variant="flat" 
                                        color="success"
                                        @click="capturePhoto"
                                        class="capture-btn-icon"
                                        elevation="4"
                                    >
                                        <v-icon size="32" color="white">mdi-camera</v-icon>
                                    </v-btn>
                                </div>
                            </div>
                            
                            <!-- Photo Preview -->
                            <div v-else-if="safeProofPhotoPreview" class="photo-preview rounded-lg overflow-hidden mb-2 position-relative">
                                <v-img 
                                    :src="safeProofPhotoPreview" 
                                    max-height="200" 
                                    cover 
                                    class="rounded-lg"
                                />
                                <v-btn
                                    icon
                                    size="small"
                                    color="error"
                                    class="position-absolute"
                                    style="top: 8px; right: 8px;"
                                    @click="removeProofPhoto"
                                >
                                    <v-icon>mdi-close</v-icon>
                                </v-btn>
                            </div>
                            
                            <!-- Photo Buttons -->
                            <div v-else class="d-flex gap-2 px-2">
                                <v-btn 
                                    variant="outlined" 
                                    color="primary" 
                                    class="flex-grow-1 rounded-lg"
                                    @click="startCamera"
                                    size="small"
                                    style="font-size: 0.8rem;"
                                >
                                    <v-icon start size="16">mdi-camera</v-icon>
                                    CAPTURE
                                </v-btn>
                                <v-btn 
                                    variant="outlined" 
                                    color="primary" 
                                    class="flex-grow-1 rounded-lg"
                                    @click="triggerFileInput"
                                    size="small"
                                    style="font-size: 0.8rem;"
                                >
                                    <v-icon start size="16">mdi-image</v-icon>
                                    UPLOAD
                                </v-btn>
                                <input 
                                    ref="fileInputRef"
                                    type="file" 
                                    accept="image/*" 
                                    class="d-none"
                                    @change="handleFileUpload"
                                />
                            </div>
                        </div>

                        <!-- Reason Field -->
                        <v-textarea
                            v-model="safeProofReason"
                            label="Why are you safe now? *"
                            variant="outlined"
                            rows="2"
                            density="comfortable"
                            placeholder="e.g., Found help nearby, reached safe area, false alarm..."
                            prepend-inner-icon="mdi-text-box-check"
                            class="mt-1"
                        />
                    </v-card-text>
                    <v-card-actions class="pa-3 pt-0">
                        <div class="d-flex flex-row gap-2 w-100">
                            <v-btn
                                variant="text"
                                class="flex-grow-1 rounded-lg"
                                :disabled="isSubmittingProof"
                                @click="cancelSafeProof"
                                size="small"
                                style="min-height: 36px;"
                            >
                                <span class="text-overline text-sm-caption font-weight-medium">CANCEL</span>
                            </v-btn>
                            <v-btn
                                color="success"
                                variant="flat"
                                class="flex-grow-1 rounded-lg"
                                :disabled="!safeProofPhoto || !safeProofReason.trim() || isSubmittingProof"
                                :loading="isSubmittingProof"
                                @click="confirmSafeWithProof"
                                size="small"
                                style="min-height: 36px;"
                            >
                                <v-icon start size="14">mdi-check-circle</v-icon>
                                <span class="text-overline text-sm-caption font-weight-medium">CONFIRM</span>
                            </v-btn>
                        </div>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Legacy Safe Reason Dialog (for self-marking as safe without rescuer - kept for compatibility) -->
            <v-dialog v-model="showSafeReasonDialog" max-width="440" persistent>
                <v-card class="rounded-xl">
                    <v-card-text class="pt-6 pb-2">
                        <div class="text-center mb-4">
                            <v-avatar color="success" size="64" class="mb-3">
                                <v-icon size="32" color="white">mdi-shield-check</v-icon>
                            </v-avatar>
                            <h3 class="text-h6 mb-1">Marking Yourself as Safe</h3>
                            <p class="text-grey text-body-2">Please provide a brief reason for marking yourself as safe without rescuer assistance.</p>
                        </div>

                        <v-textarea
                            v-model="safeReason"
                            label="Why are you safe now?"
                            variant="outlined"
                            rows="2"
                            density="comfortable"
                            placeholder="e.g., Found help nearby, reached safe area, false alarm..."
                            prepend-inner-icon="mdi-text-box-check"
                            class="mt-1"
                             
                        />
                    </v-card-text>
                    <v-card-actions class="pa-3 pt-0">
                        <div class="d-flex flex-row gap-2 w-100">
                            <v-btn
                                variant="text"
                                class="flex-grow-1 rounded-lg"
                                @click="cancelSafeReason"
                                size="small"
                                style="min-height: 36px;"
                            >
                                <span class="text-overline text-sm-caption font-weight-medium">Cancel</span>
                            </v-btn>
                            <v-btn
                                color="success"
                                variant="flat"
                                class="flex-grow-1 rounded-lg"
                                :disabled="!safeReason || !safeReason.trim()"
                                @click="confirmSafeWithReason"
                                size="small"
                                style="min-height: 36px;"
                            >
                                <v-icon start size="14">mdi-check-circle</v-icon>
                                <span class="text-overline text-sm-caption font-weight-medium">Confirm Safe</span>
                            </v-btn>
                        </div>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Wait Time Message Banner -->


            <!-- Rescuer Profile Dialog -->
            <v-dialog v-model="showRescuerProfile" max-width="420">
                <v-card class="rounded-xl overflow-hidden" elevation="4">
                    <!-- Header with gradient background -->
                    <div style="background: linear-gradient(135deg, #3674B5 0%, #2E5B94 100%); padding: 16px 20px; color: white;">
                        <div class="d-flex align-center justify-space-between">
                            <div class="d-flex align-center">
                                <v-icon size="22" color="white" class="mr-2">mdi-account-circle</v-icon>
                                <span class="text-h6 font-weight-bold">Rescuer Profile</span>
                            </div>
                            <v-btn icon variant="text" size="small" @click="showRescuerProfile = false">
                                <v-icon size="18" color="rgba(255,255,255,0.8)">mdi-close</v-icon>
                            </v-btn>
                        </div>
                    </div>

                    <v-card-text class="text-center py-6">
                        <v-avatar 
                            size="100" 
                            color="success" 
                            class="mb-4 elevation-3"
                            :style="rescuerProfilePicture ? 'cursor: pointer' : ''"
                            @click="rescuerProfilePicture && openPhotoViewer(rescuerProfilePicture, getRescuerName())"
                        >
                            <v-img v-if="rescuerProfilePicture" :src="rescuerProfilePicture" cover />
                            <span v-else class="text-h4 text-white">{{ getRescuerInitials() }}</span>
                        </v-avatar>
                        <h3 class="text-h5 font-weight-bold mb-2">{{ getRescuerName() }}</h3>
                        <v-chip color="success" variant="tonal" size="small" class="mb-4">
                            <v-icon start size="14">mdi-shield-account</v-icon>
                            Emergency Responder
                        </v-chip>
                        
                        <v-divider class="mb-4" />
                        
                        <!-- Contact Information -->
                        <div class="contact-info">
                            <!-- Phone Contact -->
                            <v-card 
                                v-if="getRescuerContact()" 
                                variant="tonal" 
                                color="primary" 
                                class="mb-3 rounded-lg contact-card"
                                style="cursor: pointer; transition: all 0.2s ease;"
                                @click="$event.target.closest('.contact-card').querySelector('a').click()"
                                hover
                            >
                                <v-card-text class="py-3 px-4">
                                    <div class="d-flex align-center justify-center">
                                        <v-icon color="primary" size="20" class="mr-3">mdi-phone</v-icon>
                                        <div class="text-center">
                                            <div class="text-caption text-grey-darken-1">Phone</div>
                                            <a 
                                                :href="`tel:${getRescuerContact()}`"
                                                class="text-decoration-none text-primary font-weight-medium"
                                                @click.stop=""
                                            >
                                                {{ getRescuerContact() }}
                                            </a>
                                        </div>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Email Contact -->
                            <v-card 
                                v-if="getRescuerEmail()" 
                                variant="tonal" 
                                color="primary" 
                                class="mb-2 rounded-lg contact-card"
                                style="cursor: pointer; transition: all 0.2s ease;"
                                @click="$event.target.closest('.contact-card').querySelector('a').click()"
                                hover
                            >
                                <v-card-text class="py-3 px-4">
                                    <div class="d-flex align-center justify-center">
                                        <v-icon color="primary" size="20" class="mr-3">mdi-email</v-icon>
                                        <div class="text-center">
                                            <div class="text-caption text-grey-darken-1">Email</div>
                                            <a 
                                                :href="`mailto:${getRescuerEmail()}`"
                                                class="text-decoration-none text-primary font-weight-medium"
                                                style="word-break: break-all;"
                                                @click.stop=""
                                            >
                                                {{ getRescuerEmail() }}
                                            </a>
                                        </div>
                                        <v-icon color="primary" size="16" class="ml-3">mdi-email-send</v-icon>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </div>

                        <!-- Helpful tip -->
                        <v-alert type="info" variant="tonal" density="compact" class="mt-3 rounded-lg text-left">
                            <div class="text-caption">
                                <v-icon size="14" class="mr-1">mdi-information</v-icon>
                                Tap contact info to call or email directly
                            </div>
                        </v-alert>
                    </v-card-text>
                </v-card>
            </v-dialog>

            <!-- Cancellation Reason Dialog -->
            <v-dialog v-model="showCancelReasonDialog" max-width="450" persistent>
                <v-card class="rounded-xl">
                    <v-card-text class="pa-6">
                        <div class="text-center mb-4">
                            <v-avatar color="error" size="64" class="mb-3">
                                <v-icon size="32" color="white">mdi-comment-alert</v-icon>
                            </v-avatar>
                            <h3 class="text-h6 mb-1">Reason for Cancellation</h3>
                            <p class="text-grey text-body-2">Please provide a brief reason for cancelling your rescue request. This will be sent to admin for review.</p>
                        </div>

                        <!-- Reason Text Area -->
                        <v-textarea
                            v-model="cancellationReason"
                            label="Cancellation Reason"
                            placeholder="Please explain why you're cancelling the rescue request..."
                            variant="outlined"
                            rows="4"
                            auto-grow
                            max-rows="6"
                            maxlength="500"
                            counter="500"
                            required
                            class="mb-4"
                            color="primary"
                        />

                        <!-- Action Buttons -->
                        <div class="d-flex flex-row gap-2">
                            <v-btn
                                color="error"
                                variant="flat"
                                size="small"
                                rounded="xl"
                                class="flex-grow-1"
                                :loading="isCancelling"
                                :disabled="!cancellationReason.trim()"
                                @click="submitCancellationWithReason"
                                style="color: white; min-height: 36px;"
                            >
                                <v-icon start size="14">mdi-send</v-icon>
                                <span class="text-overline text-sm-caption font-weight-medium">Submit</span>
                            </v-btn>
                            <v-btn
                                variant="outlined"
                                size="small"
                                rounded="xl"
                                class="flex-grow-1"
                                @click="closeCancelReasonDialog"
                                style="min-height: 36px;"
                            >
                                <v-icon start size="14">mdi-arrow-left</v-icon>
                                <span class="text-overline text-sm-caption font-weight-medium">Go Back</span>
                            </v-btn>
                        </div>
                    </v-card-text>
                </v-card>
            </v-dialog>

            <!-- Photo Viewer Dialog -->
            <v-dialog v-model="showPhotoViewer" max-width="500">
                <v-card class="bg-black rounded-xl">
                    <v-card-title class="d-flex align-center justify-space-between text-white">
                        <span>{{ photoViewerName }}</span>
                        <v-btn icon variant="text" color="white" @click="showPhotoViewer = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-card-text class="pa-0 d-flex justify-center align-center" style="min-height: 300px;">
                        <v-img :src="photoViewerUrl" max-height="400" contain class="bg-black" />
                    </v-card-text>
                </v-card>
            </v-dialog>

            <!-- Toast Notification -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">Close</v-btn>
                </template>
            </v-snackbar>
            
            <!-- Popup Notification Alert -->
            <NotificationPopup
                :show="popupAlert.show"
                :title="popupAlert.title"
                :message="popupAlert.message"
                :type="popupAlert.type"
                :icon="popupAlert.icon"
                @close="popupAlert.show = false"
                @click="popupAlert.show = false"
            />

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- RESCUE PROOF PHOTO VIEWER                               -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <v-dialog v-model="showProofPhoto" max-width="500">
                <v-card rounded="xl" class="overflow-hidden">
                    <div style="background: linear-gradient(135deg, #2E7D32 0%, #388E3C 100%); padding: 14px 20px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <v-icon color="white" size="20">mdi-camera-check</v-icon>
                            <span style="color: white; font-weight: 600; font-size: 14px;">Rescue Proof Photo</span>
                        </div>
                        <v-btn icon variant="text" size="small" @click="showProofPhoto = false">
                            <v-icon color="white">mdi-close</v-icon>
                        </v-btn>
                    </div>
                    <v-card-text class="pa-3">
                        <p class="text-caption text-grey mb-2 text-center">
                            <v-icon size="12" class="mr-1">mdi-shield-check</v-icon>
                            Photo taken by rescuer confirming patient is okay
                        </p>
                        <v-img
                            v-if="rescue?.completion_photo"
                            :src="rescue.completion_photo"
                            max-height="400"
                            contain
                            rounded="lg"
                            class="border"
                        />
                        <p v-if="rescue?.completion_notes" class="text-body-2 mt-3 pa-2" style="background: #f5f5f5; border-radius: 8px; border-left: 3px solid #388E3C;">
                            <v-icon size="14" color="grey" class="mr-1">mdi-note-text</v-icon>
                            {{ rescue.completion_notes }}
                        </p>
                    </v-card-text>
                </v-card>
            </v-dialog>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- RESCUE FEEDBACK DIALOG                                  -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <v-dialog v-model="showFeedbackDialog" max-width="420" persistent>
                <v-card class="feedback-card" elevation="4">
                    <!-- Header -->
                    <div class="feedback-header-bar">
                        <div class="feedback-header-content">
                            <v-icon size="22" color="white" class="mr-2">mdi-star-check</v-icon>
                            <div>
                                <h3 class="feedback-header-title">Rate the Assistance Responder Provided</h3>
                                <p class="feedback-header-subtitle">Not the incident itself</p>
                            </div>
                        </div>
                        <v-btn
                            icon
                            variant="text"
                            size="x-small"
                            class="feedback-header-close"
                            @click="showFeedbackDialog = false"
                        >
                            <v-icon size="18" color="rgba(255,255,255,0.8)">mdi-close</v-icon>
                        </v-btn>
                    </div>

                    <div class="feedback-body">
                        <!-- Responder Profile Section -->
                        <div class="feedback-responder-profile">
                            <v-avatar
                                :color="rescuerProfilePicture ? undefined : '#3674B5'"
                                size="56"
                                class="feedback-responder-avatar"
                            >
                                <v-img v-if="rescuerProfilePicture" :src="rescuerProfilePicture" cover />
                                <span v-else class="text-white text-body-1 font-weight-bold">{{ getRescuerInitials() }}</span>
                            </v-avatar>
                            <div class="feedback-responder-info">
                                <p class="feedback-responder-name">{{ getRescuerName() }}</p>
                                <p class="feedback-responder-role">
                                    <v-icon size="13" color="#3674B5" class="mr-1">mdi-shield-account</v-icon>
                                    Emergency Responder
                                </p>
                            </div>
                        </div>

                        <!-- Star Rating Section -->
                        <div class="feedback-question-group text-center">
                            <p class="feedback-question">How would you rate this responder?</p>
                            <div class="feedback-stars">
                                <v-icon
                                    v-for="star in 5"
                                    :key="'star-' + star"
                                    size="40"
                                    :color="star <= feedbackForm.overall_rating ? '#DFA92C' : '#D6D6D6'"
                                    class="feedback-star-icon"
                                    @click="feedbackForm.overall_rating = star"
                                >
                                    {{ star <= feedbackForm.overall_rating ? 'mdi-star' : 'mdi-star-outline' }}
                                </v-icon>
                            </div>
                            <p class="feedback-star-label">{{ getRatingLabel(feedbackForm.overall_rating) }}</p>
                        </div>

                        <!-- Quick Feedback Tags -->
                        <div class="feedback-question-group">
                            <p class="feedback-question">What stood out?</p>
                            <div class="feedback-tags-wrap">
                                <v-chip
                                    v-for="tag in feedbackTagOptions"
                                    :key="tag.value"
                                    :variant="feedbackForm.feedback_tags.includes(tag.value) ? 'flat' : 'outlined'"
                                    :color="feedbackForm.feedback_tags.includes(tag.value) ? '#3674B5' : '#78909C'"
                                    :class="{ 'feedback-tag-active': feedbackForm.feedback_tags.includes(tag.value) }"
                                    class="feedback-tag-chip"
                                    size="small"
                                    @click="toggleFeedbackTag(tag.value)"
                                >
                                    <v-icon start size="15">{{ tag.icon }}</v-icon>
                                    {{ tag.label }}
                                </v-chip>
                            </div>
                            
                            <!-- Custom "Others" text field -->
                            <v-expand-transition>
                                <v-text-field
                                    v-if="feedbackForm.feedback_tags.includes('Others')"
                                    v-model="feedbackForm.other_feedback"
                                    label="Please specify..."
                                    variant="outlined"
                                    density="compact"
                                    placeholder="What else stood out about this rescuer?"
                                    maxlength="100"
                                    counter="100"
                                    class="mt-3"
                                    hide-details="auto"
                                />
                            </v-expand-transition>
                        </div>

                        <!-- Do you feel safe now? Toggle -->
                        <div class="feedback-safe-toggle">
                            <div class="feedback-safe-toggle-content">
                                <v-icon size="20" :color="feedbackForm.feeling_safe_now ? '#2E7D32' : '#78909C'" class="mr-2">
                                    {{ feedbackForm.feeling_safe_now ? 'mdi-shield-check' : 'mdi-shield-outline' }}
                                </v-icon>
                                <span class="feedback-safe-label">Do you feel safe now?</span>
                            </div>
                            <v-switch
                                v-model="feedbackForm.feeling_safe_now"
                                color="#2E7D32"
                                hide-details
                                density="compact"
                                inset
                            />
                        </div>

                        <!-- Submit Button -->
                        <v-btn
                            block
                            variant="flat"
                            color="#3674B5"
                            size="small"
                            rounded="lg"
                            :loading="submittingFeedback"
                            :disabled="!feedbackForm.overall_rating"
                            class="feedback-submit-btn"
                            @click="submitFeedback"
                            style="min-height: 36px;"
                        >
                            <v-icon start size="14">mdi-send</v-icon>
                            <span class="text-overline text-sm-caption font-weight-medium">Submit Rating</span>
                        </v-btn>
                    </div>
                </v-card>
            </v-dialog>
        </v-main>
        
        <!-- Bottom Navigation for Mobile -->
        <UserBottomNav :notification-count="1" :message-count="unreadCount" />


    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    getRescueRequestByCode,
    getRescueRequestById,
    markRescueSafe,
    cancelRescueRequest,
    cancelSafeApproval,
    withdrawCancelRequest,
    setCancelInProgress,
    clearCancelInProgress,
    setMarkingSafeInProgress,
    clearMarkingSafeInProgress,
    translateRescueRequest,
    updateRescueRequest,
    getBuildingsFullStructure,
    getLocationDetails,
    getProfilePictureUrl,
    submitRescueFeedback,
    checkRescueFeedback,
    sendAdminNotification,
} from '@/Composables/useApi';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserAppBar from '@/Components/Pages/User/Menu/UserAppBar.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';

import NotificationPopup from '@/Components/NotificationPopup.vue';

// Props from Inertia
const props = defineProps({
    code: {
        type: String,
        default: null,
    },
});

// Navigation drawer
const drawer = ref(false);

// Unread messages count for bottom nav with new message callback
const { unreadCount, onNewMessages } = useUnreadMessages();

// Cleanup function for new message callback
let unregisterNewMessages = null;

// State
const rescue = ref(null);
const loading = ref(true);
const error = ref(null);
const showConfirmSafe = ref(false);
const showRescuerProfile = ref(false);
const isMarkingSafe = ref(false);
const isCancelling = ref(false);

// Safe marking validation states
const showSafeReasonDialog = ref(false);
const safeReason = ref('');

// Safe Proof Dialog States (photo + reason)
const showSafeProofDialog = ref(false);
const safeProofPhoto = ref(null);
const safeProofPhotoPreview = ref(null);
const safeProofReason = ref('');
const isCaptureMode = ref(false);
const videoStream = ref(null);
const videoRef = ref(null);
const fileInputRef = ref(null);
const isSubmittingProof = ref(false);

// Slide to confirm states
const isSliding = ref(false);
const slidePosition = ref(0);
const slideProgress = ref(0);
const isSlideComplete = ref(false);
const slideStartX = ref(0);
const maxSlideDistance = ref(0);
let completionTimer = null;

// ─── Hold-to-Cancel State ───
const cancelHoldActive = ref(false);
const showCancelConfirmModal = ref(false);
const showCancelReasonDialog = ref(false);
const cancellationReason = ref('');
let cancelHoldTimer = null;
let cancelHoldStartTime = null;

// ─── Feedback State ───
const showFeedbackDialog = ref(false);
const showProofPhoto = ref(false);
const feedbackSubmitted = ref(false);
const submittingFeedback = ref(false);
const feedbackForm = ref({
    overall_rating: 0,
    liked_most: null,
    comments: '',
    feeling_safe_now: true,
    feedback_tags: [],
    other_feedback: '', // Custom text for "Others" option
});

// Feedback tag options
const feedbackTagOptions = [
    { value: 'Response Time', label: 'Response Time', icon: 'mdi-timer-check-outline' },
    { value: 'Clear Communication', label: 'Clear Communication', icon: 'mdi-message-check-outline' },
    { value: 'Professionalism', label: 'Professionalism', icon: 'mdi-account-tie' },
    { value: 'Accurate Location Tracking', label: 'Accurate Tracking', icon: 'mdi-crosshairs-gps' },
    { value: 'Feeling Safe Throughout', label: 'Felt Safe Throughout', icon: 'mdi-shield-check-outline' },
    { value: 'Others', label: 'Others', icon: 'mdi-plus-circle-outline' },
];

const toggleFeedbackTag = (tag) => {
    const idx = feedbackForm.value.feedback_tags.indexOf(tag);
    if (idx >= 0) {
        feedbackForm.value.feedback_tags.splice(idx, 1);
        // Clear custom text when "Others" is deselected
        if (tag === 'Others') {
            feedbackForm.value.other_feedback = '';
        }
    } else {
        feedbackForm.value.feedback_tags.push(tag);
    }
};

// ─── Cancel state (kept for API) ───
const cancelReason = ref('');
const cancelProofDetails = ref('');
const cancelProofPhoto = ref(null);
const cancelProofPhotoPreview = ref(null);
const isTakingPhoto = ref(false);

// Check if cancellation is allowed (allow cancellation unless rescued/safe/cancelled)
// Also block if cancel approval is pending or urgency is high/critical
const canCancel = computed(() => {
    if (!rescue.value) return false;
    if (rescue.value.cancel_approval_requested && rescue.value.cancel_approval_status === 'pending') return false;
    // Block cancel for HIGH and CRITICAL urgency levels
    if (['high', 'critical'].includes((rescue.value.urgency_level || '').toLowerCase())) return false;
    return !['rescued', 'safe', 'cancelled', 'completed'].includes(rescue.value.status);
});

// Check if cancel is blocked due to urgency level
const isCancelBlockedByUrgency = computed(() => {
    if (!rescue.value) return false;
    return ['high', 'critical'].includes((rescue.value.urgency_level || '').toLowerCase());
});

// Check if urgency level is critical (requires admin approval to mark safe)
const isCriticalUrgency = computed(() => {
    if (!rescue.value) return false;
    return (rescue.value.urgency_level || '').toLowerCase() === 'critical';
});

// Check if urgency is High or Critical (requires rescuer to accept before user can slide)
const isHighOrCriticalUrgency = computed(() => {
    if (!rescue.value) return false;
    return ['high', 'critical'].includes((rescue.value.urgency_level || '').toLowerCase());
});

// Slide is locked for High/Critical urgency until a rescuer has accepted
const isSlideLockedByUrgency = computed(() => {
    if (!rescue.value) return false;
    if (!isHighOrCriticalUrgency.value) return false;
    // Unlock once a rescuer has been assigned (accepted the request)
    if (rescue.value.assigned_rescuer || rescue.value.rescuer) return false;
    return true;
});

// Whether a rescuer is currently assigned
const hasRescuerAssigned = computed(() => {
    return !!(rescue.value?.assigned_rescuer || rescue.value?.rescuer);
});
const isTranslating = ref(false);
const locationDetails = ref({
    buildingName: '',
    floorName: '',
    roomName: '',
});

// Update Details State
const showUpdateForm = ref(false);
const isUpdating = ref(false);
const buildings = ref([]);
const updateForm = ref({
    building_id: null,
    floor_id: null,
    room_id: null,
    description: '',
    mobility_status: '',
    urgency_level: '',
    injuries: [],
});

const needsLocationUpdate = computed(() => {
    return rescue.value && (!rescue.value.building_id || !rescue.value.floor_id || !rescue.value.room_id);
});

const needsDetailsUpdate = computed(() => {
    return rescue.value && !rescue.value.description && !rescue.value.urgency_level && !rescue.value.injuries;
});

const selectedUpdateBuilding = computed(() => {
    return buildings.value.find(b => b.id === updateForm.value.building_id) || null;
});

const updateFloors = computed(() => {
    return selectedUpdateBuilding.value?.floors || [];
});

const selectedUpdateFloor = computed(() => {
    return updateFloors.value.find(f => f.id === updateForm.value.floor_id) || null;
});

const updateRooms = computed(() => {
    return selectedUpdateFloor.value?.rooms || [];
});

const mobilityOptions = [
    { title: 'Can walk normally', value: 'normal' },
    { title: 'Limited mobility', value: 'limited' },
    { title: 'Cannot move / Immobile', value: 'immobile' },
    { title: 'Wheelchair or assistance device', value: 'wheelchair' },
    { title: 'Unknown', value: 'unknown' },
];
const urgencyOptions = [
    { title: 'Low - Not life threatening', value: 'low' },
    { title: 'Medium - Needs attention soon', value: 'medium' },
    { title: 'High - Urgent attention needed', value: 'high' },
    { title: 'Critical - Life threatening', value: 'critical' },
];

const injuryOptions = [
    { title: 'None', value: 'none' },
    { title: 'Bleeding / Cut', value: 'bleeding' },
    { title: 'Fracture / Broken Bone', value: 'fracture' },
    { title: 'Burn', value: 'burn' },
    { title: 'Head Injury', value: 'head' },
    { title: 'Breathing Difficulty', value: 'breathing' },
    { title: 'Unconscious', value: 'unconscious' },
    { title: 'Chest Pain', value: 'chest_pain' },
    { title: 'Seizure', value: 'seizure' },
    { title: 'Allergic Reaction', value: 'allergic' },
    { title: 'Other', value: 'other' },
];

const loadBuildings = async () => {
    try {
        buildings.value = await getBuildingsFullStructure();
    } catch (err) {
        console.error('Failed to load buildings:', err);
    }
};

const handleUpdateDetails = async () => {
    isUpdating.value = true;
    try {
        const payload = {};
        if (updateForm.value.building_id) payload.building_id = updateForm.value.building_id;
        if (updateForm.value.floor_id) payload.floor_id = updateForm.value.floor_id;
        if (updateForm.value.room_id) payload.room_id = updateForm.value.room_id;
        if (updateForm.value.description) payload.description = updateForm.value.description;
        if (updateForm.value.mobility_status) payload.mobility_status = updateForm.value.mobility_status;
        if (updateForm.value.urgency_level) payload.urgency_level = updateForm.value.urgency_level;
        if (updateForm.value.injuries && updateForm.value.injuries.length > 0) payload.injuries = updateForm.value.injuries.join(',');

        const result = await updateRescueRequest(rescue.value.id, payload);
        if (result.success && result.data) {
            rescue.value = result.data;
            // Update location details display
            if (result.data.building_id && result.data.floor_id && result.data.room_id) {
                await fetchLocationDetails(result.data.building_id, result.data.floor_id, result.data.room_id);
            }
            showUpdateForm.value = false;
            toastMessage.value = 'Details updated successfully!';
            toastColor.value = 'success';
            showToast.value = true;
        }
    } catch (err) {
        console.error('Update failed:', err);
        toastMessage.value = 'Failed to update details. Please try again.';
        toastColor.value = 'error';
        showToast.value = true;
    } finally {
        isUpdating.value = false;
    }
};

// Photo Viewer State
const showPhotoViewer = ref(false);
const photoViewerUrl = ref('');
const photoViewerName = ref('');

const openPhotoViewer = (url, name) => {
    photoViewerUrl.value = url;
    photoViewerName.value = name || 'Profile Photo';
    showPhotoViewer.value = true;
};

// Computed property for rescuer's profile picture
const rescuerProfilePicture = computed(() => {
    // assigned_rescuer can be just an ID number — only use objects
    const rescuerObj = rescue.value?.rescuer;
    const assignedObj = typeof rescue.value?.assigned_rescuer === 'object' ? rescue.value.assigned_rescuer : null;
    const rescuer = rescuerObj || assignedObj;
    if (!rescuer) return null;
    
    // Check multiple possible field names for profile picture
    const picturePath = rescuer?.profile_picture || 
                       rescuer?.avatar || 
                       rescuer?.profile_image || 
                       rescuer?.photo ||
                       rescuer?.user?.profile_picture ||
                       rescuer?.user?.avatar ||
                       rescuer?.user?.profile_image ||
                       rescuer?.user?.photo;
    
    if (!picturePath) return null;
    if (picturePath.startsWith('http') || picturePath.startsWith('data:')) return picturePath;
    return getProfilePictureUrl(picturePath);
});

// Helper functions for rescuer info
// Helper to get rescuer object safely (assigned_rescuer may be just an ID)
const getRescuerObject = () => {
    const rescuerObj = rescue.value?.rescuer;
    const assignedObj = typeof rescue.value?.assigned_rescuer === 'object' ? rescue.value.assigned_rescuer : null;
    return rescuerObj || assignedObj || null;
};

const getRescuerInitials = () => {
    const rescuer = getRescuerObject();
    
    if (!rescuer) return 'R';
    
    // Check direct properties
    let firstName = rescuer?.first_name || '';
    let lastName = rescuer?.last_name || '';
    
    // Check nested user properties if direct ones don't exist
    if (!firstName && !lastName && rescuer.user) {
        firstName = rescuer.user.first_name || '';
        lastName = rescuer.user.last_name || '';
    }
    
    // Check name field and split it
    if (!firstName && !lastName && rescuer.name) {
        const nameParts = rescuer.name.split(' ');
        firstName = nameParts[0] || '';
        lastName = nameParts[nameParts.length - 1] || '';
    }
    
    if (firstName && lastName) {
        return `${firstName[0]}${lastName[0]}`.toUpperCase();
    }
    if (firstName) return firstName.substring(0, 2).toUpperCase();
    return 'R';
};

const getRescuerContact = () => {
    const rescuer = getRescuerObject();
    return rescuer?.phone || rescuer?.contact_number || null;
};

const getRescuerEmail = () => {
    const rescuer = getRescuerObject();
    return rescuer?.email || null;
};

// Rescuer status helpers
const getRescuerStatusColor = () => {
    const rescuer = getRescuerObject();
    const status = rescuer?.status;
    if (status === 'active') return 'success';
    if (status === 'inactive') return 'grey';
    if (status === 'busy') return 'warning';
    return 'info';
};

const getRescuerStatusIcon = () => {
    const rescuer = getRescuerObject();
    const status = rescuer?.status;
    if (status === 'active') return 'mdi-check-circle';
    if (status === 'inactive') return 'mdi-minus-circle';
    if (status === 'busy') return 'mdi-clock';
    return 'mdi-account';
};

const getRescuerStatusText = () => {
    const rescueStatus = rescue.value?.status;
    // Use rescue request status to show contextual rescuer status
    if (rescueStatus === 'in_progress' || rescueStatus === 'en_route') return 'On Rescue Duty';
    if (rescueStatus === 'on_scene') return 'On Scene';
    if (rescueStatus === 'rescued' || rescueStatus === 'safe') return 'Rescue Completed';
    
    const rescuer = getRescuerObject();
    const status = rescuer?.status;
    if (status === 'active') return 'Active';
    if (status === 'inactive') return 'Inactive';
    if (status === 'busy') return 'Busy';
    return 'Available';
};

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Notification Alert
const { playNotificationSound, vibrate, notify } = useNotificationAlert();

// Notification Banner State
const notificationBanner = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});

const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'info',
    icon: 'mdi-information',
});

// Track previous status for change detection
const previousStatus = ref(null);

// Show notification with sound and vibration
const showNotification = (options) => {
    const { title, message, type, icon, sound, vibratePattern } = options;
    
    // Play sound
    if (sound) {
        playNotificationSound(sound);
    }
    
    // Vibrate
    if (vibratePattern) {
        vibrate(vibratePattern);
    }
    
    // Show popup alert (single notification only)
    popupAlert.value = {
        show: true,
        title,
        message,
        type,
        icon,
    };
    
    // Show browser notification
    notify({
        title,
        body: message,
        icon: '/images/logo.png'
    });
    
    // Auto-hide popup after 5 seconds
    setTimeout(() => {
        popupAlert.value.show = false;
    }, 5000);
};

// Handle new message notifications
const handleNewMessages = (newCount, totalCount) => {
    showNotification({
        title: '💬 New Message',
        message: `You have ${newCount} new message${newCount > 1 ? 's' : ''} from your rescuer`,
        type: 'info',
        icon: 'mdi-message-text',
        sound: 'message',
        vibratePattern: [100, 50, 100]
    });
};
// Trigger notification on status change
const triggerStatusNotification = (oldStatus, newStatus) => {
    let notificationData = null;

    switch (newStatus) {
        case 'pending':
            // Rescuer unassigned / valid cancel — request is back in queue
            if (oldStatus === 'assigned' || oldStatus === 'in_progress' || oldStatus === 'en_route') {
                notificationData = {
                    title: '⚠️ Rescuer Unassigned',
                    message: rescue.value?.cancellation_reason
                        ? `Your rescuer has been unassigned. Reason: ${rescue.value.cancellation_reason}. Your request is back in the queue for another rescuer.`
                        : 'Your rescuer has been unassigned. Your request is back in the queue for another rescuer.',
                    type: 'warning',
                    icon: 'mdi-account-remove',
                    sound: 'notification',
                    vibratePattern: 'urgent'
                };
            }
            break;
        case 'accepted':
        case 'assigned':
            notificationData = {
                title: '🚨 Rescuer Assigned!',
                message: 'A rescuer has been assigned to help you. Help is on the way!',
                type: 'success',
                icon: 'mdi-account-check',
                sound: 'notification',
                vibratePattern: 'urgent'
            };
            break;
        case 'in_progress':
        case 'en_route':
            notificationData = {
                title: '🏃 Rescuer En Route!',
                message: 'The rescuer is now on their way to your location. Stay calm.',
                type: 'info',
                icon: 'mdi-run-fast',
                sound: 'notification',
                vibratePattern: 'standard'
            };
            break;
        case 'on_scene':
            notificationData = {
                title: '📍 Rescuer Arrived!',
                message: 'The rescuer has arrived at your location. Look for them nearby.',
                type: 'success',
                icon: 'mdi-map-marker-check',
                sound: 'success',
                vibratePattern: 'urgent'
            };
            break;
        case 'rescued':
        case 'safe':
        case 'completed':
            notificationData = {
                title: '✅ Rescue Complete!',
                message: 'You have been marked as safe. Thank you for using PinPointMe.',
                type: 'success',
                icon: 'mdi-check-circle',
                sound: 'success',
                vibratePattern: 'success'
            };
            break;
        case 'cancelled':
            notificationData = {
                title: '❌ Request Cancelled',
                message: 'Your rescue request has been cancelled.',
                type: 'warning',
                icon: 'mdi-close-circle',
                sound: 'notification',
                vibratePattern: 'standard'
            };
            break;
    }

    if (notificationData) {
        showNotification(notificationData);
    }
};

// Get rescue code from URL or localStorage
const rescueCode = ref('');
const rescueId = ref('');

// Store poll interval for cleanup
let pollInterval = null;

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

onMounted(async () => {
    // Get from props (Inertia) first, then localStorage
    rescueCode.value = props.code || localStorage.getItem('lastRescueCode') || '';
    rescueId.value = localStorage.getItem('lastRescueRequestId') || '';

    await fetchRescueData();

    // Register new message callback
    unregisterNewMessages = onNewMessages(handleNewMessages);

    // Set up polling for status updates
    pollInterval = setInterval(async () => {
        // Only poll if we have a valid rescue and it's in an active state
        if (rescue.value && !['rescued', 'safe', 'cancelled', 'completed'].includes(rescue.value.status)) {
            // Also check if polling interval still exists (might be cleared by 404 handler)
            if (pollInterval) {
                try {
                    await fetchRescueData(true);
                } catch (err) {
                    console.warn('Polling error:', err);
                    // If it's a 404, the fetchRescueData function will handle cleanup and stop polling
                    // For other errors, we'll keep retrying
                    if (err.status === 404) {
                        console.log('Polling stopped due to rescue request not found');
                        return;
                    }
                }
            }
        } else {
            // Stop polling if rescue is in a final state  
            if (rescue.value && ['rescued', 'safe', 'cancelled', 'completed'].includes(rescue.value.status)) {
                console.log('Stopping polling - rescue in final state:', rescue.value.status);
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }
            }
        }
    }, 3000); // Poll every 3 seconds for real-time rescue updates
});

// Cleanup on unmount
onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
    if (unregisterNewMessages) {
        unregisterNewMessages();
    }
    if (completionTimer) {
        clearTimeout(completionTimer);
    }
    // Remove slide event listeners
    document.removeEventListener('mousemove', handleSlide);
    document.removeEventListener('mouseup', endSlide);
    document.removeEventListener('touchmove', handleSlide);
    document.removeEventListener('touchend', endSlide);
    document.removeEventListener('visibilitychange', handleVisibilityChange);

    // Clear any stuck considering states on unmount
    if (rescue.value?.id) {
        clearCancelInProgress(rescue.value.id).catch(() => {});
        clearMarkingSafeInProgress(rescue.value.id).catch(() => {});
    }
});

// Watch hold-active to set/clear cancel-in-progress status for rescuer awareness
watch(cancelHoldActive, async (newValue) => {
    if (!rescue.value) return;
    try {
        if (newValue) {
            await setCancelInProgress(rescue.value.id);
            console.log('Cancel-in-progress status set (hold started)');
        } else {
            // Clear cancel-in-progress immediately when user releases the hold button
            await clearCancelInProgress(rescue.value.id);
            console.log('Cancel-in-progress status cleared (hold released)');
        }
    } catch (err) {
        console.warn('Failed to update cancel-in-progress status:', err);
    }
});

// Safety net: clear considering states when page visibility changes or app goes to background
const handleVisibilityChange = () => {
    if (document.hidden && rescue.value?.id) {
        // Page hidden (app backgrounded, tab switched, etc.) — clear any active considering states
        if (cancelHoldActive.value) {
            endCancelHold();
        }
        if (isSliding.value) {
            resetSlide();
            clearMarkingSafeInProgress(rescue.value.id).catch(() => {});
        }
    }
};
document.addEventListener('visibilitychange', handleVisibilityChange);

const fetchRescueData = async (silent = false) => {
    if (!silent) loading.value = true;
    error.value = null;

    try {
        let data;
        if (rescueCode.value) {
            data = await getRescueRequestByCode(rescueCode.value);
        } else if (rescueId.value) {
            data = await getRescueRequestById(rescueId.value);
        } else {
            throw new Error('No rescue code or ID provided');
        }

        // Handle both wrapped and direct response formats
        const newRescueData = data.data || data;
        
        // Detect status change and trigger notification
        const oldStatus = rescue.value?.status;
        const newStatus = newRescueData.status;
        
        if (oldStatus && oldStatus !== newStatus) {
            console.log('Status changed:', oldStatus, '->', newStatus);
            triggerStatusNotification(oldStatus, newStatus);
        }
        
        // Detect safe approval status changes
        const oldApprovalStatus = rescue.value?.safe_approval_status;
        const newApprovalStatus = newRescueData.safe_approval_status;
        
        if (oldApprovalStatus === 'pending' && newApprovalStatus === 'approved') {
            // Approval granted - the status should now be 'safe'
            showNotification({
                title: '✅ Safe Approved!',
                message: 'The rescuer has confirmed you are safe. Stay safe!',
                type: 'success',
                icon: 'mdi-shield-check',
                sound: 'success',
                vibratePattern: 'success'
            });
            
            // Clear localStorage
            localStorage.removeItem('lastRescueCode');
            localStorage.removeItem('lastRescueRequestId');
            localStorage.removeItem('emergency_form_data');
            localStorage.removeItem('location_selection_data');
            
            // Redirect to scanner after a short delay
            setTimeout(() => {
                router.visit('/user/scanner');
            }, 2500);
        } else if (oldApprovalStatus === 'pending' && newApprovalStatus === 'denied') {
            // Approval denied
            showNotification({
                title: '⚠️ Safe Request Denied',
                message: newRescueData.safe_approval_reason || 'The rescuer believes you still need assistance. They are on their way.',
                type: 'warning',
                icon: 'mdi-alert-circle',
                sound: 'warning',
                vibratePattern: 'warning'
            });
        }
        
        // Detect cancel approval status changes
        const oldCancelApprovalStatus = rescue.value?.cancel_approval_status;
        const newCancelApprovalStatus = newRescueData.cancel_approval_status;
        
        if (oldCancelApprovalStatus === 'pending' && newCancelApprovalStatus === 'approved') {
            // Cancel approved - the status should now be 'cancelled'
            showNotification({
                title: '✅ Cancellation Approved!',
                message: 'The rescuer has approved your cancellation. Your request has been cancelled.',
                type: 'success',
                icon: 'mdi-check-circle',
                sound: 'success',
                vibratePattern: 'success'
            });
            
            // Clear localStorage
            localStorage.removeItem('lastRescueCode');
            localStorage.removeItem('lastRescueRequestId');
            localStorage.removeItem('emergency_form_data');
            localStorage.removeItem('location_selection_data');
            
            // Redirect after delay
            setTimeout(() => {
                router.visit('/user/scanner');
            }, 2500);
        } else if (oldCancelApprovalStatus === 'pending' && newCancelApprovalStatus === 'denied') {
            // Cancel denied
            showNotification({
                title: '⚠️ Cancellation Denied',
                message: newRescueData.cancel_approval_reason || 'The rescuer believes you still need assistance. Use chat to communicate.',
                type: 'warning',
                icon: 'mdi-alert-circle',
                sound: 'warning',
                vibratePattern: 'warning'
            });
        }
        
        // Initialize previous status on first load
        if (!previousStatus.value && newStatus) {
            previousStatus.value = newStatus;
        }
        
        rescue.value = newRescueData;
        previousStatus.value = newStatus;
        console.log('Current rescue status:', rescue.value.status); // Debug log

        // Check if feedback was already submitted for completed rescues
        // Auto-popup feedback dialog when rescue is completed
        if (['rescued', 'safe'].includes(newStatus) && !feedbackSubmitted.value) {
            const existingCheck = await checkRescueFeedback(rescue.value.id).catch(() => null);
            if (existingCheck?.success && existingCheck.has_feedback) {
                feedbackSubmitted.value = true;
            } else if (!showFeedbackDialog.value) {
                // Auto-popup the feedback dialog after a brief delay
                setTimeout(() => {
                    if (!feedbackSubmitted.value && !showFeedbackDialog.value) {
                        showFeedbackDialog.value = true;
                    }
                }, 1500);
            }
        }

        // Check if location is already in the rescue data
        if (rescue.value.building && rescue.value.floor && rescue.value.room) {
            locationDetails.value = {
                buildingName: rescue.value.building.name,
                floorName: rescue.value.floor.floor_name,
                roomName: rescue.value.room.room_name,
            };
        }
        // Fetch location details if not already available
        else if (rescue.value.building_id && rescue.value.floor_id && rescue.value.room_id) {
            await fetchLocationDetails(rescue.value.building_id, rescue.value.floor_id, rescue.value.room_id);
        }
        // No location data available
        else {
            locationDetails.value = {
                buildingName: 'Not provided',
                floorName: 'Not provided',
                roomName: 'Not provided',
            };
        }
    } catch (err) {
        console.error('Failed to fetch rescue data:', err);
        
        // Handle specific case where rescue request was deleted (404 error)
        if (err.status === 404) {
            console.log('Rescue request not found (deleted/cancelled), cleaning up...');
            
            // Clear localStorage to prevent future attempts
            localStorage.removeItem('lastRescueCode');
            localStorage.removeItem('lastRescueRequestId');
            
            // Stop polling since the rescue no longer exists
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
            
            // Show user-friendly notification
            showNotification({
                title: 'Rescue Request Cancelled',
                message: 'Your rescue request has been cancelled or marked as a false report. You will be redirected to the main page.',
                type: 'warning',
                icon: 'mdi-alert-circle',
                sound: true,
                vibratePattern: [200, 100, 200]
            });
            
            // Redirect to scanner after a short delay
            setTimeout(() => {
                router.visit('/user/scanner');
            }, 3000);
            
            return; // Don't set error state, let the notification handle it
        }
        
        if (!silent) {
            error.value = err.message || 'Failed to load rescue request';
        } else {
            // For polling errors, just log but don't show error to user unless it's a 404
            console.warn('Polling fetch failed (will retry):', err.message);
        }
    } finally {
        if (!silent) loading.value = false;
    }
};

const fetchLocationDetails = async (buildingId, floorId, roomId) => {
    try {
        const response = await getLocationDetails(buildingId, floorId, roomId);
        console.log('Location details response:', response);
        
        // Handle both nested and flat response structures
        const data = response.data || response;
        
        locationDetails.value = {
            buildingName: data.building?.name || data.building_name || data.buildingName || 'Unknown Building',
            floorName: data.floor?.floor_name || data.floor_name || data.floorName || 'Unknown Floor',
            roomName: data.room?.room_name || data.room_name || data.roomName || 'Unknown Room',
        };
    } catch (err) {
        console.error('Failed to fetch location details:', err);
        // If location fetch fails, try to get from rescue data or set defaults
        if (rescue.value) {
            locationDetails.value = {
                buildingName: rescue.value.building?.name || 'Building ' + (rescue.value.building_id || '?'),
                floorName: rescue.value.floor?.floor_name || 'Floor ' + (rescue.value.floor_id || '?'),
                roomName: rescue.value.room?.room_name || 'Room ' + (rescue.value.room_id || '?'),
            };
        } else {
            locationDetails.value = {
                buildingName: 'Location unavailable',
                floorName: 'Location unavailable', 
                roomName: 'Location unavailable',
            };
        }
    }
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'warning',
        open: 'info',
        assigned: 'primary',
        accepted: 'primary',
        in_progress: 'secondary',
        en_route: 'secondary',
        on_scene: 'success',
        rescued: 'success',
        safe: 'success',
        completed: 'success',
        cancelled: 'grey',
    };
    return colors[status] || 'grey';
};

const getStatusIcon = (status) => {
    const icons = {
        pending: 'mdi-clock-outline',
        open: 'mdi-alert-circle-outline',
        assigned: 'mdi-account-check',
        accepted: 'mdi-account-check',
        in_progress: 'mdi-run-fast',
        en_route: 'mdi-run-fast',
        on_scene: 'mdi-map-marker-check',
        rescued: 'mdi-check-circle',
        safe: 'mdi-shield-check',
        completed: 'mdi-check-circle',
        cancelled: 'mdi-close-circle',
    };
    return icons[status] || 'mdi-help-circle-outline';
};

const getStatusText = (status) => {
    const texts = {
        pending: 'Your request is pending',
        open: 'Help request is open',
        assigned: 'A rescuer has been assigned',
        accepted: 'Rescuer has accepted your request',
        in_progress: 'Rescue is in progress',
        en_route: 'Rescuer is on the way',
        on_scene: 'Rescuer has arrived',
        rescued: 'You have been rescued',
        safe: 'Marked as safe',
        completed: 'Rescue completed',
        cancelled: 'Request cancelled',
    };
    return texts[status] || 'Unknown status';
};

const getProgressValue = (status) => {
    const progress = {
        pending: 0,
        open: 0,
        assigned: 50,
        accepted: 50,
        in_progress: 50,
        en_route: 50,
        on_scene: 50,
        rescued: 100,
        safe: 100,
        completed: 100,
    };
    return progress[status] || 0;
};

// Get progress bar width percentage
const getProgressWidth = (status) => {
    const widths = {
        pending: '0%',
        open: '0%',
        assigned: '50%',
        accepted: '50%',
        in_progress: '50%',
        en_route: '50%',
        on_scene: '50%',
        rescued: '100%',
        safe: '100%',
        completed: '100%',
    };
    return widths[status] || '0%';
};

// Check if a step is active
const isStepActive = (status, step) => {
    const stepStatuses = {
        pending: ['pending', 'open'],
        in_progress: ['assigned', 'accepted', 'in_progress', 'en_route', 'on_scene'],
        rescued: ['rescued', 'safe', 'completed'],
    };
    return stepStatuses[step]?.includes(status) || false;
};

// Check if a step is completed
const isStepCompleted = (status, step) => {
    const completedOrder = ['pending', 'in_progress', 'rescued'];
    const currentStepIndex = completedOrder.findIndex(s => {
        if (s === 'pending') return ['pending', 'open'].includes(status);
        if (s === 'in_progress') return ['assigned', 'accepted', 'in_progress', 'en_route', 'on_scene'].includes(status);
        if (s === 'rescued') return ['rescued', 'safe', 'completed'].includes(status);
        return false;
    });
    const targetStepIndex = completedOrder.indexOf(step);
    return currentStepIndex > targetStepIndex;
};

const getUrgencyColor = (urgency) => {
    const colors = {
        low: 'success',
        medium: 'warning',
        high: 'orange',
        critical: 'error',
    };
    return colors[urgency] || 'grey';
};

const getUrgencyIcon = (urgency) => {
    const icons = {
        low: 'mdi-speedometer-slow',
        medium: 'mdi-speedometer-medium',
        high: 'mdi-speedometer',
        critical: 'mdi-alert-octagon',
    };
    return icons[urgency] || 'mdi-alert-circle';
};

const getUrgencyIconColor = (urgency) => {
    const colors = {
        low: 'success',
        medium: 'warning',
        high: 'orange',
        critical: 'error',
    };
    return colors[urgency] || 'grey';
};

const getEmergencyTypeIcon = (type) => {
    const icons = {
        'Fire': 'mdi-fire',
        'Medical': 'mdi-medical-bag',
        'Earthquake': 'mdi-earthquake',
        'Flood': 'mdi-waves',
        'Violence': 'mdi-shield-alert',
        'Accident': 'mdi-car-crash',
        'Other': 'mdi-alert-circle',
    };
    return icons[type] || 'mdi-alert-circle';
};

// Display title helpers - map database values to human-readable text
const getUrgencyTitle = (urgencyValue) => {
    const option = urgencyOptions.find(opt => opt.value === urgencyValue);
    return option ? option.title : urgencyValue?.toUpperCase();
};

const getMobilityTitle = (mobilityValue) => {
    const option = mobilityOptions.find(opt => opt.value === mobilityValue);
    return option ? option.title : mobilityValue;
};

const getInjuryTitles = (injuryData) => {
    if (!injuryData) return 'No injuries reported';
    
    // Handle both array (new format) and string (old format)
    let injuryValues = [];
    if (Array.isArray(injuryData)) {
        injuryValues = injuryData;
    } else if (typeof injuryData === 'string') {
        // Handle comma-separated string or single value
        injuryValues = injuryData.includes(',') ? injuryData.split(',').map(s => s.trim()) : [injuryData];
    }
    
    if (injuryValues.length === 0) return 'No injuries reported';
    
    // Map values to titles
    const titles = injuryValues.map(value => {
        const option = injuryOptions.find(opt => opt.value === value);
        return option ? option.title : value.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    });
    
    return titles.join(', ');
};

const getRescuerName = () => {
    const rescuer = getRescuerObject();
    if (rescuer) {
        const directName = `${rescuer.first_name || ''} ${rescuer.last_name || ''}`.trim();
        if (directName) return directName;
        if (rescuer.user) {
            const nestedName = `${rescuer.user.first_name || ''} ${rescuer.user.last_name || ''}`.trim();
            if (nestedName) return nestedName;
        }
        if (rescuer.name) return rescuer.name;
        return 'Rescuer';
    }
    return 'Rescuer Assigned';
};

const getPersonInNeedName = () => {
    // Check if there's a requester (the person who needs help)
    if (rescue.value?.requester) {
        const requester = rescue.value.requester;
        return `${requester.first_name || ''} ${requester.last_name || ''}`.trim() || 'Unknown Person';
    }
    // Fall back to the user data from the rescue request
    if (rescue.value?.first_name || rescue.value?.last_name) {
        return `${rescue.value.first_name || ''} ${rescue.value.last_name || ''}`.trim();
    }
    // Get current user name as last resort
    return getUserName() || 'Unknown Person';
};

const getReporterName = () => {
    if (rescue.value?.first_name || rescue.value?.last_name) {
        return `${rescue.value.first_name || ''} ${rescue.value.last_name || ''}`.trim();
    }
    return null;
};

// ─── Feedback Functions ───
const getRatingLabel = (rating) => {
    const labels = { 0: 'Tap a star to rate', 1: 'Poor', 2: 'Fair', 3: 'Good', 4: 'Very Good', 5: 'Excellent' };
    return labels[rating] || '';
};

const checkExistingFeedback = async () => {
    if (!rescue.value?.id) return;
    try {
        const result = await checkRescueFeedback(rescue.value.id);
        if (result.success && result.has_feedback) {
            feedbackSubmitted.value = true;
        }
    } catch (err) {
        // Silently fail - just means we haven't submitted feedback yet
    }
};

const submitFeedback = async () => {
    if (!feedbackForm.value.overall_rating || !rescue.value?.id) return;
    submittingFeedback.value = true;
    try {
        // Prepare feedback tags with custom "Others" text if specified
        let feedbackTags = [...feedbackForm.value.feedback_tags];
        if (feedbackForm.value.feedback_tags.includes('Others') && feedbackForm.value.other_feedback?.trim()) {
            // Replace "Others" with the custom text
            const othersIndex = feedbackTags.indexOf('Others');
            feedbackTags[othersIndex] = `Others: ${feedbackForm.value.other_feedback.trim()}`;
        }
        
        const payload = {
            overall_rating: feedbackForm.value.overall_rating,
            liked_most: feedbackTags.length > 0 ? feedbackTags[0] : null,
            comments: feedbackForm.value.comments || null,
            feeling_safe_now: feedbackForm.value.feeling_safe_now,
            feedback_tags: feedbackTags.length > 0 ? feedbackTags : null,
        };
        const result = await submitRescueFeedback(rescue.value.id, payload);
        if (result.success) {
            feedbackSubmitted.value = true;
            showFeedbackDialog.value = false;
            toastMessage.value = 'Thank you for your feedback!';
            toastColor.value = 'success';
            showToast.value = true;
        }
    } catch (err) {
        console.error('Feedback submission failed:', err);
        if (err.message?.includes('409')) {
            feedbackSubmitted.value = true;
            showFeedbackDialog.value = false;
            toastMessage.value = 'Feedback already submitted!';
            toastColor.value = 'info';
        } else {
            toastMessage.value = 'Failed to submit feedback. Please try again.';
            toastColor.value = 'error';
        }
        showToast.value = true;
    } finally {
        submittingFeedback.value = false;
    }
};

const getUserName = () => {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    return `${userData.firstName || userData.first_name || ''} ${userData.lastName || userData.last_name || ''}`.trim();
};

const formatRescueDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString([], {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Format approval time as relative time (e.g., "2 minutes ago")
const formatApprovalTime = (dateString) => {
    if (!dateString) return 'just now';
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    }
    if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    }
    return date.toLocaleString([], { hour: '2-digit', minute: '2-digit' });
};

const markAsSafe = async (proofData = null) => {
    isMarkingSafe.value = true;
    try {
        // Prepare request body - supports new format with proof_photo and reason
        let requestBody = {};
        if (proofData && typeof proofData === 'object') {
            // New format: { proof_photo: base64, reason: string }
            requestBody = proofData;
        } else if (proofData && typeof proofData === 'string') {
            // Legacy format: just reason string
            requestBody = { reason: proofData };
        }
        
        const response = await markRescueSafe(rescue.value.id, requestBody);
        
        // Check if approval was requested (rescuer or admin)
        if (response?.approval_requested) {
            // Update local state to show pending approval
            rescue.value.safe_approval_requested = true;
            rescue.value.safe_approval_status = 'pending';
            rescue.value.safe_approval_requested_at = new Date().toISOString();

            const isAdminApproval = response?.admin_approval;
            
            showNotification({
                title: isAdminApproval ? '🛡️ Safe Request Sent to Admin' : '📤 Safe Request Sent',
                message: isAdminApproval 
                    ? 'Your urgency level is Critical. An administrator must verify and approve your safety. Please wait for their response.'
                    : 'Your safe request has been sent to the rescuer. Please wait for their approval.',
                type: isAdminApproval ? 'warning' : 'info',
                icon: isAdminApproval ? 'mdi-shield-alert' : 'mdi-account-clock',
                sound: 'notification',
                vibratePattern: 'standard'
            });
            
            // Reset slide but don't navigate away
            resetSlide();
            return;
        }
        
        // Direct mark as safe (no rescuer assigned or pending status)
        rescue.value.status = 'safe';

        // Send admin notification about self-safe confirmation
        try {
            const notificationData = {
                type: 'rescue_safe_self_confirmed',
                title: 'User Self-Confirmed as Safe',
                message: `Rescue request ${rescue.value.rescue_code || '#' + rescue.value.id} - User has confirmed they are safe.`,
                data: {
                    rescue_id: rescue.value.id,
                    rescue_code: rescue.value.rescue_code,
                    user_name: `${rescue.value.first_name || ''} ${rescue.value.last_name || ''}`.trim() || 'Unknown User',
                    confirmed_at: new Date().toISOString(),
                    location: rescue.value.location || 'Unknown location',
                    proof_provided: !!(proofData?.proof_photo),
                    reason: proofData?.reason || 'No reason provided'
                }
            };

            await sendAdminNotification(notificationData);
            console.log('Admin notification sent for self-safe confirmation');
        } catch (error) {
            console.error('Failed to send admin notification for safe confirmation:', error);
            // Don't throw - marking as safe was already successful
        }

        // Clear emergency form data from localStorage
        localStorage.removeItem('emergency_form_data');
        localStorage.removeItem('location_selection_data');

        // Show notification with sound and vibration
        showNotification({
            title: '✅ Marked as Safe!',
            message: 'You have been marked as safe. Stay safe!',
            type: 'success',
            icon: 'mdi-shield-check',
            sound: 'success',
            vibratePattern: 'success'
        });

        // Clear localStorage
        localStorage.removeItem('lastRescueCode');
        localStorage.removeItem('lastRescueRequestId');

        setTimeout(() => {
            router.visit('/user/scanner');
        }, 2000);
    } catch (err) {
        console.error('Failed to mark as safe:', err);
        
        // Extract error data from API response
        const errorData = err.data || {};
        
        // Check if error is related to pending approval
        if (errorData.approval_pending) {
            showNotification({
                title: '⏳ Approval Pending',
                message: 'A safe approval request is already pending. Please wait for the rescuer to respond.',
                type: 'warning',
                icon: 'mdi-clock-outline',
                sound: 'notification',
                vibratePattern: 'standard'
            });
        } else if (errorData.approval_denied) {
            showNotification({
                title: '❌ Request Denied',
                message: errorData.denial_reason || 'The rescuer denied your safe request. You can try again or use chat.',
                type: 'error',
                icon: 'mdi-close-circle',
                sound: 'warning',
                vibratePattern: 'warning'
            });
        } else {
            toastMessage.value = errorData.message || err.message || 'Failed to update status';
            toastColor.value = 'error';
            showToast.value = true;
        }
        // Reset slide on error
        resetSlide();
    } finally {
        isMarkingSafe.value = false;
    }
};

// Handle cancelling the safe approval request
const handleCancelSafeApproval = async () => {
    try {
        await cancelSafeApproval(rescue.value.id);
        
        // Reset local state
        rescue.value.safe_approval_requested = false;
        rescue.value.safe_approval_status = null;
        rescue.value.safe_approval_requested_at = null;
        
        showNotification({
            title: '🔙 Request Cancelled',
            message: 'Your safe request has been cancelled. The rescue will continue.',
            type: 'info',
            icon: 'mdi-undo',
            sound: 'notification',
            vibratePattern: 'standard'
        });
    } catch (err) {
        console.error('Failed to cancel safe approval:', err);
        toastMessage.value = 'Failed to cancel safe request';
        toastColor.value = 'error';
        showToast.value = true;
    }
};

// Handle safe reason dialog for self-marking as safe
const handleSafeReasonDialog = async () => {
    // Check if there's already a pending safe approval request
    if (rescue.value?.safe_approval_requested && rescue.value?.safe_approval_status === 'pending') {
        // User already has a pending safe approval request
        showNotification({
            title: '⏳ Safe Request Pending',
            message: 'Your safe request is still pending rescuer approval. Please wait for their response.',
            type: 'info',
            icon: 'mdi-clock-outline',
            sound: 'notification',
            vibratePattern: 'standard'
        });
        resetSlide();
        return;
    }

    // Check if the request was recently denied
    if (rescue.value?.safe_approval_status === 'denied') {
        // Reset the denial status so user can make a new request
        rescue.value.safe_approval_status = null;
        rescue.value.safe_approval_reason = null;
    }

    // Proceed with proof collection for new safe request
    // marking_safe_in_progress_at is already set from startSlide
    showSafeProofDialog.value = true;
    resetSlide();
};

// Camera and photo handling functions for safe proof
const startCamera = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'environment' } 
        });
        videoStream.value = stream;
        isCaptureMode.value = true;
        
        // Wait for next tick to ensure videoRef is available
        await new Promise(resolve => setTimeout(resolve, 100));
        if (videoRef.value) {
            videoRef.value.srcObject = stream;
        }
    } catch (err) {
        console.error('Camera access error:', err);
        toastMessage.value = 'Unable to access camera. Please use file upload instead.';
        toastColor.value = 'warning';
        showToast.value = true;
    }
};

const stopCamera = () => {
    if (videoStream.value) {
        videoStream.value.getTracks().forEach(track => track.stop());
        videoStream.value = null;
    }
    isCaptureMode.value = false;
};

const capturePhoto = () => {
    if (!videoRef.value) return;
    
    const video = videoRef.value;
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    // Convert to base64
    const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
    safeProofPhoto.value = dataUrl;
    safeProofPhotoPreview.value = dataUrl;
    
    stopCamera();
};

const triggerFileInput = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        toastMessage.value = 'Please select an image file';
        toastColor.value = 'warning';
        showToast.value = true;
        return;
    }
    
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        toastMessage.value = 'Image must be less than 5MB';
        toastColor.value = 'warning';
        showToast.value = true;
        return;
    }
    
    const reader = new FileReader();
    reader.onload = (e) => {
        safeProofPhoto.value = e.target.result;
        safeProofPhotoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
    
    // Reset input
    event.target.value = '';
};

const removeProofPhoto = () => {
    safeProofPhoto.value = null;
    safeProofPhotoPreview.value = null;
};

const confirmSafeWithProof = async () => {
    if (!safeProofPhoto.value || !safeProofReason.value.trim()) {
        toastMessage.value = 'Please provide both photo and reason';
        toastColor.value = 'warning';
        showToast.value = true;
        return;
    }
    
    isSubmittingProof.value = true;
    try {
        const requestBody = {
            proof_photo: safeProofPhoto.value,
            reason: safeProofReason.value.trim()
        };
        
        await markAsSafe(requestBody);
        
        // Clear proof dialog state
        showSafeProofDialog.value = false;
        safeProofPhoto.value = null;
        safeProofPhotoPreview.value = null;
        safeProofReason.value = '';
    } catch (err) {
        console.error('Failed to submit safe proof:', err);
        // Error notification handled in markAsSafe
    } finally {
        isSubmittingProof.value = false;
    }
};

const cancelSafeProof = async () => {
    // Clear marking safe in progress
    try {
        await clearMarkingSafeInProgress(rescue.value.id);
    } catch (err) {
        console.error('Failed to clear marking safe in progress:', err);
    }
    
    // Stop camera if active
    stopCamera();
    
    // Reset proof dialog state
    showSafeProofDialog.value = false;
    safeProofPhoto.value = null;
    safeProofPhotoPreview.value = null;
    safeProofReason.value = '';
    resetSlide();
};

const confirmSafeWithReason = async () => {
    const reason = safeReason.value.trim();
    if (!reason || reason.length < 10) {
        toastMessage.value = 'Please provide a brief reason (at least 10 characters)';
        toastColor.value = 'warning';
        showToast.value = true;
        return;
    }
    
    showSafeReasonDialog.value = false;
    await markAsSafe(reason);
    safeReason.value = ''; // Clear the reason
};

const cancelSafeReason = () => {
    showSafeReasonDialog.value = false;
    safeReason.value = '';
    resetSlide(); // Reset the slide state
};

const toggleUpdateForm = async () => {
    showUpdateForm.value = !showUpdateForm.value;
    if (showUpdateForm.value && buildings.value.length === 0) {
        await loadBuildings();
    }
};

// ═══════════════════════════════════════════════════════ 
// ACTIVE INTENT VALIDATION — Hold-to-Cancel, Slide-to-Safe
// ═══════════════════════════════════════════════════════

/** Start cancel hold timer */
const startCancelHold = (event) => {
    event?.preventDefault?.();
    
    if (cancelHoldActive.value) return;
    
    cancelHoldActive.value = true;
    cancelHoldStartTime = Date.now();
    
    cancelHoldTimer = setTimeout(() => {
        if (cancelHoldActive.value) {
            submitCancellation();
        }
    }, 3000);
};

/** End cancel hold */
const endCancelHold = () => {
    cancelHoldActive.value = false;
    
    if (cancelHoldTimer) {
        clearTimeout(cancelHoldTimer);
        cancelHoldTimer = null;
    }
    
    cancelHoldStartTime = null;
};

/** Close cancel confirmation modal */
const closeCancelModal = () => {
    showCancelConfirmModal.value = false;
    if (rescue.value) {
        clearCancelInProgress(rescue.value.id).catch(() => {});
    }
};

/** Show cancellation reason modal */
const showCancelReasonModal = () => {
    showCancelConfirmModal.value = false;
    showCancelReasonDialog.value = true;
    cancellationReason.value = '';
};

/** Close cancellation reason dialog */
const closeCancelReasonDialog = () => {
    showCancelReasonDialog.value = false;
    cancellationReason.value = '';
    if (rescue.value) {
        clearCancelInProgress(rescue.value.id).catch(() => {});
    }
};

/** Submit cancellation with user-provided reason */
const submitCancellationWithReason = async () => {
    if (!cancellationReason.value.trim()) {
        showNotification({
            title: 'Reason Required',
            message: 'Please provide a reason for cancellation.',
            type: 'error',
            icon: 'mdi-alert-circle'
        });
        return;
    }

    isCancelling.value = true;
    
    try {
        const result = await cancelRescueRequest(
            rescue.value.id, 
            cancellationReason.value.trim(),
            'User-initiated cancellation with reason provided',
            null
        );
        
        const data = result.data || result;
        
        // Check if this went to rescuer for approval
        if (data.cancel_approval_requested) {
            rescue.value.cancel_approval_requested = true;
            rescue.value.cancel_approval_requested_at = new Date().toISOString();
            rescue.value.cancel_approval_status = 'pending';
            rescue.value.cancel_approval_reason = cancellationReason.value.trim();
            showCancelReasonDialog.value = false;
            
            showNotification({
                title: 'Cancellation Sent to Rescuer',
                message: 'Your rescuer will review the cancellation. Admin has been notified.',
                type: 'info',
                icon: 'mdi-send-check',
                sound: 'notification'
            });

            // Redirect to dashboard after short delay
            setTimeout(() => {
                router.visit('/user/scanner');
            }, 2000);
        } else {
            // Direct cancellation successful
            rescue.value = { ...rescue.value, ...data };
            showCancelReasonDialog.value = false;
            
            showNotification({
                title: 'Request Cancelled',
                message: 'Your rescue request has been cancelled. Admin has been notified.',
                type: 'success',
                icon: 'mdi-check-circle',
                sound: 'success'
            });

            // Clear saved data
            localStorage.removeItem('emergency_form_data');
            localStorage.removeItem('location_selection_data');
            localStorage.removeItem('lastRescueCode');
            localStorage.removeItem('lastRescueRequestId');

            // Redirect to dashboard after short delay
            setTimeout(() => {
                router.visit('/user/scanner');
            }, 2000);
        }
        
    } catch (err) {
        console.error('Failed to cancel rescue:', err);
        showNotification({
            title: 'Cancellation Failed',
            message: err.response?.data?.message || 'Unable to process cancellation. Please try again.',
            type: 'error',
            icon: 'mdi-alert-circle',
            sound: 'error'
        });
    } finally {
        isCancelling.value = false;
    }
};

/** Submit the actual cancellation to the backend */
const submitCancellation = async () => {
    // Redirect to reason modal instead of direct cancellation
    showCancelReasonModal();
};

// Reset simple cancel state
const resetCancelState = () => {
    cancelHoldActive.value = false;
    showCancelConfirmModal.value = false;
    if (cancelHoldTimer) {
        clearTimeout(cancelHoldTimer);
        cancelHoldTimer = null;
    }
    cancelReason.value = '';
    cancelProofDetails.value = '';
    cancelProofPhoto.value = null;
    cancelProofPhotoPreview.value = null;
};

// Photo handling functions
const takePhoto = async () => {
    isTakingPhoto.value = true;
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'environment' } 
        });
        
        // Create a video element to capture the photo
        const video = document.createElement('video');
        video.srcObject = stream;
        video.play();
        
        video.addEventListener('loadedmetadata', () => {
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            setTimeout(() => {
                context.drawImage(video, 0, 0);
                canvas.toBlob((blob) => {
                    const file = new File([blob], 'proof-photo.jpg', { type: 'image/jpeg' });
                    cancelProofPhoto.value = file;
                    cancelProofPhotoPreview.value = URL.createObjectURL(blob);
                    
                    // Stop the stream
                    stream.getTracks().forEach(track => track.stop());
                    isTakingPhoto.value = false;
                }, 'image/jpeg', 0.8);
            }, 1000); // Give time for camera to focus
        });
    } catch (error) {
        console.error('Error accessing camera:', error);
        isTakingPhoto.value = false;
        toastMessage.value = 'Unable to access camera. Please use upload instead.';
        toastColor.value = 'warning';
        showToast.value = true;
    }
};

const handlePhotoUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            toastMessage.value = 'Photo must be smaller than 5MB';
            toastColor.value = 'error';
            showToast.value = true;
            return;
        }
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            toastMessage.value = 'Please select an image file';
            toastColor.value = 'error';
            showToast.value = true;
            return;
        }
        
        cancelProofPhoto.value = file;
        cancelProofPhotoPreview.value = URL.createObjectURL(file);
    }
    // Clear the input for potential re-upload
    event.target.value = '';
};

const removePhoto = () => {
    if (cancelProofPhotoPreview.value) {
        URL.revokeObjectURL(cancelProofPhotoPreview.value);
    }
    cancelProofPhoto.value = null;
    cancelProofPhotoPreview.value = null;
};

/** Send cancellation notification to admin */
const sendCancellationToAdmin = async (reason) => {
    try {
        const notificationData = {
            type: 'rescue_cancelled',
            title: 'Rescue Request Cancelled',
            message: `Rescue request ${rescue.value.rescue_code} has been cancelled by ${`${rescue.value.first_name || ''} ${rescue.value.last_name || ''}`.trim() || 'the user'}.`,
            data: {
                rescue_id: rescue.value.id,
                rescue_code: rescue.value.rescue_code,
                user_name: `${rescue.value.first_name || ''} ${rescue.value.last_name || ''}`.trim() || 'Unknown User',
                cancellation_reason: reason,
                cancelled_at: new Date().toISOString(),
                location: rescue.value.location || 'Unknown location'
            }
        };

        await sendAdminNotification(notificationData);
        console.log('Admin notification sent successfully');
        
    } catch (error) {
        console.error('Failed to send admin notification:', error);
        // Don't throw - cancellation was already successful
    }
};

// Withdraw a pending cancel approval request
const handleWithdrawCancelRequest = async () => {
    try {
        const result = await withdrawCancelRequest(rescue.value.id);
        const data = result.data || result;
        if (data) {
            rescue.value.cancel_approval_requested = false;
            rescue.value.cancel_approval_status = null;
            rescue.value.cancel_approval_requested_at = null;
            rescue.value.cancel_approval_reason = null;
        }
        
        showNotification({
            title: '🔙 Cancel Request Withdrawn',
            message: 'Your cancellation request has been withdrawn. The rescue will continue.',
            type: 'info',
            icon: 'mdi-undo',
            sound: 'notification',
            vibratePattern: 'standard'
        });
    } catch (err) {
        console.error('Failed to withdraw cancel request:', err);
        toastMessage.value = 'Failed to withdraw cancel request';
        toastColor.value = 'error';
        showToast.value = true;
    }
};

const handleTranslate = async () => {
    if (!rescue.value?.id) return;
    isTranslating.value = true;
    try {
        const result = await translateRescueRequest(rescue.value.id);
        if (result.success && result.data) {
            rescue.value = result.data;
        }
        toastMessage.value = 'Translation completed';
        toastColor.value = 'success';
        showToast.value = true;
    } catch (err) {
        console.error('Translation failed:', err);
        toastMessage.value = 'Translation failed. Please try again.';
        toastColor.value = 'error';
        showToast.value = true;
    } finally {
        isTranslating.value = false;
    }
};

const openChat = () => {
    if (rescue.value?.assigned_rescuer || rescue.value?.rescuer_id) {
        // Use rescue-chat route which will get or create conversation
        router.visit(`/user/rescue-chat/${rescue.value.id}`);
    } else {
        toastMessage.value = 'No rescuer assigned yet';
        toastColor.value = 'warning';
        showToast.value = true;
    }
};

const viewMap = () => {
    // Navigate to the map view with the rescue code
    const code = rescue.value?.rescue_code || rescueCode.value;
    if (code) {
        router.visit(`/user/map/${code}`);
    } else {
        router.visit('/user/map');
    }
};

// Slide to confirm functions
const startSlide = async (event) => {
    if (isMarkingSafe.value || isSlideComplete.value) return;
    // Block sliding if locked by urgency level
    if (isSlideLockedByUrgency.value) return;
    
    event.preventDefault();
    isSliding.value = true;
    
    const clientX = event.type === 'touchstart' ? event.touches[0].clientX : event.clientX;
    slideStartX.value = clientX;
    
    // Calculate max slide distance (button width minus thumb width)
    const slideTrack = event.currentTarget.querySelector('.slide-track');
    maxSlideDistance.value = slideTrack.clientWidth - 64; // 64px is thumb width
    
    // Immediately notify backend that user is considering marking safe
    // This blocks rescuers from accepting the request while user is sliding
    if (rescue.value?.id) {
        try {
            await setMarkingSafeInProgress(rescue.value.id);
            console.log('Marking-safe-in-progress set (slide started)');
        } catch (err) {
            console.warn('Failed to set marking safe in progress:', err);
        }
    }
    
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
        // Reset slide if not complete — also clear marking-safe-in-progress
        resetSlide();
        if (rescue.value?.id) {
            clearMarkingSafeInProgress(rescue.value.id).catch(err =>
                console.warn('Failed to clear marking safe in progress:', err)
            );
        }
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
    
    // Complete marking as safe after a short delay
    completionTimer = setTimeout(() => {
        handleSafeReasonDialog();
    }, 800);
};

const resetSlide = () => {
    if (completionTimer) {
        clearTimeout(completionTimer);
        completionTimer = null;
    }
    isSliding.value = false;
    slidePosition.value = 0;
    slideProgress.value = 0;
    isSlideComplete.value = false;
};

const handleGoBack = () => {
    // Navigate back to dashboard (scanner)
    router.visit('/user/scanner');
};
</script>

<style scoped>
/* Header Styling */
.help-page-header {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    padding: 16px;
    padding-top: calc(env(safe-area-inset-top) + 16px);
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Notification Banner */
.notification-banner {
    position: sticky;
    top: 70px;
    z-index: 100;
    animation: slideDown 0.3s ease-out;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.notification-banner.v-alert--type-success {
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%) !important;
    border-left: 4px solid #4CAF50;
}

.notification-banner.v-alert--type-info {
    background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%) !important;
    border-left: 4px solid #2196F3;
}

.notification-banner.v-alert--type-warning {
    background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%) !important;
    border-left: 4px solid #FF9800;
}

.notification-banner.v-alert--type-error {
    background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%) !important;
    border-left: 4px solid #F44336;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 800px;
    margin: 0 auto;
}

.header-title {
    text-align: center;
    flex: 1;
}

.header-title h1 {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.header-title p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.75rem;
    margin: 0;
}

.back-btn, .refresh-btn {
    background: rgba(255, 255, 255, 0.1) !important;
}

/* Status Hero */
.status-hero {
    background: linear-gradient(135deg, #3674B5 0%, #2196F3 100%);
    padding: 24px 16px 36px;
    position: relative;
    overflow: hidden;
}

.status-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.3;
}

.status-hero.status-pending {
    background: linear-gradient(135deg, #FF9800 0%, #FFC107 100%);
}

.status-hero.status-rescued,
.status-hero.status-safe {
    background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
}

.status-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
}

.status-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 12px;
}

.status-avatar {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    width: 88px !important;
    height: 88px !important;
}

.status-avatar .v-icon {
    font-size: 44px !important;
}

.pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 88px;
    height: 88px;
    border-radius: 50%;
    border: 3px solid currentColor;
    animation: pulse 2s ease-out infinite;
}

@keyframes pulse {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.5;
        border-color: rgba(255, 255, 255, 0.5);
    }
    100% {
        transform: translate(-50%, -50%) scale(1.5);
        opacity: 0;
        border-color: rgba(255, 255, 255, 0);
    }
}

.status-title {
    color: white;
    font-size: 1.35rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.status-chip {
    font-weight: 600;
}

/* Progress Steps Container */
.progress-steps-container {
    position: relative;
    margin-top: 24px;
    padding: 0 20px;
    z-index: 1;
}

/* Progress Track */
.progress-track {
    position: absolute;
    top: 18px;
    left: 60px;
    right: 60px;
    height: 4px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.9) 0%, white 100%);
    border-radius: 4px;
    transition: width 0.5s ease-in-out;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

/* Progress Steps */
.progress-steps {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    position: relative;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    flex: 1;
    max-width: 100px;
}

.progress-step span {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    text-align: center;
    transition: all 0.3s ease;
}

.progress-step.active span {
    color: white;
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

.progress-step.completed span {
    color: rgba(255, 255, 255, 0.85);
}

.step-dot {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 3px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.progress-step.active .step-dot {
    background: white;
    border-color: white;
    box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.25), 0 4px 15px rgba(0, 0, 0, 0.2);
    transform: scale(1.1);
    animation: pulse-step 2s infinite;
}

.progress-step.completed .step-dot {
    background: rgba(255, 255, 255, 0.9);
    border-color: rgba(255, 255, 255, 0.9);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.progress-step.active .step-dot .v-icon,
.progress-step.completed .step-dot .v-icon {
    color: #3674B5 !important;
}

@keyframes pulse-step {
    0%, 100% {
        box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.25), 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(255, 255, 255, 0.15), 0 4px 20px rgba(0, 0, 0, 0.25);
    }
}

/* Content Area */
.content-area {
    margin-top: -20px;
    margin-bottom: 60px;
    border-radius: 24px 24px 0 0;
    position: relative;
    z-index: 2;
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 180px); /* Increased space for bottom nav to prevent overlap */
}

/* Enhanced Card Styling */
.location-card {
    border: 2px solid rgba(54, 116, 181, 0.15);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    transition: all 0.3s ease;
}

.location-card:hover {
    box-shadow: 0 6px 20px rgba(54, 116, 181, 0.15) !important;
    transform: translateY(-2px);
}

.rescuer-card {
    border: 2px solid rgba(76, 175, 80, 0.15);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    transition: all 0.3s ease;
}

.rescuer-card:hover {
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.15) !important;
    transform: translateY(-2px);
}

.emergency-card {
    border: 2px solid rgba(244, 67, 54, 0.15);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    transition: all 0.3s ease;
}

.emergency-card:hover {
    box-shadow: 0 6px 20px rgba(244, 67, 54, 0.15) !important;
    transform: translateY(-2px);
}

/* Update Details Card - Information Theme */
.update-details-card {
    border: 2px solid rgba(33, 150, 243, 0.2) !important;
    box-shadow: 0 4px 16px rgba(33, 150, 243, 0.12) !important;
    transition: all 0.3s ease;
    background: white !important;
}

.update-details-card:hover {
    box-shadow: 0 6px 24px rgba(33, 150, 243, 0.18) !important;
    transform: translateY(-3px);
    border-color: rgba(33, 150, 243, 0.3) !important;
}

.update-details-header {
    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%) !important;
    border-radius: 16px 16px 0 0 !important;
    margin: -5px -5px  -5px !important;
    padding: 20px 20px 16px !important;
}

.update-details-header .card-header-text h3 {
    color: white !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.update-details-header .card-header-text p {
    color: rgba(255, 255, 255, 0.9) !important;
}

/* Safe area bottom padding for buttons */
.pb-safe {
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 100px) !important;
}

/* Card Header with Icon */
.card-header-icon {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px 10px;
    background: #d7d7d7;
    border-bottom: 1px solid rgba(224, 224, 224, 0.3);
    margin-bottom: 15px;
}

.card-header-text h3 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--ppm-text-primary, #263238);
    margin: 0;
    letter-spacing: 0.3px;
}

.card-header-text p {
    font-size: 0.8rem;
    color: var(--ppm-text-secondary, #546E7A);
    margin: 2px 0 0 0;
    font-weight: 500;
}

/* Location Details */
.location-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.location-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
    border-radius: 12px;
    border: 1px solid rgba(54, 116, 181, 0.1);
    transition: all 0.2s ease;
}

.location-item:hover {
    background: linear-gradient(135deg, #E9ECEF 0%, #DEE2E6 100%);
    border-color: rgba(54, 116, 181, 0.2);
    transform: translateX(2px);
}

.location-item > div {
    display: flex;
    flex-direction: column;
}

.location-item .label {
    font-size: 0.7rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 700;
}

.location-item .value {
    font-size: 0.95rem;
    font-weight: 600;
    color: #2c3e50;
    margin-top: 2px;
}

/* View Map Button */
.view-map-btn {
    border-radius: 16px !important;
    text-transform: none;
    font-weight: 600 !important;
    height: 56px !important;
    font-size: 1rem !important;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(54, 116, 181, 0.3) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.view-map-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(54, 116, 181, 0.4) !important;
}

.view-map-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(54, 116, 181, 0.3) !important;
}

.view-map-btn .v-icon {
    margin-right: 8px;
}

/* Rescuer Card */
.rescuer-card .rescuer-info {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px;
    border-radius: 16px;
    transition: all 0.3s ease;
 }

 

.rescuer-card .rescuer-info:active {
    transform: scale(0.98);
}

.rescuer-details {
    flex: 1;
}

.rescuer-details h4 {
    font-size: 1.05rem;
    font-weight: 400;
    color: #090a09;
    margin: 0 0 2px;
    letter-spacing: 0.3px;
}

.rescuer-details p {
    font-size: 0.85rem;
    color: #2e7d32;
    margin: 0 0 6px;
    font-weight: 500;
}

/* Self-Safe Info (when no rescuer assigned) */
.rescuer-card .self-safe-info {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px;
    border-radius: 16px;
    border: 2px solid rgba(76, 175, 80, 0.2);
}

.self-safe-details {
    flex: 1;
}

.self-safe-details h4 {
    font-size: 1.05rem;
    font-weight: 700;
    color: #333;
    margin: 0 0 2px;
    letter-spacing: 0.3px;
}

.self-safe-details p {
    font-size: 0.85rem;
    color: #333;
    margin: 0 0 6px;
    font-weight: 500;
}


.status-header {
    background: linear-gradient(135deg, #4CAF50, #66BB6A);
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-content {
    display: flex;
    align-items: center;
}

.status-text {
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    letter-spacing: 0.5px;
}

.status-actions {
    display: flex;
    align-items: center;
}

 

.status-message {
    margin-bottom: 16px;
}

.status-message p {
    color: #333;
    font-size: 0.95rem;
    line-height: 1.5;
    margin: 0;
}

.status-timestamp {
    margin: 16px 0 12px 0;
    color: #333;
    font-size: 0.9rem;
}

.status-note {
    color: #666;
    font-size: 0.85rem;
    margin-top: 8px;
}

.status-note em {
    font-style: italic;
}

/* Emergency Details */
.emergency-details {
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 4px 0;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.detail-item.half {
    flex: 1;
}

.detail-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.detail-label {
    font-size: 0.75rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.detail-value {
    font-size: 0.9rem;
    color: #333;
    margin: 0;
    line-height: 1.5;
}

/* Description specific styling */
.description-item {
    background: linear-gradient(135deg, #F5F7FA 0%, #E8EEF5 100%);
    padding: 16px;
    border-radius: 12px;
    border-left: 4px solid #3674B5;
}

.description-text {
    margin-top: 8px;
}

.description-paragraph {
    margin: 0 0 12px 0;
    line-height: 1.7;
    color: #2c3e50;
    font-size: 0.95rem;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.description-paragraph:last-child {
    margin-bottom: 0;
}

.detail-chip-value {
    margin-top: 4px;
}

.detail-chip-value .v-chip {
    font-weight: 600;
    font-size: 0.85rem;
    padding: 0 12px;
    height: 32px;
}

/* Injuries Detail */
.detail-item:has(.detail-chip-value) {
    background: #F8F9FA;
    padding: 12px;
    border-radius: 10px;
}

/* Completion Notes Box */
.completion-notes-box {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    background: #f8fafb;
    padding: 10px 12px;
    border-radius: 8px;
    border-left: 3px solid #388E3C;
    margin-top: 4px;
}

/* Proof Photo Thumbnail */
.proof-photo-wrap {
    position: relative;
    display: inline-block;
    margin-top: 6px;
    cursor: pointer;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e0e0e0;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.proof-photo-wrap:hover {
    border-color: #388E3C;
    box-shadow: 0 2px 12px rgba(56, 142, 60, 0.2);
}
.proof-photo-thumb {
    width: 100%;
    max-width: 280px;
    height: auto;
    max-height: 180px;
    object-fit: cover;
    display: block;
    border-radius: 10px;
}
.proof-photo-badge {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0,0,0,0.55);
    color: white;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: 600;
}

@media (max-width: 600px) {
    .detail-row {
        flex-direction: column;
        gap: 12px;
    }
    
    .detail-item.half {
        width: 100%;
    }
}

/* Action Buttons */
.action-buttons {
    margin-top: 8px;
    position: relative;
}

.action-btn {
    min-height: 44px;
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.action-buttons .d-flex {
    gap: 8px;
}

/* ═══════════════════════════════════════════════════════ */
/* SIMPLE CANCEL BUTTON - Red Fill with White Text        */
/* ═══════════════════════════════════════════════════════ */
.cancel-hold-btn {
    min-width: 120px !important;
    height: 52px !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 600 !important;
    text-transform: none !important;
}

.cancel-hold-btn:not(.holding) {
    cursor: pointer;
}

.cancel-hold-btn.holding {
    background: #f5f5f5 !important;
    color: rgb(198, 30, 30) !important;
    transform: scale(0.96);
    box-shadow: 0 4px 20px rgba(145, 158, 171, 0.4) !important;
    border: 2px solid #e0e0e0 !important;
}

.cancel-hold-btn.holding .v-btn__content {
    opacity: 1 !important;
    color: black !important;
    font-weight: 700 !important;
    animation: pulse-text 1.2s infinite;
}

.cancel-hold-btn .v-btn__content {
    opacity: 1 !important;
    color: inherit !important;
}

@keyframes pulse-text {
    0%, 100% { 
        opacity: 0.95;
        transform: scale(1);
    }
    50% { 
        opacity: 1;
        transform: scale(1.02);
    }
}

/* ═══════════════════════════════════════════════════════ */
/* SAFETY CHECK MODAL                                     */
/* ═══════════════════════════════════════════════════════ */
.safety-check-card {
    overflow: hidden;
}

.safety-check-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #4CAF50, #66BB6A, #4CAF50);
}

.safety-check-avatar {
    animation: safetyPulse 2s ease-in-out infinite;
}

@keyframes safetyPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4); }
    50% { box-shadow: 0 0 0 12px rgba(76, 175, 80, 0); }
}

.safety-btn {
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: none;
    height: 48px !important;
}

/* No Rescue State */
.no-rescue-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 24px;
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 140px); /* Increased bottom padding to prevent overlap with navigation */
    text-align: center;
}

.no-rescue-state h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    margin: 16px 0 8px;
}

.no-rescue-state p {
    color: var(--ppm-text-muted, #888);
    margin: 0;
}

/* ═══════════════════════════════════════════════════════ */
/* FEEDBACK DIALOG STYLES                                  */
/* ═══════════════════════════════════════════════════════ */
.feedback-card {
    border-radius: 16px !important;
    overflow: hidden;
    position: relative;
}

/* Header Bar */
.feedback-header-bar {
    background: linear-gradient(135deg, #3674B5 0%, #2D5F96 100%);
    padding: 18px 20px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    position: relative;
}

.feedback-header-bar::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #DFA92C 0%, #f0c040 50%, #DFA92C 100%);
}

.feedback-header-content {
    display: flex;
    align-items: flex-start;
}

.feedback-header-title {
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: white;
    margin: 0;
    line-height: 1.3;
}

.feedback-header-subtitle {
    font-family: 'Inter', sans-serif;
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.7);
    margin: 2px 0 0;
}

.feedback-header-close {
    margin: -4px -8px 0 0;
}

/* Body */
.feedback-body {
    padding: 24px 24px 20px;
    background: #fafbfc;
}

/* Responder Profile */
.feedback-responder-profile {
    display: flex;
    align-items: center;
    gap: 14px;
    background: white;
    border: 1px solid #e8ecf0;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 20px;
}

.feedback-responder-avatar {
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(54, 116, 181, 0.15);
    border: 2px solid rgba(54, 116, 181, 0.12);
}

.feedback-responder-info {
    flex: 1;
    min-width: 0;
}

.feedback-responder-name {
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: #13294B;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.feedback-responder-role {
    font-family: 'Inter', sans-serif;
    font-size: 0.75rem;
    color: #78909C;
    margin: 2px 0 0;
    display: flex;
    align-items: center;
}

/* Question groups */
.feedback-question-group {
    margin-bottom: 20px;
}

.feedback-question {
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    color: #13294B;
    margin: 0 0 10px;
}

.feedback-stars {
    display: flex;
    justify-content: center;
    gap: 6px;
}

.feedback-star-icon {
    cursor: pointer;
    transition: transform 0.15s ease, color 0.15s ease;
}

.feedback-star-icon:hover {
    transform: scale(1.18);
}

.feedback-star-label {
    text-align: center;
    font-family: 'Inter', sans-serif;
    font-size: 0.78rem;
    font-weight: 500;
    color: #78909C;
    min-height: 20px;
    margin-top: 6px;
}

/* Feedback Tags */
.feedback-tags-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.feedback-tag-chip {
    font-family: 'Inter', sans-serif;
    font-size: 0.78rem !important;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border-radius: 20px !important;
}

.feedback-tag-chip:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(54, 116, 181, 0.15);
}

.feedback-tag-active {
    color: white !important;
    box-shadow: 0 2px 8px rgba(54, 116, 181, 0.25);
}

/* Safe Toggle */
.feedback-safe-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: white;
    border: 1px solid #e8ecf0;
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 20px;
}

.feedback-safe-toggle-content {
    display: flex;
    align-items: center;
}

.feedback-safe-label {
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    color: #13294B;
}

/* Submit Button */
.feedback-submit-btn {
    margin-top: 4px;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 0.92rem;
    letter-spacing: 0.02em;
    text-transform: none;
    height: 46px !important;
    box-shadow: 0 4px 14px rgba(54, 116, 181, 0.25);
    transition: box-shadow 0.2s, transform 0.1s;
}

.feedback-submit-btn:hover {
    box-shadow: 0 6px 20px rgba(54, 116, 181, 0.35);
    transform: translateY(-1px);
}

.feedback-submit-btn:active {
    transform: translateY(0);
}

/* Responsive */
@media (max-width: 400px) {
    .feedback-body {
        padding: 18px 16px 16px;
    }

    .feedback-header-bar {
        padding: 14px 16px;
    }

    .feedback-header-title {
        font-size: 0.88rem;
    }

    .feedback-tag-chip {
        font-size: 0.72rem !important;
    }

    .feedback-stars .feedback-star-icon {
        font-size: 34px !important;
    }
}

/* Mobile-responsive layout */
@media (max-width: 1024px) {
    .v-main :deep(.v-container) {
        padding-bottom: 0 !important;
    }
    
    .content-area {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 180px) !important;
    }
}

@media (max-width: 600px) {
    .content-area {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 180px) !important;
    }
}

/* Action Container (matching rescuer layout) */
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
    margin-top: 8px;
    margin-bottom: 100px;
}

.action-container:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

/* Approval Pending Container */
.approval-pending-container {
    background: linear-gradient(135deg, rgba(33, 150, 243, 0.05) 0%, rgba(33, 150, 243, 0.1) 100%);
    border: 2px solid rgba(33, 150, 243, 0.3);
}

.approval-pending-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 24px 16px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.1);
}

.cancel-approval-card {
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.15);
}

/* Cancel Dialog Step Indicator — REMOVED (replaced by Active Intent Validation) */

/* Photo upload area */
.photo-upload-area {
    border: 2px dashed #e0e0e0;
    border-radius: 8px;
    padding: 16px;
    text-align: center;
    background: #fafafa;
}

/* Enhanced Photo Proof Card */
.photo-proof-card {
    transition: all 0.3s ease;
    border: 2px solid transparent !important;
    position: relative;
    overflow: hidden;
}

.photo-proof-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #4CAF50, #45a049);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.photo-proof-card.v-card--variant-tonal::before {
    opacity: 1;
}

.photo-proof-card .v-card-title {
    background: rgba(255, 255, 255, 0.02);
}

.photo-proof-card.v-card--variant-tonal .v-card-title {
    background: rgba(76, 175, 80, 0.08);
}

.photo-preview {
    position: relative;
}

.photo-preview .v-img {
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

/* Old cancel step dots/lines — REMOVED (replaced by Active Intent Validation) */

.pulse-animation {
    animation: pulse-scale 2s ease-in-out infinite;
}

@keyframes pulse-scale {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
}

.approval-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    margin: 16px 0 8px 0;
}

.approval-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.5;
    max-width: 280px;
    margin: 0;
}

.approval-timestamp {
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 12px;
    padding: 4px 12px;
    background: #f8fafc;
    border-radius: 20px;
}

/* Wait Banner Styles */
.wait-banner {
    position: fixed;
    top: 80px;
    left: 16px;
    right: 16px;
    z-index: 1000;
    animation: slideDownBanner 0.3s ease-out;
}

@keyframes slideDownBanner {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.wait-alert {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    cursor: pointer;
}

.wait-alert:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2) !important;
}

/* Primary Action */
.primary-action {
    width: 100%;
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
    margin: 4px;
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
    color: rgb(254, 3, 3) !important;
}

.cancel-btn:hover {
    color: white !important;
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

/* Locked slide state */
.slide-locked {
    cursor: not-allowed !important;
    pointer-events: none;
    opacity: 0.7;
}

.slide-track-locked {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
    border-color: #f59e0b !important;
}

.slide-thumb-locked {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4) !important;
    position: absolute;
    top: 50%;
    left: 4px;
    transform: translateY(-50%);
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.slide-locked-text {
    color: #92400e !important;
    font-weight: 600;
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
    position: relative;
    z-index: 1;
    pointer-events: none;
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
    transition: color 0.3s ease;
}

.slide-thumb.slide-active ~ .slide-text .slide-instruction {
    color: rgb(12, 12, 12);
}

.slide-success {
    font-size: 0.9rem;
    font-weight: 700;
    color: white;
    letter-spacing: 0.3px;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.1);
}

/* Touch device optimizations */
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
}

/* Small mobile screens */
@media (max-height: 700px) {
    .status-hero {
        padding: 20px 16px 32px;
    }
    
    .status-avatar {
        width: 80px !important;
        height: 80px !important;
    }
    
    .status-avatar .v-icon {
        font-size: 40px !important;
    }
    
    .status-title {
        font-size: 1.25rem;
    }
    
    .progress-steps-container {
        margin-top: 16px;
        padding: 0 12px;
    }
    
    .progress-track {
        left: 45px;
        right: 45px;
    }
    
    .progress-step span {
        font-size: 0.6rem;
    }
    
    .step-dot {
        width: 32px;
        height: 32px;
    }
    
    .content-area {
        margin-top: -16px;
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 180px) !important;
    }
    
    .card-header-icon {
        padding: 12px 12px 6px;
    }
    
    .location-item {
        padding: 6px 10px;
    }
}

/* Very small screens (iPhone SE, etc.) */
@media (max-height: 600px) {
    .status-hero {
        padding: 16px 12px 24px;
    }
    
    .status-avatar {
        width: 64px !important;
        height: 64px !important;
    }
    
    .status-avatar .v-icon {
        font-size: 32px !important;
    }
    
    .status-title {
        font-size: 1.1rem;
        margin-bottom: 8px;
    }
    
    .status-chip {
        height: 28px !important;
    }
    
    .progress-steps-container {
        margin-top: 12px;
        padding: 0 8px;
    }
    
    .progress-track {
        left: 35px;
        right: 35px;
        top: 14px;
    }
    
    .step-dot {
        width: 28px;
        height: 28px;
        border-width: 2px;
    }
    
    .step-dot .v-icon {
        font-size: 12px !important;
    }
    
    .progress-step span {
        font-size: 0.55rem;
    }
    
    .pulse-ring {
        width: 64px;
        height: 64px;
    }
}

/* Show side menu on laptop and up (1024px+) */
@media (min-width: 1024px) {
    .v-main {
        margin-left: 0 !important;
        min-height: 100vh !important;
        overflow-y: visible !important;
        width: 100% !important;
    }

    .v-main :deep(.v-container) {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
        min-height: 100% !important;
    }

    .status-hero {
        padding: 24px 16px 32px !important;
        width: 100% !important;
    }

    .status-avatar {
        width: 80px !important;
        height: 80px !important;
    }

    .status-avatar .v-icon {
        font-size: 40px !important;
    }

    .status-title {
        font-size: 1.3rem !important;
        margin-bottom: 8px !important;
    }

    .status-icon-wrapper {
        margin-bottom: 10px !important;
    }

    .pulse-ring {
        width: 80px !important;
        height: 80px !important;
    }

    .progress-steps-container {
        margin-top: 20px !important;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .step-dot {
        width: 32px !important;
        height: 32px !important;
    }

    .progress-track {
        top: 16px !important;
    }
    
    .content-area {
        padding: 20px clamp(24px, 5vw, 80px) 0 clamp(24px, 5vw, 80px) !important;
        max-width: 1200px !important;
        margin: -20px auto 0 auto !important;
        width: 100% !important;
        min-height: auto !important;
        box-sizing: border-box !important;
    }
    
    .pb-safe {
        padding-bottom: 40px !important;
    }
    
    .status-hero-content {
        max-width: 900px;
        margin: 0 auto;
    }

    /* Better card spacing for desktop */
    .content-area > .v-card {
        margin-bottom: 16px !important;
        width: 100% !important;
    }

    .card-header-icon {
        padding: 14px 16px 8px !important;
    }

    /* Ensure action buttons are visible with proper spacing */
    .action-buttons {
        margin-top: 20px !important;
        margin-bottom: 0 !important;
        padding-bottom: 40px !important;
        width: 100% !important;
    }

    .action-buttons .v-btn {
        min-height: 48px !important;
    }
}

/* Tablet screens */
@media (min-width: 600px) and (max-width: 1023px) {
    .content-area {
        max-width: 680px;
        margin-left: auto;
        margin-right: auto;
        display: block !important;
        padding: 16px 16px calc(env(safe-area-inset-bottom, 0px) + 180px) 16px !important;
    }

    .content-area > .v-card {
        max-width: 100% !important;
        margin-left: auto !important;
        margin-right: auto !important;
        margin-bottom: 16px !important;
    }

    .content-area > .action-buttons,
    .content-area > .text-center {
        max-width: 100% !important;
        margin-top: 16px !important;
    }

    .action-buttons .v-btn {
        min-height: 48px !important;
    }
    
    .status-hero-content {
        max-width: 680px;
        margin: 0 auto;
    }

    .status-hero {
        padding: 16px 16px 28px !important;
    }
    
    .status-avatar {
        width: 76px !important;
        height: 76px !important;
    }

    .status-avatar .v-icon {
        font-size: 38px !important;
    }

    .pulse-ring {
        width: 76px !important;
        height: 76px !important;
    }

    .update-details-card {
        max-width: 100% !important;
    }

    .card-header-text h3 {
        font-size: 1.05rem !important;
    }

    .card-header-text p {
        font-size: 0.8rem !important;
    }
}

/* Large desktop screens */
@media (min-width: 1440px) {
    .content-area {
        max-width: 1100px !important;
    }
    
    .status-hero-content {
        max-width: 1000px;
    }

    .status-hero {
        padding: 20px 16px 32px !important;
    }

    .status-avatar {
        width: 84px !important;
        height: 84px !important;
    }

    .status-avatar .v-icon {
        font-size: 42px !important;
    }

    .status-title {
        font-size: 1.25rem !important;
    }
}

/* Expansion Panel Customization */
:deep(.v-expansion-panel-title) {
    padding: 12px 16px !important;
}

:deep(.v-expansion-panel-text__wrapper) {
    padding: 0 16px 16px !important;
}

/* Laptop/Desktop single column with proper centering */
@media (min-width: 1024px) {
    .content-area {
        display: block !important;
    }
    
    .content-area > .v-card {
        max-width: 100% !important;
        margin-left: auto !important;
        margin-right: auto !important;
        margin-bottom: 16px !important;
    }
    
    .content-area > .action-buttons {
        max-width: 100% !important;
        margin: 24px auto 0 auto !important;
    }

    .content-area > .text-center {
        max-width: 100% !important;
        margin: 20px auto 0 auto !important;
    }
}

/* Mobile: ensure proper stacking */
@media (max-width: 599px) {
    .content-area {
        display: block !important;
        padding: 12px 12px calc(env(safe-area-inset-bottom, 0px) + 180px) 12px !important;
    }
    
    .content-area > .v-card {
        flex: none !important;
        width: 100% !important;
        margin-bottom: 12px !important;
    }

    .update-details-card .card-header-icon {
        margin: -12px -12px 0 -12px;
        padding: 12px 12px 8px;
    }

    .card-header-text h3 {
        font-size: 1rem !important;
    }

    .card-header-text p {
        font-size: 0.75rem !important;
    }
}

/* Enhanced layout consistency */
@media (min-width: 600px) {
    .update-details-card {
        animation: subtle-pulse 3s ease-in-out infinite;
    }
}

@keyframes subtle-pulse {
    0%, 100% { 
        box-shadow: 0 4px 16px rgba(33, 150, 243, 0.12);
    }
    50% { 
        box-shadow: 0 4px 20px rgba(33, 150, 243, 0.16);
    }
}

/* Safe Proof Dialog Camera Styles */
.camera-container {
    background: #000;
    border: 2px solid #E0E0E0;
}

.camera-container video {
    display: block;
    width: 100%;
}

.photo-preview {
    border: 2px solid #4CAF50;
}

/* Camera capture button styling */
.capture-btn-icon {
    border-radius: 50% !important;
    width: 64px !important;
    height: 64px !important;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4) !important;
    border: 3px solid rgba(255, 255, 255, 0.8) !important;
}

.capture-btn-icon:hover {
    box-shadow: 0 6px 16px rgba(76, 175, 80, 0.5) !important;
    transform: scale(1.05);
    transition: all 0.2s ease;
}

/* Mobile-friendly safe proof dialog buttons */
@media (max-width: 600px) {
    .v-dialog .v-card-actions {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 16px) !important;
    }
    
    .camera-container .d-flex {
        padding-bottom: 8px;
        gap: 12px !important;
    }
    
    .camera-container .v-btn {
        min-height: 36px !important;
        padding: 0 12px !important;
    }
    
    /* Ensure buttons are fully visible */
    .v-dialog .v-card {
        margin-bottom: 20px;
    }
    
    /* Safe proof dialog specific mobile adjustments */
    .v-dialog[max-width="480"] .v-card-actions .v-btn {
        font-size: 0.8rem !important;
        min-height: 36px !important;
        padding: 8px 16px !important;
    }
    
    /* Make camera preview smaller on mobile */
    .camera-container video {
        max-height: 180px !important;
    }
    
    /* Mobile capture button adjustments */
    .capture-btn-icon {
        width: 56px !important;
        height: 56px !important;
    }
    
    .capture-btn-icon .v-icon {
        font-size: 28px !important;
    }
}

/* ===================================================================
   DARK MODE OVERRIDES — HelpComing.vue  (comprehensive)
   =================================================================== */
html.dark-mode .v-application {

    /* ══════════ PAGE BACKGROUND ══════════ */
    &.bg-user-gradient-light {
        background: var(--dm-bg-base, #0f172a) !important;
    }

    .content-area {
        background: var(--dm-bg-base, #0f172a) !important;
    }

    /* ══════════ STATUS HERO ══════════ */
    .status-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
    }

    /* ══════════ CARDS — base & typed ══════════ */
    .v-card {
        background: var(--dm-bg-surface, #1e293b) !important;
        color: var(--dm-text-primary, #e8edf4) !important;
        border: 1px solid var(--dm-border, #334155) !important;
    }

    .location-card {
        background: var(--dm-bg-surface, #1e293b) !important;
        border: 2px solid rgba(96, 165, 250, 0.25) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    }
    .location-card:hover {
        box-shadow: 0 6px 20px rgba(96, 165, 250, 0.2) !important;
        border-color: rgba(96, 165, 250, 0.4) !important;
    }

    .rescuer-card {
        background: var(--dm-bg-surface, #1e293b) !important;
        border: 2px solid rgba(74, 222, 128, 0.2) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    }
    .rescuer-card:hover {
        box-shadow: 0 6px 20px rgba(74, 222, 128, 0.15) !important;
        border-color: rgba(74, 222, 128, 0.35) !important;
    }

    .emergency-card {
        background: var(--dm-bg-surface, #1e293b) !important;
        border: 2px solid rgba(248, 113, 113, 0.2) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    }
    .emergency-card:hover {
        box-shadow: 0 6px 20px rgba(248, 113, 113, 0.15) !important;
        border-color: rgba(248, 113, 113, 0.35) !important;
    }

    .update-details-card {
        background: var(--dm-bg-surface, #1e293b) !important;
        border: 2px solid rgba(96, 165, 250, 0.25) !important;
    }

    /* ══════════ CARD HEADER RIBBON ══════════ */
    .card-header-icon {
        background: #1a2536 !important;
        border-bottom: 1px solid var(--dm-border, #334155) !important;
    }

    .card-header-text h3 {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .card-header-text p {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    /* ══════════ LOCATION ITEMS (Building / Floor / Room) ══════════ */
    .location-item {
        background: linear-gradient(135deg, #1e293b 0%, #263548 100%) !important;
        border: 1px solid var(--dm-border, #334155) !important;
    }

    .location-item:hover {
        background: linear-gradient(135deg, #263548 0%, #334155 100%) !important;
        border-color: rgba(96, 165, 250, 0.3) !important;
    }

    .location-item .label {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    .location-item .value {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    /* ══════════ EMERGENCY DETAIL ITEMS ══════════ */
    .detail-item {
        border-bottom-color: var(--dm-border, #334155) !important;
    }

    .detail-label {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    .detail-value {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .detail-item:has(.detail-chip-value) {
        background: var(--dm-bg-elevated, #334155) !important;
    }

    /* Description block */
    .description-item {
        background: linear-gradient(135deg, #1e293b 0%, #263548 100%) !important;
        border-left: 4px solid #60a5fa !important;
    }

    .description-paragraph {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    /* Completion notes */
    .completion-notes-box {
        background: var(--dm-bg-elevated, #334155) !important;
        border-left: 3px solid #4ade80 !important;
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    /* ══════════ RESCUER INFO ══════════ */
    .rescuer-details h4 {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .rescuer-details p {
        color: #4ade80 !important;
    }

    .self-safe-details h4 {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .self-safe-details p {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    .rescuer-card .self-safe-info {
        border-color: rgba(74, 222, 128, 0.2) !important;
    }

    /* Status message & timestamp */
    .status-message p {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .status-timestamp {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    .status-note {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    /* ══════════ ACTION CONTAINER ══════════ */
    .action-container {
        background: rgba(30, 41, 59, 0.85) !important;
        border: 1px solid var(--dm-border, #334155) !important;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25) !important;
    }

    .action-container:hover {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35) !important;
    }

    /* ══════════ APPROVAL PENDING ══════════ */
    .approval-pending-card {
        background: var(--dm-bg-surface, #1e293b) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .approval-pending-container {
        background: linear-gradient(135deg, rgba(96, 165, 250, 0.05) 0%, rgba(96, 165, 250, 0.1) 100%) !important;
        border: 2px solid rgba(96, 165, 250, 0.25) !important;
    }

    .approval-title {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .approval-subtitle {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    /* ══════════ CANCEL HOLD BUTTON ══════════ */
    .cancel-hold-btn.holding {
        background: var(--dm-bg-elevated, #334155) !important;
        border: 2px solid #475569 !important;
    }

    .cancel-hold-btn.holding .v-btn__content {
        color: #f87171 !important;
    }

    /* ══════════ SLIDE-TO-CONFIRM ══════════ */
    .slide-track {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
        border: 2px solid #475569 !important;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3) !important;
    }

    .slide-instruction {
        color: #94a3b8 !important;
    }

    .slide-thumb.slide-active ~ .slide-text .slide-instruction {
        color: #f1f5f9 !important;
    }

    .slide-success {
        color: white !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5) !important;
    }

    /* ══════════ SAFE PROOF / CAMERA DIALOG ══════════ */
    .camera-container {
        background: #000 !important;
        border: 2px solid #475569 !important;
    }

    .photo-preview {
        border: 2px solid #22c55e !important;
    }

    .photo-upload-area {
        border: 2px dashed #475569 !important;
        background: var(--dm-bg-elevated, #334155) !important;
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    .photo-proof-card {
        background: var(--dm-bg-surface, #1e293b) !important;
    }

    .proof-photo-wrap {
        border: 2px solid var(--dm-border, #334155) !important;
        background: var(--dm-bg-elevated, #334155) !important;
    }

    .proof-photo-wrap:hover {
        border-color: var(--dm-primary, #60a5fa) !important;
    }

    .proof-photo-badge {
        background: rgba(15, 23, 42, 0.9) !important;
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    /* ══════════ FEEDBACK DIALOG ══════════ */
    .feedback-card {
        background: var(--dm-bg-surface, #1e293b) !important;
    }

    .feedback-body {
        background: var(--dm-bg-base, #0f172a) !important;
    }

    .feedback-responder-profile {
        background: var(--dm-bg-elevated, #334155) !important;
        border-color: var(--dm-border, #334155) !important;
    }

    .feedback-responder-name {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .feedback-responder-role {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    .feedback-question {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .feedback-star-label {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    .feedback-safe-toggle {
        background: var(--dm-bg-elevated, #334155) !important;
        border-color: var(--dm-border, #334155) !important;
    }

    .feedback-safe-label {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .feedback-tag-chip {
        border-color: var(--dm-border, #334155) !important;
    }

    /* ══════════ FORM ELEMENTS ══════════ */
    .v-textarea .v-field,
    .v-text-field .v-field,
    .v-select .v-field {
        background: var(--dm-bg-elevated, #334155) !important;
        color: var(--dm-text-primary, #e8edf4) !important;
        border: 1px solid var(--dm-border-light, #475569) !important;
    }

    .v-textarea .v-field textarea,
    .v-text-field .v-field input {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .v-field-label {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    /* ══════════ TEXT UTILITIES ══════════ */
    .text-grey,
    .text-grey-darken-1,
    .text-grey-darken-2,
    .text-grey-darken-3 {
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    .text-body-2,
    .text-subtitle-2,
    .text-body-1 {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    /* ══════════ ALERTS & BANNERS ══════════ */
    .v-alert {
        border: 1px solid var(--dm-border, #334155) !important;
    }

    .wait-banner .wait-alert {
        background: var(--dm-bg-elevated, #334155) !important;
    }

    /* ══════════ SAFETY CHECK MODAL ══════════ */
    .safety-check-card {
        background: var(--dm-bg-surface, #1e293b) !important;
    }

    /* ══════════ NO RESCUE / EMPTY STATE ══════════ */
    .no-rescue-state {
        background: var(--dm-bg-surface, #1e293b) !important;
        color: var(--dm-text-secondary, #8e99ab) !important;
    }

    .no-rescue-state h3 {
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    /* ══════════ DIALOG BACKDROP ══════════ */
    .v-overlay--active > .v-overlay__scrim {
        background-color: rgba(0, 0, 0, 0.8) !important;
    }

    /* ══════════ SECONDARY BUTTONS ══════════ */
    .secondary-btn {
        background: var(--dm-bg-elevated, #334155) !important;
        border: 1px solid var(--dm-border, #334155) !important;
        color: var(--dm-text-primary, #e8edf4) !important;
    }

    .secondary-btn:hover {
        background: #3d4f66 !important;
        border-color: #475569 !important;
    }

    /* ══════════ INLINE STYLE OVERRIDES ══════════ */
    /* Description item has inline style with light gradient */
    .detail-item.description-item[style] {
        background: linear-gradient(135deg, #1e293b 0%, #263548 100%) !important;
        border-left-color: #60a5fa !important;
    }
}
</style>
