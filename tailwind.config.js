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
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                ink: '#1B1E27',
                paper: '#F6F5F1',
                surface: '#FFFFFF',
                line: '#E5E3DC',
                amber: {
                    DEFAULT: '#B45309',
                },
            },
        },
    },

    plugins: [forms],
};