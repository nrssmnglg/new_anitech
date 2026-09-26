<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    farmer: { type: Object, required: true },
    urls: { type: Object, default: () => ({}) },
    renewal: { type: Object, default: () => ({}) },
});

function initials(name) {
    return String(name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || '?';
}
</script>

<template>
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
        <!-- Ambient decorative shapes -->
        <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
        <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
        <div class="pointer-events-none absolute right-24 top-4 h-20 w-20 rounded-full bg-white/[0.02]"></div>

        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-4 sm:gap-5">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/[0.15] text-lg font-bold text-white shadow-sm backdrop-blur-sm">
                    {{ initials(farmer.fullName) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold tracking-[-0.02em] text-white sm:text-2xl">{{ farmer.fullName }}</h1>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[0.62rem] font-bold"
                            :class="{
                                'bg-[#dcfce7] text-[#15803d]': farmer.status.value === 'active',
                                'bg-[#fee2e2] text-[#dc2626]': farmer.status.value === 'inactive',
                                'bg-white/20 text-white': farmer.status.value === 'deceased',
                            }"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="{
                                    'bg-[#22c55e]': farmer.status.value === 'active',
                                    'bg-[#ef4444]': farmer.status.value === 'inactive',
                                    'bg-white': farmer.status.value === 'deceased',
                                }"
                            ></span>
                            {{ farmer.status.label }}
                        </span>
                    </div>

                    <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-[#7ddfb8]">
                        <span class="inline-flex items-center gap-1 font-mono text-[0.7rem] text-white/90">
                            <svg viewBox="0 0 16 16" class="h-3 w-3 text-[#7ddfb8]" fill="currentColor"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2Zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7Z"/></svg>
                            {{ farmer.farmerCode }}
                        </span>
                        <span v-if="farmer.barangay?.name" class="text-white/70">·</span>
                        <span v-if="farmer.barangay?.name" class="text-white/80">{{ farmer.barangay.name }}</span>
                        <span v-if="farmer.association?.name" class="text-white/70">·</span>
                        <span v-if="farmer.association?.name" class="text-white/80">{{ farmer.association.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Header Quick Stats & Navigation Actions -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                    <div class="px-3.5 py-2 text-center">
                        <p class="text-[0.52rem] font-bold uppercase tracking-[0.1em] text-white/60">Membership</p>
                        <p class="mt-0.5 text-xs font-bold text-[#7ddfb8]">{{ farmer.membershipStatusLabel || 'Not set' }}</p>
                    </div>
                    <div class="border-l border-white/[0.08] px-3.5 py-2 text-center">
                        <p class="text-[0.52rem] font-bold uppercase tracking-[0.1em] text-white/60">Member Type</p>
                        <p class="mt-0.5 text-xs font-bold text-white">{{ farmer.memberType?.code || 'N/A' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="urls?.edit"
                        :href="urls.edit"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/[0.18] bg-white/[0.08] px-3 text-[0.7rem] font-semibold text-white shadow-sm backdrop-blur-sm transition-all duration-200 hover:bg-white/15 active:scale-[0.97]"
                    >
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#7ddfb8]" fill="currentColor">
                            <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                            <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                        </svg>
                        Edit Farmer
                    </Link>
                    <Link
                        v-if="urls?.index"
                        :href="urls.index"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]"
                    >
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                        </svg>
                        Back to Registry
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
