<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { BaseButton, useInfraOps } from '@saas/ui';

interface ParamRow {
    key: string;
    value: string;
}

const props = withDefaults(
    defineProps<{
        defaultRole?: string;
        defaultPermissions?: string[];
    }>(),
    {
        defaultRole: 'super-admin',
        defaultPermissions: () => ['run-scheduler', 'manage-infra'],
    }
);

const infraOps = useInfraOps({
    apiBaseUrl: 'http://localhost:8000',
    staticHeaderKey: 'change-this-to-a-secure-random-token',
    userRole: props.defaultRole,
    permissions: props.defaultPermissions,
});

// UI State
const showSettings = ref(false);
const subscriptionTenantId = ref('');
const artisanCommand = ref('subscriptions:check-cycles');
const artisanParams = ref<ParamRow[]>([{ key: '--tenant', value: 'demo_tenant' }]);
const queueConnection = ref('redis');
const queueName = ref('default');
const queueMaxJobs = ref(1);
const copied = ref(false);

function addParamRow(): void {
    artisanParams.value.push({ key: '', value: '' });
}

function removeParamRow(index: number): void {
    artisanParams.value.splice(index, 1);
}

function setCommandPreset(cmd: string, params: ParamRow[] = []): void {
    artisanCommand.value = cmd;
    artisanParams.value = [...params];
}

async function handleRunSubscription(): Promise<void> {
    await infraOps.runSubscriptionCheck(subscriptionTenantId.value);
}

async function handleRunScheduler(): Promise<void> {
    await infraOps.triggerScheduler();
}

async function handleRunArtisan(): Promise<void> {
    const parameters: Record<string, any> = {};
    for (const row of artisanParams.value) {
        if (row.key.trim() !== '') {
            parameters[row.key.trim()] = row.value.trim() !== '' ? row.value.trim() : true;
        }
    }

    await infraOps.runArtisan(artisanCommand.value.trim(), parameters);
}

async function handleRunQueue(): Promise<void> {
    await infraOps.runQueueWorker({
        connection: queueConnection.value,
        queue: queueName.value,
        max_jobs: queueMaxJobs.value,
        stop_when_empty: true,
    });
}

