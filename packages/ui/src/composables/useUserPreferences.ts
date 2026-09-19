import { ref, computed } from 'vue';

export type ColorScheme = 'light' | 'dark' | 'system' | null;

export interface UserPreferences {
    locale: string | null;
    timezone: string;
    color_scheme: ColorScheme;
}

const colorScheme = ref<ColorScheme>(null);
const userLocale = ref<string | null>(null);
const timezone = ref<string>('UTC');
const isModalOpen = ref<boolean>(false);

export function sanitizeColorScheme(val: unknown): ColorScheme {
    if (val === 'light' || val === 'dark' || val === 'system') {
        return val;
    }
    return null;
}

export function sanitizeLocale(val: unknown): string | null {
    if (typeof val !== 'string') {
        return null;
    }

    const normalized = val.replace('_', '-');
    if (normalized === 'pt-BR' || normalized === 'pt') {
        return 'pt-BR';
    }
    if (normalized === 'en-US' || normalized === 'en') {
        return 'en';
    }

    return null;
}

export function sanitizeTimezone(val: unknown): string {
    if (typeof val === 'string' && val.trim().length > 0) {
        return val.trim();
    }
    return 'UTC';
}

export function useUserPreferences() {
    function applyColorScheme(scheme: ColorScheme): void {
        if (typeof window === 'undefined' || typeof document === 'undefined') {
            return;
        }

        const root = document.documentElement;
        if (scheme === 'dark') {
            root.classList.add('dark');
            return;
        }

        if (scheme === 'light') {
            root.classList.remove('dark');
            return;
        }

        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDark) {
            root.classList.add('dark');
            return;
        }

        root.classList.remove('dark');
    }

    function setPreferences(data: Partial<UserPreferences>): void {
        if ('color_scheme' in data) {
            colorScheme.value = sanitizeColorScheme(data.color_scheme);
            applyColorScheme(colorScheme.value);
        }

        if ('locale' in data) {
            userLocale.value = sanitizeLocale(data.locale);
        }

        if ('timezone' in data) {
            timezone.value = sanitizeTimezone(data.timezone);
        }

        if (typeof localStorage !== 'undefined') {
            localStorage.setItem(
                'saas_user_preferences',
                JSON.stringify({
                    color_scheme: colorScheme.value,
                    locale: userLocale.value,
                    timezone: timezone.value,
                })
            );
        }
    }

    function loadFromStorage(): void {
        if (typeof localStorage === 'undefined') {
            return;
        }

        const stored = localStorage.getItem('saas_user_preferences');
        if (!stored) {
            return;
        }

        try {
            const parsed = JSON.parse(stored);
            setPreferences(parsed);
        } catch {
            localStorage.removeItem('saas_user_preferences');
        }
    }

    function openModal(): void {
        isModalOpen.value = true;
    }

    function closeModal(): void {
        isModalOpen.value = false;
    }

    return {
        colorScheme: computed(() => colorScheme.value),
        userLocale: computed(() => userLocale.value),
        timezone: computed(() => timezone.value),
        isModalOpen: computed(() => isModalOpen.value),
        setPreferences,
        loadFromStorage,
        applyColorScheme,
        openModal,
        closeModal,
    };
}
