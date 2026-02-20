<template>
    <v-app class="bg-user-gradient-light">
        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <v-btn icon variant="text" @click="drawer = !drawer" class="menu-btn desktop-only">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>
                <div class="header-title">
                    <div class="title-with-icon">
                        <v-icon size="24" class="mr-2">mdi-lightbulb-on-outline</v-icon>
                        <h1>Help Improve PinPointMe</h1>
                    </div>
                    <p>Share your feedback to help us improve</p>
                </div>
                <v-btn icon variant="text" @click="refreshData" class="placeholder-btn desktop-only" :loading="refreshing">
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </div>
        </div>

        <!-- Navigation Drawer -->
        <RescuerMenu v-model="drawer" />

        <v-main class="page-main">
            <v-container fluid class="page-container">

                <!-- Hero Card -->
                <v-card class="mb-4 rounded-xl overflow-hidden" elevation="0">
                    <div class="hero-bg pa-5 text-center">
                        <v-icon size="48" color="white" class="mb-2">mdi-hand-heart-outline</v-icon>
                        <h2 class="text-h6 font-weight-bold text-white mb-1">Your Voice Matters</h2>
                        <p class="text-body-2 text-white-darken-1 mb-0">
                            Help us make PinPointMe better by reporting bugs or suggesting improvements. Every piece of feedback counts!
                        </p>
                    </div>
                </v-card>

                <!-- Submission Form -->
                <v-card class="mb-4 rounded-xl" elevation="0">
                    <div class="d-flex align-center py-3 px-4">
                        <v-icon color="primary" class="mr-2" size="20">mdi-pencil-plus</v-icon>
                        <span class="text-subtitle-1 font-weight-bold">Submit Feedback</span>
                    </div>
                    <v-divider />
                    <div class="pa-4">
                        <v-form ref="formRef" v-model="formValid" @submit.prevent="submitFeedback">
                            <!-- Category -->
                            <p class="text-caption font-weight-bold text-grey-darken-1 mb-2">What type of feedback?</p>
                            <div class="d-flex ga-3 mb-4">
                                <v-card
                                    :variant="form.category === 'bug' ? 'flat' : 'outlined'"
                                    :color="form.category === 'bug' ? 'error' : undefined"
                                    rounded="lg"
                                    class="flex-grow-1 pa-3 text-center category-card"
                                    :class="{ 'category-selected': form.category === 'bug' }"
                                    @click="form.category = 'bug'"
                                    style="cursor: pointer;"
                                >
                                    <v-icon :color="form.category === 'bug' ? 'white' : 'error'" size="28" class="mb-1">mdi-bug</v-icon>
                                    <div :class="form.category === 'bug' ? 'text-white' : 'text-grey-darken-2'" class="text-caption font-weight-bold">Bug Report</div>
                                    <div :class="form.category === 'bug' ? 'text-white' : 'text-grey'" class="text-caption" style="font-size: 0.65rem;">Something isn't working</div>
                                </v-card>
                                <v-card
                                    :variant="form.category === 'improvement' ? 'flat' : 'outlined'"
                                    :color="form.category === 'improvement' ? 'amber-darken-2' : undefined"
                                    rounded="lg"
                                    class="flex-grow-1 pa-3 text-center category-card"
                                    :class="{ 'category-selected': form.category === 'improvement' }"
                                    @click="form.category = 'improvement'"
                                    style="cursor: pointer;"
                                >
                                    <v-icon :color="form.category === 'improvement' ? 'white' : 'amber-darken-2'" size="28" class="mb-1">mdi-lightbulb-on</v-icon>
                                    <div :class="form.category === 'improvement' ? 'text-white' : 'text-grey-darken-2'" class="text-caption font-weight-bold">Suggestion</div>
                                    <div :class="form.category === 'improvement' ? 'text-white' : 'text-grey'" class="text-caption" style="font-size: 0.65rem;">An idea to improve</div>
                                </v-card>
                            </div>
                            <div v-if="categoryError" class="text-caption text-error mb-3" style="margin-top: -8px;">
                                Please select a category
                            </div>

                            <!-- Area -->
                            <v-select
                                v-model="form.area"
                                :items="areaOptions"
                                item-title="label"
                                item-value="value"
                                label="Related Area (Optional)"
                                variant="outlined"
                                density="compact"
                                clearable
                                hide-details="auto"
                                class="mb-3"
                                prepend-inner-icon="mdi-map-marker-radius"
                            >
                                <template v-slot:item="{ item, props: itemProps }">
                                    <v-list-item v-bind="itemProps">
                                        <template v-slot:prepend>
                                            <v-icon :color="getAreaColor(item.value)" size="18">{{ getAreaIcon(item.value) }}</v-icon>
                                        </template>
                                    </v-list-item>
                                </template>
                            </v-select>

                            <!-- Description -->
                            <v-textarea
                                v-model="form.description"
                                :rules="descriptionRules"
                                label="Describe your feedback"
                                :placeholder="form.category === 'bug' 
                                    ? 'Please describe what happened, what you expected, and steps to reproduce the issue...' 
                                    : 'Please describe your idea and how it would improve PinPointMe...'"
                                variant="outlined"
                                density="compact"
                                rows="4"
                                auto-grow
                                counter="3000"
                                maxlength="3000"
                                hide-details="auto"
                                class="mb-3"
                                prepend-inner-icon="mdi-text"
                            />

                            <!-- Attachment -->
                            <v-card variant="outlined" rounded="lg" class="mb-4 pa-3">
                                <p class="text-caption font-weight-bold text-grey-darken-1 mb-2">
                                    <v-icon size="14" class="mr-1">mdi-paperclip</v-icon>
                                    Attachment (Optional)
                                </p>
                                <div v-if="!attachmentPreview" class="text-center">
                                    <v-btn
                                        variant="tonal"
                                        color="primary"
                                        size="small"
                                        @click="triggerFileInput"
                                        class="text-none"
                                    >
                                        <v-icon start size="16">mdi-upload</v-icon>
                                        Upload Screenshot or File
                                    </v-btn>
                                    <p class="text-caption text-grey mt-1 mb-0">JPG, PNG, GIF, PDF, MP4 — Max 10MB</p>
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept="image/*,video/mp4,video/quicktime,application/pdf"
                                        style="display: none;"
                                        @change="handleFileSelect"
                                    />
                                </div>
                                <div v-else class="d-flex align-center">
                                    <v-img
                                        v-if="attachmentPreview.type === 'image'"
                                        :src="attachmentPreview.url"
                                        max-height="120"
                                        max-width="120"
                                        rounded="lg"
                                        cover
                                        class="mr-3"
                                    />
                                    <v-icon v-else size="40" color="primary" class="mr-3">
                                        {{ attachmentPreview.type === 'video' ? 'mdi-video-outline' : 'mdi-file-pdf-box' }}
                                    </v-icon>
                                    <div class="flex-grow-1">
                                        <div class="text-body-2 font-weight-medium text-truncate" style="max-width: 200px;">{{ attachmentPreview.name }}</div>
                                        <div class="text-caption text-grey">{{ attachmentPreview.size }}</div>
                                    </div>
                                    <v-btn icon variant="text" density="compact" color="error" @click="removeAttachment">
                                        <v-icon size="18">mdi-close-circle</v-icon>
                                    </v-btn>
                                </div>
                            </v-card>

                            <!-- Submit Button -->
                            <v-btn
                                block
                                color="#3674B5"
                                size="large"
                                :loading="submitting"
                                :disabled="!canSubmit"
                                @click="submitFeedback"
                                class="text-none rounded-lg"
                                elevation="0"
                            >
                                <v-icon start>mdi-send</v-icon>
                                Submit Feedback
                            </v-btn>
                        </v-form>
                    </div>
                </v-card>

                <!-- My Previous Submissions -->
                <v-card class="mb-4 rounded-xl" elevation="0">
                    <div class="d-flex align-center justify-space-between py-3 px-4">
                        <div class="d-flex align-center">
                            <v-icon color="primary" class="mr-2" size="20">mdi-history</v-icon>
                            <span class="text-subtitle-1 font-weight-bold">My Submissions</span>
                        </div>
                        <v-chip size="x-small" color="primary" variant="tonal" v-if="myFeedbacks.length > 0">
                            {{ myFeedbacks.length }}
                        </v-chip>
                    </div>
                    <v-divider />

                    <!-- Loading -->
                    <v-card-text v-if="loadingHistory" class="text-center py-6">
                        <v-progress-circular indeterminate color="primary" size="32" />
                        <p class="mt-2 text-caption text-grey">Loading your submissions...</p>
                    </v-card-text>

                    <!-- Empty -->
                    <v-card-text v-else-if="myFeedbacks.length === 0" class="text-center py-6">
                        <v-icon size="48" color="grey-lighten-1">mdi-clipboard-text-off-outline</v-icon>
                        <p class="mt-2 text-body-2 text-grey">You haven't submitted any feedback yet</p>
                    </v-card-text>

                    <!-- Feedback List -->
                    <div v-else class="pa-0">
                        <div
                            v-for="fb in myFeedbacks"
                            :key="fb.id"
                            class="feedback-item"
                            @click="viewFeedback(fb)"
                        >
                            <div class="d-flex align-center justify-space-between mb-1">
                                <v-chip
                                    :color="fb.category === 'bug' ? 'error' : 'amber-darken-2'"
                                    variant="tonal"
                                    size="x-small"
                                >
                                    <v-icon start size="10">{{ fb.category === 'bug' ? 'mdi-bug' : 'mdi-lightbulb-on' }}</v-icon>
                                    {{ fb.category === 'bug' ? 'Bug' : 'Suggestion' }}
                                </v-chip>
                                <v-chip
                                    :color="getStatusColor(fb.status)"
                                    variant="flat"
                                    size="x-small"
                                    class="text-white"
                                >
                                    <v-icon start size="10">{{ getStatusIcon(fb.status) }}</v-icon>
                                    {{ getStatusLabel(fb.status) }}
                                </v-chip>
                            </div>
                            <div class="d-flex align-center flex-wrap ga-1 mb-1">
                                <v-chip v-if="fb.area" size="x-small" :color="getAreaColor(fb.area)" variant="tonal">
                                    <v-icon start size="10">{{ getAreaIcon(fb.area) }}</v-icon>
                                    {{ fb.area }}
                                </v-chip>
                                <v-chip v-if="fb.attachment_path" size="x-small" color="grey" variant="tonal">
                                    <v-icon start size="10">mdi-paperclip</v-icon>
                                    Attachment
                                </v-chip>
                            </div>
                            <div class="text-body-2 text-grey-darken-2" style="line-height: 1.4;">
                                {{ fb.description.length > 120 ? fb.description.substring(0, 120) + '...' : fb.description }}
                            </div>
                            <div class="text-caption text-grey mt-1">
                                <v-icon size="10" class="mr-1">mdi-clock-outline</v-icon>
                                {{ formatDate(fb.created_at) }}
                            </div>
                        </div>
                    </div>
                </v-card>

                <!-- Feedback Detail Dialog -->
                <v-dialog v-model="showDetailDialog" :width="isMobile ? '95%' : 550" rounded="lg">
                    <v-card v-if="selectedFeedback" rounded="lg">
                        <v-card-title class="d-flex align-center pa-4" style="background: linear-gradient(135deg, #3674B5 0%, #2d5f96 100%); color: white;">
                            <v-icon color="white" class="mr-2">{{ selectedFeedback.category === 'bug' ? 'mdi-bug' : 'mdi-lightbulb-on' }}</v-icon>
                            {{ selectedFeedback.category === 'bug' ? 'Bug Report' : 'Suggestion' }} Detail
                            <v-spacer />
                            <v-btn icon variant="text" density="compact" @click="showDetailDialog = false">
                                <v-icon color="white">mdi-close</v-icon>
                            </v-btn>
                        </v-card-title>
                        <v-card-text class="pa-4">
                            <!-- Category & Area -->
                            <div class="d-flex flex-wrap ga-2 mb-3">
                                <v-chip :color="selectedFeedback.category === 'bug' ? 'error' : 'amber-darken-2'" variant="tonal" size="small">
                                    <v-icon start size="14">{{ selectedFeedback.category === 'bug' ? 'mdi-bug' : 'mdi-lightbulb-on' }}</v-icon>
                                    {{ selectedFeedback.category === 'bug' ? 'Bug Report' : 'Improvement' }}
                                </v-chip>
                                <v-chip v-if="selectedFeedback.area" :color="getAreaColor(selectedFeedback.area)" variant="tonal" size="small">
                                    <v-icon start size="14">{{ getAreaIcon(selectedFeedback.area) }}</v-icon>
                                    {{ selectedFeedback.area }}
                                </v-chip>
                                <v-chip :color="getStatusColor(selectedFeedback.status)" variant="flat" size="small" class="text-white">
                                    <v-icon start size="14">{{ getStatusIcon(selectedFeedback.status) }}</v-icon>
                                    {{ getStatusLabel(selectedFeedback.status) }}
                                </v-chip>
                            </div>

                            <!-- Date -->
                            <div class="text-caption text-grey mb-3">
                                <v-icon size="12" class="mr-1">mdi-calendar</v-icon>
                                Submitted {{ formatDate(selectedFeedback.created_at) }}
                            </div>

                            <v-divider class="mb-3" />

                            <!-- Description -->
                            <div class="mb-3">
                                <p class="text-caption text-grey mb-1">Description</p>
                                <v-card variant="tonal" color="grey-lighten-4" rounded="lg" class="pa-3">
                                    <div class="text-body-2" style="white-space: pre-wrap; line-height: 1.6;">{{ selectedFeedback.description }}</div>
                                </v-card>
                            </div>

                            <!-- Attachment -->
                            <div v-if="selectedFeedback.attachment_path" class="mb-3">
                                <p class="text-caption text-grey mb-1">Attachment</p>
                                <v-card variant="outlined" rounded="lg" class="pa-3">
                                    <div v-if="isImageFile(selectedFeedback.attachment_path)" class="text-center">
                                        <v-img :src="getAttachmentUrl(selectedFeedback.attachment_path)" max-height="250" contain rounded="lg" />
                                    </div>
                                    <div v-else class="d-flex align-center">
                                        <v-icon size="24" color="primary" class="mr-2">mdi-file-outline</v-icon>
                                        <a :href="getAttachmentUrl(selectedFeedback.attachment_path)" target="_blank" class="text-primary text-body-2">
                                            View Attachment
                                        </a>
                                    </div>
                                </v-card>
                            </div>

                            <v-divider class="mb-3" />

                            <!-- Admin Response -->
                            <div class="mb-2">
                                <p class="text-caption font-weight-bold text-grey-darken-1 mb-2">
                                    <v-icon size="14" color="primary" class="mr-1">mdi-shield-account</v-icon>
                                    Admin Response
                                </p>

                                <v-card v-if="selectedFeedback.admin_notes" variant="tonal" color="blue-lighten-5" rounded="lg" class="pa-3">
                                    <div class="d-flex align-start">
                                        <v-icon size="18" color="primary" class="mr-2 mt-1">mdi-message-reply-text</v-icon>
                                        <div class="text-body-2" style="white-space: pre-wrap; line-height: 1.5;">{{ selectedFeedback.admin_notes }}</div>
                                    </div>
                                </v-card>
                                <v-card v-else variant="tonal" color="grey-lighten-4" rounded="lg" class="pa-3 text-center">
                                    <v-icon size="20" color="grey" class="mb-1">mdi-clock-outline</v-icon>
                                    <p class="text-caption text-grey mb-0">No admin response yet. We'll review your feedback soon.</p>
                                </v-card>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-dialog>

            </v-container>
        </v-main>

        <!-- Bottom Navigation -->
        <RescuerBottomNav :notification-count="0" :message-count="unreadMessageCount" />

        <!-- Success Snackbar -->
        <v-snackbar
            v-model="snackbar.show"
            :color="snackbar.color"
            :timeout="3000"
            location="top"
        >
            <div class="d-flex align-center">
                <v-icon class="mr-2">{{ snackbar.icon }}</v-icon>
                {{ snackbar.message }}
            </div>
        </v-snackbar>


    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useDisplay } from 'vuetify';
