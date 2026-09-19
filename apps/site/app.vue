<script setup lang="ts">
import { BaseButton } from '@saas/ui';
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2 text-xl font-bold text-blue-600">
                    <iconify-icon icon="tabler:rocket" class="h-6 w-6 text-blue-600"></iconify-icon>
                    <span>SaaS Boilerplate</span>
                </div>
                <nav class="flex items-center gap-6 text-sm font-medium">
                    <a href="#features" class="hover:text-blue-600">Features</a>
                    <a href="#pricing" class="hover:text-blue-600">Pricing</a>
                    <BaseButton variant="primary" icon="tabler:user-plus">Get Started</BaseButton>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-20 text-center">
            <h1 class="text-5xl font-extrabold tracking-tight text-slate-900">
                Next-Gen Multi-Tenant SaaS Boilerplate
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-600">
                Scalable PostgreSQL schema tenancy, automated subscription cycles, agnostic payment gateway integration,
                and Nuxt 3 front-ends.
            </p>
            <div class="mt-8 flex justify-center gap-4">
                <BaseButton variant="primary" icon="tabler:arrow-right" icon-position="right">Explore Plans</BaseButton>
                <BaseButton variant="outline" icon="tabler:brand-github">Documentation</BaseButton>
            </div>
        </main>
    </div>
</template>
