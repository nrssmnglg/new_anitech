<script setup>
defineProps({
    farmer: { type: Object, required: true },
});

function statusBadge(value) {
    if (value === 'active') return 'bg-[#c0f190] text-[#0e2000]';
    if (value === 'inactive' || value === 'deceased') return 'bg-[#ffdad6] text-[#93000a]';
    return 'bg-[#fff3dc] text-[#a86100]';
}

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
    <section class="rounded-[24px] border border-[#dfe7e2] bg-white p-6 shadow-[0_14px_40px_rgba(15,23,42,0.06)]">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-20 w-20 items-center justify-center rounded-[20px] bg-[#eef3f0] text-2xl font-black text-[#0f4a3d]">
                    {{ initials(farmer.fullName) }}
                </div>
                <div>
                    <div class="mb-2 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-black tracking-[-0.03em] text-[#173e34] sm:text-4xl">{{ farmer.fullName }}</h1>
                        <span class="inline-flex rounded-full px-4 py-1.5 text-[0.7rem] font-black uppercase tracking-[0.18em]" :class="statusBadge(farmer.status.value)">
                            {{ farmer.status.label }}
                        </span>
                    </div>
                    <p class="flex items-center gap-2 text-[1rem] text-[#5f6c67]">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#6f7e78]" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="M7 9h10" />
                            <path d="M7 13h6" />
                        </svg>
                        {{ farmer.farmerCode }}
                    </p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[320px]">
                <div class="rounded-2xl border border-[#e5ece8] bg-[#f8faf9] px-4 py-4">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Membership</p>
                    <p class="mt-2 text-lg font-black text-[#173e34]">{{ farmer.membershipStatusLabel || 'Not set' }}</p>
                </div>
                <div class="rounded-2xl border border-[#e5ece8] bg-[#f8faf9] px-4 py-4">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Member Type</p>
                    <p class="mt-2 text-lg font-black text-[#173e34]">{{ farmer.memberType?.code || 'N/A' }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