import RescuerMenu from '@/Components/Pages/Rescuer/Menu/RescuerMenu.vue';
import RescuerBottomNav from '@/Components/Pages/Rescuer/Menu/RescuerBottomNav.vue';
import { submitSystemFeedback, getUserSystemFeedbacks, getUnreadMessageCount } from '@/Composables/useApi';


const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

// Auth
const page = usePage();
const authUser = computed(() => page.props?.auth?.user);

// State
const drawer = ref(false);
const formRef = ref(null);
const fileInputRef = ref(null);
const formValid = ref(true);
const submitting = ref(false);
const refreshing = ref(false);
const loadingHistory = ref(false);
const unreadMessageCount = ref(0);
const categoryError = ref(false);

// Form data
const form = ref({
    category: '',
    area: '',
    description: '',
});
const attachmentFile = ref(null);
const attachmentPreview = ref(null);

// My feedbacks
const myFeedbacks = ref([]);

// Detail dialog
const showDetailDialog = ref(false);
const selectedFeedback = ref(null);

// Snackbar
const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
    icon: 'mdi-check-circle',
});

// Area options (rescuer-focused features)
const areaOptions = [
    { label: 'Rescue Dashboard', value: 'Dashboard' },
    { label: 'Active Rescue Management', value: 'Active Rescue' },
    { label: 'Rescue History', value: 'History' },
    { label: 'Chat with Users', value: 'Chat' },
    { label: 'Notifications/Alerts', value: 'Notifications' },
    { label: 'Map/Navigation', value: 'Map' },
    { label: 'Profile/Account', value: 'Profile' },
    { label: 'Rescue Request Flow', value: 'Rescue Flow' },
    { label: 'Performance/Speed', value: 'Performance' },
    { label: 'Other', value: 'Other' },
];

