import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";
export default defineConfig({
    plugins: [tailwindcss()],
    build: {
        rollupOptions: {
            input: ["styles.css"],
            output: {
                assetFileNames: "[name][extname]",
            },
        },
    },
});
