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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                admin: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                cine: {
                    bg: 'rgb(var(--cine-bg) / <alpha-value>)',
                    deep: 'rgb(var(--cine-deep) / <alpha-value>)',
                    surface: 'rgb(var(--cine-surface) / <alpha-value>)',
                    elevated: 'rgb(var(--cine-elevated) / <alpha-value>)',
                    border: 'rgb(var(--cine-border) / <alpha-value>)',
                    accent: 'rgb(var(--cine-accent) / <alpha-value>)',
                    'selected-start': 'rgb(var(--cine-selected-start) / <alpha-value>)',
                    'selected-end': 'rgb(var(--cine-selected-end) / <alpha-value>)',
                    primary: 'rgb(var(--cine-primary) / <alpha-value>)',
                    secondary: 'rgb(var(--cine-secondary) / <alpha-value>)',
                    tertiary: 'rgb(var(--cine-tertiary) / <alpha-value>)',
                    'disabled-bg': 'rgb(var(--cine-disabled-bg) / <alpha-value>)',
                    disabled: 'rgb(var(--cine-disabled) / <alpha-value>)',
                    success: 'rgb(var(--cine-success) / <alpha-value>)',
                    warning: 'rgb(var(--cine-warning) / <alpha-value>)',
                    danger: 'rgb(var(--cine-danger) / <alpha-value>)',
                },
            },
            boxShadow: {
                'cine-card': '0 12px 40px rgb(0 0 0 / 0.18)',
                'cine-glow': '0 0 24px rgb(var(--cine-accent) / 0.2)',
                'cine-focus': '0 0 0 2px rgb(var(--cine-bg)), 0 0 0 4px rgb(var(--cine-accent))',
            },
        },
    },

    plugins: [forms],
};
