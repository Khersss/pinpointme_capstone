<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <UserAppBar 
            title="Preventive Measures" 
            subtitle="Stay Safe"
            :notification-count="0"
            @toggle-drawer="drawer = !drawer"
        >
            <template #actions>
                <v-btn icon variant="text" class="bar-btn" style="color: white;" @click="fetchMeasures">
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </template>
        </UserAppBar>

        <!-- Navigation Drawer -->
        <UserMenu v-model="drawer" />

        <v-main class="main-content">
            <v-container fluid class="pa-4">
                

                <!-- Category Filter -->
                <div class="category-scroll mb-5">
                    <button
                        :class="['category-pill', !selectedCategory ? 'active' : '']"
                        @click="selectedCategory = ''"
                    >
                        <v-icon size="16">mdi-view-grid</v-icon>
                        <span>All</span>
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat.value"
                        :class="['category-pill', selectedCategory === cat.value ? 'active' : '']"
                        @click="selectedCategory = cat.value"
                    >
                        <v-icon size="16">{{ cat.icon }}</v-icon>
                        <span>{{ cat.label }}</span>
                    </button>
                </div>

                <!-- Results Count -->
                <div class="d-flex align-center justify-space-between mb-3">
                    <span class="text-body-2 text-grey-darken-1 font-weight-medium">
                        {{ filteredMeasures.length }} {{ filteredMeasures.length === 1 ? 'video' : 'videos' }} found
                    </span>
                     
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="loading-state">
                    <v-progress-circular indeterminate color="primary" size="44" width="3" />
                    <span class="loading-text">Loading safety guides...</span>
                </div>

                <!-- Error State -->
                <v-card v-else-if="error" class="error-card rounded-xl pa-6 text-center" elevation="0">
                    <v-icon size="48" color="error" class="mb-3">mdi-alert-circle-outline</v-icon>
                    <h3 class="text-body-1 font-weight-bold mb-2">Something went wrong</h3>
                    <p class="text-body-2 text-grey mb-4">{{ error }}</p>
                    <v-btn color="primary" variant="flat" rounded="pill" @click="fetchMeasures" prepend-icon="mdi-refresh">
                        Try Again
                    </v-btn>
                </v-card>

                <!-- Measures Grid - Enhanced Cards -->
                <div v-else-if="filteredMeasures.length > 0" class="measures-grid">
                    <div 
                        v-for="measure in filteredMeasures"
                        :key="measure.id"
                        class="measure-card-wrap"
                    >
                        <div class="measure-card" @click="openVideo(measure)">
                            <!-- Thumbnail -->
                            <div class="measure-thumbnail">
                                <v-img
                                    :src="getThumbnailUrl(measure)"
                                    :aspect-ratio="16/9"
                                    cover
                                    class="thumbnail-img"
                                >
                                    <template v-slot:placeholder>
                                        <div class="d-flex align-center justify-center fill-height bg-grey-lighten-3">
                                            <v-progress-circular indeterminate color="primary" size="28" width="2" />
                                        </div>
                                    </template>
                                    <template v-slot:error>
                                        <div class="d-flex flex-column align-center justify-center fill-height bg-grey-lighten-2">
                                            <v-icon size="40" color="grey-lighten-1">mdi-video-off</v-icon>
                                        </div>
                                    </template>
                                </v-img>
                                
                                <!-- Play Button Overlay -->
                                <div class="play-overlay">
                                    <div class="play-circle">
                                        <v-icon size="24" color="white">mdi-play</v-icon>
                                    </div>
                                </div>

                                <!-- Category Badge -->
                                <div v-if="measure.category" class="category-badge" :style="{ background: getCategoryGradient(measure.category) }">
                                    <v-icon size="12" color="white" class="mr-1">{{ getCategoryIcon(measure.category) }}</v-icon>
                                    <span>{{ formatCategory(measure.category) }}</span>
                                </div>

                                <!-- Duration Badge (if available) -->
                                <div v-if="isYouTubeVideo(measure)" class="source-badge">
                                    <v-icon size="10" color="white" class="mr-1">mdi-youtube</v-icon>
                                    <span>YouTube</span>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="measure-body">
                                <h3 class="measure-title">{{ measure.title }}</h3>
                                <p class="measure-description">{{ measure.description }}</p>
                                <div class="measure-meta">
                                    <div class="meta-item">
                                        <v-icon size="12">mdi-account-outline</v-icon>
                                        <span>{{ measure.author || 'Unknown' }}</span>
                                    </div>
                                    <div class="meta-divider">•</div>
                                    <div class="meta-item">
                                        <v-icon size="12">mdi-calendar-outline</v-icon>
                                        <span>{{ formatDate(measure.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <div class="empty-icon-wrap">
                        <v-icon size="48" color="grey-lighten-1">mdi-shield-off-outline</v-icon>
                    </div>
                    <h3 class="text-body-1 font-weight-bold text-grey-darken-1 mb-1">No guides found</h3>
                    <p class="text-body-2 text-grey mb-4">
                        {{ selectedCategory ? 'No measures available for this category.' : 'No preventive measures have been added yet.' }}
                    </p>
                    <v-btn 
                        v-if="selectedCategory" 
                        variant="tonal" 
                        color="primary" 
                        rounded="pill"
                        size="small"
                        @click="selectedCategory = ''"
                    >
                        View All Categories
                    </v-btn>
                </div>
            </v-container>

            <!-- Video Modal - Content-fitted and centered -->
            <v-dialog
                v-model="showVideoModal"
                :max-width="$vuetify.display.smAndDown ? '95%' : '800px'"
                :fullscreen="false"
                content-class="video-modal-wrapper"
            >
                <v-card class="video-modal-card" elevation="8">
                    <!-- Header with gradient -->
                    <div class="video-modal-header">
                        <div class="header-gradient">
                            <div class="d-flex align-center justify-space-between pa-4">
                                <div class="modal-title-section">
                                    <div v-if="selectedMeasure?.category" class="modal-category">
                                        <v-icon size="12" class="mr-1">{{ getCategoryIcon(selectedMeasure.category) }}</v-icon>
                                        {{ formatCategory(selectedMeasure.category) }}
                                    </div>
                                </div>
                                <v-btn 
                                    icon 
                                    variant="text" 
                                    @click="closeVideo"
                                    class="modal-close-btn"
                                    size="small"
                                >
                                    <v-icon color="white">mdi-close</v-icon>
                                </v-btn>
                            </div>
                        </div>
                    </div>

                    <!-- Video Container -->
                    <div class="video-section">
                        <!-- Video Loading -->
                        <div v-if="videoLoading" class="video-loading">
                            <div class="loading-content">
                                <v-progress-circular indeterminate color="#3674B5" size="40" width="3" />
                                <span class="loading-text">Loading video...</span>
                            </div>
                        </div>

                        <!-- Video Player -->
                        <div v-if="selectedMeasure" class="video-player-container">
                            <!-- Local Video -->
                            <video
                                v-if="isLocalVideo(selectedMeasure)"
                                :src="getVideoUrl(selectedMeasure)"
                                controls
                                autoplay
                                class="responsive-video"
                                @loadeddata="videoLoading = false"
                                @error="handleVideoError"
                            ></video>

                            <!-- YouTube Embed -->
                            <iframe
                                v-else-if="isYouTubeVideo(selectedMeasure)"
                                :src="getYouTubeEmbedUrl(selectedMeasure)"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                class="responsive-video"
                                @load="videoLoading = false"
                                @error="handleVideoError"
                            ></iframe>

                            <!-- Direct Video -->
                            <video
                                v-else
                                :src="getVideoUrl(selectedMeasure)"
                                :poster="getThumbnailUrl(selectedMeasure)"
                                controls
                                autoplay
                                class="responsive-video"
                                @loadeddata="videoLoading = false"
                                @error="handleVideoError"
                            ></video>
                        </div>
                    </div>

                    <!-- Video Details - Compact design -->
                    <div v-if="selectedMeasure" class="video-details">
                        <div class="details-content">
                            <p v-if="selectedMeasure.description" class="video-description">
                                {{ selectedMeasure.description }}
                            </p>
                            <div class="video-meta">
                                <div class="meta-item">
                                    <v-icon size="14" color="#3674B5">mdi-account-circle</v-icon>
                                    <span>{{ selectedMeasure.author || 'Unknown Author' }}</span>
                                </div>
                                <div class="meta-divider">•</div>
                                <div class="meta-item">
                                    <v-icon size="14" color="#3674B5">mdi-calendar-clock</v-icon>
                                    <span>{{ formatDate(selectedMeasure.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-dialog>

            <!-- Toast -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">Close</v-btn>
                </template>
            </v-snackbar>
        </v-main>
        
        <!-- Bottom Navigation for Mobile -->
        <UserBottomNav :notification-count="0" :message-count="unreadCount" />


    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useDisplay } from 'vuetify';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserAppBar from '@/Components/Pages/User/Menu/UserAppBar.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';


// Props from Inertia
const props = defineProps({
    measures: {
        type: Array,
        default: () => [],
    },
});

const display = useDisplay();

// Unread messages count for bottom nav
const { unreadCount } = useUnreadMessages();

// Navigation
const drawer = ref(false);

// State
const measures = ref(props.measures || []);
const loading = ref(false);
const error = ref(null);
const selectedCategory = ref('');

// Video Modal
const showVideoModal = ref(false);
const selectedMeasure = ref(null);
const videoLoading = ref(false);
const videoError = ref(false);

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Category icons and colors mapping
const categoryConfig = {
    fire: { label: 'Fire Safety', icon: 'mdi-fire', color: 'deep-orange' },
    earthquake: { label: 'Earthquake', icon: 'mdi-earth', color: 'brown' },
    flood: { label: 'Flood', icon: 'mdi-waves', color: 'blue' },
    medical: { label: 'Medical', icon: 'mdi-medical-bag', color: 'red' },
    general: { label: 'General', icon: 'mdi-shield-check', color: 'teal' },
    safety: { label: 'Safety', icon: 'mdi-shield-account', color: 'green' },
    evacuation: { label: 'Evacuation', icon: 'mdi-exit-run', color: 'orange' },
    first_aid: { label: 'First Aid', icon: 'mdi-heart-pulse', color: 'pink' },
};

// Dynamic categories from measures data
const categories = computed(() => {
    const uniqueCategories = [...new Set(measures.value.map(m => m.category).filter(Boolean))];
    return uniqueCategories.map(cat => ({
        value: cat,
        label: categoryConfig[cat]?.label || formatCategory(cat),
        icon: categoryConfig[cat]?.icon || 'mdi-tag',
    }));
});

// Computed
const filteredMeasures = computed(() => {
    if (!selectedCategory.value) {
        return measures.value;
    }
    return measures.value.filter(m => m.category === selectedCategory.value);
});

// Methods
const fetchMeasures = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await fetch('/api/preventive-measures');
        const data = await response.json();
        
        if (data.success) {
            measures.value = data.data;
        } else {
            throw new Error(data.message || 'Failed to fetch measures');
        }
    } catch (err) {
        console.error('Error fetching preventive measures:', err);
        error.value = err.message || 'Failed to load preventive measures';
    } finally {
        loading.value = false;
    }
};

const getThumbnailUrl = (measure) => {
    if (measure.thumbnail_url) {
        // Check if it's a YouTube thumbnail
        if (measure.thumbnail_url.includes('youtube') || measure.thumbnail_url.includes('ytimg')) {
            return measure.thumbnail_url;
        }
        return measure.thumbnail_url;
    }
    
    if (measure.thumbnail) {
        if (measure.thumbnail.startsWith('http')) {
            return measure.thumbnail;
        }
        return `/storage/${measure.thumbnail}`;
    }
    
    // For local videos, use inline SVG placeholder
    if (measure.video_path && !measure.video_path.includes('youtube')) {
        return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 360"%3E%3Crect fill="%23e0e0e0" width="480" height="360"/%3E%3Ccircle cx="240" cy="180" r="40" fill="%23999"/%3E%3Cpolygon points="235,160 235,200 260,180" fill="%23e0e0e0"/%3E%3C/svg%3E';
    }
    
    // Try to extract YouTube thumbnail from video URL
    // Use hqdefault.jpg (480x360) which is always available, unlike maxresdefault.jpg
    if (measure.video_url && measure.video_url.includes('youtube')) {
        const videoId = extractYouTubeId(measure.video_url);
        if (videoId) {
            return `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
        }
    }
    
    return '/images/video-placeholder.png';
};

const getVideoUrl = (measure) => {
    if (measure.video_url) {
        return measure.video_url;
    }
    if (measure.video_path) {
        if (measure.video_path.startsWith('http')) {
            return measure.video_path;
        }
        // Properly encode the path for special characters
        const encodedPath = measure.video_path
            .split('/')
            .map(segment => encodeURIComponent(segment))
            .join('/');
        return `/storage/${encodedPath}`;
    }
    return '';
};

const isYouTubeVideo = (measure) => {
    const url = measure.video_url || measure.video_path || '';
    return url.includes('youtube.com') || url.includes('youtu.be') || url.includes('youtube.com/embed');
};

const isLocalVideo = (measure) => {
    const path = measure.video_path || '';
    return path && !path.includes('youtube');
};

const extractYouTubeId = (url) => {
    if (!url) return null;
    
    // Handle youtube.com/embed/ID format
    let match = url.match(/embed\/([a-zA-Z0-9_-]+)/);
    if (match) return match[1];
    
    // Handle youtube.com/watch?v=ID format
    match = url.match(/[?&]v=([a-zA-Z0-9_-]+)/);
    if (match) return match[1];
    
    // Handle youtu.be/ID format
    match = url.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
    if (match) return match[1];
    
    return null;
};

const getYouTubeEmbedUrl = (measure) => {
    const url = measure.video_url || measure.video_path || '';
    
    // If already an embed URL
    if (url.includes('/embed/')) {
        return url + '?autoplay=1';
    }
    
    const videoId = extractYouTubeId(url);
    if (videoId) {
        return `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    }
    
    return url;
};

const openVideo = (measure) => {
    selectedMeasure.value = measure;
    videoLoading.value = true;
    videoError.value = false;
    showVideoModal.value = true;
};

const closeVideo = () => {
    showVideoModal.value = false;
    selectedMeasure.value = null;
    videoLoading.value = false;
    videoError.value = false;
};

const handleVideoError = () => {
    videoLoading.value = false;
    videoError.value = true;
    console.error('Video Error:', {
        measure: selectedMeasure.value,
        url: getVideoUrl(selectedMeasure.value),
    });
};

const getCategoryColor = (category) => {
    return categoryConfig[category]?.color || 'grey';
};

const getCategoryIcon = (category) => {
    return categoryConfig[category]?.icon || 'mdi-tag';
};

const getCategoryGradient = (category) => {
    const gradients = {
        fire: 'linear-gradient(135deg, #FF6B35, #D32F2F)',
        earthquake: 'linear-gradient(135deg, #8D6E63, #5D4037)',
        flood: 'linear-gradient(135deg, #42A5F5, #1565C0)',
        medical: 'linear-gradient(135deg, #EF5350, #C62828)',
        general: 'linear-gradient(135deg, #26A69A, #00695C)',
        safety: 'linear-gradient(135deg, #66BB6A, #2E7D32)',
        evacuation: 'linear-gradient(135deg, #FFA726, #E65100)',
        first_aid: 'linear-gradient(135deg, #EC407A, #AD1457)',
    };
    return gradients[category] || 'linear-gradient(135deg, #78909C, #546E7A)';
};

const formatCategory = (category) => {
    if (!category) return '';
    return category.charAt(0).toUpperCase() + category.slice(1);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return dateString;
    }
};

const showNotification = (message, color = 'success') => {
    toastMessage.value = message;
    toastColor.value = color;
    showToast.value = true;
};

// Lifecycle
onMounted(() => {
    if (!measures.value || measures.value.length === 0) {
        fetchMeasures();
    }
});
</script>

<style scoped>
/* Hero Section */
.hero-section {
    text-align: center;
    padding: 24px 16px 20px;
    background: linear-gradient(135deg, rgba(54, 116, 181, 0.08), rgba(54, 116, 181, 0.03));
    border-radius: 20px;
    border: 1px solid rgba(54, 116, 181, 0.1);
}

.hero-icon-wrap {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: linear-gradient(135deg, #3674B5, #2196F3);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    box-shadow: 0 8px 24px rgba(54, 116, 181, 0.3);
}

.hero-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 6px;
}

.hero-subtitle {
    font-size: 0.82rem;
    color: #666;
    margin: 0;
    max-width: 300px;
    margin: 0 auto;
    line-height: 1.5;
}

/* Category Filter */
.category-scroll {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 4px 0;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.category-scroll::-webkit-scrollbar {
    display: none;
}

.category-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 50px;
    border: 1.5px solid #e0e0e0;
    background: white;
    font-size: 0.78rem;
    font-weight: 600;
    color: #555;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.category-pill:hover {
    border-color: #3674B5;
    color: #3674B5;
    background: rgba(54, 116, 181, 0.04);
}

.category-pill.active {
    background: #3674B5;
    color: white;
    border-color: #3674B5;
    box-shadow: 0 4px 12px rgba(54, 116, 181, 0.3);
}

.category-pill.active .v-icon {
    color: white !important;
}

/* Loading */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    gap: 16px;
}

.loading-text {
    font-size: 0.85rem;
    color: #999;
    font-weight: 500;
}

/* Error Card */
.error-card {
    background: #FFF5F5 !important;
    border: 1px solid #FFCDD2;
}

/* Measures Grid */
.measures-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}

.measure-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.04);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.measure-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.measure-card:active {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

/* Thumbnail */
.measure-thumbnail {
    position: relative;
    overflow: hidden;
}

.thumbnail-img {
    transition: transform 0.4s ease;
}

.measure-card:hover .thumbnail-img {
    transform: scale(1.03);
}

/* Play Overlay */
.play-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, transparent 40%, rgba(0, 0, 0, 0.4) 100%);
    transition: background 0.3s ease;
}

