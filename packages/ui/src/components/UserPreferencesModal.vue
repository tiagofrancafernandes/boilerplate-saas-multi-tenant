<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useUserPreferences, type ColorScheme } from '../composables/useUserPreferences';
import BaseButton from './BaseButton.vue';

const { isModalOpen, closeModal, colorScheme, userLocale, timezone, setPreferences } = useUserPreferences();
const { setLocale, locale: i18nLocale } = useI18n();

const selectedTheme = ref<ColorScheme>(colorScheme.value);
const selectedLocale = ref<string | null>(userLocale.value || i18nLocale.value);
const selectedTimezone = ref<string>(timezone.value);

watch(isModalOpen, (isOpen) => {
    if (isOpen) {
        selectedTheme.value = colorScheme.value;
        selectedLocale.value = userLocale.value || i18nLocale.value;
        selectedTimezone.value = timezone.value;
    }
});

const themeOptions = [
    { value: 'system' as ColorScheme, label: 'System Default', icon: 'tabler:device-desktop' },
    { value: 'light' as ColorScheme, label: 'Light Theme', icon: 'tabler:sun' },
    { value: 'dark' as ColorScheme, label: 'Dark Theme', icon: 'tabler:moon' },
];

const localeOptions = [
    { value: 'pt-BR', label: 'Português (BR)', flag: 'circle-flags:br' },
    { value: 'en', label: 'English (US)', flag: 'circle-flags:us' },
];

const timezoneOptions = [
    'UTC',
    'America/Sao_Paulo',
    'America/New_York',
    'America/Chicago',
    'America/Los_Angeles',
    'Europe/London',
    'Europe/Paris',
    'Asia/Tokyo',
];

function handleSave(): void {
    setPreferences({
        color_scheme: selectedTheme.value,
        locale: selectedLocale.value,
        timezone: selectedTimezone.value,
    });

    if (selectedLocale.value && typeof (setLocale as unknown) === 'function') {
        (setLocale as (code: string) => void)(selectedLocale.value);
    }

    closeModal();
}
</script>

<template>
    <div
        v-if="isModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-2xl transition-all dark:border-slate-800 dark:bg-slate-900"
        >
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
                <div class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                    <iconify-icon
                        icon="tabler:settings"
                        class="h-6 w-6 text-blue-600 dark:text-blue-400"
                    ></iconify-icon>
                    <span>User Preferences</span>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    @click="closeModal"
                >
                    <iconify-icon icon="tabler:x" class="h-5 w-5"></iconify-icon>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="mt-6 space-y-6 text-sm">
                <!-- Theme Section -->
                <div>
                    <label class="block font-medium text-slate-700 dark:text-slate-300">Color Scheme</label>
                    <div class="mt-2 grid grid-cols-3 gap-3">
                        <button
                            v-for="option in themeOptions"
                            :key="String(option.value)"
                            type="button"
                            class="flex flex-col items-center justify-center gap-2 rounded-lg border p-3 text-xs font-medium transition"
                            :class="[
                                selectedTheme === option.value
                                    ? 'border-blue-600 bg-blue-50 text-blue-700 dark:border-blue-500 dark:bg-blue-950/40 dark:text-blue-300'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 dark:hover:bg-slate-800',
                            ]"
                            @click="selectedTheme = option.value"
                        >
                            <iconify-icon :icon="option.icon" class="h-5 w-5"></iconify-icon>
                            <span>{{ option.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Language Section -->
                <div>
                    <label class="block font-medium text-slate-700 dark:text-slate-300">Language</label>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <button
                            v-for="option in localeOptions"
                            :key="option.value"
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-lg border p-3 text-xs font-medium transition"
                            :class="[
                                selectedLocale === option.value
                                    ? 'border-blue-600 bg-blue-50 text-blue-700 dark:border-blue-500 dark:bg-blue-950/40 dark:text-blue-300'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 dark:hover:bg-slate-800',
                            ]"
                            @click="selectedLocale = option.value"
                        >
                            <iconify-icon :icon="option.flag" class="h-5 w-5"></iconify-icon>
                            <span>{{ option.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Timezone Section -->
                <div>
                    <label class="block font-medium text-slate-700 dark:text-slate-300">Timezone</label>
                    <div class="relative mt-2">
                        <select
                            v-model="selectedTimezone"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200"
                        >
                            <option v-for="tz in timezoneOptions" :key="tz" :value="tz">
                                {{ tz }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="mt-8 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                <BaseButton variant="outline" @click="closeModal">Cancel</BaseButton>
                <BaseButton variant="primary" icon="tabler:check" @click="handleSave">Save Preferences</BaseButton>
            </div>
        </div>
    </div>
</template>
