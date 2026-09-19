import { ref, readonly } from 'vue'

const isReadOnly = ref(false)
const showWarningBanner = ref(false)
const warningMessage = ref('')

export function useSubscription() {
    function setReadOnly(value: boolean): void {
        isReadOnly.value = value
    }

    function setWarning(message: string): void {
        warningMessage.value = message
        showWarningBanner.value = message.length > 0
    }

    function dismissWarning(): void {
        showWarningBanner.value = false
    }

    return {
        isReadOnly: readonly(isReadOnly),
        showWarningBanner: readonly(showWarningBanner),
        warningMessage: readonly(warningMessage),
        setReadOnly,
        setWarning,
        dismissWarning,
    }
}