.measure-card:hover .play-overlay {
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.5) 100%);
}

.play-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(54, 116, 181, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(8px);
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.measure-card:hover .play-circle {
    transform: scale(1.12);
    background: rgba(54, 116, 181, 1);
    box-shadow: 0 6px 24px rgba(54, 116, 181, 0.4);
}

.play-circle .v-icon {
    margin-left: 2px; /* optical centering */
}

/* Category Badge */
.category-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    display: flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 700;
    color: white;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    backdrop-filter: blur(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Source Badge */
.source-badge {
    position: absolute;
    bottom: 10px;
    right: 10px;
    display: flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.6rem;
    font-weight: 600;
    color: white;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

/* Card Body */
.measure-body {
    padding: 14px 16px 16px;
}

.measure-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.35;
}

.measure-description {
    font-size: 0.78rem;
    color: #777;
    margin-bottom: 10px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
}

.measure-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    color: #999;
}

.meta-item .v-icon {
    color: #bbb;
}

.meta-divider {
    color: #ccc;
    font-size: 0.6rem;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 20px;
    text-align: center;
}

.empty-icon-wrap {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

/* Enhanced Video Modal - Content-fitted */
.video-modal-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2vh 2vw;
}

.video-modal-card {
    overflow: hidden;
    border-radius: 16px !important;
    background: white;
    width: 100%;
    max-width: 100%;
    margin: 0;
}

/* Remove old modal classes */
.video-modal-desktop,
.video-modal-mobile {
    /* These are now handled by the wrapper */
}

/* Modal Header */
.video-modal-header {
    position: relative;
    overflow: hidden;
}

.header-gradient {
    background: linear-gradient(135deg, #3674B5 0%, #13294B 100%);
    position: relative;
}

.header-gradient::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
    pointer-events: none;
}

.modal-title-section {
    flex: 1;
    min-width: 0;
}

.modal-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: white;
    margin: 0 0 4px 0;
    line-height: 1.3;
    /* Truncate long titles */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 250px;
}

