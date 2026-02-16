<template>
    <!-- App Bar -->
    <v-app-bar color="#3674B5" elevation="2">
        <v-app-bar-nav-icon v-if="!backButton" @click="toggleDrawer"></v-app-bar-nav-icon>
        <v-app-bar-nav-icon v-else @click="$emit('back')">
            <v-icon>mdi-arrow-left</v-icon>
        </v-app-bar-nav-icon>
        <v-app-bar-title class="d-flex align-center">
            <img
                src="/images/Icons/logo_w_word.png"
                alt="PinPointMe"
                class="app-bar-logo"
            />
        </v-app-bar-title>
        <v-spacer />

        <!-- Extra slot for custom buttons (e.g. save button in FloorPlanEditor) -->
        <slot name="actions"></slot>

        <!-- Notification Bell -->
        <v-btn v-if="!hideNotifications" icon @click="showNotificationPanel = !showNotificationPanel" class="mr-1">
            <v-badge :content="totalUnreadCount" :model-value="totalUnreadCount > 0" color="error" overlap>
                <v-icon color="white">mdi-bell</v-icon>
            </v-badge>
        </v-btn>

        <!-- Profile Avatar Menu -->
        <v-menu v-if="!hideProfile" offset-y>
            <template v-slot:activator="{ props: menuProps }">
                <v-btn icon v-bind="menuProps">
                    <v-avatar color="white" size="36">
                        <v-img v-if="profilePictureUrl" :src="profilePictureUrl" cover></v-img>
                        <span v-else class="text-primary font-weight-bold">{{ adminInitials }}</span>
                    </v-avatar>
                </v-btn>
            </template>
            <v-list>
                <v-list-item @click="goToProfile" prepend-icon="mdi-account">
                    <v-list-item-title>Profile</v-list-item-title>
                </v-list-item>
                <v-list-item @click="logout" prepend-icon="mdi-logout">
                    <v-list-item-title>Logout</v-list-item-title>
                </v-list-item>
            </v-list>
        </v-menu>
    </v-app-bar>

    <!-- Navigation Drawer (Sidebar) -->
    <v-navigation-drawer
        v-if="!hideDrawer"
        v-model="drawerModel"
        :permanent="!isMobile"
        :temporary="isMobile"
        app
    >
        <v-list>
            <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" href="/admin/dashboard" :active="activePage === 'dashboard'" @click="closeDrawerOnMobile"></v-list-item>
            <v-list-item prepend-icon="mdi-account-group" title="Users" href="/admin/users" :active="activePage === 'users'" @click="closeDrawerOnMobile"></v-list-item>
            <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" href="/admin/rescuers" :active="activePage === 'rescuers'" @click="closeDrawerOnMobile"></v-list-item>
            <v-list-item prepend-icon="mdi-office-building" title="Buildings" href="/admin/buildings" :active="activePage === 'buildings'" @click="closeDrawerOnMobile"></v-list-item>
            <v-list-item prepend-icon="mdi-file-chart" title="Reports" href="/admin/reports" :active="activePage === 'reports'" @click="closeDrawerOnMobile"></v-list-item>
            <v-list-item prepend-icon="mdi-shield-alert" title="Preventive Measures" href="/admin/preventive-measures" :active="activePage === 'preventive-measures'" @click="closeDrawerOnMobile"></v-list-item>
        </v-list>
    </v-navigation-drawer>

    <!-- ── Notification Center Panel ── -->
    <v-navigation-drawer
        v-model="showNotificationPanel"
        location="right"
        temporary
        :width="isMobile ? 360 : 420"
        class="nc-drawer"
    >
        <!-- ── Header ── -->
        <div class="nc-header">
            <div class="nc-header-inner">
                <div class="nc-header-icon">
                    <v-icon size="18" color="white">mdi-bell</v-icon>
                </div>
                <div>
                    <div class="nc-header-title">Notifications</div>
                    <div class="nc-header-sub">{{ totalUnreadCount > 0 ? `${totalUnreadCount} unread` : 'All caught up' }}</div>
                </div>
            </div>
            <div class="nc-header-actions">
                <button v-if="unreadActivityCount > 0" class="nc-mark-read-btn" @click.stop="markAllRead" title="Mark all as read">
                    <v-icon size="16">mdi-check-all</v-icon>
                </button>
                <button class="nc-close-btn" @click="showNotificationPanel = false">
                    <v-icon size="18">mdi-close</v-icon>
                </button>
            </div>
        </div>

        <!-- ── Tabs ── -->
        <div class="nc-tabs nc-tabs-four">
            <button class="nc-tab" :class="{ 'nc-tab-active': notifTab === 'urgent' }" @click="notifTab = 'urgent'">
                <v-icon size="15">mdi-alarm-light</v-icon>
                <span>{{ isMobile ? 'Urgent' : 'Force Alerts' }}</span>
                <span v-if="forceAlertNotifications.length > 0" class="nc-badge nc-badge-error">{{ forceAlertNotifications.length }}</span>
            </button>
            <button class="nc-tab" :class="{ 'nc-tab-active': notifTab === 'approvals' }" @click="notifTab = 'approvals'">
                <v-icon size="15">mdi-account-clock</v-icon>
                <span>Approvals</span>
                <span v-if="pendingRescuerApplications.length > 0" class="nc-badge nc-badge-orange">{{ pendingRescuerApplications.length }}</span>
            </button>
            <button class="nc-tab" :class="{ 'nc-tab-active': notifTab === 'activity' }" @click="notifTab = 'activity'">
                <v-icon size="15">mdi-pulse</v-icon>
                <span>Activity</span>
                <span v-if="unreadActivityCount > 0" class="nc-badge nc-badge-red">{{ unreadActivityCount }}</span>
            </button>
            <button class="nc-tab" :class="{ 'nc-tab-active': notifTab === 'messages' }" @click="notifTab = 'messages'">
                <v-icon size="15">mdi-chat-processing-outline</v-icon>
                <span>Messages</span>
                <span v-if="unreadMessageBadge > 0" class="nc-badge nc-badge-blue">{{ unreadMessageBadge }}</span>
            </button>
            <div class="nc-tab-slider nc-tab-slider-four" :class="{ 
                'nc-tab-slider-0': notifTab === 'urgent',
                'nc-tab-slider-1': notifTab === 'approvals',
                'nc-tab-slider-2': notifTab === 'activity',
                'nc-tab-slider-3': notifTab === 'messages'
            }"></div>
        </div>

        <!-- ─── Approvals Tab ─── -->
        <div v-if="notifTab === 'approvals'" class="nc-body">
            <div v-if="pendingRescuerApplications.length === 0" class="nc-empty">
                <div class="nc-empty-icon">
                    <v-icon size="32" color="#B0BEC5">mdi-account-check-outline</v-icon>
                </div>
                <p class="nc-empty-title">No pending applications</p>
                <p class="nc-empty-sub">External rescuer applications will appear here for review</p>
            </div>
            <div v-else>
                <div
                    v-for="app in pendingRescuerApplications"
                    :key="app.id"
                    class="nc-item nc-item-approval"
                    :class="{ 'nc-item-unread': !approvalReadIds.has(app.id) }"
                    @click="approvalReadIds.add(app.id)"
                >
                    <div class="nc-item-bar nc-bar-orange"></div>
                    <div class="nc-item-icon nc-icon-orange">
                        <v-icon size="16" color="white">mdi-account-plus</v-icon>
                    </div>
                    <div class="nc-item-content">
                        <div class="nc-item-top">
                            <span class="nc-item-title">{{ app.first_name }} {{ app.last_name }}</span>
                            <span v-if="!approvalReadIds.has(app.id)" class="nc-unread-dot"></span>
                        </div>
                        <div class="nc-item-msg">{{ app.email }}</div>
                        <div class="nc-item-detail">
                            <span v-if="app.organization" class="nc-detail-code">
                                <v-icon size="10">mdi-domain</v-icon>
                                {{ app.organization }}
                            </span>
                            <span v-if="app.phone" class="nc-detail-name">
                                <v-icon size="10">mdi-phone</v-icon>
                                {{ app.phone }}
                            </span>
                            <span v-if="app.is_external" class="nc-external-chip">
                                <v-icon size="10">mdi-earth</v-icon> External
                            </span>
                        </div>
                        <div class="nc-item-footer">
                            <span class="nc-item-time">
                                <v-icon size="10">mdi-clock-outline</v-icon>
                                {{ formatTimeAgo(app.created_at) }}
                            </span>
                            <span v-if="app.otp_verified" class="nc-verified-chip">
                                <v-icon size="10">mdi-email-check</v-icon> Verified
                            </span>
                            <span v-else class="nc-unverified-chip">
                                <v-icon size="10">mdi-email-alert</v-icon> Unverified
                            </span>
                        </div>
                        <div class="nc-approval-actions">
                            <button 
                                class="nc-approve-btn"
                                :class="{ 'nc-btn-loading': approvalLoading === app.id }"
                                :disabled="approvalLoading === app.id"
                                @click.stop="handleApproveRescuer(app)"
                            >
                                <v-icon size="14">mdi-check</v-icon>
                                <span>Approve</span>
                            </button>
                            <button 
                                class="nc-decline-btn"
                                :class="{ 'nc-btn-loading': approvalLoading === app.id }"
                                :disabled="approvalLoading === app.id"
                                @click.stop="showDeclineDialog(app)"
                            >
                                <v-icon size="14">mdi-close</v-icon>
                                <span>Decline</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Force Alerts / Urgent Tab ─── -->
        <div v-if="notifTab === 'urgent'" class="nc-body">
            <div v-if="forceAlertNotifications.length === 0" class="nc-empty">
                <div class="nc-empty-icon">
                    <v-icon size="32" color="#B0BEC5">mdi-check-circle-outline</v-icon>
                </div>
                <p class="nc-empty-title">No urgent alerts</p>
                <p class="nc-empty-sub">Requests that need force alerts will appear here</p>
            </div>
            <div v-else>
                <div
                    v-for="notif in paginatedForceAlertNotifications"
                    :key="notif.id"
                    class="nc-item nc-item-urgent"
                    :class="{ 'nc-item-unread': !notif.read }"
                    @click="markActivityRead(notif)"
                >
                    <div class="nc-item-bar nc-bar-error"></div>
                    <div class="nc-item-icon nc-icon-error">
                        <v-icon size="16" color="white">mdi-alarm-light</v-icon>
                    </div>
                    <div class="nc-item-content">
                        <div class="nc-item-top">
                            <span class="nc-item-title">{{ notif.title }}</span>
                            <span v-if="!notif.read" class="nc-unread-dot"></span>
                        </div>
                        <div class="nc-item-msg">{{ notif.message }}</div>
                        <div v-if="notif.request" class="nc-item-detail">
                            <span v-if="notif.request.rescue_code" class="nc-detail-code">
                                <v-icon size="10">mdi-pound</v-icon>
                                {{ notif.request.rescue_code }}
                            </span>
                            <span v-if="notif.request.firstName || notif.request.lastName" class="nc-detail-name">
                                <v-icon size="10">mdi-account</v-icon>
                                {{ `${notif.request.firstName || ''} ${notif.request.lastName || ''}`.trim() }}
                            </span>
                        </div>
                        <div class="nc-item-footer">
                            <span class="nc-item-time">
                                <v-icon size="10">mdi-clock-outline</v-icon>
                                {{ formatTimeAgo(notif.time) }}
                            </span>
                            <span v-if="notif.request" class="nc-urgency-chip" :class="`nc-urgency-${(notif.request.urgency_level || 'medium').toLowerCase()}`">
                                {{ (notif.request.urgency_level || 'Medium') }}
                            </span>
                            <!-- Show safe badge if rescued/completed -->
                            <span v-if="notif.request && ['rescued', 'completed', 'safe'].includes(notif.request.status)" class="nc-safe-chip">
                                <v-icon size="10">mdi-shield-check</v-icon> Safe
                            </span>
                            <!-- Show cancelled badge if cancelled -->
                            <span v-else-if="notif.request && notif.request.status === 'cancelled'" class="nc-cancelled-chip">
                                <v-icon size="10">mdi-close-circle</v-icon> Cancelled
                            </span>
                            <!-- Force alert button - disabled if not pending -->
                            <button
                                v-else-if="notif.canForceAlert && !notif.forceAlerted && notif.request.status === 'pending'"
                                class="nc-force-btn nc-force-btn-large"
                                :class="{ 'nc-force-loading': forceAlertLoading === notif.request.id }"
                                :title="getThresholdLabel(notif.request)"
                                @click.stop="sendForceAlert(notif.request)"
                            >
                                <v-icon size="14">mdi-alarm-light</v-icon>
                                <span>Send Force Alert</span>
                            </button>
                            <!-- Disabled force alert button for non-pending -->
                            <button
                                v-else-if="notif.canForceAlert && !notif.forceAlerted && notif.request.status !== 'pending'"
                                class="nc-force-btn nc-force-btn-large nc-force-disabled"
                                disabled
                                title="Cannot send force alert - request no longer pending"
                            >
                                <v-icon size="14">mdi-alarm-off</v-icon>
                                <span>Force Alert</span>
                            </button>
                            <span v-else-if="notif.forceAlerted" class="nc-alerted-chip">
                                <v-icon size="10">mdi-check</v-icon> Alerted
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination Controls for Force Alerts -->
                <div v-if="forceAlertTotalPages > 1" class="nc-pagination">
                    <button 
                        class="nc-page-btn" 
                        :class="{ 'nc-page-disabled': forceAlertCurrentPage === 1 }"
                        :disabled="forceAlertCurrentPage === 1"
                        @click="forceAlertCurrentPage = Math.max(1, forceAlertCurrentPage - 1)"
                    >
                        <v-icon size="14">mdi-chevron-left</v-icon>
                    </button>
                    
                    <span class="nc-page-info">{{ forceAlertCurrentPage }} of {{ forceAlertTotalPages }}</span>
                    
                    <button 
                        class="nc-page-btn" 
                        :class="{ 'nc-page-disabled': forceAlertCurrentPage === forceAlertTotalPages }"
                        :disabled="forceAlertCurrentPage === forceAlertTotalPages"
                        @click="forceAlertCurrentPage = Math.min(forceAlertTotalPages, forceAlertCurrentPage + 1)"
                    >
                        <v-icon size="14">mdi-chevron-right</v-icon>
                    </button>
                </div>
            </div>
        </div>

        <!-- ─── Activity Tab ─── -->
        <div v-if="notifTab === 'activity'" class="nc-body">
            <div v-if="activityNotifications.length === 0" class="nc-empty">
                <div class="nc-empty-icon">
                    <v-icon size="32" color="#B0BEC5">mdi-bell-check-outline</v-icon>
                </div>
                <p class="nc-empty-title">No activity yet</p>
                <p class="nc-empty-sub">Rescue request updates will appear here</p>
            </div>
            <div v-else>
                <div
                    v-for="notif in paginatedNotifications"
                    :key="notif.id"
                    class="nc-item"
                    :class="{ 'nc-item-unread': !notif.read }"
                    @click="markActivityRead(notif)"
                >
                    <div class="nc-item-bar" :class="`nc-bar-${notif.color}`"></div>
                    <div class="nc-item-icon" :class="`nc-icon-${notif.color}`">
                        <v-icon size="16" color="white">{{ notif.icon }}</v-icon>
                    </div>
                    <div class="nc-item-content">
                        <div class="nc-item-top">
                            <span class="nc-item-title">{{ notif.title }}</span>
                            <span v-if="!notif.read" class="nc-unread-dot"></span>
                        </div>
                        <div class="nc-item-msg">{{ notif.message }}</div>
                        <div v-if="notif.request" class="nc-item-detail">
                            <span v-if="notif.request.rescue_code" class="nc-detail-code">
                                <v-icon size="10">mdi-pound</v-icon>
                                {{ notif.request.rescue_code }}
                            </span>
                            <span v-if="notif.request.firstName || notif.request.lastName" class="nc-detail-name">
                                <v-icon size="10">mdi-account</v-icon>
                                {{ `${notif.request.firstName || ''} ${notif.request.lastName || ''}`.trim() }}
                            </span>
                        </div>
                        <div class="nc-item-footer">
                            <span class="nc-item-time">
                                <v-icon size="10">mdi-clock-outline</v-icon>
                                {{ formatTimeAgo(notif.time) }}
                            </span>
                            <span v-if="notif.type === 'pending' && notif.request" class="nc-urgency-chip" :class="`nc-urgency-${(notif.request.urgency_level || 'medium').toLowerCase()}`">
                                {{ (notif.request.urgency_level || 'Medium') }}
                            </span>
                            <!-- Show safe badge if rescued/completed -->
                            <span v-if="notif.request && ['rescued', 'completed', 'safe'].includes(notif.request.status)" class="nc-safe-chip">
                                <v-icon size="10">mdi-shield-check</v-icon> Safe
                            </span>
                            <!-- Show cancelled badge if cancelled -->
                            <span v-else-if="notif.request && notif.request.status === 'cancelled'" class="nc-cancelled-chip">
                                <v-icon size="10">mdi-close-circle</v-icon> Cancelled
                            </span>
                            <!-- Force alert button - only for pending requests -->
                            <button
                                v-else-if="notif.type === 'pending' && notif.canForceAlert && !notif.forceAlerted && notif.request.status === 'pending'"
                                class="nc-force-btn"
                                :class="{ 'nc-force-loading': forceAlertLoading === notif.request.id }"
                                :title="getThresholdLabel(notif.request)"
                                @click.stop="sendForceAlert(notif.request)"
                            >
                                <v-icon size="12">mdi-alarm-light</v-icon>
                                <span>Force Alert</span>
                            </button>
                            <!-- Disabled force alert button for non-pending -->
                            <button
                                v-else-if="notif.type === 'pending' && notif.canForceAlert && !notif.forceAlerted && notif.request.status !== 'pending'"
                                class="nc-force-btn nc-force-disabled"
                                disabled
                                title="Cannot send force alert - request no longer pending"
                            >
                                <v-icon size="12">mdi-alarm-off</v-icon>
                                <span>Force Alert</span>
                            </button>
                            <span v-else-if="notif.type === 'pending' && !notif.canForceAlert && !notif.forceAlerted && getForceAlertCountdown(notif.request)" class="nc-countdown-chip">
                                <v-icon size="10">mdi-timer-sand</v-icon>
                                {{ getForceAlertCountdown(notif.request) }}
                            </span>
                            <span v-else-if="notif.forceAlerted" class="nc-alerted-chip">
                                <v-icon size="10">mdi-check</v-icon> Alerted
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination Controls -->
                <div v-if="totalPages > 1" class="nc-pagination">
                    <button 
                        class="nc-page-btn" 
                        :class="{ 'nc-page-disabled': currentPage === 1 }"
                        :disabled="currentPage === 1"
                        @click="currentPage = Math.max(1, currentPage - 1)"
                    >
                        <v-icon size="14">mdi-chevron-left</v-icon>
                    </button>
                    
                    <span class="nc-page-info">{{ currentPage }} of {{ totalPages }}</span>
                    
                    <button 
                        class="nc-page-btn" 
                        :class="{ 'nc-page-disabled': currentPage === totalPages }"
                        :disabled="currentPage === totalPages"
                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                    >
                        <v-icon size="14">mdi-chevron-right</v-icon>
                    </button>
                </div>
            </div>
        </div>

        <!-- ─── Messages Tab ─── -->
        <div v-if="notifTab === 'messages'" class="nc-body">
            <div v-if="adminConversations.length === 0" class="nc-empty">
                <div class="nc-empty-icon">
                    <v-icon size="32" color="#B0BEC5">mdi-chat-sleep-outline</v-icon>
                </div>
                <p class="nc-empty-title">No conversations</p>
                <p class="nc-empty-sub">Messages between users and rescuers will appear here</p>
            </div>
            <div v-else>
                <div
                    v-for="conv in paginatedAdminConversations"
                    :key="conv.id"
                    class="nc-conv"
                    :class="{ 'nc-conv-open': expandedConv === conv.id, 'nc-conv-new': conv._hasNewMsg }"
                >
                    <div class="nc-conv-header" @click="toggleConversation(conv)">
                        <div class="nc-conv-avatars">
                            <div class="nc-avatar nc-avatar-user">{{ getConvInitials(conv, 'user') }}</div>
                            <div class="nc-avatar nc-avatar-rescuer">{{ getConvInitials(conv, 'rescuer') }}</div>
                        </div>
                        <div class="nc-conv-info">
                            <div class="nc-conv-name">{{ getConvParticipantNames(conv) }}</div>
                            <div class="nc-conv-preview" v-if="conv.last_message">
                                <span class="nc-conv-sender">{{ conv.last_message?.sender_name }}:</span>
                                {{ truncate(conv.last_message?.content, 35) }}
                            </div>
                            <div class="nc-conv-tags">
                                <span
                                    v-if="conv.rescue_request?.rescue_code"
                                    class="nc-tag"
                                    :class="`nc-tag-${getStatusColor(conv.rescue_request?.status)}`"
                                >
                                    {{ conv.rescue_request.rescue_code }}
                                </span>
                                <span class="nc-conv-time">{{ formatTimeAgo(conv.updated_at) }}</span>
                            </div>
                        </div>
                        <div class="nc-conv-right">
                            <span v-if="conv.total_messages" class="nc-msg-count">
                                {{ conv.total_messages }}
                                <v-icon size="11">mdi-message-text</v-icon>
                            </span>
                            <v-icon size="16" class="nc-conv-chevron" :class="{ 'nc-chevron-up': expandedConv === conv.id }">
                                mdi-chevron-down
                            </v-icon>
                        </div>
                    </div>
                    <v-expand-transition>
                        <div v-if="expandedConv === conv.id" class="nc-conv-body" @click.stop>
                            <div class="nc-conv-body-label">
                                <v-icon size="12">mdi-eye-outline</v-icon>
                                Read-only view
                            </div>
                            <div v-if="loadingMessages" class="nc-conv-loading">
                                <v-progress-circular indeterminate size="22" width="2" color="#3674B5"></v-progress-circular>
                            </div>
                            <div v-else class="nc-msg-list">
                                <div
                                    v-for="msg in expandedMessages"
                                    :key="msg.id"
                                    class="nc-msg"
                                    :class="getParticipantType(conv, msg.sender_id) === 'rescuer' ? 'nc-msg-right' : 'nc-msg-left'"
                                >
                                    <div class="nc-msg-name">{{ msg.sender_name || 'Unknown' }}</div>
                                    <div class="nc-msg-bubble">{{ msg.content }}</div>
                                    <div class="nc-msg-time">{{ formatMsgTime(msg.sent_at) }}</div>
                                </div>
                                <div v-if="expandedMessages.length === 0" class="nc-conv-empty-msg">
                                    No messages yet
                                </div>
                            </div>
                        </div>
                    </v-expand-transition>
                </div>
                
                <!-- Pagination Controls for Messages -->
                <div v-if="messagesTotalPages > 1" class="nc-pagination">
                    <button 
                        class="nc-page-btn" 
                        :class="{ 'nc-page-disabled': messagesCurrentPage === 1 }"
                        :disabled="messagesCurrentPage === 1"
                        @click="messagesCurrentPage = Math.max(1, messagesCurrentPage - 1)"
                    >
                        <v-icon size="14">mdi-chevron-left</v-icon>
                    </button>
                    
                    <span class="nc-page-info">{{ messagesCurrentPage }} of {{ messagesTotalPages }}</span>
                    
                    <button 
                        class="nc-page-btn" 
                        :class="{ 'nc-page-disabled': messagesCurrentPage === messagesTotalPages }"
                        :disabled="messagesCurrentPage === messagesTotalPages"
                        @click="messagesCurrentPage = Math.min(messagesTotalPages, messagesCurrentPage + 1)"
                    >
                        <v-icon size="14">mdi-chevron-right</v-icon>
                    </button>
                </div>
            </div>
        </div>
    </v-navigation-drawer>

    <!-- Decline Rescuer Dialog -->
    <v-dialog v-model="declineDialogVisible" max-width="450" persistent>
        <v-card rounded="lg">
            <v-card-title class="d-flex align-center pa-4 text-error">
                <v-icon start color="error">mdi-account-cancel</v-icon>
                Decline Rescuer Application
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="pa-4">
                <p class="mb-3">
                    Are you sure you want to decline the application from
                    <strong>{{ declineTarget?.first_name }} {{ declineTarget?.last_name }}</strong>
                    ({{ declineTarget?.email }})?
                </p>
                <v-textarea
                    v-model="declineReason"
                    label="Reason for declining (optional)"
                    variant="outlined"
                    density="compact"
                    rows="3"
                    placeholder="Provide a reason that will be sent to the applicant..."
                    hide-details
                />
            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn variant="text" @click="declineDialogVisible = false" :disabled="approvalLoading">Cancel</v-btn>
                <v-btn color="error" :loading="approvalLoading === declineTarget?.id" @click="handleDeclineRescuer">
                    <v-icon start>mdi-close</v-icon>
                    Decline
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Notification Popup -->
    <NotificationPopup
        :show="popupAlert.show"
        :title="popupAlert.title"
        :message="popupAlert.message"
        :type="popupAlert.type"
        :icon="popupAlert.icon"
        @close="popupAlert.show = false"
        @click="handlePopupClick"
    />

    <!-- Toast Notification Snackbar -->
    <v-snackbar
        v-model="snackbar.show"
        :color="snackbar.color"
        :timeout="snackbar.timeout"
        location="top right"
        multi-line
    >
        <div class="d-flex align-center">
            <v-icon :color="snackbar.iconColor || 'white'" class="mr-2">{{ snackbar.icon }}</v-icon>
            <div>
                <div class="font-weight-bold">{{ snackbar.title }}</div>
                <div class="text-caption">{{ snackbar.message }}</div>
            </div>
        </div>
        <template v-slot:actions>
            <v-btn
                v-if="snackbar.action"
                variant="text"
                @click="snackbar.action.handler"
                size="small"
            >
                {{ snackbar.action.label }}
            </v-btn>
            <v-btn
                variant="text"
                @click="snackbar.show = false"
                icon="mdi-close"
                size="small"
            ></v-btn>
        </template>
    </v-snackbar>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useDisplay } from 'vuetify';
