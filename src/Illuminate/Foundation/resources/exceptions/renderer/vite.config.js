/** @type {import('vite').UserConfig} */
export default {
    plugins: [],
    build: {
        assetsDir: '',
        rollupOptions: {
            input: ['scripts.js', 'styles.css', 'dark-mode.css', 'light-mode.css'],
            output: {
                assetFileNames: '[name][extname]',
                entryFileNames: '[name].js',
            },
        },
    },
};
