<template>
    <v-menu location="bottom">
        <template v-slot:activator="{ props }">
            <v-btn
                icon="mdi-cog"
                v-bind="props"
                v-tooltip:bottom="'Settings'"
                class="mr-2 ml-3"
            >
            </v-btn>
        </template>

        <v-list>
            <!-- <c-list-item
                v-for="(item, index) in items"
                :key="index"
                :value="index"
            >
                <c-list-item-title>{{ item.title }}</c-list-item-title>
            </c-list-item> -->

            <v-list-item
                v-if="showChangePassword"
                @click="handleChangePassword"
                class="settings-hover"
            >
                <template v-slot:prepend>
                    <v-icon
                        size="small"
                        :color="
                            theme.global.current.value.dark
                                ? 'grey-lighten-2'
                                : 'primary'
                        "
                        icon="mdi-lock-reset"
                    />
                </template>
                Change Password
            </v-list-item>

            <v-list-item @click="handleLogout" class="settings-hover">
                <template v-slot:prepend>
                    <v-icon
                        size="small"
                        color="error"
                        icon="mdi-logout"
                    ></v-icon>
                </template>
                Logout
            </v-list-item>
        </v-list>
    </v-menu>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { setUserActiveStatus } from '@/Utilities/firebase';
import { useTheme } from "vuetify";

const theme = useTheme();

defineProps({
    showChangePassword: {
        type: Boolean,
        default: true,
    },
    errors: Object,
    flash: Object,
    can: Array,
});

// const items = [{ title: "Change Password" }];

const changePasswordRef = ref(null);
const handleChangePassword = () => {
    if (!changePasswordRef.value) {
        console.error("Change Password component is not available.");
        return;
    }

    changePasswordRef.value.toggleDialog();
};

const handleLogout = async () => {
    // 1. Clear ALL local storage & session data FIRST to ensure clean state
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
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
</script>
