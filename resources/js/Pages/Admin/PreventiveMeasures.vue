<template>
    <v-app class="bg-grey-lighten-4">
        <!-- Admin App Bar -->
        <AdminAppBar activePage="preventive-measures" />

        <!-- Main Content -->
        <v-main>
            <v-container fluid :class="isMobile ? 'pa-3' : 'pa-6'" class="pm-container">
                <!-- Page Header -->
                <div class="pm-page-header mb-4">
                    <div class="pm-header-content">
                        <h1 :class="isMobile ? 'text-h6' : 'text-h5'" class="font-weight-bold gradient-text">
                            <v-icon :size="isMobile ? 20 : 24" class="mr-2" color="primary">mdi-shield-check</v-icon>
                            Preventive Measures
                        </h1>
                        <p class="text-grey mt-1 text-caption">Manage educational content and safety videos</p>
                    </div>
                    <v-btn 
                        color="primary" 
                        @click="openAddDialog"
                        :size="isMobile ? 'small' : 'default'"
                        rounded="lg"
                        elevation="2"
                    >
                        <v-icon start>mdi-plus</v-icon>
                        {{ isMobile ? 'Add' : 'Add Video' }}
                    </v-btn>
                </div>

                <!-- Stats Row -->
                <div class="pm-stats-row mb-4">
                    <div class="pm-stat-card">
                        <div class="pm-stat-icon" style="background: linear-gradient(135deg, #3674B5, #2196F3);">
                            <v-icon size="20" color="white">mdi-video</v-icon>
                        </div>
                        <div class="pm-stat-info">
                            <span class="pm-stat-value">{{ measuresList.length }}</span>
                            <span class="pm-stat-label">Total</span>
                        </div>
                    </div>
                    <div class="pm-stat-card">
                        <div class="pm-stat-icon" style="background: linear-gradient(135deg, #13294B, #3674B5);">
                            <v-icon size="20" color="white">mdi-folder</v-icon>
                        </div>
                        <div class="pm-stat-info">
                            <span class="pm-stat-value">{{ categories.length }}</span>
                            <span class="pm-stat-label">Categories</span>
                        </div>
                    </div>
                    <div class="pm-stat-card">
                        <div class="pm-stat-icon" style="background: linear-gradient(135deg, #2E7D32, #4CAF50);">
                            <v-icon size="20" color="white">mdi-check-circle</v-icon>
                        </div>
                        <div class="pm-stat-info">
                            <span class="pm-stat-value">{{ measuresList.filter(m => m.is_published).length }}</span>
                            <span class="pm-stat-label">Visible to Users</span>
                        </div>
                    </div>
                </div>

                <!-- Category Filter Pills -->
                <div class="pm-category-scroll mb-4">
                    <button
                        :class="['pm-cat-pill', selectedCategory === 'all' ? 'active' : '']"
                        @click="selectedCategory = 'all'"
                    >
                        <v-icon size="14">mdi-view-grid</v-icon>
                        <span>All</span>
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        :class="['pm-cat-pill', selectedCategory === cat ? 'active' : '']"
                        @click="selectedCategory = cat"
                    >
                        <v-icon size="14">{{ getCategoryIcon(cat) }}</v-icon>
                        <span>{{ cat }}</span>
                    </button>
                </div>

                <!-- Results Count -->
                <div class="d-flex align-center justify-space-between mb-3">
                    <span class="text-caption text-grey-darken-1 font-weight-medium">
                        {{ filteredMeasures.length }} {{ filteredMeasures.length === 1 ? 'video' : 'videos' }}
                    </span>
                    <v-btn variant="text" size="x-small" color="primary" @click="fetchMeasures" prepend-icon="mdi-refresh">
                        Refresh
                    </v-btn>
                </div>

                <!-- Info Alert -->
                <v-alert v-if="measuresList.length > 0" type="info" variant="tonal" density="compact" class="mb-4" rounded="lg">
                    <div class="text-caption">
                        <v-icon size="14" class="mr-1">mdi-information-outline</v-icon>
                        <strong>Video Visibility:</strong> Hidden videos are not deleted — they're just invisible to users. Toggle visibility anytime.
                    </div>
                </v-alert>

                <!-- Videos Grid -->
                <div class="pm-grid">
                    <div v-for="measure in filteredMeasures" :key="measure.id" class="pm-card-wrap">
                        <div class="pm-card">
                            <!-- Thumbnail -->
                            <div class="pm-thumb" @click="previewVideo(measure)">
                                <v-img
                                    :src="getThumbnail(measure)"
                                    :aspect-ratio="16/9"
                                    cover
                                    class="pm-thumb-img"
                                >
                                    <template v-slot:placeholder>
                                        <div class="pm-thumb-placeholder">
                                            <div class="pm-placeholder-icon">
                                                <v-icon size="32" color="white">mdi-video</v-icon>
                                            </div>
                                            <div class="pm-placeholder-dots">
                                                <div class="dot"></div>
                                                <div class="dot"></div>
                                                <div class="dot"></div>
                                            </div>
                                        </div>
                                    </template>
                                </v-img>
                                
                                <!-- Gradient Overlay -->
                                <div class="pm-gradient-overlay"></div>
                                
                                <!-- Play Button -->
                                <div class="pm-play-overlay">
                                    <div class="pm-play-circle">
                                        <v-icon size="24" color="white">mdi-play</v-icon>
                                        <div class="pm-play-ripple"></div>
                                    </div>
                                </div>
                                
                                <!-- Duration Badge -->
                                <div v-if="measure.duration" class="pm-duration-badge">
                                    {{ measure.duration }}
                                </div>
                                
                                <!-- Status Badge -->
                                <div class="pm-status-badge" :class="measure.is_published ? 'published' : 'draft'">
                                    <div class="pm-status-dot"></div>
                                    <span>{{ measure.is_published ? 'Visible' : 'Hidden' }}</span>
                                </div>
                                
                                <!-- Category Badge -->
                                <div v-if="measure.category" class="pm-cat-badge" :style="{ background: getCategoryGradient(measure.category) }">
                                    <v-icon size="10" color="white" class="mr-1">{{ getCategoryIcon(measure.category) }}</v-icon>
                                    <span>{{ measure.category }}</span>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="pm-card-body">
                                <div class="pm-card-header">
                                    <h3 class="pm-card-title">{{ measure.title }}</h3>
                                    <v-btn
                                        icon
                                        size="x-small"
                                        variant="text"
                                        class="pm-more-btn"
                                        @click.stop="openEditDialog(measure)"
                                    >
                                        <v-icon size="16" color="grey">mdi-dots-horizontal</v-icon>
                                    </v-btn>
                                </div>
                                <p class="pm-card-desc">{{ measure.description }}</p>
                                <div class="pm-card-meta">
                                    <div class="pm-meta-row">
                                        <div class="pm-meta-item">
                                            <div class="pm-author-avatar">
                                                <v-icon size="10" color="white">mdi-account</v-icon>
                                            </div>
                                            <span>{{ measure.author || 'Anonymous' }}</span>
                                        </div>
                                    </div>
                                    <div class="pm-meta-row">
                                        <div class="pm-meta-item">
                                            <v-icon size="12" color="grey-lighten-1">mdi-calendar-outline</v-icon>
                                            <span>{{ formatDate(measure.created_at) }}</span>
                                        </div>
                                        <div class="pm-meta-item ml-auto">
                                            <v-icon size="12" color="grey-lighten-1">mdi-eye-outline</v-icon>
                                            <span>{{ Math.floor(Math.random() * 100) + 10 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Actions -->
                            <div class="pm-card-actions">
                                <div class="pm-action-row">
                                    <v-btn 
                                        variant="tonal" 
                                        size="small" 
                                        color="primary" 
                                        rounded="lg"
                                        @click="openEditDialog(measure)"
                                        class="pm-edit-btn"
                                    >
                                        <v-icon start size="14">mdi-pencil</v-icon>
                                        Edit
                                    </v-btn>
                                    
                                    <div class="pm-quick-actions">
                                        <v-btn 
                                            icon 
                                            size="small" 
                                            variant="text" 
                                            :color="measure.is_published ? 'warning' : 'success'"
                                            @click="togglePublish(measure)"
                                            class="pm-toggle-btn"
                                        >
                                            <v-icon size="16">{{ measure.is_published ? 'mdi-eye-off-outline' : 'mdi-eye-outline' }}</v-icon>
                                            <v-tooltip activator="parent" location="top">
                                                {{ measure.is_published ? 'Hide from users' : 'Show to users' }}
                                            </v-tooltip>
                                        </v-btn>
                                        
                                        <v-btn 
                                            icon 
                                            size="small" 
                                            variant="text" 
                                            color="error"
                                            @click="confirmDelete(measure)"
                                            class="pm-delete-btn"
                                        >
                                            <v-icon size="16">mdi-delete-outline</v-icon>
                                            <v-tooltip activator="parent" location="top">
                                                Delete video
                                            </v-tooltip>
                                        </v-btn>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredMeasures.length === 0" class="pm-empty">
                    <div class="pm-empty-icon">
                        <v-icon size="44" color="grey-lighten-1">mdi-video-off-outline</v-icon>
                    </div>
                    <h3 class="text-body-1 font-weight-bold text-grey-darken-1 mb-1">No videos found</h3>
                    <p class="text-caption text-grey mb-3">
                        {{ selectedCategory !== 'all' ? 'No videos in this category.' : 'Click "Add Video" to get started.' }}
                    </p>
                    <v-btn v-if="selectedCategory !== 'all'" variant="tonal" color="primary" size="small" rounded="pill" @click="selectedCategory = 'all'">
                        View All
                    </v-btn>
                </div>
            </v-container>
        </v-main>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" :max-width="isMobile ? '100%' : 560" :fullscreen="isMobile">
            <v-card rounded="lg">
                <v-toolbar color="primary" density="compact" class="pm-dialog-toolbar">
                    <v-btn icon @click="dialog = false" :disabled="saving">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <v-toolbar-title class="text-body-1 font-weight-bold">
                        {{ isEditing ? 'Edit Video' : 'Add New Video' }}
                    </v-toolbar-title>
                    <v-spacer />
                    <v-btn 
                        variant="text"
                        :loading="saving" 
                        @click="saveMeasure"
                        :disabled="(videoSourceType === 'upload' && videoFile && videoFile.size > 100 * 1024 * 1024) || (thumbnailFile && thumbnailFile.size > 5 * 1024 * 1024)"
                    >
                        {{ isEditing ? 'Update' : 'Create' }}
                    </v-btn>
                </v-toolbar>

                <v-card-text class="pa-4">
                    <!-- VPS Upload Info -->
                    <v-alert type="info" variant="tonal" density="compact" class="mb-4" rounded="lg">
                        <div class="text-caption">
                            <strong>Limits:</strong> Videos: 100MB | Thumbnails: 5MB — Use YouTube for larger files.
                        </div>
                    </v-alert>
                    
                    <v-form ref="form">
                        <v-text-field
                            v-model="formData.title"
                            label="Title"
                            variant="outlined"
                            density="compact"
                            :rules="[v => !!v || 'Title is required']"
                            class="mb-3"
                        />
                        <v-text-field
                            v-model="formData.author"
                            label="Author"
                            variant="outlined"
                            density="compact"
                            placeholder="e.g., Red Cross Philippines"
                            prepend-inner-icon="mdi-account-outline"
                            class="mb-3"
                        />
                        <v-textarea
                            v-model="formData.description"
                            label="Description"
                            variant="outlined"
                            density="compact"
                            rows="3"
                            class="mb-3"
                        />
                        <v-select
                            v-model="formData.category"
                            :items="categoryOptions"
                            label="Category"
                            variant="outlined"
                            density="compact"
                            :rules="[v => !!v || 'Category is required']"
                            class="mb-3"
                        />

                        <!-- Video Source Type Selection -->
                        <p class="text-caption font-weight-bold text-grey-darken-2 mb-2">Video Source</p>
                        <v-radio-group v-model="videoSourceType" inline density="compact" class="mb-3" hide-details>
                            <v-radio label="Upload File" value="upload"></v-radio>
                            <v-radio label="YouTube Link" value="youtube"></v-radio>
                        </v-radio-group>

                        <!-- Video File Upload -->
                        <v-file-input
                            v-if="videoSourceType === 'upload'"
                            v-model="videoFile"
                            label="Video File"
                            variant="outlined"
                            density="compact"
                            accept="video/mp4,video/webm,video/mov,video/avi"
                            prepend-icon="mdi-video"
                            :rules="[
                                v => (!!v || !!formData.video_url || isEditing) || 'Video file is required',
                                v => !v || v.size <= 100 * 1024 * 1024 || 'Video must be 100MB or less'
                            ]"
                            :hint="videoFile ? `${videoFile.name} (${formatFileSize(videoFile.size)})` : 'Max 100MB (MP4 recommended)'"
                            :error-messages="videoFileError"
                            persistent-hint
                            show-size
                            class="mb-3"
                            @update:model-value="onVideoFileChange"
                        />
                        <v-alert v-if="videoFile && videoFile.size > 100 * 1024 * 1024" type="warning" variant="tonal" density="compact" class="mb-3" rounded="lg">
                            File too large: {{ formatFileSize(videoFile.size) }}. Max 100MB.
                        </v-alert>

                        <!-- YouTube URL -->
                        <v-text-field
                            v-if="videoSourceType === 'youtube'"
                            v-model="formData.video_url"
                            label="YouTube Video URL"
                            variant="outlined"
                            density="compact"
                            placeholder="https://youtube.com/watch?v=..."
                            prepend-inner-icon="mdi-youtube"
                            :rules="[v => (!!v || videoSourceType !== 'youtube') || 'YouTube URL is required']"
                            class="mb-3"
                        />
                       
                        <!-- Thumbnail Upload -->
                        <v-file-input
                            v-model="thumbnailFile"
                            label="Thumbnail Image (optional)"
                            variant="outlined"
                            density="compact"
                            accept="image/*"
                            prepend-icon="mdi-image"
                            :rules="[
                                v => !v || v.size <= 5 * 1024 * 1024 || 'Thumbnail must be 5MB or less'
                            ]"
                            :hint="thumbnailFile ? `${thumbnailFile.name} (${formatFileSize(thumbnailFile.size)})` : 'Auto-generated if empty. Max 5MB'"
                            persistent-hint
                            show-size
                            class="mb-3"
                            @update:model-value="onThumbnailFileChange"
                        />

                        <!-- Thumbnail Preview -->
                        <v-img
                            v-if="thumbnailPreviewUrl"
                            :src="thumbnailPreviewUrl"
                            max-height="100"
                            class="mb-3 rounded-lg"
                            cover
                        />

                        <v-switch
                            v-model="formData.is_published"
                            label="Visible to Users"
                            color="success"
                            density="compact"
                            hide-details
                        />
                        <p class="text-caption text-grey mt-1 mb-0">When enabled, users can view this video. When disabled, video is hidden but not deleted.</p>
                    </v-form>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation -->
        <v-dialog v-model="deleteDialog" max-width="380">
            <v-card rounded="lg">
                <v-card-text class="text-center pa-5">
                    <div class="pm-delete-icon mb-3">
                        <v-icon size="36" color="error">mdi-delete-alert</v-icon>
                    </div>
                    <h3 class="text-body-1 font-weight-bold mb-2">Delete Video?</h3>
                    <p class="text-caption text-grey mb-0">
                        "<strong>{{ selectedMeasure?.title }}</strong>" will be permanently removed.
                    </p>
                </v-card-text>
                <v-card-actions class="px-4 pb-4">
                    <v-btn variant="tonal" block class="mr-2" @click="deleteDialog = false" rounded="lg">Cancel</v-btn>
                    <v-btn color="error" variant="flat" block :loading="deleting" @click="deleteMeasure" rounded="lg">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Video Preview Dialog -->
        <v-dialog v-model="previewDialog" :max-width="isMobile ? '100%' : 900" :fullscreen="isMobile" class="pm-preview-dialog">
            <v-card v-if="previewMeasure" rounded="lg" class="pm-preview-card">
                <!-- Enhanced Header -->
                <div class="pm-preview-header">
                    <div class="pm-header-gradient"></div>
                    <div class="pm-header-content">
                        <div class="pm-header-left">
                            <div class="pm-preview-category" :style="{ background: getCategoryGradient(previewMeasure.category) }">
                                <v-icon size="12" color="white">{{ getCategoryIcon(previewMeasure.category) }}</v-icon>
                                <span>{{ previewMeasure.category }}</span>
                            </div>
                            <h2 class="pm-preview-title">{{ previewMeasure.title }}</h2>
                        </div>
                        <v-btn icon variant="text" @click="previewDialog = false" class="pm-close-btn">
                            <v-icon color="white">mdi-close</v-icon>
                        </v-btn>
                    </div>
                </div>
                
                <!-- Video Container -->
                <div class="pm-video-wrapper">
                    <div class="pm-video-container">
                        <video
                            v-if="isLocalVideo(previewMeasure.video_url || previewMeasure.video_path)"
                            :src="previewMeasure.video_url || (previewMeasure.video_path ? `/storage/${previewMeasure.video_path}` : '')"
                            controls
                            class="pm-video-player"
                            controlslist="nodownload"
                        />
                        <iframe
                            v-else
                            :src="getEmbedUrl(previewMeasure.video_url || previewMeasure.video_path)"
                            class="pm-video-iframe"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        />
                    </div>
                </div>
                
                <!-- Enhanced Details Section -->
                <div class="pm-preview-details">
                    <div class="pm-details-main">
                        <div class="pm-author-section">
                            <div class="pm-author-info">
                                <div class="pm-author-avatar-lg">
                                    <v-icon size="16" color="white">mdi-account</v-icon>
                                </div>
                                <div class="pm-author-text">
                                    <p class="pm-author-name">{{ previewMeasure.author || 'Anonymous Creator' }}</p>
                                    <p class="pm-publish-date">Published {{ formatDate(previewMeasure.created_at) }}</p>
                                </div>
                            </div>
                            <div class="pm-video-stats">
                                <div class="pm-stat-item">
                                    <v-icon size="14" color="grey">mdi-eye-outline</v-icon>
                                    <span>{{ Math.floor(Math.random() * 500) + 50 }} views</span>
                                </div>
                                <div class="pm-stat-item" v-if="previewMeasure.duration">
                                    <v-icon size="14" color="grey">mdi-clock-outline</v-icon>
                                    <span>{{ previewMeasure.duration }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div v-if="previewMeasure.description" class="pm-description-section">
                            <h4 class="pm-section-title">Description</h4>
                            <p class="pm-description-text">{{ previewMeasure.description }}</p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="pm-preview-actions">
                        <v-btn 
                            variant="tonal" 
                            color="primary" 
                            rounded="lg"
                            @click="previewDialog = false; openEditDialog(previewMeasure)"
                            class="pm-preview-edit"
                        >
                            <v-icon start>mdi-pencil</v-icon>
                            Edit Video
                        </v-btn>
                        <v-btn 
                            variant="outlined" 
                            :color="previewMeasure.is_published ? 'warning' : 'success'"
                            rounded="lg"
                            @click="togglePublish(previewMeasure); previewDialog = false"
                            class="pm-preview-toggle"
                        >
                            <v-icon start>{{ previewMeasure.is_published ? 'mdi-eye-off-outline' : 'mdi-eye-outline' }}</v-icon>
                            {{ previewMeasure.is_published ? 'Hide from Users' : 'Show to Users' }}
                        </v-btn>
                    </div>
                </div>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000" location="bottom right" rounded="lg">
            {{ snackbarText }}
            <template v-slot:actions>
                <v-btn variant="text" size="small" @click="snackbar = false">Close</v-btn>
            </template>
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDisplay } from 'vuetify';
import AdminAppBar from '@/Components/AdminAppBar.vue';

