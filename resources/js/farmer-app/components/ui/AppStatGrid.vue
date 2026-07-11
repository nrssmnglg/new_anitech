<script setup>
defineProps({
    stats: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div v-if="stats.length" class="farmer-app__stats">
        <article v-for="stat in stats" :key="stat.label" class="farmer-app__stat">
            <small>{{ stat.label }}</small>
            <strong>{{ stat.value }}</strong>
        </article>
    </div>
</template>