// Validation rules
const descriptionRules = [
    v => !!v || 'Description is required',
    v => (v && v.length >= 10) || 'At least 10 characters required',
    v => (v && v.length <= 3000) || 'Maximum 3000 characters',
];

// Computed
const canSubmit = computed(() => {
    return form.value.category && form.value.description && form.value.description.length >= 10 && !submitting.value;
});

// Methods
const getAreaIcon = (area) => {
    const icons = {
        'Dashboard': 'mdi-view-dashboard',
        'Active Rescue': 'mdi-lifebuoy',
        'History': 'mdi-history',
        'Chat': 'mdi-message-text',
        'Notifications': 'mdi-bell',
        'Map': 'mdi-map-marker',
        'Profile': 'mdi-account',
        'Rescue Flow': 'mdi-run-fast',
        'Performance': 'mdi-speedometer',
        'Other': 'mdi-dots-horizontal-circle',
    };
    return icons[area] || 'mdi-help-circle';
};

const getAreaColor = (area) => {
    const colors = {
        'Dashboard': 'cyan-darken-1',
        'Active Rescue': 'deep-orange',
        'History': 'brown',
        'Chat': 'blue',
        'Notifications': 'amber-darken-2',
        'Map': 'green',
        'Profile': 'indigo',
        'Rescue Flow': 'red',
        'Performance': 'red',
        'Other': 'grey',
    };
    return colors[area] || 'grey';
};