const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

const props = defineProps({
    measures: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] }
});

const dialog = ref(false);
const deleteDialog = ref(false);
const previewDialog = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const deleting = ref(false);
const selectedCategory = ref('all');
const selectedMeasure = ref(null);
const previewMeasure = ref(null);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');

// File upload refs
const videoSourceType = ref('upload');
const videoFile = ref(null);
const thumbnailFile = ref(null);
const videoPreviewUrl = ref('');
const thumbnailPreviewUrl = ref('');
const videoFileError = ref('');
const uploadProgress = ref(0);

const measuresList = ref(props.measures || []);
const categories = ref(props.categories || ['Fire Safety', 'Earthquake', 'First Aid', 'Evacuation', 'General Safety']);

const categoryOptions = [
    'Fire Safety',
    'Earthquake',
    'First Aid',
    'Evacuation',
    'General Safety',
    'Flood',
    'Medical Emergency'
];

const formData = ref({
    title: '',
    description: '',
    author: '',
    category: '',
    video_url: '',
    video_path: '',
    thumbnail_url: '',
    is_published: true
});

// Category helpers
const getCategoryIcon = (cat) => {
    const icons = {
        'Fire Safety': 'mdi-fire',
        'Earthquake': 'mdi-earth',
        'First Aid': 'mdi-heart-pulse',
        'Evacuation': 'mdi-exit-run',
        'General Safety': 'mdi-shield-check',
        'Flood': 'mdi-waves',
        'Medical Emergency': 'mdi-medical-bag',
        'fire': 'mdi-fire',
        'earthquake': 'mdi-earth',
        'first_aid': 'mdi-heart-pulse',
        'evacuation': 'mdi-exit-run',
        'general': 'mdi-shield-check',
        'flood': 'mdi-waves',
        'medical': 'mdi-medical-bag',
        'safety': 'mdi-shield-account',
    };
    return icons[cat] || 'mdi-tag';
};

