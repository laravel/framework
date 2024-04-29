const defaultTheme = require('tailwindcss/defaultTheme')

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./**/*.blade.php"],
    safelist: [
        {
            pattern: /grid-cols-(\d+)/,
            variants: ['sm', 'md', 'lg', 'xl', '2xl', 'default', 'default:lg'],
        },
        {
            pattern: /(row|col)-span-(\d+|full)/,
            variants: ['sm', 'md', 'lg', 'xl', '2xl', 'default', 'default:lg'],
        },
        {
            pattern: /h-\d+/,
            variants: ['sm', 'md', 'lg', 'xl', '2xl'],
        }
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                'sans': ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            height: {
                '128': '32rem',
            }
        },
    },
    plugins: [
    ],
};