import { useNotificationAlert } from '@/Composables/useNotificationAlert';
import { getAllRescueRequests, triggerForceAlert, getAdminConversations, getConversationMessages } from '@/Composables/useApi';
import { setUserActiveStatus } from '@/Utilities/firebase';
import NotificationPopup from '@/Components/NotificationPopup.vue';

// ── Props ──
const props = defineProps({
    activePage: {
        type: String,
        default: ''
    },
    backButton: {
        type: Boolean,
        default: false
    },
    hideDrawer: {
        type: Boolean,
        default: false
    },
    hideNotifications: {
        type: Boolean,
        default: false
    },
    hideProfile: {
        type: Boolean,
        default: false
    },
    profilePictureUrl: {
        type: String,
        default: null
    }
});

// ── Emits ──
const emit = defineEmits(['back', 'update:drawer']);

// ── Display ──
const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

// ── Drawer ──
const drawerModel = ref(!mobile.value);

const toggleDrawer = () => {
    drawerModel.value = !drawerModel.value;
    emit('update:drawer', drawerModel.value);
};

const closeDrawerOnMobile = () => {
    if (isMobile.value) {
        drawerModel.value = false;
        emit('update:drawer', false);
    }
};

// Expose drawer model so parent can sync
watch(() => drawerModel.value, (val) => {
    emit('update:drawer', val);
});

