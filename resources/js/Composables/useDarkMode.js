/**
 * useDarkMode Composable
 * ======================
 * Global dark mode state management shared across User, Rescuer, and Admin.
 *
 * Usage:
 *   import { useDarkMode } from '@/Composables/useDarkMode';
 *   const { isDark, toggle, enable, disable } = useDarkMode();
 *
 * Features:
 *   - Single localStorage key ('ppm-dark-mode') for all roles
 *   - Reactive state that auto-persists
 *   - Applies 'dark-mode' class to both <html> and <body>
 *   - Also switches Vuetify's global theme between 'light' and 'dark'
 *   - Can be initialized at app startup (initDarkMode)
 */

import { ref, watch } from 'vue';

const STORAGE_KEY = 'ppm-dark-mode';

// Shared reactive state (singleton across all imports)
const isDark = ref(false);
let initialized = false;
let vuetifyInstance = null;

/**
 * Apply or remove the dark-mode class on html and body,
 * AND switch the Vuetify theme globally.
 */
function applyDarkMode(enabled) {
    const action = enabled ? 'add' : 'remove';
    document.documentElement.classList[action]('dark-mode');
    document.body.classList[action]('dark-mode');

    // Switch Vuetify theme
    if (vuetifyInstance?.theme?.global?.name != null) {
        vuetifyInstance.theme.global.name.value = enabled ? 'dark' : 'light';
    }
}

/**
 * Persist dark mode preference to localStorage
 */
function persist(enabled) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(enabled));
        // Also sync to legacy keys so settings toggles stay in sync
        try {
            const userSettings = localStorage.getItem('userSettings');
            if (userSettings) {
                const parsed = JSON.parse(userSettings);
                parsed.darkMode = enabled;
                localStorage.setItem('userSettings', JSON.stringify(parsed));
            }
            const rescuerSettings = localStorage.getItem('rescuerSettings');
            if (rescuerSettings) {
                const parsed = JSON.parse(rescuerSettings);
                parsed.darkMode = enabled;
                localStorage.setItem('rescuerSettings', JSON.stringify(parsed));
            }
        } catch { /* ignore */ }
    } catch {
        // Silently fail if localStorage is unavailable
    }
}

/**
 * Read persisted preference from localStorage.
 * Also checks legacy keys ('userSettings', 'rescuerSettings') for migration.
 */
function readPersistedValue() {
    try {
        // Check the unified key first
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored !== null) {
            return JSON.parse(stored);
        }

        // Migrate from legacy User settings
        const userSettings = localStorage.getItem('userSettings');
        if (userSettings) {
            const parsed = JSON.parse(userSettings);
            if (typeof parsed.darkMode === 'boolean') {
                return parsed.darkMode;
            }
        }

        // Migrate from legacy Rescuer settings
        const rescuerSettings = localStorage.getItem('rescuerSettings');
        if (rescuerSettings) {
            const parsed = JSON.parse(rescuerSettings);
            if (typeof parsed.darkMode === 'boolean') {
                return parsed.darkMode;
            }
        }
    } catch {
        // Silently fail
    }

    return false;
}

/**
 * Initialize dark mode from persisted state.
 * Should be called once at app startup (e.g., in app.js).
 * @param {object} vuetify - The Vuetify instance for theme switching
 */
export function initDarkMode(vuetify) {
    if (vuetify) {
        vuetifyInstance = vuetify;
    }

    if (initialized) {
        // If already initialized but Vuetify just became available, sync theme
        if (vuetify) applyDarkMode(isDark.value);
        return;
    }
    initialized = true;

    const persisted = readPersistedValue();
    isDark.value = persisted;
    applyDarkMode(persisted);

    // Persist to unified key (handles migration)
    persist(persisted);
}

/**
 * Composable for using dark mode in any component.
 */
export function useDarkMode() {
    // Ensure initialized on first use
    if (!initialized) {
        initDarkMode();
    }

    // Watch for changes and apply
    watch(isDark, (newVal) => {
        applyDarkMode(newVal);
        persist(newVal);
    });

    function toggle() {
        isDark.value = !isDark.value;
    }

    function enable() {
        isDark.value = true;
    }

    function disable() {
        isDark.value = false;
    }

    function set(value) {
        isDark.value = !!value;
    }

    return {
        isDark,
        toggle,
        enable,
        disable,
        set,
    };
}

export default useDarkMode;
