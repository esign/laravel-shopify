import { defineConfig } from 'vite';
import preact from '@preact/preset-vite';

/**
 * Vite Configuration for Laravel Shopify App
 * 
 * This configuration compiles Preact components for your embedded Shopify app.
 * It's pre-configured to work with App Bridge and Polaris web components.
 * 
 * Key features:
 * - Preact with Fast Refresh for development
 * - Builds to public/build for Laravel integration
 * - Entry point: resources/js/app.jsx
 * - HMR configured for local development
 * - Shopify Polaris web components integration
 * 
 * To customize:
 * - Add additional entry points in rollupOptions.input
 * - Adjust server.hmr.host if using custom domain
 * - Add plugins as needed (e.g., @vitejs/plugin-legacy)
 */
export default defineConfig({
    plugins: [preact()],
    build: {
        // Output directory for production builds
        outDir: 'public/build',
        // Generate manifest.json for Laravel's @vite directive
        manifest: true,
        rollupOptions: {
            // Entry point for your React app
            input: 'resources/js/app.jsx',
        },
    },
    server: {
        hmr: {
            // HMR host for development
            host: 'localhost',
        },
    },
});
