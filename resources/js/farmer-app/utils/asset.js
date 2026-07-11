const shellConfig = window.__FARMER_PWA__ ?? {};

function resolveAssetBase() {
    const configuredBase = shellConfig.assetBase;

    if (configuredBase) {
        return configuredBase;
    }

    try {
        const { origin, pathname } = window.location;
        const indexPath = '/index.php/';
        const indexPosition = pathname.indexOf(indexPath);

        if (indexPosition >= 0) {
            return `${origin}${pathname.slice(0, indexPosition)}`;
        }

        return origin;
    } catch {
        return window.location.origin;
    }
}

export function publicAsset(path) {
    const normalizedPath = path.replace(/^\/+/, '');

    try {
        return new URL(normalizedPath, `${resolveAssetBase().replace(/\/+$/, '')}/`).toString();
    } catch {
        return `/${normalizedPath}`;
    }
}

export function brandLogoUrl() {
    return shellConfig.logoUrl ?? publicAsset('/figures/anitech-mark-official.svg');
}
