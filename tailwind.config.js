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
                    DEFAULT: '#38bdf8',
                    strong: '#0ea5e9',
                    soft: 'rgba(56, 189, 248, 0.28)',
                },
                ink: {
                    DEFAULT: '#071018',
                    2: '#0b1a2a',
                },
                paper: '#e8f1f5',
            },
            fontFamily: {
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
                display: ['Syne', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