const getStatusColor = (status) => {
    const colors = {
        'open': 'orange',
        'in_review': 'info',
        'resolved': 'success',
        'closed': 'grey',
    };
    return colors[status] || 'grey';
};

const getStatusIcon = (status) => {
    const icons = {
        'open': 'mdi-alert-circle-outline',
        'in_review': 'mdi-magnify-scan',
        'resolved': 'mdi-check-circle',
        'closed': 'mdi-close-circle',
    };
    return icons[status] || 'mdi-help-circle';
};

const getStatusLabel = (status) => {
    const labels = {
        'open': 'Open',
        'in_review': 'In Review',
        'resolved': 'Resolved',
        'closed': 'Closed',
    };
    return labels[status] || status;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const isImageFile = (path) => {
    if (!path) return false;
    return /\.(jpg|jpeg|png|gif|webp)$/i.test(path);
};

const getAttachmentUrl = (path) => {
    if (!path) return '';
    const base = window.location.origin || '';
    return `${base}/storage/${path}`;
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const handleFileSelect = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    // Validate size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
        showSnackbar('File size must be under 10MB', 'error', 'mdi-alert-circle');
        return;
    }

    attachmentFile.value = file;

    // Generate preview
    const isImage = file.type.startsWith('image/');
    const isVideo = file.type.startsWith('video/');
    const sizeStr = file.size > 1024 * 1024
        ? `${(file.size / (1024 * 1024)).toFixed(1)} MB`
        : `${(file.size / 1024).toFixed(0)} KB`;

    attachmentPreview.value = {
        name: file.name,
        size: sizeStr,
        type: isImage ? 'image' : isVideo ? 'video' : 'file',
        url: isImage ? URL.createObjectURL(file) : null,
    };
};