const getCategoryGradient = (cat) => {
    const gradients = {
        'Fire Safety': 'linear-gradient(135deg, #FF6B35, #D32F2F)',
        'Earthquake': 'linear-gradient(135deg, #8D6E63, #5D4037)',
        'First Aid': 'linear-gradient(135deg, #EC407A, #AD1457)',
        'Evacuation': 'linear-gradient(135deg, #FFA726, #E65100)',
        'General Safety': 'linear-gradient(135deg, #3674B5, #13294B)',
        'Flood': 'linear-gradient(135deg, #42A5F5, #1565C0)',
        'Medical Emergency': 'linear-gradient(135deg, #EF5350, #C62828)',
        'fire': 'linear-gradient(135deg, #FF6B35, #D32F2F)',
        'earthquake': 'linear-gradient(135deg, #8D6E63, #5D4037)',
        'first_aid': 'linear-gradient(135deg, #EC407A, #AD1457)',
        'evacuation': 'linear-gradient(135deg, #FFA726, #E65100)',
        'general': 'linear-gradient(135deg, #3674B5, #13294B)',
        'flood': 'linear-gradient(135deg, #42A5F5, #1565C0)',
        'medical': 'linear-gradient(135deg, #EF5350, #C62828)',
        'safety': 'linear-gradient(135deg, #66BB6A, #2E7D32)',
    };
    return gradients[cat] || 'linear-gradient(135deg, #78909C, #546E7A)';
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
        return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch { return dateString; }
};

