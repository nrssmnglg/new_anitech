import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { readStorage, writeStorage } from '../utils/storage';
import en from '../locales/en';
import fil from '../locales/fil';

const STORAGE_KEY = 'language';
const FALLBACK_LOCALE = 'en';
const dictionaries = { en, fil };

function readKey(source, path) {
    return path.split('.').reduce((carry, segment) => carry?.[segment], source);
}

function applyReplacements(template, replacements = {}) {
    return Object.entries(replacements).reduce(
        (carry, [key, value]) => carry.replaceAll(`{${key}}`, String(value)),
        template,
    );
}

export const useLanguageStore = defineStore('farmer-language', () => {
    const locale = ref(readStorage(STORAGE_KEY, FALLBACK_LOCALE) ?? FALLBACK_LOCALE);
    const current = computed(() => dictionaries[locale.value] ?? dictionaries[FALLBACK_LOCALE]);

    const setLocale = (value) => {
        locale.value = dictionaries[value] ? value : FALLBACK_LOCALE;
        writeStorage(STORAGE_KEY, locale.value);
    };

    const t = (key, replacements = {}) => {
        const primary = readKey(current.value, key);
        const fallback = readKey(dictionaries[FALLBACK_LOCALE], key);
        const template = typeof primary === 'string'
            ? primary
            : typeof fallback === 'string'
                ? fallback
                : key;

        return applyReplacements(template, replacements);
    };

    return {
        locale,
        setLocale,
        t,
    };
});
