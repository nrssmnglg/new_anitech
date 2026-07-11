const DB_NAME = 'anitech-farmer-drafts';
const STORE_NAME = 'attachments';
const DB_VERSION = 1;

function openDb() {
    return new Promise((resolve, reject) => {
        if (typeof window === 'undefined' || !window.indexedDB) {
            reject(new Error('IndexedDB is not available.'));
            return;
        }

        const request = window.indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => reject(request.error ?? new Error('Unable to open draft file storage.'));
        request.onsuccess = () => resolve(request.result);
        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME);
            }
        };
    });
}

function withStore(mode, callback) {
    return openDb().then((db) => new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, mode);
        const store = transaction.objectStore(STORE_NAME);

        let request;

        try {
            request = callback(store);
        } catch (error) {
            reject(error);
            return;
        }

        request.onerror = () => reject(request.error ?? new Error('Draft file operation failed.'));
        request.onsuccess = () => resolve(request.result);
        transaction.oncomplete = () => db.close();
        transaction.onerror = () => reject(transaction.error ?? new Error('Draft file transaction failed.'));
    }));
}

export function putDraftFile(key, payload) {
    return withStore('readwrite', (store) => store.put(payload, key));
}

export function getDraftFile(key) {
    return withStore('readonly', (store) => store.get(key));
}

export function deleteDraftFile(key) {
    return withStore('readwrite', (store) => store.delete(key));
}
