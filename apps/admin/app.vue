<script setup lang="ts">
import { ref } from 'vue';
import { BaseButton, LanguageSwitcher, useInfraOps } from '@saas/ui';
import InfraOpsDashboard from './components/InfraOpsDashboard.vue';

const currentTab = ref<'tenants' | 'infra'>('tenants');

// Initialize admin authorization context
const infraOps = useInfraOps({
    userRole: 'super-admin',
    permissions: ['run-scheduler', 'manage-infra'],
});
</script>

<template>
    <div class="min-h-screen bg-slate-900 text-slate-100">
        <!-- Main Top Bar -->
        <header class="border-b border-slate-800 bg-slate-950 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2 text-lg font-bold text-red-400">
                        <iconify-icon icon="tabler:shield-lock" class="h-6 w-6 text-red-400"></iconify-icon>
                        <span>{{ $t('app.title') }}</span>
                    </div>

                    <!-- Navigation Tabs -->
                    <nav class="flex items-center gap-1 border-l border-slate-800 pl-6">
                        <button
                            type="button"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                                currentTab === 'tenants'
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200',
                            ]"
                            @click="currentTab = 'tenants'"
                        >
                            <iconify-icon icon="tabler:building-community" class="h-4 w-4"></iconify-icon>
                            {{ $t('nav.tenants') }}
                        </button>

                        <!-- Only rendered if user has infrastructure permissions -->
                        <button
                            v-if="infraOps.canAccessInfra.value"
                            type="button"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                                currentTab === 'infra'
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200',
                            ]"
                            @click="currentTab = 'infra'"
                        >
                            <iconify-icon icon="tabler:terminal-2" class="h-4 w-4 text-indigo-400"></iconify-icon>
                            {{ $t('nav.infra') }}
                        </button>
                    </nav>
                </div>

                <div class="flex items-center gap-4 text-sm">
                    <LanguageSwitcher />
                    <span class="rounded bg-slate-800 px-2 py-1 text-xs text-slate-300">{{ $t('role') }}</span>
                    <BaseButton variant="danger" icon="tabler:logout">{{ $t('signOut') }}</BaseButton>
                </div>
            </div>
        </header>

        <main class="p-8">
            <!-- Tab 1: Tenants Management -->
            <section v-if="currentTab === 'tenants'">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">{{ $t('management.title') }}</h1>
                        <p class="text-sm text-slate-400">{{ $t('management.subtitle') }}</p>
                    </div>
                    <BaseButton variant="primary" icon="tabler:plus">{{ $t('management.addTenant') }}</BaseButton>
                </div>

                <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-slate-900 text-xs uppercase text-slate-400">
                            <tr>
                                <th class="px-6 py-3">{{ $t('table.tenantId') }}</th>
                                <th class="px-6 py-3">{{ $t('table.name') }}</th>
                                <th class="px-6 py-3">{{ $t('table.schema') }}</th>
                                <th class="px-6 py-3">{{ $t('table.paidUntil') }}</th>
                                <th class="px-6 py-3">{{ $t('table.status') }}</th>
                                <th class="px-6 py-3 text-right">{{ $t('table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <tr class="hover:bg-slate-900/50">
                                <td class="px-6 py-4 font-mono text-xs">ten_demo_01</td>
                                <td class="px-6 py-4 font-semibold text-white">Acme Corp</td>
                                <td class="px-6 py-4 text-xs text-slate-400">tenant_acme</td>
                                <td class="px-6 py-4">2026-12-31 UTC</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400"
                                    >
                                        <iconify-icon icon="tabler:circle-check" class="h-3.5 w-3.5"></iconify-icon>
                                        {{ $t('table.statusActive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <BaseButton variant="outline" icon="tabler:dots-vertical">
                                        {{ $t('table.manage') }}
                                    </BaseButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Tab 2: Infrastructure Operations Console -->
            <section v-else-if="currentTab === 'infra'">
                <InfraOpsDashboard />
            </section>
        </main>
    </div>
</template>
