import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        'bg-[#DEE535]',
        'bg-[#16BC2E]',
        'text-slate-700',
        'font-medium',
        'hover:bg-[#DEE535]',
        'hover:text-black',
        'focus-visible:ring-[#16BC2E]/50',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'brand-lime': '#16BC2E',
                'brand-yellow-green': '#DEE535',
                'brand-green-dark': '#0F8420',
            },
        },
    },

    plugins: [forms],
};
