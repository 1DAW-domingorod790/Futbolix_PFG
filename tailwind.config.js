import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                futbolix: {
                    green:   '#16a34a',
                    'green-dark': '#15803d',
                    'green-light': '#22c55e',
                    navy:    '#0f172a',
                    dark:    '#1e293b',
                    gold:    '#f59e0b',
                    'gold-light': '#fbbf24',
                },
            },
        },
    },

    plugins: [forms],
};