// ── Navigation ──
const goToProfile = () => {
    window.location.href = '/admin/profile';
};

const logout = async () => {
    // 1. Clear ALL local storage & session data FIRST to ensure clean state
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    localStorage.removeItem('adminActivityNotifs');
    localStorage.removeItem('adminReadNotifs');
    localStorage.removeItem('userSettings');
    localStorage.removeItem('rescuerSettings');
    localStorage.removeItem('lastRescueCode');
    localStorage.removeItem('lastRescueRequestId');
    localStorage.removeItem('lastRescueRequestTime');
    localStorage.removeItem('conversationId');
    localStorage.removeItem('chatId');
    localStorage.removeItem('activeRescue');
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

// ── Admin Profile ──
const page = usePage();

const adminProfile = ref({
    full_name: 'Administrator',
    email: '',
    profile_picture: null
});

const adminInitials = computed(() => {
    if (adminProfile.value.full_name) {
        const names = adminProfile.value.full_name.split(' ');
        if (names.length >= 2) {
            return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase();
        }
        return names[0][0]?.toUpperCase() || 'AD';
    }
    return 'AD';
});

const loadAdminProfile = () => {
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (userData.first_name || userData.last_name) {
            adminProfile.value = {
                full_name: `${userData.first_name || ''} ${userData.last_name || ''}`.trim(),
                email: userData.email || '',
                profile_picture: userData.profile_picture || null
            };
        } else if (page.props.auth?.user) {
            const user = page.props.auth.user;
            adminProfile.value = {
                full_name: `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.name || 'Administrator',
                email: user.email || '',
                profile_picture: user.profile_picture || null
            };
        }
    } catch (e) {
        console.error('Error loading admin profile:', e);
    }
};

// ── Notification System ──
const { playNotificationSound, vibrate, notifyEmergency } = useNotificationAlert();

const showNotificationPanel = ref(false);
const notifTab = ref('activity');

// Toast snackbar
const snackbar = ref({
    show: false,
    title: '',
    message: '',
    color: 'success',
    icon: 'mdi-check-circle',
    iconColor: 'white',
    timeout: 6000,
    action: null
});

const showToast = (title, message, options = {}) => {
    snackbar.value = {
        show: true,
        title,
        message,
        color: options.color || 'info',
        icon: options.icon || 'mdi-information',
        iconColor: options.iconColor || 'white',
        timeout: options.timeout || 6000,
        action: options.action || null
    };
};

// Polling state
const pendingRequests = ref([]);
const previousPendingCount = ref(0);
const previousPendingIds = ref([]);
const previousAllRequests = ref([]); // Track all requests to detect status changes
const forceAlertLoading = ref(null);
const notifiedThresholdIds = ref(new Set());
let pollingInterval = null;
const POLLING_INTERVAL = 8000;

// Activity notifications — persistent with read/unread state
const loadSavedNotifications = () => {
    try {
        const saved = JSON.parse(localStorage.getItem('adminActivityNotifs') || '[]');
        return Array.isArray(saved) ? saved : [];
    } catch { return []; }
};

const activityNotifications = ref(loadSavedNotifications());
const readNotifIds = ref(new Set(JSON.parse(localStorage.getItem('adminReadNotifs') || '[]')));

// Pagination for activity notifications
const currentPage = ref(1);
const pageSize = 10;
const totalPages = computed(() => Math.ceil(sortedActivityNotifications.value.length / pageSize));

// Pagination for messages
const messagesCurrentPage = ref(1);
const messagesTotalPages = computed(() => Math.ceil(sortedAdminConversations.value.length / pageSize));

// Pagination for force alerts
const forceAlertCurrentPage = ref(1);
const forceAlertTotalPages = computed(() => Math.ceil(sortedForceAlertNotifications.value.length / pageSize));

// Sort notifications by time (newest first) and paginate
const sortedActivityNotifications = computed(() => {
    return [...activityNotifications.value].sort((a, b) => new Date(b.time) - new Date(a.time));
});

const paginatedNotifications = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    const end = start + pageSize;
    return sortedActivityNotifications.value.slice(start, end);
});

// Sort and paginate messages (conversations)
const sortedAdminConversations = computed(() => {
    return [...adminConversations.value].sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
});

const paginatedAdminConversations = computed(() => {
    const start = (messagesCurrentPage.value - 1) * pageSize;
    const end = start + pageSize;
    return sortedAdminConversations.value.slice(start, end);
});

// Sort and paginate force alert notifications
const sortedForceAlertNotifications = computed(() => {
    const urgent = sortedActivityNotifications.value.filter(n =>
        n.type === 'pending' &&
        n.request &&
        canForceAlertByUrgency(n.request) // Keep all that meet urgency threshold, regardless of current status
    );
    const priorityOrder = { critical: 0, high: 1, medium: 2, low: 3 };
    return urgent.sort((a, b) => {
        const urgencyA = (a.request?.urgency_level || 'medium').toLowerCase();
        const urgencyB = (b.request?.urgency_level || 'medium').toLowerCase();
        return (priorityOrder[urgencyA] ?? 2) - (priorityOrder[urgencyB] ?? 2);
    });
});

const paginatedForceAlertNotifications = computed(() => {
    const start = (forceAlertCurrentPage.value - 1) * pageSize;
    const end = start + pageSize;
    return sortedForceAlertNotifications.value.slice(start, end);
});

// Apply saved read state
activityNotifications.value.forEach(n => {
    if (readNotifIds.value.has(n.id)) n.read = true;
});

const saveNotifications = () => {
    try {
        const toSave = activityNotifications.value.slice(0, 100).map(n => ({
            ...n,
            request: n.request ? {
                id: n.request.id,
                rescue_code: n.request.rescue_code,
                urgency_level: n.request.urgency_level,
                force_alert: n.request.force_alert,
                created_at: n.request.created_at,
                firstName: n.request.firstName,
                lastName: n.request.lastName,
                status: n.request.status,
            } : null,
        }));
        localStorage.setItem('adminActivityNotifs', JSON.stringify(toSave));
    } catch { /* storage full */ }
};

// Conversations
const adminConversations = ref([]);
const previousConvMessageCounts = ref({});
const expandedConv = ref(null);
const expandedMessages = ref([]);
const loadingMessages = ref(false);

// Popup alert
const popupAlert = ref({
    show: false,
    title: '',
    message: '',
    type: 'error',
    icon: 'mdi-bell',
    callback: null,
});

// ── Computed counts ──
const forceAlertNotifications = computed(() => {
    return sortedForceAlertNotifications.value;
});

const unreadActivityCount = computed(() =>
    sortedActivityNotifications.value.filter(n => !n.read).length
);

const unreadMessageBadge = computed(() =>
    adminConversations.value.filter(c => c._hasNewMsg).length
);

const totalUnreadCount = computed(() =>
    unreadActivityCount.value + unreadMessageBadge.value + forceAlertNotifications.value.length + pendingRescuerApplications.value.length
);

// ── Pending Rescuer Applications ──
const pendingRescuerApplications = ref([]);
const previousPendingRescuerCount = ref(0);
const approvalLoading = ref(null);
const approvalReadIds = ref(new Set());
const declineDialogVisible = ref(false);
const declineTarget = ref(null);
const declineReason = ref('');

const fetchPendingRescuers = async () => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const resp = await fetch('/admin/rescuers/pending', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
            credentials: 'include',
        });
        if (!resp.ok) return;
        const result = await resp.json();
        if (result.success) {
            const newApps = result.data || [];
            
            // Notify admin of new applications
            if (newApps.length > previousPendingRescuerCount.value && previousPendingRescuerCount.value >= 0) {
                const newOnes = newApps.filter(a => !pendingRescuerApplications.value.find(p => p.id === a.id));
                newOnes.forEach(app => {
                    const org = app.organization ? ` from ${app.organization}` : '';
                    addActivityNotification(
                        `rescuer-app-${app.id}`,
                        `New rescuer application`,
                        `${app.first_name} ${app.last_name}${org} wants to join as a rescuer`,
                        'mdi-account-plus',
                        'orange',
                        'rescuer_application',
                        null
                    );
                });
                
                if (newOnes.length > 0 && previousPendingRescuerCount.value > 0) {
                    playNotificationSound('message');
                    vibrate([100, 50, 100]);
                    showToast('🆕 New Rescuer Application', `${newOnes[0].first_name} ${newOnes[0].last_name} applied to be a rescuer`, {
                        color: 'warning', icon: 'mdi-account-clock', timeout: 8000,
                        action: {
                            label: 'Review',
                            handler: () => { showNotificationPanel.value = true; notifTab.value = 'approvals'; snackbar.value.show = false; }
                        }
                    });
                }
            }
            
            previousPendingRescuerCount.value = newApps.length;
            pendingRescuerApplications.value = newApps;
        }
    } catch (err) {
        console.error('Error fetching pending rescuers:', err);
    }
};

const handleApproveRescuer = async (app) => {
    approvalLoading.value = app.id;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const resp = await fetch(`/admin/rescuers/${app.id}/approve`, {
            method: 'POST',
            headers: { 
                'Accept': 'application/json', 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            credentials: 'include',
        });
        const result = await resp.json();
        if (result.success) {
            pendingRescuerApplications.value = pendingRescuerApplications.value.filter(a => a.id !== app.id);
            previousPendingRescuerCount.value = pendingRescuerApplications.value.length;
            showToast('✅ Rescuer Approved', `${app.first_name} ${app.last_name} can now access the full system`, {
                color: 'success', icon: 'mdi-account-check', timeout: 5000
            });
        } else {
            showToast('Error', result.message || 'Failed to approve rescuer', { color: 'error', icon: 'mdi-alert' });
        }
    } catch (err) {
        console.error('Error approving rescuer:', err);
        showToast('Error', 'Failed to approve rescuer', { color: 'error', icon: 'mdi-alert' });
    } finally {
        approvalLoading.value = null;
    }
};

const showDeclineDialog = (app) => {
    declineTarget.value = app;
    declineReason.value = '';
    declineDialogVisible.value = true;
};

const handleDeclineRescuer = async () => {
    if (!declineTarget.value) return;
    const app = declineTarget.value;
    approvalLoading.value = app.id;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const resp = await fetch(`/admin/rescuers/${app.id}/decline`, {
            method: 'POST',
            headers: { 
                'Accept': 'application/json', 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            credentials: 'include',
            body: JSON.stringify({ reason: declineReason.value || undefined }),
        });
        const result = await resp.json();
        if (result.success) {
            pendingRescuerApplications.value = pendingRescuerApplications.value.filter(a => a.id !== app.id);
            previousPendingRescuerCount.value = pendingRescuerApplications.value.length;
            declineDialogVisible.value = false;
            showToast('Application Declined', `${app.first_name} ${app.last_name}'s application was declined`, {
                color: 'warning', icon: 'mdi-account-cancel', timeout: 5000
            });
        } else {
            showToast('Error', result.message || 'Failed to decline rescuer', { color: 'error', icon: 'mdi-alert' });
        }
    } catch (err) {
        console.error('Error declining rescuer:', err);
        showToast('Error', 'Failed to decline rescuer', { color: 'error', icon: 'mdi-alert' });
    } finally {
        approvalLoading.value = null;
    }
};

