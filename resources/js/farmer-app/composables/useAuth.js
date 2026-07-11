import { storeToRefs } from 'pinia';
import { useAuthStore } from '../stores/auth';

export function useAuth() {
    const store = useAuthStore();
    const refs = storeToRefs(store);

    return {
        ...refs,
        bootstrap: store.bootstrap,
        login: store.login,
        logout: store.logout,
        restore: store.restore,
        setup: store.setup,
    };
}
