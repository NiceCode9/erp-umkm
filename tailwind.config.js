import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: [
                    "-apple-system",
                    "BlinkMacSystemFont",
                    '"Segoe UI"',
                    "Roboto",
                    '"Helvetica Neue"',
                    "Arial",
                    '"Noto Sans"',
                    "sans-serif",
                    '"Apple Color Emoji"',
                    '"Segoe UI Emoji"',
                    '"Segoe UI Symbol"',
                    '"Noto Color Emoji"',
                ],
                // Dipakai khusus di welcome.blade.php (landing page), tidak menimpa "sans"
                display: ["Nunito", "-apple-system", "sans-serif"],
                body: [
                    "Nunito",
                    "-apple-system",
                    "BlinkMacSystemFont",
                    "sans-serif",
                ],
            },
            colors: {
                background: "var(--background)",
                foreground: "var(--foreground)",
                card: "var(--card)",
                "card-foreground": "var(--card-foreground)",
                popover: "var(--popover)",
                "popover-foreground": "var(--popover-foreground)",
                primary: "var(--primary)",
                "primary-foreground": "var(--primary-foreground)",
                secondary: "var(--secondary)",
                "secondary-foreground": "var(--secondary-foreground)",
                muted: "var(--muted)",
                "muted-foreground": "var(--muted-foreground)",
                accent: "var(--accent)",
                "accent-foreground": "var(--accent-foreground)",
                info: "var(--info)",
                "info-foreground": "var(--info-foreground)",
                warning: "var(--warning)",
                "warning-foreground": "var(--warning-foreground)",
                destructive: "var(--destructive)",
                "destructive-foreground": "var(--destructive-foreground)",
                border: "var(--border)",
                input: "var(--input)",
                ring: "var(--ring)",

                // --- Ditambahkan untuk welcome.blade.php (landing page) ---
                // Semua key baru di bawah ini TIDAK menimpa key bawaan Tailwind
                // (mis. tidak memakai nama polos "red"/"yellow"/"purple" yang
                // akan menabrak palet default dan bisa dipakai di halaman lain).
                "primary-hover": "#49AD00",
                "primary-shadow": "#58A700",
                "secondary-shadow": "#1899D6",
                "brand-yellow": "#FFC800",
                "brand-yellow-shadow": "#E5B400",
                "brand-red": "#FF4B4B",
                "brand-red-shadow": "#EA2B2B",
                "brand-purple": "#CE82FF",
                "brand-purple-shadow": "#A568CC",
                ink: "#3C3C3C",
                "ink-muted": "#777777",
                canvas: "#ffffff",
                "surface-1": "#F7F7F7",
                "surface-2": "#EBEBEB",
                streak: "#FF9600",
                xp: "#FFC800",
            },
            borderRadius: {
                DEFAULT: "var(--radius)",
            },
            boxShadow: {
                // --- Ditambahkan untuk welcome.blade.php (landing page) ---
                // Key baru (btn, btn-sm, dst) — tidak ada shadow bawaan dengan
                // nama ini, jadi aman tanpa menimpa apa pun.
                btn: "0 4px 0 #58A700",
                "btn-sm": "0 3px 0 #58A700",
                "btn-blue": "0 4px 0 #1899D6",
                "btn-purple": "0 4px 0 #A568CC",
                card: "0 2px 0 #E5E5E5",
                elevated: "0 12px 32px rgba(60,60,60,0.10)",
            },
        },
    },

    plugins: [forms],
};