// ── Urgency Helpers ──
const URGENCY_THRESHOLDS = { critical: 10, high: 30, medium: 120, low: 300 };

const getThresholdSeconds = (request) => {
    const urgency = (request.urgency_level || 'medium').toLowerCase();
    return URGENCY_THRESHOLDS[urgency] ?? 120;
};

const getElapsedSeconds = (request) => {
    if (!request.created_at) return 0;
    return Math.floor((Date.now() - new Date(request.created_at).getTime()) / 1000);
};

const canForceAlertByUrgency = (request) => {
    if (!request?.created_at) return false;
    return getElapsedSeconds(request) >= getThresholdSeconds(request);
};

const getForceAlertCountdown = (request) => {
    if (!request?.created_at) return '';
    const remaining = getThresholdSeconds(request) - getElapsedSeconds(request);
    if (remaining <= 0) return '';
    if (remaining >= 60) return `${Math.ceil(remaining / 60)}m`;
    return `${remaining}s`;
};

const getThresholdLabel = (request) => {
    const secs = getThresholdSeconds(request);
    const urgency = (request.urgency_level || 'medium').toLowerCase();
    const label = secs >= 60 ? `${secs / 60} min` : `${secs}s`;
    return `${urgency.charAt(0).toUpperCase() + urgency.slice(1)} — notify after ${label}`;
};

