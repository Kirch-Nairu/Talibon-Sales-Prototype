const APP_ORIGIN = 'https://one-talibon.internal';

const hasMalformedEncoding = (value: string) => /%(?![0-9a-fA-F]{2})/.test(value);

const isInternalPath = (value: string) => {
    if (!value.startsWith('/') || value.startsWith('//') || value.includes('\\') || hasMalformedEncoding(value)) return false;

    try {
        const decoded = decodeURIComponent(value);
        return !decoded.includes('\\') && !/[\u0000-\u001f\u007f]/.test(decoded);
    } catch {
        return false;
    }
};

const stripNestedReturnTargets = (url: URL) => {
    [...url.searchParams.keys()].forEach((key) => {
        if (key === 'return_to' || key.startsWith('return_to[')) url.searchParams.delete(key);
    });
};

const recordsReturnEligible = new Set(['/transactions', '/correspondence', '/travel-orders']);

export function validateReturnTarget(candidate: string | null | undefined, expectedPath: string): string {
    if (!candidate || !isInternalPath(candidate)) return expectedPath;

    try {
        const url = new URL(candidate, APP_ORIGIN);
        const allowedPaths = recordsReturnEligible.has(expectedPath)
            ? [expectedPath, '/records']
            : [expectedPath];
        if (url.origin !== APP_ORIGIN || !allowedPaths.includes(url.pathname)) return expectedPath;
        stripNestedReturnTargets(url);
        return `${url.pathname}${url.search}`;
    } catch {
        return expectedPath;
    }
}

export function withReturnContext(detailHref: string, currentUrl: string, expectedListPath: string): string {
    if (!isInternalPath(detailHref)) return detailHref;

    try {
        const detail = new URL(detailHref, APP_ORIGIN);
        if (detail.origin !== APP_ORIGIN) return detailHref;
        detail.searchParams.set('return_to', validateReturnTarget(currentUrl, expectedListPath));
        return `${detail.pathname}${detail.search}${detail.hash}`;
    } catch {
        return detailHref;
    }
}

export function returnTargetFromDetailUrl(detailUrl: string, expectedListPath: string): string {
    if (!isInternalPath(detailUrl)) return expectedListPath;

    try {
        const detail = new URL(detailUrl, APP_ORIGIN);
        if (detail.origin !== APP_ORIGIN) return expectedListPath;
        return validateReturnTarget(detail.searchParams.get('return_to'), expectedListPath);
    } catch {
        return expectedListPath;
    }
}
