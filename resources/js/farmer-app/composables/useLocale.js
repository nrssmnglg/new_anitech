import { storeToRefs } from 'pinia';
import { useLanguageStore } from '../stores/language';

export function useLocale() {
    const language = useLanguageStore();
    const { locale } = storeToRefs(language);

    return {
        locale,
        setLocale: language.setLocale,
        t: language.t,
    };
}
