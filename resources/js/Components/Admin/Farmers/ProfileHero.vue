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
    <section class="rounded-[20px] border border-[#dfe7e2] bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-16 w-16 items-center justify-center rounded-[18px] bg-[#eef3f0] text-xl font-black text-[#0f4a3d]">
                    {{ initials(farmer.fullName) }}
                </div>
                <div>
                    <div class="mb-1.5 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black tracking-[-0.03em] text-[#173e34] sm:text-3xl">{{ farmer.fullName }}</h1>
                        <span class="inline-flex rounded-full px-3 py-1 text-[0.62rem] font-black uppercase tracking-[0.18em]" :class="statusBadge(farmer.status.value)">
                            {{ farmer.status.label }}
                        </span>
                    </div>
                    <p class="flex items-center gap-2 text-sm text-[#5f6c67]">
                        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 text-[#6f7e78]" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="M7 9h10" />
                            <path d="M7 13h6" />
                        </svg>
                        {{ farmer.farmerCode }}
                    </p>
                </div>
            </div>

            <div class="grid gap-2.5 sm:grid-cols-2 lg:min-w-[280px]">
                <div class="rounded-[18px] border border-[#e5ece8] bg-[#f8faf9] px-4 py-3">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Membership</p>
                    <p class="mt-1.5 text-base font-black text-[#173e34]">{{ farmer.membershipStatusLabel || 'Not set' }}</p>
                </div>
                <div class="rounded-[18px] border border-[#e5ece8] bg-[#f8faf9] px-4 py-3">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Member Type</p>
                    <p class="mt-1.5 text-base font-black text-[#173e34]">{{ farmer.memberType?.code || 'N/A' }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