function copyOutput(): void {
    if (!infraOps.lastResult.value?.output) {
        return;
    }

    navigator.clipboard.writeText(infraOps.lastResult.value.output);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

// Permission simulation toggle for easy live testing of sad paths
const hasPermissionSimulated = ref(true);

function toggleSimulatedPermission(): void {
    hasPermissionSimulated.value = !hasPermissionSimulated.value;
    if (hasPermissionSimulated.value) {
        infraOps.userRole.value = 'super-admin';
        infraOps.permissions.value = ['run-scheduler', 'manage-infra'];
    } else {
        infraOps.userRole.value = 'tenant-user';
        infraOps.permissions.value = [];
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Page Title & Controls Header -->
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="flex items-center gap-2 text-2xl font-bold text-white">
                    <iconify-icon icon="tabler:terminal-2" class="h-7 w-7 text-indigo-400"></iconify-icon>
                    {{ $t('infra.title') }}
                </h1>
                <p class="text-sm text-slate-400">{{ $t('infra.subtitle') }}</p>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:bg-slate-700"
                    @click="showSettings = !showSettings"
                >
                    <iconify-icon icon="tabler:adjustments-horizontal" class="h-4 w-4 text-slate-400"></iconify-icon>
                    {{ $t('infra.settingsTitle') }}
                </button>

                <button
                    type="button"
                    :class="[
                        'inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition',
                        hasPermissionSimulated
                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'
                            : 'border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/20',
                    ]"
                    @click="toggleSimulatedPermission"
                >
                    <iconify-icon
                        :icon="hasPermissionSimulated ? 'tabler:lock-open' : 'tabler:lock'"
                        class="h-4 w-4"
                    ></iconify-icon>
                    {{ $t('infra.simulatePermission') }}: {{ hasPermissionSimulated ? 'Super Admin' : 'Unauthorized' }}
                </button>
            </div>
        </div>

        <!-- Collapsible Authentication Settings Bar -->
        <div v-if="showSettings" class="rounded-lg border border-slate-800 bg-slate-950 p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-400">
                {{ $t('infra.settingsTitle') }}
            </h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-400">
                        {{ $t('infra.apiBaseUrl') }}
                    </label>
                    <input
                        v-model="infraOps.apiBaseUrl.value"
                        type="text"
                        class="w-full rounded border border-slate-800 bg-slate-900 px-3 py-1.5 text-sm text-white focus:border-indigo-500 focus:outline-none"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-400">
                        {{ $t('infra.staticKey') }}
                    </label>
                    <input
                        v-model="infraOps.staticHeaderKey.value"
                        type="text"
                        class="w-full rounded border border-slate-800 bg-slate-900 px-3 py-1.5 text-sm text-white focus:border-indigo-500 focus:outline-none"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-400">
                        {{ $t('infra.bearerToken') }}
                    </label>
                    <input
                        v-model="infraOps.bearerToken.value"
                        type="password"
                        placeholder="Optional Sanctum Token"
                        class="w-full rounded border border-slate-800 bg-slate-900 px-3 py-1.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none"
                    />
                </div>
            </div>
        </div>

        <!-- Access Denied Guard Banner (if user lacks permission) -->
        <div
            v-if="!infraOps.canAccessInfra.value"
            class="rounded-lg border border-red-500/30 bg-red-950/30 p-8 text-center"
        >
            <div
                class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-red-500/20 text-red-400"
            >
                <iconify-icon icon="tabler:shield-x" class="h-8 w-8"></iconify-icon>
            </div>
            <h2 class="text-lg font-bold text-red-400">{{ $t('infra.accessDeniedTitle') }}</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">
                {{ $t('infra.accessDeniedDesc') }}
            </p>
        </div>

        <!-- Main Operations Panel (when authorized) -->
        <template v-else>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- 1. Subscription Cycles Sync Card -->
                <div class="flex flex-col justify-between rounded-lg border border-slate-800 bg-slate-950 p-5">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-400">
                                <iconify-icon icon="tabler:refresh" class="h-6 w-6"></iconify-icon>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">
                                    {{ $t('infra.cards.subscriptionTitle') }}
                                </h3>
                                <span class="text-xs text-slate-500">subscriptions:check-cycles</span>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-slate-400">
                            {{ $t('infra.cards.subscriptionDesc') }}
                        </p>

                        <div class="mt-4">
                            <label class="mb-1 block text-xs text-slate-400">
                                {{ $t('infra.cards.tenantIdPlaceholder') }}
                            </label>
                            <input
                                v-model="subscriptionTenantId"
                                type="text"
                                placeholder="e.g. demo_tenant or blank"
                                class="w-full rounded border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800">
                        <BaseButton
                            variant="primary"
                            icon="tabler:player-play"
                            class="w-full"
                            :disabled="infraOps.loading.value"
                            @click="handleRunSubscription"
                        >
                            {{ $t('infra.cards.runSubscriptionCheck') }}
                        </BaseButton>
                    </div>
                </div>

                <!-- 2. Laravel Scheduler (Cron) Card -->
                <div class="flex flex-col justify-between rounded-lg border border-slate-800 bg-slate-950 p-5">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-blue-500/10 p-2 text-blue-400">
                                <iconify-icon icon="tabler:clock-play" class="h-6 w-6"></iconify-icon>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">
                                    {{ $t('infra.cards.schedulerTitle') }}
                                </h3>
                                <span class="text-xs text-slate-500">POST /api/infra/scheduler</span>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-slate-400">
                            {{ $t('infra.cards.schedulerDesc') }}
                        </p>

                        <div class="mt-4 rounded border border-slate-800 bg-slate-900/60 p-3">
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <span>Action:</span>
                                <span class="font-mono text-slate-300">schedule:run</span>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-xs text-slate-400">
                                <span>Frequency:</span>
                                <span class="text-slate-300">On-demand via HTTP</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800">
                        <BaseButton
                            variant="primary"
                            icon="tabler:clock"
                            class="w-full"
                            :disabled="infraOps.loading.value"
                            @click="handleRunScheduler"
                        >
                            {{ $t('infra.cards.runScheduler') }}
                        </BaseButton>
                    </div>
                </div>

                <!-- 3. Queue Worker Trigger Card -->
                <div class="flex flex-col justify-between rounded-lg border border-slate-800 bg-slate-950 p-5">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-amber-500/10 p-2 text-amber-400">
                                <iconify-icon icon="tabler:cpu" class="h-6 w-6"></iconify-icon>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">
                                    {{ $t('infra.cards.queueTitle') }}
                                </h3>
                                <span class="text-xs text-slate-500">POST /api/infra/queue</span>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-slate-400">
                            {{ $t('infra.cards.queueDesc') }}
                        </p>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-xs text-slate-400">Connection</label>
                                <input
                                    v-model="queueConnection"
                                    type="text"
                                    class="w-full rounded border border-slate-800 bg-slate-900 px-2 py-1 text-xs text-white"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs text-slate-400">Max Jobs</label>
                                <input
                                    v-model.number="queueMaxJobs"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="w-full rounded border border-slate-800 bg-slate-900 px-2 py-1 text-xs text-white"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800">
                        <BaseButton
                            variant="outline"
                            icon="tabler:bolt"
                            class="w-full"
                            :disabled="infraOps.loading.value"
                            @click="handleRunQueue"
                        >
                            {{ $t('infra.cards.runQueue') }}
                        </BaseButton>
                    </div>
                </div>
            </div>

            <!-- 4. Artisan Command Runner Card (Full Width) -->
            <div class="rounded-lg border border-slate-800 bg-slate-950 p-6">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-purple-500/10 p-2 text-purple-400">
                            <iconify-icon icon="tabler:terminal" class="h-6 w-6"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-white">
                                {{ $t('infra.cards.artisanTitle') }}
                            </h3>
                            <p class="text-xs text-slate-400">{{ $t('infra.cards.artisanDesc') }}</p>
                        </div>
                    </div>

                    <!-- Preset Pills -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs text-slate-500">Presets:</span>
                        <button
                            type="button"
                            class="rounded border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-800"
                            @click="setCommandPreset('inspire', [])"
                        >
                            inspire
                        </button>
                        <button
                            type="button"
                            class="rounded border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-800"
                            @click="
                                setCommandPreset('subscriptions:check-cycles', [
                                    { key: '--tenant', value: 'demo_tenant' },
                                ])
                            "
                        >
                            subscriptions:check-cycles
                        </button>
                        <button
                            type="button"
                            class="rounded border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-800"
                            @click="setCommandPreset('route:list', [{ key: '--path', value: 'infra' }])"
                        >
                            route:list --path=infra
                        </button>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <!-- Command Input -->
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-300">Artisan Command</label>
                        <input
                            v-model="artisanCommand"
                            type="text"
                            :placeholder="$t('infra.cards.commandPlaceholder')"
                            class="w-full rounded border border-slate-800 bg-slate-900 px-4 py-2 font-mono text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none"
                        />
                    </div>

                    <!-- Dynamic Parameters Key/Value Rows -->
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-xs font-medium text-slate-400">
                                Arguments & Parameters (Key / Value)
                            </label>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-medium text-indigo-400 hover:text-indigo-300"
                                @click="addParamRow"
                            >
                                <iconify-icon icon="tabler:plus" class="h-3.5 w-3.5"></iconify-icon>
                                {{ $t('infra.cards.addParam') }}
                            </button>
                        </div>

                        <div
                            v-if="artisanParams.length === 0"
                            class="rounded border border-dashed border-slate-800 p-3 text-center text-xs text-slate-500"
                        >
                            No parameters specified. Click "Add Parameter" to append command flags.
                        </div>

                        <div v-else class="space-y-2">
                            <div v-for="(param, idx) in artisanParams" :key="idx" class="flex items-center gap-3">
                                <input
                                    v-model="param.key"
                                    type="text"
                                    :placeholder="$t('infra.cards.paramKeyPlaceholder')"
                                    class="w-1/2 rounded border border-slate-800 bg-slate-900 px-3 py-1.5 font-mono text-xs text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none"
                                />
                                <input
                                    v-model="param.value"
                                    type="text"
                                    :placeholder="$t('infra.cards.paramValPlaceholder')"
                                    class="w-1/2 rounded border border-slate-800 bg-slate-900 px-3 py-1.5 font-mono text-xs text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none"
                                />
                                <button
                                    type="button"
                                    class="rounded p-1.5 text-slate-500 transition hover:bg-slate-800 hover:text-red-400"
                                    :title="$t('infra.cards.removeParam')"
                                    @click="removeParamRow(idx)"
                                >
                                    <iconify-icon icon="tabler:trash" class="h-4 w-4"></iconify-icon>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Run Button -->
                    <div class="pt-2">
                        <BaseButton
                            variant="primary"
                            icon="tabler:terminal"
                            :disabled="infraOps.loading.value || artisanCommand.trim() === ''"
                            @click="handleRunArtisan"
                        >
                            {{ $t('infra.cards.runArtisan') }}
                        </BaseButton>
                    </div>
                </div>
            </div>

            <!-- 5. Live Execution Console (Terminal View) -->
            <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                <!-- Console Top Bar -->
                <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900/80 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-3 w-3 items-center justify-center">
                            <span
                                :class="[
                                    'h-2 w-2 rounded-full',
                                    infraOps.loading.value
                                        ? 'animate-ping bg-amber-400'
                                        : infraOps.lastResult.value?.success
                                          ? 'bg-emerald-400'
                                          : infraOps.lastResult.value
                                            ? 'bg-red-400'
                                            : 'bg-slate-600',
                                ]"
                            ></span>
                        </span>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-300">
                            {{ $t('infra.terminal.title') }}
                        </h4>
                    </div>

                    <!-- Meta Badges -->
                    <div v-if="infraOps.lastResult.value" class="flex items-center gap-3 text-xs text-slate-400">
                        <span
                            :class="[
                                'rounded px-2 py-0.5 text-xs font-semibold',
                                infraOps.lastResult.value.success
                                    ? 'bg-emerald-500/10 text-emerald-400'
                                    : 'bg-red-500/10 text-red-400',
                            ]"
                        >
                            Exit: {{ infraOps.lastResult.value.exitCode }}
                        </span>
                        <span>{{ infraOps.lastResult.value.durationMs }}ms</span>
                        <span class="hidden font-mono text-slate-500 sm:inline">
                            {{ infraOps.lastResult.value.endpoint }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button
                            v-if="infraOps.lastResult.value"
                            type="button"
                            class="inline-flex items-center gap-1 rounded bg-slate-800 px-2 py-1 text-xs text-slate-300 hover:bg-slate-700"
                            @click="copyOutput"
                        >
                            <iconify-icon icon="tabler:copy" class="h-3.5 w-3.5"></iconify-icon>
                            {{ copied ? $t('infra.terminal.copied') : $t('infra.terminal.copy') }}
                        </button>
                        <button
                            type="button"
                            class="rounded bg-slate-800 px-2 py-1 text-xs text-slate-400 hover:bg-slate-700 hover:text-slate-200"
                            @click="infraOps.clearLastResult"
                        >
                            {{ $t('infra.terminal.clear') }}
                        </button>
                    </div>
                </div>

                <!-- Terminal Body -->
                <div
                    class="p-4 bg-black/90 font-mono text-xs text-slate-200 min-h-[160px] max-h-[380px] overflow-y-auto"
                >
                    <div v-if="infraOps.loading.value" class="flex items-center gap-2 text-amber-400">
                        <iconify-icon icon="tabler:loader" class="h-4 w-4 animate-spin"></iconify-icon>
                        <span>Executing infrastructure call...</span>
                    </div>
                    <pre v-else-if="infraOps.lastResult.value" class="whitespace-pre-wrap leading-relaxed">{{
                        infraOps.lastResult.value.output
                    }}</pre>
                    <p v-else class="text-slate-600">
                        {{ $t('infra.terminal.ready') }}
                    </p>
                </div>
            </div>
        </template>
    </div>
</template>
