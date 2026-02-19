<template>
    <v-app class="bg-grey-lighten-4">
        <AdminAppBar activePage="feedbacks" />

        <v-main>
            <v-container fluid :class="isMobile ? 'pa-3' : 'pa-6'">
                <!-- Page Header -->
                <div class="page-header mb-4 mb-md-6">
                    <div class="page-header-content">
                        <h1 :class="isMobile ? 'text-h5' : 'text-h4'" class="font-weight-bold">Feedback & Ratings</h1>
                        <p class="text-grey mt-1 text-body-2">View and analyze rescue feedback from users</p>
                    </div>
                </div>

                <!-- Tab Switcher -->
                <v-tabs v-model="activeTab" color="#3674B5" class="mb-4" grow>
                    <v-tab value="rescue">
                        <v-icon start>mdi-lifebuoy</v-icon>
                        Rescue Feedback
                    </v-tab>
                    <v-tab value="system">
                        <v-icon start>mdi-bug-outline</v-icon>
                        Bug Reports & Suggestions
                    </v-tab>
                </v-tabs>

                <!-- ===================== RESCUE FEEDBACK TAB ===================== -->
                <div v-show="activeTab === 'rescue'">

                <!-- Stats Overview Cards -->
                <v-row class="mb-4 mb-md-6">
                    <v-col cols="6" sm="4" md="2">
                        <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                            <div class="stat-icon-wrap mb-2">
                                <v-icon size="28" color="primary">mdi-message-star</v-icon>
                            </div>
                            <div class="text-h5 font-weight-bold" style="color: #3674B5;">{{ stats.total_feedbacks || 0 }}</div>
                            <div class="text-caption text-grey">Total Reviews</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                            <div class="stat-icon-wrap mb-2">
                                <v-icon size="28" color="amber-darken-2">mdi-star</v-icon>
                            </div>
                            <div class="text-h5 font-weight-bold" style="color: #F9A825;">{{ stats.average_overall || '—' }}</div>
                            <div class="text-caption text-grey">Avg Rating</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                            <div class="stat-icon-wrap mb-2">
                                <v-icon size="28" color="success">mdi-thumb-up</v-icon>
                            </div>
                            <div class="text-h5 font-weight-bold" style="color: #388E3C;">{{ stats.would_recommend_percent || 0 }}%</div>
                            <div class="text-caption text-grey">Recommend</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                            <div class="stat-icon-wrap mb-2">
                                <v-icon size="28" color="teal">mdi-shield-check</v-icon>
                            </div>
                            <div class="text-h5 font-weight-bold" style="color: #00796B;">{{ stats.feeling_safe_percent || 0 }}%</div>
                            <div class="text-caption text-grey">Feel Safe</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                            <div class="stat-icon-wrap mb-2">
                                <v-icon size="28" color="green">mdi-timer-check</v-icon>
                            </div>
                            <div class="text-h5 font-weight-bold" style="color: #2E7D32;">{{ stats.average_response_time || '—' }}</div>
                            <div class="text-caption text-grey">Avg Response</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                            <div class="stat-icon-wrap mb-2">
                                <v-icon size="28" color="purple">mdi-chat-processing</v-icon>
                            </div>
                            <div class="text-h5 font-weight-bold" style="color: #7B1FA2;">{{ stats.average_communication || '—' }}</div>
                            <div class="text-caption text-grey">Avg Comm.</div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Rating Distribution + Tag Distribution + Filters -->
                <v-row class="mb-6">
                    <!-- Rating Distribution -->
                    <v-col cols="12" md="4">
                        <v-card rounded="lg" elevation="0">
                            <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
                                <v-icon start color="amber-darken-2">mdi-chart-bar</v-icon>
                                Rating Distribution
                            </v-card-title>
                            <v-card-text>
                                <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="rating-row mb-2">
                                    <div class="rating-label">
                                        <span class="font-weight-medium">{{ star }}</span>
                                        <v-icon size="14" color="amber-darken-2" class="ml-1">mdi-star</v-icon>
                                    </div>
                                    <div class="rating-bar-wrap">
                                        <div class="rating-bar" :style="{ width: getRatingPercent(star) + '%' }" :class="`rating-bar-${star}`"></div>
                                    </div>
                                    <div class="rating-count">{{ stats.rating_distribution?.[star] || 0 }}</div>
                                </div>
                            </v-card-text>
                        </v-card>

                        <!-- Tag Distribution -->
                        <v-card rounded="lg" elevation="0" class="mt-3" v-if="Object.keys(stats.tag_distribution || {}).length > 0">
                            <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
                                <v-icon start color="primary">mdi-tag-multiple</v-icon>
                                Feedback Tags
                            </v-card-title>
                            <v-card-text>
                                <div v-for="(count, tag) in stats.tag_distribution" :key="tag" class="tag-dist-row mb-2">
                                    <div class="tag-dist-label">
                                        <v-icon size="14" :color="getTagColor(tag)" class="mr-1">{{ getTagIcon(tag) }}</v-icon>
                                        <span class="font-weight-medium text-caption">{{ tag }}</span>
                                    </div>
                                    <div class="tag-dist-bar-wrap">
                                        <div class="tag-dist-bar" :style="{ width: getTagPercent(count) + '%', background: getTagColor(tag) }"></div>
                                    </div>
                                    <div class="tag-dist-count">{{ count }}</div>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Filters -->
                    <v-col cols="12" md="8">
                        <v-card rounded="lg" elevation="0">
                            <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
                                <v-icon start color="primary">mdi-filter-variant</v-icon>
                                Filters
                            </v-card-title>
                            <v-card-text>
                                <v-row dense>
                                    <v-col cols="12" sm="6">
                                        <v-select
                                            v-model="selectedRescuer"
                                            :items="rescuerOptions"
                                            item-title="label"
                                            item-value="value"
                                            label="Filter by Rescuer"
                                            variant="outlined"
                                            density="compact"
                                            clearable
                                            hide-details
                                            @update:model-value="fetchFeedbacks"
                                        />
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <v-text-field
                                            v-model="dateFrom"
                                            label="From"
                                            type="date"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                            @update:model-value="fetchFeedbacks"
                                        />
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <v-text-field
                                            v-model="dateTo"
                                            label="To"
                                            type="date"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                            @update:model-value="fetchFeedbacks"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="searchQuery"
                                            label="Search feedbacks..."
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                            prepend-inner-icon="mdi-magnify"
                                            clearable
                                        />
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <v-select
                                            v-model="ratingFilter"
                                            :items="ratingFilterOptions"
                                            item-title="label"
                                            item-value="value"
                                            label="Rating"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                            clearable
                                        />
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <v-btn block color="grey-lighten-1" variant="outlined" density="compact" @click="resetFilters" class="mt-1">
                                            <v-icon start>mdi-refresh</v-icon>
                                            Reset
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Feedbacks List -->
                <v-card rounded="lg" elevation="0">
                    <v-card-title class="d-flex align-center justify-space-between">
                        <div>
                            <v-icon start color="primary">mdi-format-list-bulleted</v-icon>
                            <span class="text-subtitle-1 font-weight-bold">All Feedback ({{ filteredFeedbacks.length }})</span>
                        </div>
                    </v-card-title>

                    <!-- Loading -->
                    <v-card-text v-if="loading" class="text-center py-8">
                        <v-progress-circular indeterminate color="primary" size="40" />
                        <p class="mt-3 text-grey">Loading feedback data...</p>
                    </v-card-text>

                    <!-- Empty state -->
                    <v-card-text v-else-if="filteredFeedbacks.length === 0" class="text-center py-8">
                        <v-icon size="48" color="grey-lighten-1">mdi-message-off-outline</v-icon>
                        <p class="mt-3 text-grey">No feedback found</p>
                    </v-card-text>

                    <!-- Feedbacks -->
                    <v-card-text v-else class="pa-0">
                        <div
                            v-for="feedback in paginatedFeedbacks"
                            :key="feedback.id"
                            class="feedback-item"
                            @click="viewFeedback(feedback)"
                        >
                            <div class="feedback-header">
                                <div class="feedback-user">
                                    <v-avatar :color="getAvatarColor(feedback.user)" size="36" class="mr-3">
                                        <span class="text-white text-caption font-weight-bold">{{ getInitials(feedback.user) }}</span>
                                    </v-avatar>
                                    <div>
                                        <div class="font-weight-medium text-body-2">
                                            {{ getUserName(feedback.user) }}
                                        </div>
                                        <div class="text-caption text-grey">
                                            {{ formatDate(feedback.created_at) }}
                                            <span v-if="feedback.rescue_request">
                                                · Code: {{ feedback.rescue_request.rescue_code }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="feedback-rating-badge">
                                    <v-icon size="16" color="amber-darken-2" class="mr-1">mdi-star</v-icon>
                                    <span class="font-weight-bold" :style="{ color: getRatingColor(feedback.overall_rating) }">
                                        {{ feedback.overall_rating }}/5
                                    </span>
                                </div>
                            </div>

                            <!-- Rating bars -->
                            <div v-if="feedback.feedback_tags && feedback.feedback_tags.length" class="feedback-ratings-grid mt-2">
                                <v-chip
                                    v-for="tag in (Array.isArray(feedback.feedback_tags) ? feedback.feedback_tags : [])"
                                    :key="tag"
                                    size="x-small"
                                    :color="getTagColor(tag)"
                                    variant="tonal"
                                    class="mr-1 mb-1"
                                >
                                    <v-icon start size="10">{{ getTagIcon(tag) }}</v-icon>
                                    {{ tag }}
                                </v-chip>
                            </div>

                            <!-- Comment -->
                            <div v-if="feedback.comments" class="feedback-comment mt-2">
                                <v-icon size="12" color="grey" class="mr-1">mdi-format-quote-open</v-icon>
                                {{ feedback.comments.length > 120 ? feedback.comments.substring(0, 120) + '...' : feedback.comments }}
                            </div>

                            <!-- Tags -->
                            <div class="feedback-tags mt-2">
                                <v-chip v-if="feedback.would_recommend" size="x-small" color="success" variant="tonal" class="mr-1">
                                    <v-icon start size="10">mdi-thumb-up</v-icon>
                                    Would Recommend
                                </v-chip>
                                <v-chip v-if="feedback.feeling_safe_now !== null && feedback.feeling_safe_now !== undefined" size="x-small" :color="feedback.feeling_safe_now ? 'teal' : 'error'" variant="tonal" class="mr-1">
                                    <v-icon start size="10">{{ feedback.feeling_safe_now ? 'mdi-shield-check' : 'mdi-shield-alert' }}</v-icon>
                                    {{ feedback.feeling_safe_now ? 'Feels Safe' : 'Doesn\'t Feel Safe' }}
                                </v-chip>
                                <v-chip v-if="feedback.liked_most" size="x-small" color="primary" variant="tonal" class="mr-1">
                                    <v-icon start size="10">mdi-heart</v-icon>
                                    {{ feedback.liked_most }}
                                </v-chip>
                                <v-chip v-if="feedback.rescuer" size="x-small" color="info" variant="tonal">
                                    <v-icon start size="10">mdi-lifebuoy</v-icon>
                                    {{ getRescuerName(feedback.rescuer) }}
                                </v-chip>
                            </div>
                        </div>
                    </v-card-text>

                    <!-- Pagination -->
                    <v-card-actions v-if="feedbackTotalPages > 1" class="justify-center py-3">
                        <v-pagination
                            v-model="currentPage"
                            :length="feedbackTotalPages"
                            :total-visible="isMobile ? 3 : 7"
                            density="compact"
                            rounded="circle"
                            color="primary"
                        />
                    </v-card-actions>
                </v-card>

                <!-- Feedback Detail Dialog -->
                <v-dialog v-model="showDetailDialog" :width="isMobile ? '95%' : 600" rounded="lg">
                    <v-card v-if="selectedFeedback" rounded="lg">
                        <v-card-title class="d-flex align-center pa-4" style="background: linear-gradient(135deg, #3674B5 0%, #2d5f96 100%); color: white;">
                            <v-icon color="white" class="mr-2">mdi-message-star</v-icon>
                            Feedback Detail
                            <v-spacer />
                            <v-btn icon variant="text" density="compact" @click="showDetailDialog = false">
                                <v-icon color="white">mdi-close</v-icon>
                            </v-btn>
                        </v-card-title>
                        <v-card-text class="pa-4">
                            <!-- User & Rescuer -->
                            <div class="d-flex align-center mb-4">
                                <v-avatar :color="getAvatarColor(selectedFeedback.user)" size="40" class="mr-3">
                                    <span class="text-white font-weight-bold">{{ getInitials(selectedFeedback.user) }}</span>
                                </v-avatar>
                                <div>
                                    <div class="font-weight-bold">{{ getUserName(selectedFeedback.user) }}</div>
                                    <div class="text-caption text-grey">
                                        {{ formatDate(selectedFeedback.created_at) }}
                                        <span v-if="selectedFeedback.rescue_request">
                                            · Rescue Code: {{ selectedFeedback.rescue_request.rescue_code }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <v-divider class="mb-4" />

                            <!-- Rescuer Info -->
                            <div v-if="selectedFeedback.rescuer" class="mb-4">
                                <p class="text-caption text-grey mb-1">Rescuer</p>
                                <div class="d-flex align-center">
                                    <v-icon size="18" color="primary" class="mr-2">mdi-lifebuoy</v-icon>
                                    <span class="font-weight-medium">{{ getRescuerName(selectedFeedback.rescuer) }}</span>
                                </div>
                            </div>

                            <!-- Overall Rating -->
                            <div class="text-center mb-4">
                                <div class="text-h3 font-weight-bold" :style="{ color: getRatingColor(selectedFeedback.overall_rating) }">
                                    {{ selectedFeedback.overall_rating }}
                                </div>
                                <div class="mb-1">
                                    <v-icon v-for="s in 5" :key="s" size="28" :color="s <= selectedFeedback.overall_rating ? 'amber-darken-2' : 'grey-lighten-2'">mdi-star</v-icon>
                                </div>
                                <div class="text-caption text-grey">Overall Rating</div>
                            </div>

                            <!-- Feedback Tags -->
                            <div v-if="selectedFeedback.feedback_tags && selectedFeedback.feedback_tags.length" class="mb-4">
                                <p class="text-caption text-grey mb-2">Performance Highlights</p>
                                <div class="d-flex flex-wrap ga-2">
                                    <v-chip
                                        v-for="tag in (Array.isArray(selectedFeedback.feedback_tags) ? selectedFeedback.feedback_tags : [])"
                                        :key="tag"
                                        :color="getTagColor(tag)"
                                        variant="tonal"
                                        size="small"
                                    >
                                        <v-icon start size="14">{{ getTagIcon(tag) }}</v-icon>
                                        {{ tag }}
                                    </v-chip>
                                </div>
                            </div>

                            <!-- Feeling Safe -->
                            <div v-if="selectedFeedback.feeling_safe_now !== null && selectedFeedback.feeling_safe_now !== undefined" class="mb-4">
                                <v-card variant="tonal" :color="selectedFeedback.feeling_safe_now ? 'teal' : 'error'" rounded="lg" class="pa-3">
                                    <div class="d-flex align-center">
                                        <v-icon size="20" :color="selectedFeedback.feeling_safe_now ? 'teal' : 'error'" class="mr-2">
                                            {{ selectedFeedback.feeling_safe_now ? 'mdi-shield-check' : 'mdi-shield-alert' }}
                                        </v-icon>
                                        <span class="font-weight-medium text-body-2">
                                            {{ selectedFeedback.feeling_safe_now ? 'User feels safe after rescue' : 'User does not feel safe - may need follow-up' }}
                                        </span>
                                    </div>
                                </v-card>
                            </div>

                            <!-- Comment -->
                            <div v-if="selectedFeedback.comments" class="mb-4">
                                <p class="text-caption text-grey mb-1">Comment</p>
                                <v-card variant="tonal" color="grey-lighten-4" rounded="lg" class="pa-3">
                                    <v-icon size="14" color="grey" class="mr-1">mdi-format-quote-open</v-icon>
                                    {{ selectedFeedback.comments }}
                                </v-card>
                            </div>

                            <!-- Tags -->
                            <div class="d-flex flex-wrap ga-2">
                                <v-chip v-if="selectedFeedback.would_recommend" color="success" variant="tonal" size="small">
                                    <v-icon start size="14">mdi-thumb-up</v-icon>
                                    Would Recommend
                                </v-chip>
                                <v-chip v-else color="error" variant="tonal" size="small">
                                    <v-icon start size="14">mdi-thumb-down</v-icon>
                                    Would Not Recommend
                                </v-chip>
                                <v-chip v-if="selectedFeedback.liked_most" color="primary" variant="tonal" size="small">
                                    <v-icon start size="14">mdi-heart</v-icon>
                                    Most liked: {{ selectedFeedback.liked_most }}
                                </v-chip>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-dialog>

                </div><!-- END rescue tab -->

                <!-- ===================== BUG REPORTS & SUGGESTIONS TAB ===================== -->
                <div v-show="activeTab === 'system'">

                    <!-- System Stats Cards -->
                    <v-row class="mb-4 mb-md-6">
                        <v-col cols="6" sm="4" md="2">
                            <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                                <div class="stat-icon-wrap mb-2">
                                    <v-icon size="28" color="primary">mdi-clipboard-list</v-icon>
                                </div>
                                <div class="text-h5 font-weight-bold" style="color: #3674B5;">{{ sysStats.total || 0 }}</div>
                                <div class="text-caption text-grey">Total Reports</div>
                            </v-card>
                        </v-col>
                        <v-col cols="6" sm="4" md="2">
                            <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                                <div class="stat-icon-wrap mb-2">
                                    <v-icon size="28" color="error">mdi-bug</v-icon>
                                </div>
                                <div class="text-h5 font-weight-bold" style="color: #C62828;">{{ sysStats.bugs || 0 }}</div>
                                <div class="text-caption text-grey">Bug Reports</div>
                            </v-card>
                        </v-col>
                        <v-col cols="6" sm="4" md="2">
                            <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                                <div class="stat-icon-wrap mb-2">
                                    <v-icon size="28" color="amber-darken-2">mdi-lightbulb-on</v-icon>
                                </div>
                                <div class="text-h5 font-weight-bold" style="color: #F9A825;">{{ sysStats.improvements || 0 }}</div>
                                <div class="text-caption text-grey">Suggestions</div>
                            </v-card>
                        </v-col>
                        <v-col cols="6" sm="4" md="2">
                            <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                                <div class="stat-icon-wrap mb-2">
                                    <v-icon size="28" color="orange">mdi-alert-circle-outline</v-icon>
                                </div>
                                <div class="text-h5 font-weight-bold" style="color: #EF6C00;">{{ sysStats.open || 0 }}</div>
                                <div class="text-caption text-grey">Open</div>
                            </v-card>
                        </v-col>
                        <v-col cols="6" sm="4" md="2">
                            <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                                <div class="stat-icon-wrap mb-2">
                                    <v-icon size="28" color="info">mdi-magnify-scan</v-icon>
                                </div>
                                <div class="text-h5 font-weight-bold" style="color: #1565C0;">{{ sysStats.in_review || 0 }}</div>
                                <div class="text-caption text-grey">In Review</div>
                            </v-card>
                        </v-col>
                        <v-col cols="6" sm="4" md="2">
                            <v-card rounded="lg" class="stat-card text-center pa-3 pa-md-4" elevation="0">
                                <div class="stat-icon-wrap mb-2">
                                    <v-icon size="28" color="success">mdi-check-circle</v-icon>
                                </div>
                                <div class="text-h5 font-weight-bold" style="color: #2E7D32;">{{ sysStats.resolved || 0 }}</div>
                                <div class="text-caption text-grey">Resolved</div>
                            </v-card>
                        </v-col>
                    </v-row>

                    <!-- System Filters + Area Distribution -->
                    <v-row class="mb-6">
                        <!-- Area Distribution -->
                        <v-col cols="12" md="4">
                            <v-card rounded="lg" elevation="0" v-if="sysStats.area_distribution && Object.keys(sysStats.area_distribution).length > 0">
                                <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
                                    <v-icon start color="primary">mdi-chart-pie</v-icon>
                                    Reports by Area
                                </v-card-title>
                                <v-card-text>
                                    <div v-for="(count, area) in sysStats.area_distribution" :key="area" class="tag-dist-row mb-2">
                                        <div class="tag-dist-label">
                                            <v-icon size="14" :color="getSysAreaColor(area)" class="mr-1">{{ getSysAreaIcon(area) }}</v-icon>
                                            <span class="font-weight-medium text-caption">{{ area }}</span>
                                        </div>
                                        <div class="tag-dist-bar-wrap">
                                            <div class="tag-dist-bar" :style="{ width: getSysAreaPercent(count) + '%', background: getSysAreaColor(area) }"></div>
                                        </div>
                                        <div class="tag-dist-count">{{ count }}</div>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <!-- Filters -->
                        <v-col cols="12" md="8">
                            <v-card rounded="lg" elevation="0">
                                <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
                                    <v-icon start color="primary">mdi-filter-variant</v-icon>
                                    Filters
                                </v-card-title>
                                <v-card-text>
                                    <v-row dense>
                                        <v-col cols="12" sm="4">
                                            <v-select
                                                v-model="sysCategoryFilter"
                                                :items="sysCategoryOptions"
                                                item-title="label"
                                                item-value="value"
                                                label="Category"
                                                variant="outlined"
                                                density="compact"
                                                clearable
                                                hide-details
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <v-select
                                                v-model="sysAreaFilter"
                                                :items="sysAreaOptions"
                                                item-title="label"
                                                item-value="value"
                                                label="Area"
                                                variant="outlined"
                                                density="compact"
                                                clearable
                                                hide-details
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <v-select
                                                v-model="sysStatusFilter"
                                                :items="sysStatusOptions"
                                                item-title="label"
                                                item-value="value"
                                                label="Status"
                                                variant="outlined"
                                                density="compact"
                                                clearable
                                                hide-details
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="8">
                                            <v-text-field
                                                v-model="sysSearchQuery"
                                                label="Search reports..."
                                                variant="outlined"
                                                density="compact"
                                                hide-details
                                                prepend-inner-icon="mdi-magnify"
                                                clearable
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <v-btn block color="grey-lighten-1" variant="outlined" density="compact" @click="resetSysFilters" class="mt-1">
                                                <v-icon start>mdi-refresh</v-icon>
                                                Reset
                                            </v-btn>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>

                    <!-- System Feedbacks List -->
                    <v-card rounded="lg" elevation="0">
                        <v-card-title class="d-flex align-center justify-space-between">
                            <div>
                                <v-icon start color="primary">mdi-format-list-bulleted</v-icon>
                                <span class="text-subtitle-1 font-weight-bold">All Reports ({{ filteredSysFeedbacks.length }})</span>
                            </div>
                        </v-card-title>

                        <!-- Loading -->
                        <v-card-text v-if="sysLoading" class="text-center py-8">
                            <v-progress-circular indeterminate color="primary" size="40" />
                            <p class="mt-3 text-grey">Loading reports...</p>
                        </v-card-text>

                        <!-- Empty state -->
                        <v-card-text v-else-if="filteredSysFeedbacks.length === 0" class="text-center py-8">
                            <v-icon size="48" color="grey-lighten-1">mdi-clipboard-off-outline</v-icon>
                            <p class="mt-3 text-grey">No reports found</p>
                        </v-card-text>

                        <!-- Reports -->
                        <v-card-text v-else class="pa-0">
                            <div
                                v-for="report in paginatedSysFeedbacks"
                                :key="report.id"
                                class="feedback-item"
                                @click="viewSysReport(report)"
                            >
                                <div class="feedback-header">
                                    <div class="feedback-user">
                                        <v-avatar :color="getAvatarColor(report.user)" size="36" class="mr-3">
                                            <span class="text-white text-caption font-weight-bold">{{ getInitials(report.user) }}</span>
                                        </v-avatar>
                                        <div>
                                            <div class="d-flex align-center ga-1">
                                                <span class="font-weight-medium text-body-2">{{ getUserName(report.user) }}</span>
                                                <v-chip v-if="report.user?.role" size="x-small" :color="report.user.role === 'rescuer' ? 'teal' : 'indigo'" variant="tonal">
                                                    <v-icon start size="8">{{ report.user.role === 'rescuer' ? 'mdi-lifebuoy' : 'mdi-account' }}</v-icon>
                                                    {{ report.user.role === 'rescuer' ? 'Rescuer' : 'User' }}
                                                </v-chip>
                                            </div>
                                            <div class="text-caption text-grey">
                                                {{ formatDate(report.created_at) }}
                                            </div>
                                        </div>
                                    </div>
                                    <v-chip :color="report.category === 'bug' ? 'error' : 'amber-darken-2'" variant="tonal" size="small">
                                        <v-icon start size="14">{{ report.category === 'bug' ? 'mdi-bug' : 'mdi-lightbulb-on' }}</v-icon>
                                        {{ report.category === 'bug' ? 'Bug' : 'Improvement' }}
                                    </v-chip>
                                </div>

                                <!-- Area & Status -->
                                <div class="d-flex align-center flex-wrap ga-2 mt-2">
                                    <v-chip v-if="report.area" size="x-small" :color="getSysAreaColor(report.area)" variant="tonal">
                                        <v-icon start size="10">{{ getSysAreaIcon(report.area) }}</v-icon>
                                        {{ report.area }}
                                    </v-chip>
                                    <v-chip size="x-small" :color="getSysStatusColor(report.status)" variant="flat" class="text-white">
                                        {{ getSysStatusLabel(report.status) }}
                                    </v-chip>
                                    <v-chip v-if="report.attachment_path" size="x-small" color="grey" variant="tonal">
                                        <v-icon start size="10">mdi-paperclip</v-icon>
                                        Attachment
                                    </v-chip>
                                </div>

                                <!-- Description preview -->
                                <div v-if="report.description" class="feedback-comment mt-2">
                                    <v-icon size="12" color="grey" class="mr-1">mdi-text</v-icon>
                                    {{ report.description.length > 150 ? report.description.substring(0, 150) + '...' : report.description }}
                                </div>
                            </div>
                        </v-card-text>

                        <!-- Pagination -->
                        <v-card-actions v-if="sysTotalPages > 1" class="justify-center py-3">
                            <v-pagination
                                v-model="sysCurrentPage"
                                :length="sysTotalPages"
                                :total-visible="isMobile ? 3 : 7"
                                density="compact"
                                rounded="circle"
                                color="primary"
                            />
                        </v-card-actions>
                    </v-card>

                    <!-- System Report Detail Dialog -->
                    <v-dialog v-model="showSysDetailDialog" :width="isMobile ? '95%' : 650" rounded="lg">
                        <v-card v-if="selectedSysReport" rounded="lg">
                            <v-card-title class="d-flex align-center pa-4" style="background: linear-gradient(135deg, #3674B5 0%, #2d5f96 100%); color: white;">
                                <v-icon color="white" class="mr-2">{{ selectedSysReport.category === 'bug' ? 'mdi-bug' : 'mdi-lightbulb-on' }}</v-icon>
                                {{ selectedSysReport.category === 'bug' ? 'Bug Report' : 'Improvement Suggestion' }} Detail
                                <v-spacer />
                                <v-btn icon variant="text" density="compact" @click="showSysDetailDialog = false">
                                    <v-icon color="white">mdi-close</v-icon>
                                </v-btn>
                            </v-card-title>
                            <v-card-text class="pa-4">
                                <!-- User info -->
                                <div class="d-flex align-center mb-4">
                                    <v-avatar :color="getAvatarColor(selectedSysReport.user)" size="40" class="mr-3">
                                        <span class="text-white font-weight-bold">{{ getInitials(selectedSysReport.user) }}</span>
                                    </v-avatar>
                                    <div>
                                        <div class="d-flex align-center ga-2">
                                            <span class="font-weight-bold">{{ getUserName(selectedSysReport.user) }}</span>
                                            <v-chip v-if="selectedSysReport.user?.role" size="x-small" :color="selectedSysReport.user.role === 'rescuer' ? 'teal' : 'indigo'" variant="tonal">
                                                <v-icon start size="10">{{ selectedSysReport.user.role === 'rescuer' ? 'mdi-lifebuoy' : 'mdi-account' }}</v-icon>
                                                {{ selectedSysReport.user.role === 'rescuer' ? 'Rescuer' : 'User' }}
                                            </v-chip>
                                        </div>
                                        <div class="text-caption text-grey">{{ formatDate(selectedSysReport.created_at) }}</div>
                                    </div>
                                </div>

                                <v-divider class="mb-4" />

                                <!-- Category & Area -->
                                <div class="d-flex flex-wrap ga-2 mb-4">
                                    <v-chip :color="selectedSysReport.category === 'bug' ? 'error' : 'amber-darken-2'" variant="tonal">
                                        <v-icon start size="16">{{ selectedSysReport.category === 'bug' ? 'mdi-bug' : 'mdi-lightbulb-on' }}</v-icon>
                                        {{ selectedSysReport.category === 'bug' ? 'Bug Report' : 'Improvement Suggestion' }}
                                    </v-chip>
                                    <v-chip v-if="selectedSysReport.area" :color="getSysAreaColor(selectedSysReport.area)" variant="tonal">
                                        <v-icon start size="16">{{ getSysAreaIcon(selectedSysReport.area) }}</v-icon>
                                        {{ selectedSysReport.area }}
                                    </v-chip>
                                </div>

                                <!-- Description -->
                                <div class="mb-4">
                                    <p class="text-caption text-grey mb-1">Description</p>
                                    <v-card variant="tonal" color="grey-lighten-4" rounded="lg" class="pa-3">
                                        <div class="text-body-2" style="white-space: pre-wrap; line-height: 1.6;">{{ selectedSysReport.description }}</div>
                                    </v-card>
                                </div>

                                <!-- Attachment -->
                                <div v-if="selectedSysReport.attachment_path" class="mb-4">
                                    <p class="text-caption text-grey mb-1">Attachment</p>
                                    <v-card variant="outlined" rounded="lg" class="pa-3">
                                        <div v-if="isImageAttachment(selectedSysReport.attachment_path)" class="text-center">
                                            <v-img :src="'/storage/' + selectedSysReport.attachment_path" max-height="300" contain rounded="lg" />
                                        </div>
                                        <div v-else class="d-flex align-center">
                                            <v-icon size="24" color="primary" class="mr-2">mdi-file-outline</v-icon>
                                            <a :href="'/storage/' + selectedSysReport.attachment_path" target="_blank" class="text-primary text-body-2">
                                                View Attachment
                                            </a>
                                        </div>
                                    </v-card>
                                </div>

                                <v-divider class="mb-4" />

                                <!-- Admin Status & Notes -->
                                <p class="text-subtitle-2 font-weight-bold mb-2" style="color: #13294B;">
                                    <v-icon start size="18" color="primary">mdi-shield-account</v-icon>
                                    Admin Actions
                                </p>

                                <v-select
                                    v-model="sysEditStatus"
                                    :items="sysStatusOptions"
                                    item-title="label"
                                    item-value="value"
                                    label="Update Status"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    class="mb-3"
                                />

                                <v-textarea
                                    v-model="sysEditNotes"
                                    label="Admin Notes"
                                    variant="outlined"
                                    density="compact"
                                    rows="3"
                                    placeholder="Add notes about this report..."
                                    hide-details
                                    class="mb-3"
                                />

                                <v-btn
                                    block
                                    color="#3674B5"
                                    :loading="sysUpdating"
                                    @click="updateSysReport"
                                    class="text-none"
                                >
                                    <v-icon start>mdi-content-save</v-icon>
                                    Save Changes
                                </v-btn>
                            </v-card-text>
                        </v-card>
                    </v-dialog>

                </div><!-- END system tab -->

            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useDisplay } from 'vuetify';
import AdminAppBar from '@/Components/AdminAppBar.vue';
import { getRescueFeedbackStats, getSystemFeedbacks, getSystemFeedbackStats, updateSystemFeedback } from '@/Composables/useApi';

const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

// Active Tab
const activeTab = ref('rescue');

// State
const loading = ref(true);
const feedbacks = ref([]);
const stats = ref({});
const selectedFeedback = ref(null);
const showDetailDialog = ref(false);

// Filters
const selectedRescuer = ref(null);
const dateFrom = ref('');
const dateTo = ref('');
const searchQuery = ref('');
const ratingFilter = ref(null);
const currentPage = ref(1);
const pageSize = 10;

const ratingFilterOptions = [
    { label: 'All Ratings', value: null },
    { label: '5 Stars', value: 5 },
    { label: '4 Stars', value: 4 },
    { label: '3 Stars', value: 3 },
    { label: '2 Stars', value: 2 },
    { label: '1 Star', value: 1 },
];

const rescuerOptions = ref([]);

// Computed
const filteredFeedbacks = computed(() => {
    let result = [...feedbacks.value];

    if (selectedRescuer.value) {
        result = result.filter(f => f.rescuer_id === selectedRescuer.value);
    }

    if (ratingFilter.value) {
        result = result.filter(f => f.overall_rating === ratingFilter.value);
    }

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(f =>
            getUserName(f.user).toLowerCase().includes(q) ||
            getRescuerName(f.rescuer).toLowerCase().includes(q) ||
            (f.comments || '').toLowerCase().includes(q) ||
            (f.rescue_request?.rescue_code || '').toLowerCase().includes(q)
        );
    }

    return result;
});

