<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    total: { type: Number, required: true },
    createUrl: { type: String, required: true },
});

defineEmits(['export']);
</script>

<template>
    <section class="overflow-hidden rounded-[24px] border border-[#d8e2dc] bg-[radial-gradient(circle_at_top_right,_rgba(186,238,217,0.95),_rgba(248,250,249,0.95)_48%,_#f8faf9_100%)] shadow-[0_14px_34px_rgba(15,23,42,0.06)]">
        <div class="grid gap-6 px-5 py-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(220px,0.55fr)] lg:px-7 lg:py-6">
            <div class="space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/70 px-4 py-1.5 shadow-sm backdrop-blur">
                    <span class="h-2 w-2 rounded-full bg-[#416918]"></span>
                    <span class="text-[0.62rem] font-extrabold uppercase tracking-[0.24em] text-[#416918]">System Operational</span>
                </div>

                <div>
                    <p class="text-[0.74rem] font-black uppercase tracking-[0.3em] text-[#5a7168]">Farmer Management</p>
                    <h1 class="mt-2 text-[2.85rem] font-black tracking-[-0.05em] text-[#003629] sm:text-[3.5rem]">Farmer Registry</h1>
                </div>

                <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center">
                    <div class="flex flex-col gap-2.5 sm:flex-row">
                        <Link
                            :href="createUrl"
                            class="inline-flex items-center justify-center rounded-[1.15rem] bg-[#003629] px-5 py-3 text-[0.92rem] font-extrabold text-white shadow-[0_14px_28px_rgba(0,54,41,0.16)] transition hover:-translate-y-0.5 hover:bg-[#0d4637]"
                        >
                            New Membership Application
                        </Link>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-[1.15rem] border border-[#c8d8cf] bg-white/85 px-4 py-3 text-[0.92rem] font-extrabold text-[#003629] transition hover:-translate-y-0.5 hover:bg-white"
                            @click="$emit('export', 'pdf')"
                        >
                            Export PDF
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-[1.15rem] border border-[#c8d8cf] bg-white/85 px-4 py-3 text-[0.92rem] font-extrabold text-[#003629] transition hover:-translate-y-0.5 hover:bg-white"
                            @click="$emit('export', 'xlsx')"
                        >
                            Export Excel
                        </button>
                    </div>

                    <div class="flex items-center gap-3 rounded-[1.15rem] border border-white/70 bg-white/65 px-4 py-2.5 backdrop-blur">
                        <div class="h-9 w-px bg-[#cbd6cf]"></div>
                        <div>
                            <p class="text-[1.75rem] font-black leading-none text-[#003629]">{{ total.toLocaleString() }}</p>
                            <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.26em] text-[#72817b]">Total Records</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden lg:block">
                <div class="relative h-full min-h-[180px] overflow-hidden rounded-[24px] border border-[#c8d8cf] bg-[linear-gradient(160deg,_rgba(255,255,255,0.72)_0%,_rgba(233,243,237,0.92)_100%)] p-5">
                    <div class="absolute inset-x-5 top-5 h-24 rounded-[20px] border border-[#dbe5df] bg-[linear-gradient(180deg,_rgba(186,238,217,0.6)_0%,_rgba(255,255,255,0.85)_100%)]"></div>
                    <div class="absolute bottom-0 left-0 right-0 h-24 bg-[radial-gradient(circle_at_center,_rgba(65,105,24,0.18),_transparent_70%)]"></div>

                    <div class="relative flex h-full flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <div class="rounded-[1.1rem] bg-white/85 px-3.5 py-2.5 shadow-sm">
                                <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.24em] text-[#72817b]">Registry View</p>
                                <p class="mt-1 text-sm font-bold text-[#003629]">Membership Records</p>
                            </div>
                            <div class="rounded-full border border-[#c4d6cb] bg-white/80 px-3 py-1.5 text-[0.58rem] font-extrabold uppercase tracking-[0.22em] text-[#376757]">
                                Official
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            <div class="flex items-end gap-3">
                                <div class="h-12 flex-1 rounded-t-[20px] bg-[#9ed1bd]"></div>
                                <div class="h-20 flex-1 rounded-t-[20px] bg-[#416918]"></div>
                                <div class="h-10 flex-1 rounded-t-[20px] bg-[#c0f190]"></div>
                            </div>
                            <div class="rounded-[18px] border border-[#d9e3dd] bg-white/88 p-3.5 shadow-sm">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#72817b]">Registry Processing</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
