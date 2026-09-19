<script setup lang="ts">
import 'iconify-icon'

interface Props {
    type?: 'button' | 'submit' | 'reset'
    icon?: string
    iconPosition?: 'left' | 'right'
    disabled?: boolean
    loading?: boolean
    variant?: 'primary' | 'secondary' | 'outline' | 'danger'
}

withDefaults(defineProps<Props>(), {
    type: 'button',
    icon: '',
    iconPosition: 'left',
    disabled: false,
    loading: false,
    variant: 'primary',
})

defineEmits<{
    (e: 'click', event: MouseEvent): void
}>()
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2',
            variant === 'primary' && 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 disabled:bg-blue-400',
            variant === 'secondary' && 'bg-gray-100 text-gray-900 hover:bg-gray-200 focus:ring-gray-500 disabled:bg-gray-50',
            variant === 'outline' && 'border border-gray-300 bg-transparent text-gray-700 hover:bg-gray-50 focus:ring-gray-500 disabled:border-gray-200 disabled:text-gray-400',
            variant === 'danger' && 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 disabled:bg-red-400',
            (disabled || loading) && 'cursor-not-allowed opacity-75',
        ]"
        @click="$emit('click', $event)"
    >
        <iconify-icon
            v-if="loading"
            icon="fa7-solid:spinner"
            class="h-4 w-4 animate-spin text-current"
        ></iconify-icon>

        <iconify-icon
            v-if="!loading && icon && iconPosition === 'left'"
            :icon="icon"
            class="h-4 w-4 text-current transition"
        ></iconify-icon>

        <span><slot /></span>

        <iconify-icon
            v-if="!loading && icon && iconPosition === 'right'"
            :icon="icon"
            class="h-4 w-4 text-current transition"
        ></iconify-icon>
    </button>
</template>