const getReqLocation = (req) => {
    const parts = [];
    if (req.building?.name) parts.push(req.building.name);
    if (req.floor?.floor_name) parts.push(req.floor.floor_name);
    if (req.room?.room_name) parts.push(req.room.room_name);
    return parts.join(' › ') || 'Unknown Location';
};

// ── Format helpers ──
const formatTimeAgo = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMin = Math.floor(diffMs / 60000);
    
    let relativeTime = '';
    if (diffMin < 1) relativeTime = 'Just now';
    else if (diffMin < 60) relativeTime = `${diffMin}m ago`;
    else {
        const diffHr = Math.floor(diffMin / 60);
        if (diffHr < 24) relativeTime = `${diffHr}h ago`;
        else relativeTime = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    
    // Add actual time
    const actualTime = date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    });
    
    return `${relativeTime} • ${actualTime}`;
};

const formatMsgTime = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

const truncate = (str, len) => {
    if (!str) return '';
    return str.length > len ? str.substring(0, len) + '…' : str;
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning', 'accepted': 'info', 'in_progress': 'info',
        'en_route': 'info', 'rescued': 'success', 'completed': 'success', 'cancelled': 'error'
    };
    return colors[status] || 'grey';
};

// ── Activity notification helpers ──
const addActivityNotification = (id, title, message, icon, color, type = 'info', request = null) => {
    if (activityNotifications.value.find(n => n.id === id)) return;
    
    // Use the request's actual timestamp instead of current time
    let notificationTime = new Date().toISOString(); // fallback
    if (request) {
        // Use created_at for new requests, updated_at for status changes
        if (type === 'pending') {
            notificationTime = request.created_at || notificationTime;
        } else {
            // For status changes (progress, completed, cancelled), use updated_at or created_at
            notificationTime = request.updated_at || request.created_at || notificationTime;
        }
    }
    
    activityNotifications.value.unshift({
        id, title, message, icon, color, type,
        time: notificationTime,
        read: readNotifIds.value.has(id),
        request,
        requestId: request?.id || null,
        canForceAlert: type === 'pending' && request && canForceAlertByUrgency(request),
        forceAlerted: request?.force_alert || false,
    });
    if (activityNotifications.value.length > 100) {
        activityNotifications.value = activityNotifications.value.slice(0, 100);
    }
    // Reset to first page when new notifications arrive
    currentPage.value = 1;
    forceAlertCurrentPage.value = 1;
    saveNotifications();
};

const markActivityRead = (notif) => {
    notif.read = true;
    readNotifIds.value.add(notif.id);
    localStorage.setItem('adminReadNotifs', JSON.stringify([...readNotifIds.value]));
    saveNotifications();
};

const markAllRead = () => {
    activityNotifications.value.forEach(n => {
        n.read = true;
        readNotifIds.value.add(n.id);
    });
    localStorage.setItem('adminReadNotifs', JSON.stringify([...readNotifIds.value]));
    saveNotifications();
};

// ── Conversation helpers ──
const getConvParticipantNames = (conv) => {
    if (!conv.participants) return 'Unknown';
    const names = conv.participants.map(p => {
        const u = p.user;
        return u ? `${u.first_name || ''} ${u.last_name || ''}`.trim() : 'Unknown';
    });
    return names.join(' & ');
};

const getConvInitials = (conv, type) => {
    const p = conv.participants?.find(p => p.participant_type === type);
    if (p?.user) {
        return `${(p.user.first_name || '?')[0]}${(p.user.last_name || '?')[0]}`.toUpperCase();
    }
    return type === 'user' ? 'U' : 'R';
};

const getParticipantType = (conv, senderId) => {
    const p = conv.participants?.find(p => p.user_id === senderId || p.user?.id === senderId);
    return p?.participant_type || 'user';
};

const toggleConversation = async (conv) => {
    if (expandedConv.value === conv.id) {
        expandedConv.value = null;
        expandedMessages.value = [];
        return;
    }
    expandedConv.value = conv.id;
    // Mark conversation as read and reset message flag
    conv._hasNewMsg = false;
    // Remove from previous counts to reset badge
    delete previousConvMessageCounts.value[conv.id];
    previousConvMessageCounts.value[conv.id] = conv.total_messages || 0;
    
    loadingMessages.value = true;
    try {
        const response = await getConversationMessages(conv.id);
        const msgs = Array.isArray(response) ? response : (response?.data || []);
        expandedMessages.value = msgs;
    } catch (e) {
        console.error('Error loading messages:', e);
        expandedMessages.value = [];
    } finally {
        loadingMessages.value = false;
    }
};

// ── Popup click handler ──
const handlePopupClick = () => {
    popupAlert.value.show = false;
    if (popupAlert.value.callback) {
        popupAlert.value.callback();
    }
};

