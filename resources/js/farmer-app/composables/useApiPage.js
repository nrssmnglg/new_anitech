import { ref } from 'vue';

export function useApiPage(loader) {
    const loading = ref(false);
    const error = ref('');

    const run = async (...args) => {
        loading.value = true;
        error.value = '';

        try {
            return await loader(...args);
        } catch (err) {
            error.value = err?.response?.data?.message ?? 'Unable to load this section right now.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        error,
        loading,
        run,
    };
}
