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
            colors: {
                primary: {
                    100: 'var(--colors-primary-g100)',
                    500: 'var(--colors-primary-g500)',
                    600: 'var(--colors-primary-g600)',
                },
                mono: {
                    white: 'var(--colors-mono-white)',
                    black: 'var(--colors-mono-black)',
                    50: 'var(--colors-mono-g50)',
                    100: 'var(--colors-mono-g100)',
                    200: 'var(--colors-mono-g200)',
                    300: 'var(--colors-mono-g300)',
                    400: 'var(--colors-mono-g400)',
                    600: 'var(--colors-mono-g600)',
                    900: 'var(--colors-mono-g900)',
                },
                success: 'var(--colors-success)',
                'success-bg': 'var(--colors-success-bg)',
                error: 'var(--colors-error)',
                up: 'var(--colors-up)',
                'up-bg': 'var(--colors-up-bg)',
                down: 'var(--colors-down)',
                'down-bg': 'var(--colors-down-bg)',
                info: 'var(--colors-info)',
                'info-bg': 'var(--colors-info-bg)',
            },
            fontFamily: {
                sans: ['Reddit Sans', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                xxs: '0.625rem',
            },
            lineHeight: {
                sm: '1.2',
                md: '1.4',
                lg: '1.6',
            },
            borderRadius: {
                pill: '999px',
            },
            boxShadow: {
                card: 'var(--shadow-card)',
                dropdown: 'var(--shadow-dropdown)',
                elevated: 'var(--shadow-elevated)',
            },
            zIndex: {
                dropdown: '100',
                modal: '1000',
            },
        },
    },

    plugins: [forms],
};
