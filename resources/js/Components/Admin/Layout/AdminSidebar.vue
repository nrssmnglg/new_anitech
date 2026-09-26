<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    brand: {
        type: Object,
        required: true,
    },
    currentRouteName: {
        type: String,
        default: '',
    },
    navigation: {
        type: Array,
        required: true,
    },
});

const isMobileOpen = ref(false);

function matchesRoute(patterns = []) {
    return patterns.some((pattern) => {
        if (!props.currentRouteName) {
            return false;
        }

        if (pattern.endsWith('.*')) {
            return props.currentRouteName.startsWith(pattern.slice(0, -1));
        }

        return props.currentRouteName === pattern;
    });
}

function sectionActive(item) {
    if (item.activePatterns && matchesRoute(item.activePatterns)) {
        return true;
    }

    return (item.children || []).some((child) => matchesRoute(child.activePatterns || []));
}

const openGroups = ref(
    props.navigation.flatMap((group) => group.items.filter(sectionActive).map((item) => item.label)),
);

function toggleGroup(label) {
    openGroups.value = openGroups.value.includes(label)
        ? openGroups.value.filter((entry) => entry !== label)
        : [...openGroups.value, label];
}

function isGroupOpen(item) {
    return openGroups.value.includes(item.label) || sectionActive(item);
}

const iconPaths = {
    dashboard: ['M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-10h8V3h-8v8Z'],
    farmers: ['M3 4h18v14H3z', 'M7 8h4', 'M7 12h10'],
    renewals: ['M21 12a9 9 0 1 1-2.64-6.36', 'M21 3v6h-6'],
    mortuary: ['M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 5.65-7 10-7 10Z', 'M9 12h6', 'M12 9v6'],
    locations: ['M12 21s-6-4.35-6-10a6 6 0 1 1 12 0c0 5.65-6 10-6 10Z', 'M12 11m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0'],
    fees: ['M12 1v22', 'M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
    documents: ['M9 3h6', 'M10 8h4', 'M5 5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5Z', 'M9 13h6', 'M9 17h6'],
    users: ['M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0', 'M22 21v-2a4 4 0 0 0-3-3.87', 'M16 3.13a4 4 0 0 1 0 7.75'],
    audit: ['M12 3l7 4v5c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V7l7-4Z', 'M9 12l2 2 4-4'],
    communication: ['M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z', 'M8 10h8', 'M8 14h5'],
};
</script>

<template>
    <div class="lg:hidden">
        <button
            type="button"
            class="fixed left-3.5 top-3.5 z-50 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] text-white shadow-md transition-all duration-200 hover:shadow-lg active:scale-95"
            @click="isMobileOpen = true"
        >
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <div v-if="isMobileOpen" class="fixed inset-0 z-40 bg-[#08130f]/60 backdrop-blur-sm" @click="isMobileOpen = false"></div>
    </div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-[240px] flex-col overflow-hidden border-r border-[#e3e9e6] bg-white text-[#15372f] shadow-[6px_0_20px_rgba(15,23,42,0.04)] transition-transform duration-300 lg:translate-x-0"
        :class="isMobileOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="border-b border-[#e8edea] px-4 py-3.5">
            <div class="flex items-start justify-between gap-4">
                <Link :href="brand.homeUrl || '/admin'" class="min-w-0 no-underline">
                    <div class="flex items-center gap-2.5">
                        <img :src="brand.logoUrl" alt="AniTech" class="h-8 w-8 object-contain">
                        <div class="min-w-0">
                            <div class="truncate text-lg font-semibold tracking-[-0.02em] text-[#143c32]">{{ brand.name }}</div>
                            <div class="truncate text-[0.58rem] font-medium uppercase tracking-[0.1em] text-[#6f7e78]">{{ brand.subtitle }}</div>
                        </div>
                    </div>
                </Link>

                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#e7ece9] bg-white text-[#406359] lg:hidden" @click="isMobileOpen = false">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m6 6 12 12" />
                        <path d="m18 6-12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-2.5 py-3.5">
            <div v-for="group in navigation" :key="group.label" class="mb-4 last:mb-0">
                <p class="mb-1.5 px-2.5 text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-[#7b8681]">{{ group.label }}</p>

                <div class="space-y-0.5">
                    <div v-for="item in group.items" :key="item.label">
                        <Link
                            v-if="item.href && !item.children"
                            :href="item.href"
                            class="flex min-h-9 items-center gap-2.5 rounded-md border-l-[3px] px-2.5 py-2 text-xs font-medium transition"
                            :class="sectionActive(item) ? 'border-l-[#5f8418] bg-[#eef5e9] text-[#0f4a3d]' : 'border-l-transparent text-[#344a43] hover:bg-[#f4f7f5] hover:text-[#173e34]'"
                            @click="isMobileOpen = false"
                        >
                            <span class="inline-flex h-5 w-5 items-center justify-center text-[#31584e]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                    <path v-for="path in iconPaths[item.icon] || []" :key="path" :d="path" />
                                </svg>
                            </span>
                            <span>{{ item.label }}</span>
                        </Link>

                        <div v-else>
                            <button
                                type="button"
                                class="flex min-h-9 w-full items-center gap-2.5 rounded-md border-l-[3px] px-2.5 py-2 text-left text-xs font-medium transition"
                                :class="sectionActive(item) ? 'border-l-[#5f8418] bg-[#eef5e9] text-[#0f4a3d]' : 'border-l-transparent text-[#344a43] hover:bg-[#f4f7f5] hover:text-[#173e34]'"
                                @click="toggleGroup(item.label)"
                            >
                                <span class="inline-flex h-5 w-5 items-center justify-center text-[#31584e]">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path v-for="path in iconPaths[item.icon] || []" :key="path" :d="path" />
                                    </svg>
                                </span>
                                <span class="flex-1">{{ item.label }}</span>
                                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 text-[#7b8782] transition-transform" :class="isGroupOpen(item) ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            <div v-if="isGroupOpen(item)" class="mt-0.5 space-y-0.5 pl-8">
                                <Link
                                    v-for="child in item.children"
                                    :key="child.label"
                                    :href="child.href"
                                    class="block rounded-md px-3 py-1.5 text-[0.7rem] transition"
                                    :class="matchesRoute(child.activePatterns || []) ? 'bg-[#edf4f0] font-semibold text-[#0f4a3d]' : 'text-[#60706a] hover:bg-[#f6f8f7] hover:text-[#173e34]'"
                                    @click="isMobileOpen = false"
                                >
                                    {{ child.label }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>