.modal-category {
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.modal-close-btn {
    background: rgba(255, 255, 255, 0.15) !important;
    backdrop-filter: blur(8px);
    transition: all 0.2s ease;
}

.modal-close-btn:hover {
    background: rgba(255, 255, 255, 0.25) !important;
    transform: scale(1.05);
}

/* Video Section */
.video-section {
    position: relative;
    background: #000;
}

.video-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.loading-text {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 500;
}

.video-player-container {
    position: relative;
    width: 100%;
    /* Responsive aspect ratio */
    aspect-ratio: 16 / 9;
    background: #000;
    overflow: hidden;
    max-height: 60vh;
}

.responsive-video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #000;
}

/* Video Details - Content based */
.video-details {
    border-top: 1px solid rgba(54, 116, 181, 0.1);
    background: linear-gradient(180deg, #ffffff 0%, #fafafb 100%);
}

.details-content {
    padding: 16px 20px;
}

.video-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 12px 0;
    line-height: 1.4;
}

.video-description {
    font-size: 0.9rem;
    color: #4a5568;
    margin: 0 0 12px 0;
    line-height: 1.5;
}

.video-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #718096;
    font-weight: 500;
}

.meta-divider {
    color: #CBD5E0;
    font-size: 0.8rem;
    user-select: none;
}

