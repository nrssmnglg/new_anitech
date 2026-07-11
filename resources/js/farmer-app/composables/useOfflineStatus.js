import { onMounted, onUnmounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useAppStore } from '../stores/app';

export function useOfflineStatus() {
    const app = useAppStore();
    const refs = storeToRefs(app);

    const handleOnline = () => app.setOnline(true);
    const handleOffline = () => app.setOnline(false);

    onMounted(() => {
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
    });

    onUnmounted(() => {
        window.removeEventListener('online', handleOnline);
        window.removeEventListener('offline', handleOffline);
    });

    return {
        ...refs,
    };
}
