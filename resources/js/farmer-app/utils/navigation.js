export function resolveFarmerTarget(targetUrl) {
    if (!targetUrl || typeof targetUrl !== 'string') {
        return null;
    }

    let pathname = targetUrl;

    try {
        pathname = new URL(targetUrl, window.location.origin).pathname;
    } catch {
        pathname = targetUrl;
    }

    const normalizedPath = pathname
        .replace('/farmer/app/', '/farmer/')
        .replace('/farmer/app', '/farmer');

    if (normalizedPath.includes('/farmer/inquiries/')) {
        const id = normalizedPath.split('/farmer/inquiries/')[1]?.split('/')[0];
        return id ? { name: 'inquiry-detail', params: { inquiryId: id } } : { name: 'inquiries' };
    }

    if (normalizedPath.includes('/farmer/queries/')) {
        const id = normalizedPath.split('/farmer/queries/')[1]?.split('/')[0];
        return id ? { name: 'inquiry-detail', params: { inquiryId: id } } : { name: 'inquiries' };
    }

    if (normalizedPath.includes('/farmer/advisories/')) {
        const id = normalizedPath.split('/farmer/advisories/')[1]?.split('/')[0];
        return id ? { name: 'advisory-detail', params: { advisoryId: id } } : { name: 'advisories' };
    }

    if (normalizedPath.includes('/farmer/payments')) {
        return { name: 'payments' };
    }

    if (normalizedPath.includes('/farmer/renewals') || normalizedPath.includes('/farmer/renewal')) {
        return { name: 'renewals' };
    }

    if (normalizedPath.includes('/farmer/alerts') || normalizedPath.includes('/farmer/notifications')) {
        return { name: 'notifications' };
    }

    return null;
}

export function formatDateTime(value) {
    if (!value) {
        return 'No date';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
}

export function formatMoney(value) {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(amount);
}
