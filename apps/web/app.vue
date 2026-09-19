<script setup lang="ts">
import { BaseButton, useSubscription } from '@saas/ui';

const { isReadOnly, showWarningBanner, warningMessage, dismissWarning } = useSubscription();
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900">
        <!-- Grace period banner -->
        <div
            v-if="showWarningBanner"
            class="flex items-center justify-between bg-amber-500 px-4 py-2 text-sm font-medium text-white shadow-sm"
        >
            <div class="flex items-center gap-2">
                <iconify-icon icon="tabler:alert-triangle" class="h-5 w-5 text-white"></iconify-icon>
                <span>{{ warningMessage || 'Subscription warning: payment pending. Grace period active.' }}</span>
            </div>
            <button
                type="button"
                class="text-xs uppercase tracking-wider underline hover:opacity-80"
                @click="dismissWarning"
            >
                Dismiss
            </button>
        </div>

        <!-- Read-only mode banner -->
        <div
            v-if="isReadOnly"
            class="flex items-center justify-between bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm"
        >
            <div class="flex items-center gap-2">
                <iconify-icon icon="tabler:lock" class="h-5 w-5 text-white"></iconify-icon>
                <span>
                    Workspace is in Read-Only mode. Submissions and changes are disabled until subscription is renewed.
                </span>
            </div>
            <a
                href="#billing"
                class="rounded bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider hover:bg-white/30"
            >
                Renew Now
            </a>
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
                        href="#billing"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                    >
                        <iconify-icon icon="tabler:credit-card" class="h-5 w-5"></iconify-icon>
                        <span>Billing & Subscription</span>
                    </a>
                    <a
                        href="#settings"
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
                    <BaseButton variant="primary" icon="tabler:plus" :disabled="isReadOnly">New Resource</BaseButton>
                </div>

                <div class="mt-8 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800">Subscription Status</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Read-only enforcement automatically disables write actions when past due.
                    </p>
                    <div class="mt-4 flex items-center gap-2 text-sm">
                        <span class="font-medium text-slate-700">Current Mode:</span>
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                                isReadOnly ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800',
                            ]"
                        >
                            <iconify-icon
                                :icon="isReadOnly ? 'tabler:lock' : 'tabler:circle-check'"
                                class="h-3.5 w-3.5"
                            ></iconify-icon>
                            {{ isReadOnly ? 'Read-Only (Locked)' : 'Full Access (Read/Write)' }}
                        </span>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
