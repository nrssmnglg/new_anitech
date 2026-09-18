<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    src: { type: String, required: true },
    label: { type: String, default: 'Document preview' },
    isPdf: { type: Boolean, default: false },
});
const emit = defineEmits(['load', 'error']);
const viewport = ref(null);
const zoom = ref(1);
const rotation = ref(0);
const availableWidth = ref(720);
const naturalWidth = ref(720);
const naturalHeight = ref(960);
let observer;
const baseWidth = computed(() => props.isPdf ? availableWidth.value : Math.min(
    availableWidth.value, naturalWidth.value, 500 * naturalWidth.value / naturalHeight.value,
));
const baseHeight = computed(() => props.isPdf ? 540 : baseWidth.value * naturalHeight.value / naturalWidth.value);
const sideways = computed(() => rotation.value % 180 !== 0);
const canvasStyle = computed(() => ({
    width: `${(sideways.value ? baseHeight.value : baseWidth.value) * zoom.value}px`,
    height: `${(sideways.value ? baseWidth.value : baseHeight.value) * zoom.value}px`,
}));
const fileStyle = computed(() => ({
    width: `${baseWidth.value}px`, height: `${baseHeight.value}px`,
    transform: `translate(-50%, -50%) rotate(${rotation.value}deg) scale(${zoom.value})`,
}));
function reset() { zoom.value = 1; rotation.value = 0; }
function imageLoaded(event) {
    naturalWidth.value = event.target.naturalWidth || 720;
    naturalHeight.value = event.target.naturalHeight || 960;
    emit('load');
}
watch(() => props.src, reset);
onMounted(() => {
    observer = new ResizeObserver(([entry]) => {
        availableWidth.value = Math.max(100, entry.contentRect.width - 24);
    });
    observer.observe(viewport.value);
});
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <div class="document-preview">
        <div class="preview-tools" role="toolbar" aria-label="Document view controls">
            <button type="button" aria-label="Zoom out" :disabled="zoom <= 0.25" @click="zoom = Math.max(0.25, zoom - 0.25)">−</button>
            <span aria-live="polite">{{ Math.round(zoom * 100) }}%</span>
            <button type="button" aria-label="Zoom in" :disabled="zoom >= 4" @click="zoom = Math.min(4, zoom + 0.25)">+</button>
            <button type="button" aria-label="Rotate left" @click="rotation = (rotation + 270) % 360">↶</button>
            <button type="button" aria-label="Rotate right" @click="rotation = (rotation + 90) % 360">↷</button>
            <button type="button" @click="reset">Reset</button>
        </div>
        <div ref="viewport" class="preview-viewport">
            <div class="preview-canvas" :style="canvasStyle">
                <iframe v-if="isPdf" :src="src" :title="label" :style="fileStyle" class="preview-file" @load="emit('load')" />
                <img v-else :src="src" :alt="label" :style="fileStyle" class="preview-file" @load="imageLoaded" @error="emit('error')">
            </div>
        </div>
    </div>
</template>

<style scoped>
.preview-tools { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 8px; padding: 8px; background: white; border-bottom: 1px solid #d7e0db; }
.preview-tools button { min-width: 36px; min-height: 36px; padding: 4px 10px; border: 1px solid #d7e0db; border-radius: 6px; font-weight: 600; color: #195642; }
.preview-tools button:hover { background: #eef7f2; }
.preview-tools button:disabled { opacity: 0.4; cursor: default; }
.preview-tools span { min-width: 48px; text-align: center; font-size: 13px; }
.preview-viewport { overflow: auto; height: min(60vh, 600px); padding: 12px; background: #f5f7f6; }
.preview-canvas { position: relative; margin: 0 auto; flex-shrink: 0; }
.preview-file { position: absolute; left: 50%; top: 50%; max-width: none; transform-origin: center; object-fit: contain; border: 0; background: white; }
</style>
