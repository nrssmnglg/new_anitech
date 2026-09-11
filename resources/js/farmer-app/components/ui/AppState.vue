<script setup>
defineProps({
    type: {
        type: String,
        default: 'empty',
    },
    message: {
        type: String,
        required: true,
    },
    actionLabel: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['action']);
</script>

<template>
    <div
        class="farmer-app__state"
        :class="`farmer-app__state--${type}`"
        :role="type === 'error' ? 'alert' : 'status'"
        aria-live="polite"
    >
        <span class="farmer-app__state-icon" aria-hidden="true">
            <svg v-if="type === 'error'" viewBox="0 0 24 24" fill="none">
                <path d="M12 8v4m0 4h.01M10.3 4.7 3.6 16.3A2 2 0 0 0 5.3 19h13.4a2 2 0 0 0 1.7-2.7L13.7 4.7a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <svg v-else-if="type === 'success'" viewBox="0 0 24 24" fill="none">
                <path d="m7 12 3.2 3.2L17.5 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" />
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none">
                <path d="M4 7.5h16v10A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5v-10Z" stroke="currentColor" stroke-width="1.8" />
                <path d="M8 11h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        </span>
        <div class="farmer-app__state-content">
            <div class="farmer-app__state-message">{{ message }}</div>
            <div v-if="actionLabel" class="farmer-app__actions farmer-app__state-actions">
                <button type="button" class="farmer-app__state-action" @click="emit('action')">{{ actionLabel }}</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.farmer-app__state { gap: 8px; padding: 10px; border-radius: 10px; box-shadow: none; }
.farmer-app__state-icon { flex-basis: 28px; width: 28px; height: 28px; }
.farmer-app__state-icon svg { width: 16px; height: 16px; }
.farmer-app__state-message { padding-top: 4px; font-size: .75rem; line-height: 1.45; }
.farmer-app__state-actions { margin-top: 6px; }
.farmer-app__state-action { min-height: 36px; padding: 0 11px; border: 1px solid currentColor; border-radius: 8px; background: transparent; color: inherit; font-size: .7rem; font-weight: 800; }
</style>