// File change handlers
const onVideoFileChange = (file) => {
    videoFileError.value = '';
    if (file) {
        const maxSize = 100 * 1024 * 1024;
        if (file.size > maxSize) {
            videoFileError.value = `File size (${formatFileSize(file.size)}) exceeds 100MB limit`;
        }
        videoPreviewUrl.value = URL.createObjectURL(file);
    } else {
        videoPreviewUrl.value = '';
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const onThumbnailFileChange = (file) => {
    if (file) {
        thumbnailPreviewUrl.value = URL.createObjectURL(file);
    } else {
        thumbnailPreviewUrl.value = '';
    }
};

const filteredMeasures = computed(() => {
    if (selectedCategory.value === 'all') {
        return measuresList.value;
    }
    return measuresList.value.filter(m => m.category === selectedCategory.value);
});

const fetchMeasures = async () => {
    try {
        const response = await fetch('/api/preventive-measures', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success || Array.isArray(data)) {
            measuresList.value = data.data || data;
        }
    } catch (error) {
        console.error('Error fetching measures:', error);
    }
};

const openAddDialog = () => {
    isEditing.value = false;
    formData.value = {
        title: '',
        description: '',
        author: '',
        category: '',
        video_url: '',
        video_path: '',
        thumbnail_url: '',
        is_published: true
    };
    videoSourceType.value = 'upload';
    videoFile.value = null;
    thumbnailFile.value = null;
    videoPreviewUrl.value = '';
    thumbnailPreviewUrl.value = '';
    dialog.value = true;
};

const openEditDialog = (measure) => {
    isEditing.value = true;
    selectedMeasure.value = measure;
    formData.value = { ...measure };
    if (measure.video_url && measure.video_url.includes('youtube')) {
        videoSourceType.value = 'youtube';
    } else {
        videoSourceType.value = 'upload';
    }
    videoFile.value = null;
    thumbnailFile.value = null;
    videoPreviewUrl.value = '';
    thumbnailPreviewUrl.value = '';
    dialog.value = true;
};

const saveMeasure = async () => {
    if (videoFile.value && videoFile.value.size > 100 * 1024 * 1024) {
        showSnackbar('Video file must be 100MB or less', 'error');
        return;
    }
    
    if (thumbnailFile.value && thumbnailFile.value.size > 5 * 1024 * 1024) {
        showSnackbar('Thumbnail must be 5MB or less', 'error');
        return;
    }
    
    saving.value = true;
    uploadProgress.value = 0;
    
    try {
        const url = isEditing.value ? `/preventive-measures/${selectedMeasure.value.id}` : '/preventive-measures';
        
        const submitData = new FormData();
        submitData.append('title', formData.value.title);
        submitData.append('description', formData.value.description || '');
        submitData.append('author', formData.value.author || '');
        submitData.append('category', formData.value.category);
        submitData.append('is_active', formData.value.is_published ? '1' : '0');
        
        if (videoSourceType.value === 'upload' && videoFile.value) {
            submitData.append('video', videoFile.value);
        } else if (videoSourceType.value === 'youtube' && formData.value.video_url) {
            submitData.append('video_url', formData.value.video_url);
        }
        
        if (thumbnailFile.value) {
            submitData.append('thumbnail', thumbnailFile.value);
        }
        
        if (isEditing.value) {
            submitData.append('_method', 'PUT');
        }
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: submitData
        });
        
        const data = await response.json();
        
        if (response.ok && (data.success || data.id)) {
            showSnackbar(isEditing.value ? 'Video updated successfully' : 'Video created successfully', 'success');
            dialog.value = false;
            videoFile.value = null;
            thumbnailFile.value = null;
            videoPreviewUrl.value = '';
            thumbnailPreviewUrl.value = '';
            videoFileError.value = '';
            
            // Auto-publish the video (set to visible)
            if (data.id || data.data?.id) {
                const videoId = data.id || data.data.id;
                // Auto-publish after creation/editing
                setTimeout(async () => {
                    try {
                        await fetch(`/preventive-measures/${videoId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                            },
                            body: JSON.stringify({ is_published: true })
                        });
                    } catch (error) {
                        console.log('Auto-publish error:', error);
                    }
                }, 500);
            }
            
            fetchMeasures();
        } else {
            let errorMessage = 'Error saving video';
            if (data.errors) {
                errorMessage = Object.values(data.errors).flat().join(', ');
            } else if (data.message) {
                errorMessage = data.message;
            }
            if (errorMessage.includes('max') || errorMessage.includes('size') || errorMessage.includes('large')) {
                errorMessage += ' — Try a smaller file or compress the video.';
            }
            showSnackbar(errorMessage, 'error');
            console.error('Save error details:', data);
        }
    } catch (error) {
        console.error('Error saving measure:', error);
        let errorMsg = 'Network error. ';
        if (error.message.includes('Failed to fetch')) {
            errorMsg += 'Please check your connection.';
        } else {
            errorMsg += 'Please try with a smaller file size.';
        }
        showSnackbar(errorMsg, 'error');
    } finally {
        saving.value = false;
        uploadProgress.value = 0;
    }
};

const togglePublish = async (measure) => {
    try {
        const response = await fetch(`/preventive-measures/${measure.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ is_published: !measure.is_published })
        });
        
        if (response.ok) {
            const action = measure.is_published ? 'hidden from users' : 'published to users';
            showSnackbar(`Video ${action} successfully`, 'success');
            fetchMeasures();
        } else {
            throw new Error('Failed to update video visibility');
        }
    } catch (error) {
        console.error('Error toggling publish:', error);
        showSnackbar('Error updating video visibility', 'error');
    }
};

const confirmDelete = (measure) => {
    selectedMeasure.value = measure;
    deleteDialog.value = true;
};

const deleteMeasure = async () => {
    deleting.value = true;
    try {
        const response = await fetch(`/preventive-measures/${selectedMeasure.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        if (response.ok) {
            showSnackbar('Video deleted successfully', 'success');
            deleteDialog.value = false;
            fetchMeasures();
        }
    } catch (error) {
        console.error('Error deleting measure:', error);
        showSnackbar('Error deleting video', 'error');
    } finally {
        deleting.value = false;
    }
};

const previewVideo = (measure) => {
    previewMeasure.value = measure;
    previewDialog.value = true;
};

const getThumbnail = (measure) => {
    if (measure.thumbnail_url) return measure.thumbnail_url;
    if (measure.thumbnail) {
        if (measure.thumbnail.startsWith('http')) return measure.thumbnail;
        return `/storage/${measure.thumbnail}`;
    }
    if (isLocalVideo(measure.video_url || measure.video_path)) {
        return 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180" viewBox="0 0 320 180"><rect fill="#13294B" width="320" height="180"/><polygon fill="#3674B5" points="130,60 130,120 200,90"/></svg>');
    }
    const videoId = getYouTubeId(measure.video_url || measure.video_path);
    return videoId ? `https://img.youtube.com/vi/${videoId}/hqdefault.jpg` : '';
};

const isLocalVideo = (url) => {
    return url && (url.startsWith('/storage') || url.startsWith('/videos') || url.includes('.mp4') || url.includes('.webm'));
};

const getYouTubeId = (url) => {
    if (!url) return null;
    const match = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
    return match ? match[1] : null;
};

const getEmbedUrl = (url) => {
    const videoId = getYouTubeId(url);
    return videoId ? `https://www.youtube.com/embed/${videoId}` : url;
};

const getCategoryColor = (category) => {
    const colors = {
        'Fire Safety': 'red',
        'Earthquake': 'brown',
        'First Aid': 'green',
        'Evacuation': 'orange',
        'General Safety': 'blue',
        'Flood': 'cyan',
        'Medical Emergency': 'pink'
    };
    return colors[category] || 'grey';
};

const showSnackbar = (text, color) => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};

onMounted(() => {
    if (!measuresList.value || measuresList.value.length === 0) {
        fetchMeasures();
    }
});
</script>

<style scoped>
/* PPM Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, #3674B5, #13294B);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Page Header */
.pm-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.pm-header-content {
    flex: 1;
    min-width: 180px;
}

/* Stats Row */
.pm-stats-row {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding: 2px 0;
    scrollbar-width: none;
}

.pm-stats-row::-webkit-scrollbar { display: none; }

.pm-stat-card {
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
    border-radius: 14px;
    padding: 12px 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.04);
    flex: 1;
    min-width: 120px;
}

.pm-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.pm-stat-info {
    display: flex;
    flex-direction: column;
}

.pm-stat-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1.2;
}

.pm-stat-label {
    font-size: 0.68rem;
    color: #999;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Category Filter */
.pm-category-scroll {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 4px 0;
    scrollbar-width: none;
}

.pm-category-scroll::-webkit-scrollbar { display: none; }

.pm-cat-pill {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 7px 14px;
    border-radius: 50px;
    border: 1.5px solid #e0e0e0;
    background: white;
    font-size: 0.72rem;
    font-weight: 600;
    color: #555;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.pm-cat-pill:hover {
    border-color: #3674B5;
    color: #3674B5;
    background: rgba(54, 116, 181, 0.04);
}

.pm-cat-pill.active {
    background: linear-gradient(135deg, #3674B5, #13294B);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(54, 116, 181, 0.3);
}

.pm-cat-pill.active .v-icon { color: white !important; }

/* Video Grid */
.pm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
}

.pm-card {
    background: white;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.04);
    transition: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.pm-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(54, 116, 181, 0.12);
}

/* Thumbnail */
.pm-thumb {
    position: relative;
    overflow: hidden;
    cursor: pointer;
    border-radius: 14px 14px 0 0;
}

.pm-thumb-img {
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.pm-card:hover .pm-thumb-img {
    transform: scale(1.05);
}

/* Enhanced Placeholder */
.pm-thumb-placeholder {
    position: relative;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #3674B5 0%, #13294B 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 12px;
}

.pm-placeholder-icon {
    opacity: 0.8;
    animation: pulse 2s ease-in-out infinite;
}

.pm-placeholder-dots {
    display: flex;
    gap: 4px;
}

.pm-placeholder-dots .dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    animation: dotPulse 1.5s ease-in-out infinite;
}

.pm-placeholder-dots .dot:nth-child(2) { animation-delay: 0.2s; }
.pm-placeholder-dots .dot:nth-child(3) { animation-delay: 0.4s; }

/* Gradient Overlay */
.pm-gradient-overlay {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 60%;
    background: linear-gradient(180deg, transparent 0%, rgba(19, 41, 75, 0.7) 100%);
    pointer-events: none;
    transition: opacity 0.3s ease;
}

.pm-card:hover .pm-gradient-overlay {
    opacity: 0.9;
}

.pm-play-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.pm-play-circle {
    position: relative;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(54, 116, 181, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(4px);
}

.pm-play-circle .v-icon { 
    margin-left: 2px; 
    z-index: 2;
}

.pm-play-ripple {
    position: absolute;
    top: -8px; left: -8px; right: -8px; bottom: -8px;
    border-radius: 50%;
    border: 2px solid rgba(54, 116, 181, 0.4);
    animation: ripple 2s ease-out infinite;
}

.pm-card:hover .pm-play-circle {
    transform: scale(1.15);
    background: #3674B5;
    box-shadow: 0 6px 30px rgba(54, 116, 181, 0.5);
}

/* Enhanced Badges */
.pm-duration-badge {
    position: absolute;
    bottom: 8px;
    right: 8px;
    padding: 3px 6px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    font-size: 0.65rem;
    font-weight: 600;
    border-radius: 6px;
    backdrop-filter: blur(4px);
}

.pm-status-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 0.6rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    backdrop-filter: blur(4px);
    animation: statusPulse 2s ease-in-out infinite;
}

.pm-status-badge.published {
    background: rgba(46, 125, 50, 0.9);
}

.pm-status-badge.draft {
    background: rgba(117, 117, 117, 0.8);
}

.pm-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    animation: dotBlink 1.5s ease-in-out infinite;
}

.pm-cat-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    display: flex;
    align-items: center;
    gap: 3px;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 0.6rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    backdrop-filter: blur(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Card Body */
.pm-card-body {
    padding: 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pm-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
}

.pm-card-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
    flex: 1;
}

.pm-more-btn {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.pm-card:hover .pm-more-btn {
    opacity: 1;
}

.pm-card-desc {
    font-size: 0.75rem;
    color: #777;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
}

.pm-card-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: auto;
}

.pm-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.pm-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.68rem;
    color: #999;
}

