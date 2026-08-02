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
                    "Inter",
                    "ui-sans-serif",
                    "system-ui",
                    ...defaultTheme.fontFamily.sans,
                ],
            },
            colors: {
                brand: {
                    50: "#eff6ff",
                    100: "#dbeafe",
                    200: "#bfdbfe",
                    300: "#93c5fd",
                    400: "#60a5fa",
                    500: "#3b82f6",
                    600: "#2563eb",
                    700: "#1d4ed8",
                    800: "#1e40af",
                    900: "#1e3a8a",
                    950: "#172554",
                },
            },
            boxShadow: {
                card: "0 24px 80px rgba(15, 23, 42, 0.08)",
                "card-sm":
                    "0 1px 2px 0 rgb(0 0 0 / 0.03), 0 1px 6px -1px rgb(0 0 0 / 0.06)",
                "card-md": "0 4px 12px -2px rgb(15 23 42 / 0.08)",
                "card-lg": "0 12px 32px -8px rgb(15 23 42 / 0.12)",
            },
            borderRadius: {
                "2.5xl": "1.25rem",
            },
        },
    },

    plugins: [forms],
};