// ── Polling: Rescue requests ──
const fetchPendingRequests = async () => {
    try {
        const response = await getAllRescueRequests();
        const data = response?.data || response;
        const all = Array.isArray(data) ? data : (data?.data || []);
        const pending = all.filter(r => r.status === 'pending');
        const currentIds = pending.map(r => r.id);
        const newIds = currentIds.filter(id => !previousPendingIds.value.includes(id));

        if (newIds.length > 0 && previousPendingIds.value.length > 0) {
            const newReq = pending.find(r => r.id === newIds[0]);
            const name = newReq ? `${newReq.firstName || ''} ${newReq.lastName || ''}`.trim() : 'Someone';
            const location = newReq ? getReqLocation(newReq) : 'Unknown';

            popupAlert.value = {
                show: true,
                title: 'New Rescue Request',
                message: `${name} needs help at ${location}`,
                type: 'error',
                icon: 'mdi-alert-circle',
                callback: () => { showNotificationPanel.value = true; notifTab.value = 'activity'; },
            };

            showToast('🚨 New Rescue Request', `${name} needs help at ${location}`, {
                color: 'error', icon: 'mdi-alert-octagon', timeout: 8000,
                action: {
                    label: 'View',
                    handler: () => { showNotificationPanel.value = true; notifTab.value = 'activity'; snackbar.value.show = false; }
                }
            });

            playNotificationSound('message');
            vibrate([200, 100, 200]);
            setTimeout(() => { popupAlert.value.show = false; }, 10000);
        }

        // Only add notifications for NEW rescue requests or STATUS CHANGES
        // Compare with previous state to avoid duplicates
        all.forEach(req => {
            const name = `${req.firstName || ''} ${req.lastName || ''}`.trim() || 'Someone';
            const location = getReqLocation(req);
            const status = req.status;
            const reqId = req.id;
            
            // Find previous state of this request
            const previousReq = previousAllRequests.value.find(r => r.id === reqId);
            const previousStatus = previousReq?.status;
            
            // Only create notifications for new requests or status changes
            if (!previousReq || previousStatus !== status) {
                // Remove any existing notifications for this request to avoid duplicates
                activityNotifications.value = activityNotifications.value.filter(
                    n => !n.requestId || n.requestId !== reqId
                );
                
                // Add new notification based on current status
                if (status === 'pending') {
                    addActivityNotification(`rescue-pending-${reqId}`, `${name} needs help!`, `📍 ${location}`, 'mdi-alert-circle', 'warning', 'pending', req);
                } else if (status === 'accepted' || status === 'in_progress' || status === 'en_route') {
                    addActivityNotification(`rescue-progress-${reqId}`, 'Rescue in progress', `${name} — ${location}`, 'mdi-run-fast', 'info', 'progress', req);
                } else if (status === 'rescued' || status === 'completed' || status === 'safe') {
                    addActivityNotification(`rescue-done-${reqId}`, 'Rescue completed', `${name} has been rescued at ${location}`, 'mdi-check-circle', 'success', 'completed', req);
                } else if (status === 'cancelled') {
                    addActivityNotification(`rescue-cancelled-${reqId}`, 'Rescue cancelled', `Request for ${name} at ${location} was cancelled`, 'mdi-close-circle', 'error', 'cancelled', req);
                }
            }
        });

        // Update ALL existing notifications with fresh request data
        activityNotifications.value.forEach(n => {
            if (n.request && n.request.id) {
                const fresh = all.find(r => r.id === n.request.id);
                if (fresh) {
                    n.request = fresh;
                    n.canForceAlert = n.type === 'pending' && fresh.status === 'pending' && canForceAlertByUrgency(fresh);
                    n.forceAlerted = fresh.force_alert || false;
                }
            }
        });
        
        // Store current state for next comparison
        previousAllRequests.value = JSON.parse(JSON.stringify(all));
        saveNotifications();

        // Auto-notify admin for threshold breaches
        pending.forEach(req => {
            if (canForceAlertByUrgency(req) && !req.force_alert && !notifiedThresholdIds.value.has(req.id)) {
                notifiedThresholdIds.value.add(req.id);
                const name = `${req.firstName || ''} ${req.lastName || ''}`.trim() || 'Someone';
                const location = getReqLocation(req);
                const urgency = (req.urgency_level || 'Medium');
                const secs = getThresholdSeconds(req);
                const waitLabel = secs >= 60 ? `${secs / 60} minute(s)` : `${secs} seconds`;

                popupAlert.value = {
                    show: true,
                    title: `⚠️ No Response — ${urgency} Urgency`,
                    message: `It's been ${waitLabel}, still no rescuer for ${name} at ${location}. You may now send a Force Alert.`,
                    type: 'warning', icon: 'mdi-timer-alert',
                    callback: () => { showNotificationPanel.value = true; notifTab.value = 'activity'; },
                };
                playNotificationSound('message');
                vibrate([200, 100, 200]);
                setTimeout(() => { popupAlert.value.show = false; }, 12000);
            }
        });

        previousPendingIds.value = currentIds;
        previousPendingCount.value = pending.length;
        pendingRequests.value = pending;
    } catch (err) {
        console.error('Error fetching pending requests:', err);
    }
};

// ── Polling: Admin conversations ──
const fetchAdminConversations = async () => {
    try {
        const response = await getAdminConversations();
        const convs = Array.isArray(response) ? response : (response?.data || []);

        convs.forEach(conv => {
            const prevCount = previousConvMessageCounts.value[conv.id] || 0;
            const currentCount = conv.total_messages || 0;
            if (currentCount > prevCount && prevCount > 0) {
                conv._hasNewMsg = true;
                const lastMsg = conv.last_message;
                const senderName = lastMsg?.sender_name || 'Someone';
                
                // Use the conversation's updated_at time or last message time instead of current time
                const messageTime = conv.updated_at || lastMsg?.sent_at || new Date().toISOString();
                
                // Create a custom notification with proper timestamp
                const existingNotif = activityNotifications.value.find(n => n.id === `msg-${conv.id}-${currentCount}`);
                if (!existingNotif) {
                    activityNotifications.value.unshift({
                        id: `msg-${conv.id}-${currentCount}`,
                        title: `New message from ${senderName}`,
                        message: `${truncate(lastMsg?.content, 60)}`,
                        icon: 'mdi-message-text',
                        color: 'primary',
                        type: 'message',
                        time: messageTime, // Use actual message time
                        read: readNotifIds.value.has(`msg-${conv.id}-${currentCount}`),
                        request: null,
                        requestId: null,
                        canForceAlert: false,
                        forceAlerted: false,
                    });
                    if (activityNotifications.value.length > 100) {
                        activityNotifications.value = activityNotifications.value.slice(0, 100);
                    }
                    // Reset to first page when new notifications arrive
                    currentPage.value = 1;
                    messagesCurrentPage.value = 1;
                    saveNotifications();
                }

                if (!showNotificationPanel.value) {
                    popupAlert.value = {
                        show: true, title: 'New Message',
                        message: `${senderName}: ${truncate(lastMsg?.content, 50)}`,
                        type: 'info', icon: 'mdi-message-text',
                        callback: () => { showNotificationPanel.value = true; notifTab.value = 'messages'; },
                    };
                    showToast('💬 New Message', `${senderName}: ${truncate(lastMsg?.content, 50)}`, {
                        color: 'primary', icon: 'mdi-message-text', timeout: 6000,
                        action: {
                            label: 'View',
                            handler: () => { showNotificationPanel.value = true; notifTab.value = 'messages'; snackbar.value.show = false; }
                        }
                    });
                    playNotificationSound('message');
                    vibrate([100, 50, 100]);
                    setTimeout(() => { popupAlert.value.show = false; }, 6000);
                }
            } else {
                // Preserve existing new message state, but don't increment count
                const existing = adminConversations.value.find(c => c.id === conv.id);
                conv._hasNewMsg = existing?._hasNewMsg || false;
            }
            previousConvMessageCounts.value[conv.id] = currentCount;
        });

        adminConversations.value = convs;
    } catch (err) {
        console.error('Error fetching admin conversations:', err);
    }
};

// ── Force Alert ──
const sendForceAlert = async (request) => {
    forceAlertLoading.value = request.id;
    try {
        await triggerForceAlert(request.id);
        request.force_alert = true;
        request.force_alert_at = new Date().toISOString();
        
        // Update the notification to reflect that force alert was sent
        const notif = activityNotifications.value.find(n => n.request?.id === request.id && n.type === 'pending');
        if (notif) {
            notif.forceAlerted = true;
            notif.request.force_alert = true;
            notif.request.force_alert_at = request.force_alert_at;
            saveNotifications();
        }

        popupAlert.value = {
            show: true, title: 'Force Alert Sent',
            message: 'Available rescuers will receive an unstoppable ringtone. Rescuers with ongoing rescues will only get a normal notification.',
            type: 'success', icon: 'mdi-check-circle', callback: null,
        };
        setTimeout(() => { popupAlert.value.show = false; }, 5000);
    } catch (err) {
        console.error('Error sending force alert:', err);
        const backendMsg = err.data?.message || '';
        popupAlert.value = {
            show: true,
            title: backendMsg.includes('wait') ? 'Too Early' : 'Error',
            message: backendMsg || err.message || 'Failed to send force alert',
            type: backendMsg.includes('wait') ? 'warning' : 'error',
            icon: backendMsg.includes('wait') ? 'mdi-timer-sand' : 'mdi-alert',
            callback: null,
        };
        setTimeout(() => { popupAlert.value.show = false; }, 6000);
    } finally {
        forceAlertLoading.value = null;
    }
};