.pm-meta-item .v-icon { color: #bbb; }

.pm-author-avatar {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3674B5, #13294B);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Card Actions */
.pm-card-actions {
    padding: 12px 16px;
    border-top: 1px solid #f0f0f0;
    background: #fafafa;
}

.pm-action-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.pm-edit-btn {
    font-size: 0.75rem;
    font-weight: 600;
}

.pm-quick-actions {
    display: flex;
    gap: 4px;
}

.pm-toggle-btn,
.pm-delete-btn {
    transition: all 0.2s ease;
}

.pm-toggle-btn:hover {
    background: rgba(76, 175, 80, 0.1);
}

.pm-delete-btn:hover {
    background: rgba(244, 67, 54, 0.1);
}

/* Empty State */
.pm-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 20px;
    text-align: center;
}

.pm-empty-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
}

/* Delete Dialog */
.pm-delete-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #FFEBEE;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Dialog Toolbar */
.pm-dialog-toolbar {
    background: linear-gradient(135deg, #3674B5, #13294B) !important;
}

/* Video Container */
.pm-video-container {
    position: relative;
    width: 100%;
    padding-top: 56.25%;
    background: #000;
    overflow: hidden;
}

.pm-video-player,
.pm-video-iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 8px;
}

.pm-video-player { 
    background: #000; 
    object-fit: cover;
}

