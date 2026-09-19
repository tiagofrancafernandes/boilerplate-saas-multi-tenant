// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    compatibilityDate: '2025-07-15',
    devtools: { enabled: true },
    modules: ['@nuxtjs/tailwindcss'],
    devServer: {
        port: 3000,
    },
    vue: {
        compilerOptions: {
            isCustomElement: (tag: string) => tag === 'iconify-icon',
        },
    },
});