// ── Polling lifecycle ──
const startPolling = () => {
    if (pollingInterval) return;
    pollingInterval = setInterval(() => {
        fetchPendingRequests();
        fetchAdminConversations();
        fetchPendingRescuers();
    }, POLLING_INTERVAL);
};

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

// ── Lifecycle ──
onMounted(() => {
    loadAdminProfile();
    // Start notification polling
    fetchPendingRequests();
    fetchAdminConversations();
    fetchPendingRescuers();
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});

// Watch for notification panel opening to reset message badges
watch(() => showNotificationPanel.value, (isOpen) => {
    if (isOpen && notifTab.value === 'messages') {
        // Reset all conversation new message flags when messages tab is viewed
        adminConversations.value.forEach(conv => {
            if (conv._hasNewMsg) {
                conv._hasNewMsg = false;
            }
        });
    }
});

// Watch for tab changes to reset pagination
watch(() => notifTab.value, () => {
    currentPage.value = 1;
    messagesCurrentPage.value = 1;
    forceAlertCurrentPage.value = 1;
});

// Expose showToast so parent pages can use it
defineExpose({ showToast, showNotificationPanel, notifTab });
</script>

<style scoped>
/* ── Logo ── */
.app-bar-logo {
    height: 180px;
    max-width: 220px;
    object-fit: contain;
}

/* ══════════════════════════════════════════════
   NOTIFICATION CENTER — PinPointMe Branded
   ══════════════════════════════════════════════ */
.nc-drawer {
    background: #f4f6f9 !important;
    border-left: 1px solid #e8ecf0 !important;
}

.nc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    background: linear-gradient(135deg, #3674B5 0%, #2d5f96 100%);
}

.nc-header-inner {
    display: flex;
    align-items: center;
    gap: 12px;
}

.nc-header-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
}

.nc-header-title {
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.2px;
}

.nc-header-sub {
    font-size: 11.5px;
    color: rgba(255, 255, 255, 0.7);
    margin-top: 1px;
}

.nc-header-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}

.nc-mark-read-btn,
.nc-close-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s ease;
}

.nc-mark-read-btn:hover,
.nc-close-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}

/* ── Tabs ── */
.nc-tabs {
    display: flex;
    gap: 0;
    padding: 12px 16px 0;
    background: #ffffff;
    border-bottom: 1px solid #e8ecf0;
    position: relative;
}

.nc-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 12px 12px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 600;
    color: #90A4AE;
    cursor: pointer;
    transition: color 0.2s ease;
    position: relative;
    z-index: 1;
}