/* Enhanced Preview Modal */
.pm-preview-dialog :deep(.v-dialog) {
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.2) !important;
}

.pm-preview-card {
    overflow: hidden !important;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.15) !important;
}

.pm-preview-header {
    position: relative;
    background: linear-gradient(135deg, #3674B5 0%, #13294B 100%);
    color: white;
    padding: 20px;
    overflow: hidden;
}

.pm-header-gradient {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
    pointer-events: none;
}

.pm-header-content {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.pm-header-left {
    flex: 1;
}

.pm-preview-category {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.pm-preview-title {
    font-size: 1.25rem;
    font-weight: 800;
    margin: 0;
    line-height: 1.4;
    color: white;
}

.pm-close-btn {
    background: rgba(255, 255, 255, 0.1) !important;
    backdrop-filter: blur(8px);
}

.pm-close-btn:hover {
    background: rgba(255, 255, 255, 0.2) !important;
}

.pm-video-wrapper {
    padding: 0;
    background: #000;
}

.pm-preview-details {
    padding: 24px;
    background: #f8f9fa;
}

.pm-details-main {
    margin-bottom: 20px;
}

.pm-author-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e0e0e0;
}

.pm-author-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pm-author-avatar-lg {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3674B5, #13294B);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(54, 116, 181, 0.3);
}

.pm-author-text {
    flex: 1;
}

.pm-author-name {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 2px 0;
}

.pm-publish-date {
    font-size: 0.75rem;
    color: #777;
    margin: 0;
}

.pm-video-stats {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: flex-end;
}

.pm-stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: #777;
}

.pm-description-section {
    margin-bottom: 16px;
}

.pm-section-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 8px 0;
}

