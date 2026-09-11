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
    <nav v-if="meta?.pagination?.last_page > 1" class="farmer-app__pagination" aria-label="Pagination">
        <button
            type="button"
            class="farmer-app__pagination-btn"
            :disabled="meta.pagination.current_page <= 1"
            @click="goTo(meta.pagination.current_page - 1)"
        >
            Previous
        </button>
        <span class="farmer-app__pagination-label">
            Page {{ meta.pagination.current_page }} of {{ meta.pagination.last_page }}
        </span>
        <button
            type="button"
            class="farmer-app__pagination-btn"
            :disabled="meta.pagination.current_page >= meta.pagination.last_page"
            @click="goTo(meta.pagination.current_page + 1)"
        >
            Next
        </button>
    </nav>
</template>

<style scoped>
.farmer-app__pagination { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 8px; width: 100%; }
.farmer-app__pagination-btn { min-height: 40px; padding: 0 12px; border: 1px solid var(--pwa-border); border-radius: 9px; background: #fff; color: var(--pwa-green-800); font-size: .72rem; font-weight: 800; }
.farmer-app__pagination-btn:last-child { justify-self: stretch; }
.farmer-app__pagination-btn:disabled { color: #98a49f; background: var(--pwa-surface-soft); cursor: not-allowed; }
.farmer-app__pagination-label { color: var(--pwa-muted); font-size: .68rem; white-space: nowrap; }
</style>
