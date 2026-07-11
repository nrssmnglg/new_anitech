<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();
import { brandLogoUrl } from '../utils/asset';

const brandLogo = brandLogoUrl();

const trackTarget = computed(() => ({
    name: 'track-status',
    query: {
        application_no: String(route.query.application_no ?? ''),
        birth_date: String(route.query.birth_date ?? ''),
    },
}));
</script>

<template>
    <div class="farmer-app__upload-complete-screen">
        <div class="farmer-app__upload-complete-backdrop"></div>

        <main class="farmer-app__upload-complete-shell">
            <div class="farmer-app__upload-complete-brand">AniTech</div>

            <section class="farmer-app__upload-complete-card">
                <div class="farmer-app__upload-complete-icon-wrap">
                    <div class="farmer-app__upload-complete-glow"></div>
                    <div class="farmer-app__upload-complete-icon">
                        <img :src="brandLogo" alt="AniTech mark">
                    </div>
                </div>

                <div class="farmer-app__upload-complete-copy">
                    <h1>Documents Submitted</h1>
                    <p>Your required documents have been submitted successfully.</p>
                </div>

                <div class="farmer-app__upload-complete-status">
                    <div class="farmer-app__upload-complete-status-head">
                        <span class="farmer-app__upload-complete-pulse"></span>
                        <strong>Waiting For Office Approval</strong>
                    </div>
                    <h2>Next Steps</h2>
                    <p>
                        Please wait while the Municipal Agriculture Office reviews your application.
                        Check your tracking screen regularly for updates.
                    </p>
                </div>

                <RouterLink :to="trackTarget" class="farmer-app__btn farmer-app__upload-complete-primary">
                    Go Back To Tracking
                </RouterLink>
            </section>
        </main>

        <footer class="farmer-app__upload-complete-footer">
            AniTech Solutions | Smart agriculture for a sustainable future.
        </footer>
    </div>
</template>
