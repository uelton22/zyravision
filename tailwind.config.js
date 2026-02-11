/** @type {import('tailwindcss').Config} */

// ZyraVision Design Tokens
const colors = {
    // Primary — Azul Profundo (Segurança e Tecnologia)
    primary: {
        50: '#EBF5FF',
        100: '#D6EBFF',
        200: '#ADD6FF',
        300: '#85C2FF',
        400: '#5CADFF',
        500: '#1A73E8', // Main
        600: '#155CB8',
        700: '#10448A',
        800: '#0A2D5C',
        900: '#05162E',
    },
    // Secondary — Verde Esmeralda (Status e Ativo)
    secondary: {
        50: '#ECFDF5',
        100: '#D1FAE5',
        200: '#A7F3D0',
        300: '#6EE7B7',
        400: '#34D399',
        500: '#10B981', // Main
        600: '#059669',
        700: '#047857',
        800: '#065F46',
        900: '#064E3B',
    },
    // Accent — Laranja Vibrante (CTAs e Atenção)
    accent: {
        50: '#FFF7ED',
        100: '#FFEDD5',
        200: '#FED7AA',
        300: '#FDBA74',
        400: '#FB923C',
        500: '#F97316', // Main
        600: '#EA580C',
        700: '#C2410C',
        800: '#9A3412',
        900: '#7C2D12',
    },
    // Danger
    danger: {
        50: '#FEF2F2',
        100: '#FEE2E2',
        200: '#FECACA',
        300: '#FCA5A5',
        400: '#F87171',
        500: '#EF4444',
        600: '#DC2626',
        700: '#B91C1C',
        800: '#991B1B',
        900: '#7F1D1D',
    },
    // Warning
    warning: {
        50: '#FFFBEB',
        100: '#FEF3C7',
        200: '#FDE68A',
        300: '#FCD34D',
        400: '#FBBF24',
        500: '#F59E0B',
        600: '#D97706',
        700: '#B45309',
        800: '#92400E',
        900: '#78350F',
    },
    // Dark (surface colors)
    dark: {
        50: '#F8FAFC',
        100: '#F1F5F9',
        200: '#E2E8F0',
        300: '#CBD5E1',
        400: '#94A3B8',
        500: '#64748B',
        600: '#475569',
        700: '#334155',
        800: '#1E293B',
        900: '#0F172A',
        950: '#020617',
    },
};

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.ts',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors,
            fontFamily: {
                sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
            },
            fontSize: {
                'display-xl': ['4.5rem', { lineHeight: '1.1', letterSpacing: '-0.02em' }],
                'display-lg': ['3.75rem', { lineHeight: '1.1', letterSpacing: '-0.02em' }],
                'display': ['3rem', { lineHeight: '1.2', letterSpacing: '-0.01em' }],
                'heading-xl': ['2.25rem', { lineHeight: '1.3' }],
                'heading-lg': ['1.875rem', { lineHeight: '1.3' }],
                'heading': ['1.5rem', { lineHeight: '1.4' }],
                'heading-sm': ['1.25rem', { lineHeight: '1.4' }],
                'body-lg': ['1.125rem', { lineHeight: '1.6' }],
                'body': ['1rem', { lineHeight: '1.6' }],
                'body-sm': ['0.875rem', { lineHeight: '1.5' }],
                'caption': ['0.75rem', { lineHeight: '1.5' }],
            },
            borderRadius: {
                'zy-sm': '0.375rem',
                'zy': '0.5rem',
                'zy-md': '0.75rem',
                'zy-lg': '1rem',
                'zy-xl': '1.5rem',
            },
            boxShadow: {
                'zy-sm': '0 1px 2px rgba(0, 0, 0, 0.05)',
                'zy': '0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06)',
                'zy-md': '0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06)',
                'zy-lg': '0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05)',
                'zy-xl': '0 20px 25px rgba(0, 0, 0, 0.1), 0 10px 10px rgba(0, 0, 0, 0.04)',
                'zy-glow': '0 0 20px rgba(26, 115, 232, 0.3)',
            },
            animation: {
                'zy-fade-in': 'zyFadeIn 0.3s ease-out',
                'zy-slide-up': 'zySlideUp 0.3s ease-out',
                'zy-slide-down': 'zySlideDown 0.3s ease-out',
                'zy-pulse': 'zyPulse 2s ease-in-out infinite',
            },
            keyframes: {
                zyFadeIn: {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                zySlideUp: {
                    from: { opacity: '0', transform: 'translateY(10px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                zySlideDown: {
                    from: { opacity: '0', transform: 'translateY(-10px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                zyPulse: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.5' },
                },
            },
            zIndex: {
                'dropdown': '1000',
                'sticky': '1020',
                'fixed': '1030',
                'modal-backdrop': '1040',
                'modal': '1050',
                'popover': '1060',
                'tooltip': '1070',
                'toast': '1080',
            },
        },
    },
    plugins: [],
};