.nc-tab-active { color: #3674B5; }
.nc-tab:hover:not(.nc-tab-active) { color: #546E7A; }

.nc-tab-slider {
    position: absolute;
    bottom: 0;
    left: 16px;
    width: calc(50% - 16px);
    height: 2.5px;
    background: #3674B5;
    border-radius: 2px 2px 0 0;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.nc-tabs-three .nc-tab-slider { width: calc(33.33% - 16px); }
.nc-tabs-three .nc-tab-slider-0 { transform: translateX(0); }
.nc-tabs-three .nc-tab-slider-1 { transform: translateX(100%); }
.nc-tabs-three .nc-tab-slider-2 { transform: translateX(200%); }

/* 4-tab layout */
.nc-tabs-four .nc-tab-slider { width: calc(25% - 8px); }
.nc-tabs-four .nc-tab { font-size: 11.5px; gap: 4px; padding: 10px 6px 12px; }
.nc-tab-slider-four.nc-tab-slider-0 { transform: translateX(0); }
.nc-tab-slider-four.nc-tab-slider-1 { transform: translateX(100%); }
.nc-tab-slider-four.nc-tab-slider-2 { transform: translateX(200%); }
.nc-tab-slider-four.nc-tab-slider-3 { transform: translateX(300%); }

.nc-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    font-size: 10px;
    font-weight: 700;
    padding: 0 5px;
    color: #ffffff;
    line-height: 1;
}

.nc-badge-error { background: #b71c1c; }
.nc-badge-red { background: #b71c1c; }
.nc-badge-blue { background: #3674B5; }
.nc-badge-orange { background: #E65100; }

/* ── Body / List ── */
.nc-body {
    overflow-y: auto;
    max-height: calc(100vh - 140px);
    padding: 12px 14px;
}

.nc-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 60px 24px;
    text-align: center;
}

.nc-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #eef1f5;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.nc-empty-title { font-size: 14px; font-weight: 600; color: #546E7A; margin: 0 0 4px; }
.nc-empty-sub { font-size: 12.5px; color: #90A4AE; margin: 0; }

/* ── Notification Items ── */
.nc-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    margin-bottom: 8px;
    background: #ffffff;
    border-radius: 14px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    border: 1px solid transparent;
}

.nc-item:hover { background: #fafbfd; box-shadow: 0 2px 10px rgba(54, 116, 181, 0.07); transform: translateY(-1px); }
.nc-item-unread { background: #f0f6ff; border-color: rgba(54, 116, 181, 0.1); }
.nc-item-unread:hover { background: #e8f0fc; }

.nc-item-bar { position: absolute; left: 0; top: 0; bottom: 0; width: 3.5px; border-radius: 0 3px 3px 0; }
.nc-bar-warning { background: linear-gradient(180deg, #DFA92C, #c9941f); }
.nc-bar-info    { background: linear-gradient(180deg, #42A5F5, #3674B5); }
.nc-bar-success { background: linear-gradient(180deg, #66BB6A, #185D33); }
.nc-bar-error   { background: linear-gradient(180deg, #EF5350, #b71c1c); }
.nc-bar-primary { background: linear-gradient(180deg, #42A5F5, #3674B5); }
.nc-bar-orange  { background: linear-gradient(180deg, #FF9800, #E65100); }

.nc-item-icon { width: 38px; height: 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.nc-icon-warning { background: linear-gradient(135deg, #FFA726, #DFA92C); }
.nc-icon-info    { background: linear-gradient(135deg, #42A5F5, #3674B5); }
.nc-icon-success { background: linear-gradient(135deg, #66BB6A, #2E7D32); }
.nc-icon-error   { background: linear-gradient(135deg, #EF5350, #b71c1c); }
.nc-icon-primary { background: linear-gradient(135deg, #42A5F5, #3674B5); }
.nc-icon-orange  { background: linear-gradient(135deg, #FF9800, #E65100); }

.nc-item-content { flex: 1; min-width: 0; }
.nc-item-top { display: flex; align-items: center; gap: 8px; margin-bottom: 3px; }
.nc-item-title { font-size: 13px; font-weight: 650; color: #13294B; line-height: 1.35; flex: 1; word-break: break-word; }

.nc-unread-dot { width: 7px; height: 7px; border-radius: 50%; background: #3674B5; flex-shrink: 0; box-shadow: 0 0 0 2px rgba(54, 116, 181, 0.2); margin-top: 2px; }
.nc-item-msg { font-size: 12px; color: #546E7A; line-height: 1.45; word-break: break-word; }
.nc-item-detail { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; }
.nc-detail-code, .nc-detail-name { display: inline-flex; align-items: center; gap: 3px; font-size: 11px; color: #78909C; background: rgba(54, 116, 181, 0.06); padding: 1px 7px; border-radius: 5px; }
.nc-item-footer { display: flex; align-items: center; gap: 10px; margin-top: 6px; }
.nc-item-time { font-size: 11px; color: #90A4AE; display: flex; align-items: center; gap: 3px; }

/* Force Alert buttons */
.nc-force-btn {
    display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border: none; border-radius: 8px;
    background: linear-gradient(135deg, #EF5350, #C62828); color: #ffffff; font-size: 10.5px; font-weight: 700;
    letter-spacing: 0.3px; cursor: pointer; transition: transform 0.12s ease, box-shadow 0.12s ease;
    box-shadow: 0 2px 6px rgba(198, 40, 40, 0.25);
}
.nc-force-btn:hover { transform: scale(1.04); box-shadow: 0 3px 10px rgba(198, 40, 40, 0.35); }
.nc-force-disabled { 
    opacity: 0.4 !important; 
    cursor: not-allowed !important; 
    background: linear-gradient(135deg, #9E9E9E, #757575) !important;
    box-shadow: none !important;
}
.nc-force-disabled:hover { 
    transform: none !important; 
    box-shadow: none !important; 
}

.nc-force-btn-large {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: none; border-radius: 10px;
    background: linear-gradient(135deg, #EF5350, #C62828); color: #ffffff; font-size: 13px; font-weight: 700;
    letter-spacing: 0.4px; cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease; box-shadow: 0 3px 8px rgba(198, 40, 40, 0.3);
}
.nc-force-btn-large:hover { transform: scale(1.05); box-shadow: 0 4px 14px rgba(198, 40, 40, 0.45); }
.nc-force-btn-large:active { transform: scale(0.98); }
.nc-force-disabled { 
    opacity: 0.4 !important; 
    cursor: not-allowed !important; 
    background: linear-gradient(135deg, #9E9E9E, #757575) !important;
    box-shadow: none !important;
}
.nc-force-disabled:hover { 
    transform: none !important; 
    box-shadow: none !important; 
}

.nc-item-urgent { background: linear-gradient(135deg, rgba(239, 83, 80, 0.04), rgba(198, 40, 40, 0.04)); border-color: rgba(239, 83, 80, 0.2); border-width: 1.5px; }
.nc-item-urgent:hover { background: linear-gradient(135deg, rgba(239, 83, 80, 0.06), rgba(198, 40, 40, 0.06)); box-shadow: 0 3px 12px rgba(239, 83, 80, 0.15); }
.nc-item-urgent .nc-item-bar { width: 4px; background: linear-gradient(180deg, #EF5350, #C62828); }
.nc-item-urgent .nc-item-title { color: #B71C1C; font-weight: 700; }
.nc-force-loading { opacity: 0.6; pointer-events: none; }

.nc-alerted-chip { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; background: rgba(198, 40, 40, 0.08); color: #C62828; font-size: 10.5px; font-weight: 600; }
.nc-safe-chip { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; background: rgba(76, 175, 80, 0.1); color: #2E7D32; font-size: 10.5px; font-weight: 600; }
.nc-cancelled-chip { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; background: rgba(183, 28, 28, 0.08); color: #B71C1C; font-size: 10.5px; font-weight: 600; }
.nc-countdown-chip { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 152, 0, 0.1); color: #E65100; font-size: 10.5px; font-weight: 600; }

/* ── Approval Item Styles ── */
.nc-item-approval { background: linear-gradient(135deg, rgba(255, 152, 0, 0.03), rgba(230, 81, 0, 0.03)); border-color: rgba(255, 152, 0, 0.15); }
.nc-item-approval:hover { background: linear-gradient(135deg, rgba(255, 152, 0, 0.06), rgba(230, 81, 0, 0.06)); box-shadow: 0 3px 12px rgba(255, 152, 0, 0.12); }
.nc-item-approval .nc-item-bar { width: 3.5px; background: linear-gradient(180deg, #FF9800, #E65100); }

.nc-external-chip { display: inline-flex; align-items: center; gap: 3px; padding: 1px 7px; border-radius: 5px; background: rgba(230, 81, 0, 0.08); color: #E65100; font-size: 10.5px; font-weight: 600; }
.nc-verified-chip { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; background: rgba(76, 175, 80, 0.1); color: #2E7D32; font-size: 10.5px; font-weight: 600; }
.nc-unverified-chip { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 152, 0, 0.1); color: #E65100; font-size: 10.5px; font-weight: 600; }

.nc-approval-actions { display: flex; gap: 8px; margin-top: 10px; }
.nc-approve-btn {
    display: inline-flex; align-items: center; gap: 5px; padding: 6px 16px; border: none; border-radius: 8px;
    background: linear-gradient(135deg, #66BB6A, #2E7D32); color: #ffffff; font-size: 12px; font-weight: 700;
    cursor: pointer; transition: transform 0.12s ease, box-shadow 0.12s ease;
    box-shadow: 0 2px 6px rgba(46, 125, 50, 0.25);
}
.nc-approve-btn:hover { transform: scale(1.04); box-shadow: 0 3px 10px rgba(46, 125, 50, 0.35); }
.nc-decline-btn {
    display: inline-flex; align-items: center; gap: 5px; padding: 6px 16px; border: 1.5px solid #EF5350; border-radius: 8px;
    background: transparent; color: #C62828; font-size: 12px; font-weight: 700;
    cursor: pointer; transition: all 0.12s ease;
}
.nc-decline-btn:hover { background: rgba(239, 83, 80, 0.06); transform: scale(1.04); }
.nc-btn-loading { opacity: 0.6; pointer-events: none; }

.nc-urgency-chip { display: inline-flex; align-items: center; padding: 1px 7px; border-radius: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.3px; text-transform: uppercase; }
.nc-urgency-critical { background: rgba(183, 28, 28, 0.1); color: #b71c1c; }
.nc-urgency-high     { background: rgba(230, 81, 0, 0.1);  color: #E65100; }
.nc-urgency-medium   { background: rgba(255, 152, 0, 0.1); color: #E65100; }
.nc-urgency-low      { background: rgba(46, 125, 50, 0.1); color: #2E7D32; }

/* ── Conversation Cards ── */
.nc-conv { background: #ffffff; border-radius: 14px; margin-bottom: 8px; overflow: hidden; border: 1px solid transparent; transition: box-shadow 0.2s ease, border-color 0.2s ease; }
.nc-conv:hover { box-shadow: 0 2px 10px rgba(54, 116, 181, 0.07); }
.nc-conv-open { border-color: rgba(54, 116, 181, 0.15); box-shadow: 0 4px 16px rgba(54, 116, 181, 0.1); }
.nc-conv-new { border-color: rgba(54, 116, 181, 0.2); background: #f8fbff; }

.nc-conv-header { display: flex; align-items: center; gap: 12px; padding: 14px; cursor: pointer; transition: background 0.15s ease; }
.nc-conv-header:hover { background: #fafbfd; }

.nc-conv-avatars { position: relative; width: 46px; height: 36px; flex-shrink: 0; }
.nc-avatar { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: #ffffff; position: absolute; border: 2.5px solid #ffffff; }
.nc-avatar-user { background: linear-gradient(135deg, #42A5F5, #3674B5); left: 0; top: 0; z-index: 2; }
.nc-avatar-rescuer { background: linear-gradient(135deg, #66BB6A, #185D33); left: 16px; top: 4px; z-index: 1; }

.nc-conv-info { flex: 1; min-width: 0; }
.nc-conv-name { font-size: 13px; font-weight: 650; color: #13294B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.3; }
.nc-conv-preview { font-size: 12px; color: #78909C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
.nc-conv-sender { font-weight: 600; color: #546E7A; }
.nc-conv-tags { display: flex; align-items: center; gap: 8px; margin-top: 5px; }

.nc-tag { display: inline-block; padding: 1px 7px; border-radius: 5px; font-size: 10px; font-weight: 700; letter-spacing: 0.4px; }
.nc-tag-warning { background: rgba(223, 169, 44, 0.12); color: #c9941f; }
.nc-tag-info    { background: rgba(54, 116, 181, 0.1);  color: #3674B5; }
.nc-tag-success { background: rgba(24, 93, 51, 0.1);    color: #185D33; }
.nc-tag-error   { background: rgba(183, 28, 28, 0.1);   color: #b71c1c; }
.nc-tag-grey    { background: rgba(0, 0, 0, 0.06);      color: #78909C; }

.nc-conv-time { font-size: 11px; color: #90A4AE; }
.nc-conv-right { display: flex; flex-direction: column; align-items: center; gap: 4px; flex-shrink: 0; }
.nc-msg-count { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 8px; background: rgba(54, 116, 181, 0.08); color: #3674B5; font-size: 11px; font-weight: 700; }
.nc-conv-chevron { color: #B0BEC5; transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.nc-chevron-up { transform: rotate(180deg); }

.nc-conv-body { border-top: 1px solid #eef1f5; background: #f8f9fb; }
.nc-conv-body-label { display: flex; align-items: center; justify-content: center; gap: 5px; padding: 7px 12px; font-size: 10.5px; font-weight: 600; color: #90A4AE; text-transform: uppercase; letter-spacing: 0.5px; background: #f0f2f5; }
.nc-conv-loading { display: flex; justify-content: center; padding: 20px; }
.nc-msg-list { padding: 12px; max-height: 320px; overflow-y: auto; }

.nc-msg { max-width: 82%; margin-bottom: 8px; }
.nc-msg-left { margin-right: auto; }
.nc-msg-right { margin-left: auto; }
.nc-msg-name { font-size: 10.5px; font-weight: 650; margin-bottom: 3px; padding: 0 4px; }
.nc-msg-left .nc-msg-name { color: #3674B5; }
.nc-msg-right .nc-msg-name { color: #185D33; text-align: right; }

.nc-msg-bubble { padding: 9px 14px; border-radius: 14px; font-size: 13px; color: #263238; line-height: 1.45; word-break: break-word; }
.nc-msg-left .nc-msg-bubble { background: #ffffff; border: 1px solid #e8ecf0; border-bottom-left-radius: 4px; }
.nc-msg-right .nc-msg-bubble { background: linear-gradient(135deg, #e8f5e9, #dcedc8); border-bottom-right-radius: 4px; }

.nc-msg-time { font-size: 10px; color: #90A4AE; margin-top: 3px; padding: 0 4px; }
.nc-msg-left .nc-msg-time { text-align: left; }
.nc-msg-right .nc-msg-time { text-align: right; }
.nc-conv-empty-msg { text-align: center; font-size: 12.5px; color: #90A4AE; padding: 16px 0; }

/* ── Pagination Controls ── */
.nc-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px;
    border-top: 1px solid #eef1f5;
    background: #fafbfd;
    margin-top: 8px;
}

.nc-page-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    background: #ffffff;
    color: #546E7A;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.nc-page-btn:hover:not(.nc-page-disabled) {
    background: #3674B5;
    color: white;
    border-color: #3674B5;
}

.nc-page-disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

.nc-page-info {
    font-size: 12px;
    font-weight: 600;
    color: #546E7A;
    padding: 0 8px;
    min-width: 60px;
    text-align: center;
}
</style>
