import { describe, it, expect } from 'vitest';
import fs from 'node:fs';
import path from 'node:path';

function getNestedKeys(obj: Record<string, any>, prefix = ''): string[] {
    let keys: string[] = [];

    for (const [key, value] of Object.entries(obj)) {
        const currentPath = prefix ? `${prefix}.${key}` : key;
        if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
            keys = keys.concat(getNestedKeys(value, currentPath));
        } else {
            keys.push(currentPath);
        }
    }

    return keys;
}

function getNonEmptyStrings(obj: Record<string, any>, prefix = ''): { key: string; value: any }[] {
    let items: { key: string; value: any }[] = [];

    for (const [key, value] of Object.entries(obj)) {
        const currentPath = prefix ? `${prefix}.${key}` : key;
        if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
            items = items.concat(getNonEmptyStrings(value, currentPath));
        } else {
            items.push({ key: currentPath, value });
        }
    }

    return items;
}

describe('Internationalization (i18n) Dictionaries & Parity Tests', () => {
    const apps = [
        { name: '@saas/site', dir: 'apps/site' },
        { name: '@saas/web', dir: 'apps/web' },
        { name: '@saas/admin', dir: 'apps/admin' },
    ];

    const repoRoot = path.resolve(__dirname, '../../../../');

    apps.forEach(({ name, dir }) => {
        describe(`App: ${name}`, () => {
            const enPath = path.join(repoRoot, dir, 'i18n/locales/en.json');
            const ptBrPath = path.join(repoRoot, dir, 'i18n/locales/pt-BR.json');

            it('happy path verifies both en.json and pt-BR.json exist and are valid JSON', () => {
                expect(fs.existsSync(enPath)).toBe(true);
                expect(fs.existsSync(ptBrPath)).toBe(true);

                const enContent = JSON.parse(fs.readFileSync(enPath, 'utf-8'));
                const ptBrContent = JSON.parse(fs.readFileSync(ptBrPath, 'utf-8'));

                expect(typeof enContent).toBe('object');
                expect(typeof ptBrContent).toBe('object');
            });

            it('happy path verifies exact key symmetry between en.json and pt-BR.json', () => {
                const enContent = JSON.parse(fs.readFileSync(enPath, 'utf-8'));
                const ptBrContent = JSON.parse(fs.readFileSync(ptBrPath, 'utf-8'));

                const enKeys = getNestedKeys(enContent).sort();
                const ptBrKeys = getNestedKeys(ptBrContent).sort();

                expect(enKeys).toEqual(ptBrKeys);
            });

            it('sad path verifies no translations are empty strings or whitespace', () => {
                const enContent = JSON.parse(fs.readFileSync(enPath, 'utf-8'));
                const ptBrContent = JSON.parse(fs.readFileSync(ptBrPath, 'utf-8'));

                const enItems = getNonEmptyStrings(enContent);
                const ptBrItems = getNonEmptyStrings(ptBrContent);

                enItems.forEach(({ key, value }) => {
                    expect(typeof value).toBe('string');
                    expect(value.trim().length, `Key "${key}" in ${dir}/en.json should not be empty`).toBeGreaterThan(
                        0
                    );
                });

                ptBrItems.forEach(({ key, value }) => {
                    expect(typeof value).toBe('string');
                    expect(
                        value.trim().length,
                        `Key "${key}" in ${dir}/pt-BR.json should not be empty`
                    ).toBeGreaterThan(0);
                });
            });
        });
    });

    describe('Dictionary Parity Logic (Sad Path)', () => {
        it('sad path detects missing keys when two dictionaries diverge', () => {
            const sampleEn = {
                title: 'Hello',
                buttons: {
                    submit: 'Send',
                    cancel: 'Cancel',
                },
            };

            const samplePtIncomplete = {
                title: 'Olá',
                buttons: {
                    submit: 'Enviar',
                    // missing 'cancel'
                },
            };

            const enKeys = getNestedKeys(sampleEn).sort();
            const ptKeys = getNestedKeys(samplePtIncomplete).sort();

            expect(enKeys).not.toEqual(ptKeys);
            expect(enKeys).toContain('buttons.cancel');
            expect(ptKeys).not.toContain('buttons.cancel');
        });
    });
});
