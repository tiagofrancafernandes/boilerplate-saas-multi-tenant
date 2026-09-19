import { useSubscription } from '@saas/ui';
import { ofetch } from 'ofetch';

export default defineNuxtPlugin(() => {
    const { setReadOnly, setWarning } = useSubscription();

    globalThis.$fetch = ofetch.create({
        onResponse({ response }) {
            const warningHeader = response.headers.get('x-subscription-warning');
            if (warningHeader) {
                setWarning(warningHeader);
            }
        },
        onResponseError({ response }) {
            if (response.status === 403) {
                const responseData = response._data;
                if (responseData?.error === 'SUBSCRIPTION_READ_ONLY') {
                    setReadOnly(true);
                }
            }
        },
    });
});
