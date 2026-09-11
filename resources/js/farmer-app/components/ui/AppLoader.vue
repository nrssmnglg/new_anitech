<template>
    <div class="farmer-app__loader" role="status" aria-live="polite" aria-label="Loading">
        <span class="farmer-app__loader-spinner" aria-hidden="true"></span>
        <span class="farmer-app__sr-only">Loading content</span>
    </div>
</template>

<style scoped>
.farmer-app__loader { min-height: 72px; padding: 12px 0; }
.farmer-app__loader-spinner { width: 26px; height: 26px; border-width: 2px; }
</style>
