<script setup>
defineProps({
    meta: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['change']);

const goTo = (page) => {
    emit('change', page);
};
</script>

<template>
    <div v-if="meta?.pagination?.last_page > 1" class="farmer-app__actions">
        <button
            type="button"
            class="farmer-app__btn farmer-app__btn--soft farmer-app__btn--tiny"
            :disabled="meta.pagination.current_page <= 1"
            @click="goTo(meta.pagination.current_page - 1)"
        >
            Previous
        </button>
        <div class="farmer-app__empty">
            Page {{ meta.pagination.current_page }} of {{ meta.pagination.last_page }}
        </div>
        <button
            type="button"
            class="farmer-app__btn farmer-app__btn--soft farmer-app__btn--tiny"
            :disabled="meta.pagination.current_page >= meta.pagination.last_page"
            @click="goTo(meta.pagination.current_page + 1)"
        >
            Next
        </button>
    </div>
</template>
