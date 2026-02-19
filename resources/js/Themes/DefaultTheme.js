const light = {
    dark: false,
    colors: {
        background: "#ECEFF1",
        primary: "#13294B", //blue
        secondary: "#DFA92C", //gold
        accent: "#185D33", //green
        error: "#b71c1c", //red
        "search-bg": "#FBFBFC",
        "status-occupied": "#4c3629", // light brown
        "expansion-panel-bg": "#FFFFFF", // white
        // User App colors
        "user-bg-start": "#13294B", // primary gradient start
        "user-bg-end": "#185D33", // accent gradient end
        "user-surface": "#f8f9fa", // light surface
    },
};

const dark = {
    dark: true,
    colors: {
        background: "#0f172a",           // Matched to --dm-bg-base (deep slate)
        surface: "#1e293b",              // Matched to --dm-bg-surface
        "surface-variant": "#334155",    // Matched to --dm-bg-elevated
        primary: "#60a5fa",              // Bright blue – readable on dark bg
        secondary: "#fbbf24",            // Amber – warm accent
        accent: "#4ade80",               // Green accent
        error: "#f87171",               // Soft red
        info: "#38bdf8",                // Cyan
        success: "#4ade80",             // Green
        warning: "#fbbf24",             // Amber
        "on-background": "#f1f5f9",     // Light text on dark bg
        "on-surface": "#f1f5f9",        // Light text on dark surfaces
        "on-primary": "#0f172a",        // Dark text on bright primary
        "search-bg": "#1e293b",
        "primary-tonal": "#60a5fa",
        "secondary-tonal": "#fbbf24",
        "accent-tonal": "#4ade80",
        "error-tonal": "#f87171",
        "primary-tonal-alert": "#60a5fa",
        "secondary-tonal-alert": "#fbbf24",
        "accent-tonal-alert": "#4ade80",
        "error-tonal-alert": "#f87171",
        "status-occupied": "#c1b7b1",
        "expansion-panel-bg": "#1e293b",
        // User App colors
        "user-bg-start": "#0f172a",      // Deep slate start
        "user-bg-end": "#1e293b",        // Slate surface end
        "user-surface": "#1e293b",       // Dark surface
    },
};

export { light, dark };
export default light;
