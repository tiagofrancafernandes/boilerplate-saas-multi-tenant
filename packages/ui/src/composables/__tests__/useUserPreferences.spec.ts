import { describe, it, expect, beforeEach } from 'vitest';
import { useUserPreferences, sanitizeColorScheme, sanitizeLocale, sanitizeTimezone } from '../useUserPreferences';

describe('useUserPreferences', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.className = '';
    });

    describe('Sanitizers (Happy & Sad Paths)', () => {
        it('sanitizeColorScheme happy path returns valid values', () => {
            expect(sanitizeColorScheme('light')).toBe('light');
            expect(sanitizeColorScheme('dark')).toBe('dark');
            expect(sanitizeColorScheme('system')).toBe('system');
        });

        it('sanitizeColorScheme sad path returns null for invalid inputs', () => {
            expect(sanitizeColorScheme('neon')).toBeNull();
            expect(sanitizeColorScheme('')).toBeNull();
            expect(sanitizeColorScheme(123)).toBeNull();
            expect(sanitizeColorScheme(null)).toBeNull();
            expect(sanitizeColorScheme(undefined)).toBeNull();
            expect(sanitizeColorScheme({})).toBeNull();
        });

        it('sanitizeLocale happy path normalizes and validates locales', () => {
            expect(sanitizeLocale('pt_BR')).toBe('pt-BR');
            expect(sanitizeLocale('pt-BR')).toBe('pt-BR');
            expect(sanitizeLocale('pt')).toBe('pt-BR');
            expect(sanitizeLocale('en_US')).toBe('en');
            expect(sanitizeLocale('en-US')).toBe('en');
            expect(sanitizeLocale('en')).toBe('en');
        });

        it('sanitizeLocale sad path returns null for unsupported or malformed locales', () => {
            expect(sanitizeLocale('fr-FR')).toBeNull();
            expect(sanitizeLocale('de_DE')).toBeNull();
            expect(sanitizeLocale('')).toBeNull();
            expect(sanitizeLocale(42)).toBeNull();
            expect(sanitizeLocale(null)).toBeNull();
        });

        it('sanitizeTimezone happy path returns trimmed timezone', () => {
            expect(sanitizeTimezone('America/Sao_Paulo')).toBe('America/Sao_Paulo');
            expect(sanitizeTimezone('  UTC  ')).toBe('UTC');
        });

        it('sanitizeTimezone sad path returns UTC fallback for invalid or empty values', () => {
            expect(sanitizeTimezone('')).toBe('UTC');
            expect(sanitizeTimezone('   ')).toBe('UTC');
            expect(sanitizeTimezone(null)).toBe('UTC');
            expect(sanitizeTimezone(undefined)).toBe('UTC');
            expect(sanitizeTimezone(123)).toBe('UTC');
        });
    });

    describe('Composable State & Theme Handling (Happy & Sad Paths)', () => {
        it('happy path sets preferences and persists to localStorage', () => {
            const { colorScheme, userLocale, timezone, setPreferences } = useUserPreferences();

            setPreferences({
                color_scheme: 'dark',
                locale: 'pt_BR',
                timezone: 'America/Sao_Paulo',
            });

            expect(colorScheme.value).toBe('dark');
            expect(userLocale.value).toBe('pt-BR');
            expect(timezone.value).toBe('America/Sao_Paulo');
            expect(document.documentElement.classList.contains('dark')).toBe(true);

            const stored = JSON.parse(localStorage.getItem('saas_user_preferences') || '{}');
            expect(stored.color_scheme).toBe('dark');
            expect(stored.locale).toBe('pt-BR');
            expect(stored.timezone).toBe('America/Sao_Paulo');
        });

        it('happy path removes dark class when light color scheme is set', () => {
            const { setPreferences } = useUserPreferences();

            setPreferences({ color_scheme: 'dark' });
            expect(document.documentElement.classList.contains('dark')).toBe(true);

            setPreferences({ color_scheme: 'light' });
            expect(document.documentElement.classList.contains('dark')).toBe(false);
        });

        it('happy path opens and closes preferences modal', () => {
            const { isModalOpen, openModal, closeModal } = useUserPreferences();

            expect(isModalOpen.value).toBe(false);
            openModal();
            expect(isModalOpen.value).toBe(true);
            closeModal();
            expect(isModalOpen.value).toBe(false);
        });

        it('sad path safely recovers from corrupted localStorage JSON', () => {
            localStorage.setItem('saas_user_preferences', 'invalid-non-json-string{{{');

            const { loadFromStorage } = useUserPreferences();
            expect(() => loadFromStorage()).not.toThrow();
            expect(localStorage.getItem('saas_user_preferences')).toBeNull();
        });

        it('sad path ignores invalid color scheme and locale updates', () => {
            const { colorScheme, userLocale, setPreferences } = useUserPreferences();

            setPreferences({
                color_scheme: 'invalid-scheme' as any,
                locale: 'unsupported-language',
            });

            expect(colorScheme.value).toBeNull();
            expect(userLocale.value).toBeNull();
        });
    });
});
