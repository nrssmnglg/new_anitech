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
    <div :class="type === 'error' ? 'farmer-app__error' : 'farmer-app__empty'">
        <div>{{ message }}</div>
        <div v-if="actionLabel" class="farmer-app__actions" style="margin-top: 10px;">
            <button type="button" class="farmer-app__btn farmer-app__btn--soft farmer-app__btn--tiny" @click="emit('action')">
                {{ actionLabel }}
            </button>
        </div>
    </div>
</template>
