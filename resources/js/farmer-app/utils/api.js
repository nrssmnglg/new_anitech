export function extractApiMessage(error, fallback = 'Something went wrong.') {
    if (typeof error?.response?.data?.message === 'string' && error.response.data.message.trim() !== '') {
        return error.response.data.message;
    }

    if (typeof error?.message === 'string' && error.message.trim() !== '') {
        return error.message;
    }

    return fallback;
}

export function extractValidationErrors(error) {
    const raw = error?.response?.data?.errors;

    if (!raw || typeof raw !== 'object') {
        return {};
    }

    return Object.fromEntries(
        Object.entries(raw).map(([key, value]) => [
            key,
            Array.isArray(value) ? value.join(' ') : String(value),
        ]),
    );
}
