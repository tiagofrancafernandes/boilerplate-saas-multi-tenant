// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    compatibilityDate: '2025-07-15',
    devtools: { enabled: true },
    modules: ['@nuxtjs/tailwindcss', '@nuxtjs/i18n'],
    devServer: {
        port: 3000,
    },
    i18n: {
        strategy: 'no_prefix',
        defaultLocale: 'en',
        lazy: true,
        langDir: 'locales',
        locales: [
            {
                code: 'pt-BR',
                name: 'Português (BR)',
                file: 'pt-BR.json',
            },
            {
                code: 'en',
                name: 'English (US)',
                file: 'en.json',
            },
        ],
    },
    vue: {
        compilerOptions: {
            isCustomElement: (tag: string) => tag === 'iconify-icon',
        },
    },
});
