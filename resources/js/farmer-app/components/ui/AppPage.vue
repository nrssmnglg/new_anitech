<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    badge: {
        type: String,
        default: '',
    },
    badgeTone: {
        type: String,
        default: 'success',
    },
    backTo: {
        type: [String, Object],
        default: null,
    },
    backLabel: {
        type: String,
        default: 'Back',
    },
});

const badgeClass = computed(() => ({
    'farmer-app__badge--success': props.badgeTone === 'success',
    'farmer-app__badge--warning': props.badgeTone === 'warning',
    'farmer-app__badge--neutral': props.badgeTone === 'neutral',
}));
</script>

<template>
    <section class="farmer-app__stack">
        <article class="farmer-app__panel">
            <RouterLink v-if="backTo" :to="backTo" class="farmer-app__backlink">
                {{ backLabel }}
            </RouterLink>
            <div class="farmer-app__section-title">
                <div>
                    <h1>{{ title }}</h1>
                    <p v-if="description">{{ description }}</p>
                </div>
                <span v-if="badge" class="farmer-app__badge" :class="badgeClass">
                    {{ badge }}
                </span>
            </div>

            <slot />
        </article>
    </section>
</template>
