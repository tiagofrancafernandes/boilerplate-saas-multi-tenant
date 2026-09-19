<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';

interface LocaleOption {
    code: string;
    name: string;
    flag: string;
}

const { locale, setLocale } = useI18n();

const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const availableLocales: LocaleOption[] = [
    { code: 'pt-BR', name: 'Português (BR)', flag: 'circle-flags:br' },
    { code: 'en', name: 'English (US)', flag: 'circle-flags:us' },
];

const currentLocale = computed(() => {
    return availableLocales.find((l) => l.code === locale.value) || availableLocales[0];
});

function selectLocale(code: string): void {
    if (typeof (setLocale as unknown) === 'function') {
        (setLocale as (c: string) => void)(code);
    } else {
        locale.value = code;
    }
    isOpen.value = false;
}

function toggleDropdown(): void {
    isOpen.value = !isOpen.value;
}

function handleClickOutside(event: MouseEvent): void {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        isOpen.value = false;
    }
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('click', handleClickOutside);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('click', handleClickOutside);
    }
});
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block text-left">
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            @click.stop="toggleDropdown"
            :aria-expanded="isOpen"
            aria-haspopup="true"
        >
            <iconify-icon :icon="currentLocale.flag" class="h-4 w-4"></iconify-icon>
            <span>{{ currentLocale.name }}</span>
            <iconify-icon
                icon="tabler:chevron-down"
                class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
            ></iconify-icon>
        </button>

        <div
            v-if="isOpen"
            class="absolute right-0 z-50 mt-2 w-44 origin-top-right rounded-lg border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-800"
        >
            <button
                v-for="item in availableLocales"
                :key="item.code"
                type="button"
                class="flex w-full items-center justify-between px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700"
                :class="{ 'font-semibold text-blue-600 dark:text-blue-400': item.code === locale }"
                @click="selectLocale(item.code)"
            >
                <div class="flex items-center gap-2">
                    <iconify-icon :icon="item.flag" class="h-4 w-4"></iconify-icon>
                    <span>{{ item.name }}</span>
                </div>
                <iconify-icon
                    v-if="item.code === locale"
                    icon="tabler:check"
                    class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400"
                ></iconify-icon>
            </button>
        </div>
    </div>
</template>
