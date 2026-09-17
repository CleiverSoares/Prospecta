import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#0083C1',
                    strong: '#006EA3',
                    soft: '#E6F4FB',
                    muted: '#4BA3D1',
                },
                surface: {
                    DEFAULT: '#FFFFFF',
                    muted: '#F4F6F9',
                    line: '#E5EAF0',
                },
                ink: {
                    DEFAULT: '#1B2329',
                    soft: '#5B6B79',
                    faint: '#8A98A6',
                },
            },
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                panel: '0 1px 2px rgba(27, 35, 41, 0.06), 0 1px 3px rgba(27, 35, 41, 0.04)',
            },
        },
    },

    plugins: [forms],
};
