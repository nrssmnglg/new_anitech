const shellConfig = window.__FARMER_PWA__ ?? {};

function normalizePath(path) {
    if (!path) {
        return '/';
    }

    return path.startsWith('/') ? path : `/${path}`;
}

export function resolveFarmerAppBasePath() {
    const configuredBase = shellConfig.appBase ?? '/farmer/app';

    try {
        const url = new URL(configuredBase, window.location.origin);

        return url.pathname.endsWith('/') ? url.pathname.slice(0, -1) : url.pathname;
    } catch {
        return '/farmer/app';
    }
}

export function resolveFarmerPublicBasePath() {
    const appBasePath = resolveFarmerAppBasePath();

    if (appBasePath.endsWith('/farmer/app')) {
        return appBasePath.slice(0, -'/farmer/app'.length) || '';
    }

    return '';
}

export function farmerPublicUrl(path, query = null) {
    const normalizedPath = normalizePath(path);
    const basePath = resolveFarmerPublicBasePath();

    const url = new URL(`${basePath}${normalizedPath}`, window.location.origin);

    if (query && typeof query === 'object') {
        Object.entries(query).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, String(value));
            }
        });
    }

    return url.toString();
}

export function farmerAppPath(path) {
    const normalizedPath = normalizePath(path);
    const basePath = resolveFarmerAppBasePath();

    return `${basePath}${normalizedPath}`;
}

export function resolveFarmerAppRedirect(target) {
    if (!target) {
        return null;
    }

    try {
        const url = new URL(target, window.location.origin);
        const appBasePath = resolveFarmerAppBasePath();

        if (url.pathname.startsWith(`${appBasePath}/`) || url.pathname === appBasePath) {
            const relativePath = url.pathname.slice(appBasePath.length) || '/';

            return `${relativePath}${url.search}`;
        }

        return url.toString();
    } catch {
        return target;
    }
}