const removeAttachment = () => {
    if (attachmentPreview.value?.url) {
        URL.revokeObjectURL(attachmentPreview.value.url);
    }
    attachmentFile.value = null;
    attachmentPreview.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

const submitFeedback = async () => {
    // Validate category
    if (!form.value.category) {
        categoryError.value = true;
        return;
    }
    categoryError.value = false;

    // Validate form
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    submitting.value = true;
    try {
        const formData = new FormData();
        formData.append('user_id', authUser.value?.id);
        formData.append('category', form.value.category);
        formData.append('description', form.value.description);
        if (form.value.area) formData.append('area', form.value.area);
        if (attachmentFile.value) formData.append('attachment', attachmentFile.value);

        const response = await submitSystemFeedback(formData);

        if (response?.success) {
            showSnackbar('Thank you! Your feedback has been submitted successfully.', 'success', 'mdi-check-circle');
            resetForm();
            await loadMyFeedbacks();
        } else {
            showSnackbar(response?.message || 'Failed to submit. Please try again.', 'error', 'mdi-alert-circle');
        }
    } catch (error) {
        console.error('Failed to submit feedback:', error);
        showSnackbar('Something went wrong. Please try again later.', 'error', 'mdi-alert-circle');
    } finally {
        submitting.value = false;
    }
};

const resetForm = () => {
    form.value = { category: '', area: '', description: '' };
    removeAttachment();
    formRef.value?.resetValidation();
    categoryError.value = false;
};

const loadMyFeedbacks = async () => {
    if (!authUser.value?.id) return;
    loadingHistory.value = true;
    try {
        const response = await getUserSystemFeedbacks(authUser.value.id);
        if (response?.success) {
            myFeedbacks.value = response.data || [];
        }
    } catch (error) {
        console.error('Failed to load feedback history:', error);
    } finally {
        loadingHistory.value = false;
    }
};

const viewFeedback = (fb) => {
    selectedFeedback.value = fb;
    showDetailDialog.value = true;
};

const refreshData = async () => {
    refreshing.value = true;
    await loadMyFeedbacks();
    refreshing.value = false;
};

const showSnackbar = (message, color = 'success', icon = 'mdi-check-circle') => {
    snackbar.value = { show: true, message, color, icon };
};

// Load unread message count
const loadUnreadMessages = async () => {
    try {
        const response = await getUnreadMessageCount(authUser.value?.id);
        if (response?.success) {
            unreadMessageCount.value = response.data?.unread_count || 0;
        }
    } catch (e) {
        // silent fail
    }
};

onMounted(async () => {
    if (!authUser.value) {
        router.visit('/login');
        return;
    }
    await Promise.all([loadMyFeedbacks(), loadUnreadMessages()]);
});
</script>

<style scoped>
/* Page Header - matches rescuer pages */
.page-header {
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
    font-size: 1.1rem;
    font-weight: 700;
    color: white;
    margin: 0;
}

.header-title p {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

/* Hero Card */
.hero-bg {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 50%, #0D47A1 100%);
}

.text-white-darken-1 {
    color: rgba(255, 255, 255, 0.8) !important;
}

/* Category Cards */
.category-card {
    transition: all 0.2s ease;
    border-width: 2px !important;
}

.category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.category-selected {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

/* Feedback Items */
.feedback-item {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    cursor: pointer;
    transition: background 0.2s ease;
}

.feedback-item:hover {
    background: rgba(54, 116, 181, 0.04);
}

.feedback-item:last-child {
    border-bottom: none;
}

/* Main layout */
.page-main {
    min-height: 100vh;
    min-height: 100dvh;
}

/* Container - mobile-first padding */
.page-container {
    padding: 12px !important;
    padding-bottom: 180px !important;
}

@media (min-width: 600px) {
    .page-container {
        padding: 16px !important;
        padding-bottom: 180px !important;
    }

    .header-title h1 {
        font-size: 1.25rem;
    }
}

/* Tablet */
@media (min-width: 600px) and (max-width: 1023px) {
    .page-main {
        padding-bottom: 180px !important;
    }
}

/* Desktop - hide bottom nav, no extra padding */
@media (min-width: 1024px) {
    .page-container {
        max-width: 800px;
        margin: 0 auto;
        padding-bottom: 0 !important;
    }

    .desktop-only {
        display: flex !important;
    }
}

/* Mobile adjustments */
@media (max-width: 599px) {
    .page-container {
        padding-bottom: 180px !important;
    }

    .header-title h1 {
        font-size: 1rem;
    }
}

/* Hide desktop-only elements on mobile */
@media (max-width: 1023px) {
    .desktop-only {
        display: none !important;
    }
}
</style>
