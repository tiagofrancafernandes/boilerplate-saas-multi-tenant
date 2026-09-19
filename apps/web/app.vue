<script setup lang="ts">
import { BaseButton } from '@saas/ui';
import { ref } from 'vue';

const showGracePeriodWarning = ref(true);
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900">
        <!-- Grace period banner demo -->
        <div
            v-if="showGracePeriodWarning"
            class="flex items-center justify-between bg-amber-500 px-4 py-2 text-sm font-medium text-white shadow-sm"
        >
            <div class="flex items-center gap-2">
                <iconify-icon icon="tabler:alert-triangle" class="h-5 w-5"></iconify-icon>
                <span>
                    Subscription notice: Payment pending. Grace period active. Please update your payment method.
                </span>
            </div>
            <button
                class="text-xs uppercase tracking-wider underline hover:opacity-80"
                @click="showGracePeriodWarning = false"
            >
                Dismiss
            </button>
        </div>

        <div class="flex min-h-[calc(100vh-40px)]">
            <!-- Sidebar -->
            <aside class="w-64 border-r border-slate-200 bg-white p-6">
                <div class="flex items-center gap-2 text-lg font-bold text-slate-800">
                    <iconify-icon icon="tabler:layout-dashboard" class="h-6 w-6 text-blue-600"></iconify-icon>
                    <span>Tenant App</span>
                </div>
                <nav class="mt-8 space-y-2">
                    <a
                        href="#"
                        class="flex items-center gap-3 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700"
                    >
                        <iconify-icon icon="tabler:home" class="h-5 w-5"></iconify-icon>
                        <span>Dashboard</span>
                    </a>
                    <a
                        href="#"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                    >
                        <iconify-icon icon="tabler:credit-card" class="h-5 w-5"></iconify-icon>
                        <span>Billing & Subscription</span>
                    </a>
                    <a
                        href="#"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                    >
                        <iconify-icon icon="tabler:settings" class="h-5 w-5"></iconify-icon>
                        <span>Settings</span>
                    </a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 p-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900">Workspace Overview</h2>
                        <p class="text-sm text-slate-500">Welcome to your SaaS tenant space.</p>
                    </div>
                    <BaseButton variant="primary" icon="tabler:plus">New Resource</BaseButton>
                </div>
            </main>
        </div>
    </div>
</template>
