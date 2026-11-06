import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'],
            refresh: true,
            fonts: [
                // Official UC Color Run 2027 fonts (Design Division, assets/Font).
                // Afacad Flux is a Latin-only, upright subset of the variable font (weights 400–800).
                local('Afacad Flux', {
                    variable: '--font-afacad-flux',
                    variants: [{ src: 'resources/fonts/afacad-flux-latin.woff', weight: '400 800' }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
                local('Belanosima', {
                    variable: '--font-belanosima',
                    variants: [{ src: 'assets/Font/Belanosima-Regular.ttf', weight: 400 }],
                    fallbacks: ['ui-rounded', 'system-ui', 'sans-serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
