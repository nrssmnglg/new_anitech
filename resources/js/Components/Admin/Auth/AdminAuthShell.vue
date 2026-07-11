<script setup>
defineProps({
    heroTitle: {
        type: String,
        required: true,
    },
    heroCopy: {
        type: String,
        required: true,
    },
    logoUrl: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="min-h-screen">
        <div class="flex min-h-screen w-full">
            <section
                class="relative hidden bg-center bg-cover px-14 py-14 text-white lg:flex lg:w-1/2 lg:flex-col lg:justify-between"
                style="background-image:
                    linear-gradient(rgba(27,77,62,.85), rgba(27,77,62,.70)),
                    url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200');"
            >
                <div>
                    <img :src="logoUrl" alt="AniTech official logo" class="h-16 w-auto drop-shadow-[0_8px_18px_rgba(0,0,0,0.25)]">
                </div>

                <div class="max-w-xl">
                    <h1 class="whitespace-pre-line text-5xl font-extrabold leading-tight tracking-wide uppercase">
                        {{ heroTitle }}
                    </h1>

                    <div class="mt-5 border-l-4 border-[#8DBB61] pl-5">
                        <p class="text-base leading-relaxed text-white/90">
                            {{ heroCopy }}
                        </p>
                    </div>
                </div>

                <div class="text-center text-sm text-white/70">
                    &copy; 2026 AniTech.
                </div>
            </section>

            <section class="relative flex w-full items-center justify-center overflow-y-auto bg-white px-6 py-10 lg:w-1/2">
                <div
                    class="pointer-events-none absolute inset-0 opacity-70"
                    style="background-image: radial-gradient(#e5e5e5 1px, transparent 1px); background-size: 20px 20px;"
                />

                <slot />
            </section>
        </div>
    </div>
</template>