/* Mobile Optimizations - Content based */
@media (max-width: 600px) {
    .video-modal-wrapper {
        padding: 1vh 1vw;
        align-items: flex-start;
        padding-top: 5vh;
    }
    
    .video-modal-card {
        border-radius: 12px !important;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .video-player-container {
        max-height: 50vh;
        aspect-ratio: 16 / 9;
    }
    
    .modal-title {
        font-size: 1rem;
        max-width: 200px;
    }
    
    .details-content {
        padding: 16px;
    }
    
    .video-description {
        font-size: 0.9rem;
        line-height: 1.6;
    }
    
    .video-loading {
        min-height: 200px;
    }
}

/* Landscape phone optimization */
@media (max-width: 900px) and (orientation: landscape) {
    .video-modal-wrapper {
        padding: 2vh 2vw;
        align-items: center;
    }
    
    .video-modal-card {
        max-height: 95vh;
        overflow-y: auto;
    }
    
    .video-player-container {
        max-height: 45vh;
    }
    
    .details-content {
        padding: 12px 16px;
    }
    
    .video-description {
        font-size: 0.85rem;
        line-height: 1.4;
    }
}

/* Tablet and desktop */
@media (min-width: 601px) {
    .video-player-container {
        min-height: 300px;
        max-height: 500px;
    }
    
    .modal-title {
        max-width: 400px;
    }
    
    .video-modal-wrapper {
        padding: 3vh 3vw;
    }
}

/* Main Content */
.main-content {
    -webkit-overflow-scrolling: touch;
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px) !important;
}

