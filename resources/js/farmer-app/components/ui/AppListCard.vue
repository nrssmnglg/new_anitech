<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    to: {
        type: [String, Object],
        default: null,
    },
    href: {
        type: String,
        default: '',
    },
});

const tag = computed(() => {
    if (props.href) {
        return 'a';
    }

    return props.to ? RouterLink : 'article';
});
</script>

<template>
    <component
        :is="tag"
        class="farmer-app__list-item"
        :to="to"
        :href="href || undefined"
        :target="href ? '_blank' : undefined"
        :rel="href ? 'noreferrer' : undefined"
    >
        <strong>{{ title }}</strong>
        <span v-if="subtitle">{{ subtitle }}</span>
        <slot />
    </component>
</template>
