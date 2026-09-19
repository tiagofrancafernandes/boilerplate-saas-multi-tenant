import { describe, it, expect, beforeEach } from 'vitest';
import { useSubscription } from '../useSubscription';

describe('useSubscription Composable (Happy & Sad Paths)', () => {
    const { isReadOnly, showWarningBanner, warningMessage, setReadOnly, setWarning, dismissWarning } =
        useSubscription();

    beforeEach(() => {
        setReadOnly(false);
        dismissWarning();
    });

    it('happy path initializes with full read/write access and no warning', () => {
        expect(isReadOnly.value).toBe(false);
        expect(showWarningBanner.value).toBe(false);
    });

    it('happy path sets warning message and allows dismissing', () => {
        setWarning('Subscription expires in 2 days');
        expect(showWarningBanner.value).toBe(true);
        expect(warningMessage.value).toBe('Subscription expires in 2 days');

        dismissWarning();
        expect(showWarningBanner.value).toBe(false);
    });

    it('sad path engages read-only lockdown mode when past due', () => {
        setReadOnly(true);
        expect(isReadOnly.value).toBe(true);

        setReadOnly(false);
        expect(isReadOnly.value).toBe(false);
    });

    it('sad path does not display banner for empty warning message', () => {
        setWarning('');
        expect(showWarningBanner.value).toBe(false);
    });
});