const feedbackTotalPages = computed(() => Math.ceil(filteredFeedbacks.value.length / pageSize));

const paginatedFeedbacks = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return filteredFeedbacks.value.slice(start, start + pageSize);
});

// Methods
const fetchFeedbacks = async () => {
    loading.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const params = new URLSearchParams();
        if (selectedRescuer.value) params.append('rescuer_id', selectedRescuer.value);
        if (dateFrom.value) params.append('from', dateFrom.value);
        if (dateTo.value) params.append('to', dateTo.value);
        params.append('per_page', '200'); // Get all for client-side filtering

        const resp = await fetch(`/api/rescue-feedbacks?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
            credentials: 'include',
        });
        const result = await resp.json();
        if (result.success) {
            feedbacks.value = result.data?.data || result.data || [];
        }
    } catch (e) {
        console.error('Error fetching feedbacks:', e);
    } finally {
        loading.value = false;
    }
};

const fetchStats = async () => {
    try {
        const result = await getRescueFeedbackStats(selectedRescuer.value || undefined);
        if (result.success) {
            stats.value = result.data;
        }
    } catch (e) {
        console.error('Error fetching stats:', e);
    }
};

const fetchRescuers = async () => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const resp = await fetch('/admin/rescuers', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken || '' },
            credentials: 'include',
        });
        const data = await resp.json();
        // Parse rescuers from Inertia response
        const rescuers = data?.props?.rescuers || data?.rescuers || [];
        rescuerOptions.value = [
            ...rescuers.map(r => ({
                label: `${r.first_name || ''} ${r.last_name || ''}`.trim() || r.name || 'Unknown',
                value: r.id,
            }))
        ];
    } catch (e) {
        console.error('Error fetching rescuers:', e);
    }
};

const resetFilters = () => {
    selectedRescuer.value = null;
    dateFrom.value = '';
    dateTo.value = '';
    searchQuery.value = '';
    ratingFilter.value = null;
    currentPage.value = 1;
    fetchFeedbacks();
    fetchStats();
};

const viewFeedback = (feedback) => {
    selectedFeedback.value = feedback;
    showDetailDialog.value = true;
};

// Helpers
const getUserName = (user) => {
    if (!user) return 'Unknown User';
    return `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.name || 'Unknown';
};

const getRescuerName = (rescuer) => {
    if (!rescuer) return 'No Rescuer';
    return `${rescuer.first_name || ''} ${rescuer.last_name || ''}`.trim() || rescuer.name || 'Unknown';
};

const getInitials = (user) => {
    if (!user) return '?';
    return `${(user.first_name || '?')[0]}${(user.last_name || '?')[0]}`.toUpperCase();
};

const getAvatarColor = (user) => {
    if (!user) return '#546E7A';
    const colors = ['#3674B5', '#2E7D32', '#7B1FA2', '#C62828', '#F57F17', '#00695C', '#1565C0', '#4E342E'];
    const hash = (user.id || 0) % colors.length;
    return colors[hash];
};

const getRatingColor = (rating) => {
    if (rating >= 4.5) return '#2E7D32';
    if (rating >= 3.5) return '#689F38';
    if (rating >= 2.5) return '#F9A825';
    if (rating >= 1.5) return '#EF6C00';
    return '#C62828';
};

const getRatingPercent = (star) => {
    const total = stats.value.total_feedbacks || 0;
    if (total === 0) return 0;
    return ((stats.value.rating_distribution?.[star] || 0) / total) * 100;
};

// Tag helpers
const tagMeta = {
    'Response Time': { icon: 'mdi-timer-check-outline', color: '#2E7D32' },
    'Clear Communication': { icon: 'mdi-message-check-outline', color: '#7B1FA2' },
    'Professionalism': { icon: 'mdi-account-tie', color: '#00796B' },
    'Accurate Location Tracking': { icon: 'mdi-crosshairs-gps', color: '#1565C0' },
    'Feeling Safe Throughout': { icon: 'mdi-shield-check-outline', color: '#388E3C' },
};

const getTagIcon = (tag) => tagMeta[tag]?.icon || 'mdi-tag';
const getTagColor = (tag) => tagMeta[tag]?.color || '#3674B5';

const getTagPercent = (count) => {
    const total = stats.value.total_feedbacks || 0;
    if (total === 0) return 0;
    return Math.min((count / total) * 100, 100);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// ==================== SYSTEM FEEDBACK STATE ====================
const sysLoading = ref(false);
const sysFeedbacks = ref([]);
const sysStats = ref({});
const selectedSysReport = ref(null);
const showSysDetailDialog = ref(false);
const sysUpdating = ref(false);
const sysEditStatus = ref('open');
const sysEditNotes = ref('');

// Sys Filters
const sysCategoryFilter = ref(null);
const sysAreaFilter = ref(null);
const sysStatusFilter = ref(null);
const sysSearchQuery = ref('');
const sysCurrentPage = ref(1);
const sysPageSize = 10;

const sysCategoryOptions = [
    { label: 'Bug Report', value: 'bug' },
    { label: 'Improvement', value: 'improvement' },
];

const sysAreaOptions = [
    { label: 'Scanner', value: 'Scanner' },
    { label: 'Voice Command', value: 'Voice Command' },
    { label: 'Account', value: 'Account' },
    { label: 'Notifications', value: 'Notifications' },
    { label: 'Rescue Request', value: 'Rescue Request' },
    { label: 'Chat', value: 'Chat' },
    { label: 'Location', value: 'Location' },
    { label: 'Map', value: 'Map' },
    { label: 'Login', value: 'Login' },
    { label: 'Other', value: 'Other' },
];

const sysStatusOptions = [
    { label: 'Open', value: 'open' },
    { label: 'In Review', value: 'in_review' },
    { label: 'Resolved', value: 'resolved' },
    { label: 'Closed', value: 'closed' },
];

// Sys Computed
const filteredSysFeedbacks = computed(() => {
    let result = [...sysFeedbacks.value];

    if (sysCategoryFilter.value) {
        result = result.filter(r => r.category === sysCategoryFilter.value);
    }
    if (sysAreaFilter.value) {
        result = result.filter(r => r.area === sysAreaFilter.value);
    }
    if (sysStatusFilter.value) {
        result = result.filter(r => r.status === sysStatusFilter.value);
    }
    if (sysSearchQuery.value) {
        const q = sysSearchQuery.value.toLowerCase();
        result = result.filter(r =>
            getUserName(r.user).toLowerCase().includes(q) ||
            (r.description || '').toLowerCase().includes(q) ||
            (r.area || '').toLowerCase().includes(q)
        );
    }

    return result;
});

const sysTotalPages = computed(() => Math.ceil(filteredSysFeedbacks.value.length / sysPageSize));

const paginatedSysFeedbacks = computed(() => {
    const start = (sysCurrentPage.value - 1) * sysPageSize;
    return filteredSysFeedbacks.value.slice(start, start + sysPageSize);
});

// Sys Methods
const fetchSysFeedbacks = async () => {
    sysLoading.value = true;
    try {
        const result = await getSystemFeedbacks({ per_page: 500 });
        if (result.success) {
            sysFeedbacks.value = result.data?.data || result.data || [];
        }
    } catch (e) {
        console.error('Error fetching system feedbacks:', e);
    } finally {
        sysLoading.value = false;
    }
};

const fetchSysStats = async () => {
    try {
        const result = await getSystemFeedbackStats();
        if (result.success) {
            sysStats.value = result.data;
        }
    } catch (e) {
        console.error('Error fetching system stats:', e);
    }
};

const viewSysReport = (report) => {
    selectedSysReport.value = report;
    sysEditStatus.value = report.status || 'open';
    sysEditNotes.value = report.admin_notes || '';
    showSysDetailDialog.value = true;
};

const updateSysReport = async () => {
    if (!selectedSysReport.value) return;
    sysUpdating.value = true;
    try {
        const result = await updateSystemFeedback(selectedSysReport.value.id, {
            status: sysEditStatus.value,
            admin_notes: sysEditNotes.value,
        });
        if (result.success) {
            // Update local data
            const idx = sysFeedbacks.value.findIndex(f => f.id === selectedSysReport.value.id);
            if (idx !== -1) {
                sysFeedbacks.value[idx].status = sysEditStatus.value;
                sysFeedbacks.value[idx].admin_notes = sysEditNotes.value;
            }
            selectedSysReport.value.status = sysEditStatus.value;
            selectedSysReport.value.admin_notes = sysEditNotes.value;
            showSysDetailDialog.value = false;
            await fetchSysStats();
        }
    } catch (e) {
        console.error('Error updating system feedback:', e);
    } finally {
        sysUpdating.value = false;
    }
};

const resetSysFilters = () => {
    sysCategoryFilter.value = null;
    sysAreaFilter.value = null;
    sysStatusFilter.value = null;
    sysSearchQuery.value = '';
    sysCurrentPage.value = 1;
};

// Sys Helpers
const sysAreaMeta = {
    'Scanner': { icon: 'mdi-barcode-scan', color: '#7B1FA2' },
    'Voice Command': { icon: 'mdi-microphone', color: '#C62828' },
    'Account': { icon: 'mdi-account-cog', color: '#00796B' },
    'Notifications': { icon: 'mdi-bell-ring', color: '#EF6C00' },
    'Rescue Request': { icon: 'mdi-lifebuoy', color: '#1565C0' },
    'Chat': { icon: 'mdi-chat', color: '#388E3C' },
    'Location': { icon: 'mdi-map-marker', color: '#D32F2F' },
    'Map': { icon: 'mdi-map', color: '#5D4037' },
    'Login': { icon: 'mdi-login', color: '#512DA8' },
    'Other': { icon: 'mdi-dots-horizontal-circle', color: '#546E7A' },
};

const getSysAreaIcon = (area) => sysAreaMeta[area]?.icon || 'mdi-help-circle';
const getSysAreaColor = (area) => sysAreaMeta[area]?.color || '#546E7A';

const getSysAreaPercent = (count) => {
    const total = sysStats.value.total || 0;
    if (total === 0) return 0;
    return Math.min((count / total) * 100, 100);
};

const getSysStatusColor = (status) => {
    const map = { open: '#EF6C00', in_review: '#1565C0', resolved: '#2E7D32', closed: '#546E7A' };
    return map[status] || '#546E7A';
};

const getSysStatusLabel = (status) => {
    const map = { open: 'Open', in_review: 'In Review', resolved: 'Resolved', closed: 'Closed' };
    return map[status] || status;
};

const isImageAttachment = (path) => {
    if (!path) return false;
    return /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i.test(path);
};

// Load system data when tab switches to system
watch(activeTab, (val) => {
    if (val === 'system' && sysFeedbacks.value.length === 0) {
        fetchSysFeedbacks();
        fetchSysStats();
    }
});

// Lifecycle
onMounted(async () => {
    await Promise.all([fetchFeedbacks(), fetchStats(), fetchRescuers()]);
});
</script>

<style scoped>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.stat-card {
    border: 1px solid #e8ecf0;
    transition: box-shadow 0.2s ease;
}
.stat-card:hover {
    box-shadow: 0 4px 16px rgba(54, 116, 181, 0.1) !important;
}

.stat-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #f4f6f9;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Rating distribution */
.rating-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.rating-label {
    width: 40px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    font-size: 13px;
}
.rating-bar-wrap {
    flex: 1;
    height: 8px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}
.rating-bar {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}
.rating-bar-5 { background: linear-gradient(90deg, #2E7D32, #43A047); }
.rating-bar-4 { background: linear-gradient(90deg, #689F38, #8BC34A); }
.rating-bar-3 { background: linear-gradient(90deg, #F9A825, #FDD835); }
.rating-bar-2 { background: linear-gradient(90deg, #EF6C00, #FF9800); }
.rating-bar-1 { background: linear-gradient(90deg, #C62828, #EF5350); }
.rating-count {
    width: 30px;
    text-align: right;
    font-size: 12px;
    font-weight: 600;
    color: #546E7A;
}

/* Feedback items */
.feedback-item {
    padding: 16px 20px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.15s ease;
}
.feedback-item:hover {
    background: #f8fafb;
}
.feedback-item:last-child {
    border-bottom: none;
}

.feedback-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.feedback-user {
    display: flex;
    align-items: center;
}
.feedback-rating-badge {
    display: flex;
    align-items: center;
    padding: 4px 10px;
    background: #FFFDE7;
    border-radius: 20px;
    border: 1px solid #FFF59D;
}

.feedback-ratings-grid {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.rating-mini {
    display: flex;
    align-items: center;
    gap: 6px;
}
.rating-mini-label {
    font-size: 11px;
    color: #78909C;
    font-weight: 500;
}

.feedback-comment {
    font-size: 13px;
    color: #546E7A;
    line-height: 1.5;
    padding: 8px 12px;
    background: #fafbfd;
    border-radius: 8px;
    border-left: 3px solid #e0e0e0;
}

.feedback-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

/* Detail dialog */
.detail-rating-box {
    background: #f8fafb;
    border-radius: 12px;
    border: 1px solid #e8ecf0;
}

/* Tag distribution */
.tag-dist-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.tag-dist-label {
    width: 140px;
    display: flex;
    align-items: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tag-dist-bar-wrap {
    flex: 1;
    height: 6px;
    background: #f0f0f0;
    border-radius: 3px;
    overflow: hidden;
}
.tag-dist-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}
.tag-dist-count {
    width: 24px;
    text-align: right;
    font-size: 12px;
    font-weight: 600;
    color: #546E7A;
}
</style>
