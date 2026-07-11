import { reactive, watch } from 'vue';
import { readStorage, removeStorage, writeStorage } from '../utils/storage';

export function useDraft(storageKey, initialState, options = {}) {
    const cached = readStorage(storageKey, {});
    const draft = reactive({
        ...initialState,
        ...cached,
    });

    if (typeof options.onHydrate === 'function') {
        options.onHydrate(draft, cached);
    }

    watch(
        draft,
        (value) => {
            writeStorage(storageKey, value);
        },
        { deep: true },
    );

    const clearDraft = () => {
        Object.entries(initialState).forEach(([key, value]) => {
            draft[key] = value;
        });
        removeStorage(storageKey);
    };

    return {
        clearDraft,
        draft,
    };
}