.pm-description-text {
    font-size: 0.8rem;
    color: #555;
    line-height: 1.6;
    margin: 0;
}

.pm-preview-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-start;
}

.pm-preview-edit,
.pm-preview-toggle {
    font-weight: 600;
    transition: all 0.2s ease;
}

.pm-preview-edit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(54, 116, 181, 0.3);
}

.pm-preview-toggle:hover {
    transform: translateY(-1px);
}

/* Animations */
@keyframes pulse {
    0%, 100% { opacity: 0.8; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.05); }
}

@keyframes dotPulse {
    0%, 100% { opacity: 0.6; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.2); }
}

@keyframes ripple {
    0% { 
        opacity: 1;
        transform: scale(1);
    }
    100% {
        opacity: 0;
        transform: scale(1.5);
    }
}

@keyframes statusPulse {
    0%, 100% { opacity: 0.9; }
    50% { opacity: 1; }
}

@keyframes dotBlink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* Responsive */
@media (max-width: 600px) {
    .pm-page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .pm-stats-row {
        gap: 8px;
    }
    
    .pm-stat-card {
        padding: 10px 12px;
        min-width: 100px;
    }
    
    .pm-stat-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
    }
    
    .pm-stat-icon .v-icon { font-size: 16px !important; }
    
    .pm-stat-value { font-size: 1.05rem; }
    .pm-stat-label { font-size: 0.6rem; }
    
    .pm-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .pm-cat-pill {
        padding: 6px 10px;
        font-size: 0.68rem;
    }
    
    .pm-play-circle {
        width: 44px;
        height: 44px;
    }
    
    .pm-preview-header {
        padding: 16px;
    }
    
    .pm-preview-title {
        font-size: 1.1rem;
    }
    
    .pm-preview-details {
        padding: 16px;
    }
    
    .pm-author-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .pm-video-stats {
        align-items: flex-start;
    }
    
    .pm-preview-actions {
        flex-direction: column;
    }
}

@media (min-width: 601px) and (max-width: 959px) {
    .pm-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
}

@media (min-width: 960px) and (max-width: 1279px) {
    .pm-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
}

@media (min-width: 1280px) {
    .pm-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }
    
    .pm-container {
        max-width: 1400px;
    }
}

@media (max-width: 359px) {
    .pm-stat-card {
        padding: 8px 10px;
        min-width: 90px;
    }
    
    .pm-stat-value { font-size: 0.95rem; }
    
    .pm-card-title { font-size: 0.8rem; }
    .pm-card-desc { font-size: 0.68rem; }
    
    .pm-preview-title { font-size: 1rem; }
}
</style>