/* Global modal overrides */
:deep(.v-dialog) {
    align-items: center;
    justify-content: center;
}

:deep(.v-overlay__content) {
    margin: 0 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

:deep(.video-modal-wrapper) {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Tablet: 2-column grid */
@media (min-width: 600px) {
    .measures-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .hero-subtitle {
        max-width: 400px;
    }
}

/* Desktop: 3-column grid */
@media (min-width: 1024px) {
    .measures-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    
    .hero-section {
        padding: 32px 24px;
    }
    
    .hero-title {
        font-size: 1.6rem;
    }
    
    .desktop-only {
        display: flex;
    }
    
    .main-content {
        padding-bottom: 0 !important;
    }
    
    /* Larger video modal on desktop */
    .video-player-container {
        min-height: 400px;
        max-height: 600px;
    }
}

@media (max-width: 1023px) {
    .desktop-only {
        display: none !important;
    }
    
    .main-content {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px) !important;
    }
    
    .main-content :deep(.v-container) {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 120px) !important;
    }
}

@media (max-width: 600px) {
    .main-content {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 140px) !important;
    }
    
    .main-content :deep(.v-container) {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 140px) !important;
    }
    
    .hero-section {
        padding: 20px 12px 16px;
    }
    
    .hero-icon-wrap {
        width: 52px;
        height: 52px;
        border-radius: 14px;
    }
    
    .hero-icon-wrap .v-icon {
        font-size: 28px !important;
    }
    
    .hero-title {
        font-size: 1.2rem;
    }
    
    .category-pill {
        padding: 6px 12px;
        font-size: 0.72rem;
    }
    
    .play-circle {
        width: 42px;
        height: 42px;
    }
    
    .play-circle .v-icon {
        font-size: 20px !important;
    }
}

@media (max-width: 359px) {
    .hero-title {
        font-size: 1.1rem;
    }
    
    .hero-subtitle {
        font-size: 0.75rem;
    }
}
</style>
