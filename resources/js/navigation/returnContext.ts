const APP_ORIGIN = 'https://one-talibon.internal';

const isInternalPath = (value: string) => value.startsWith('/') && !value.startsWith('//') && !value.includes('\\');

export function validateReturnTarget(candidate: string | null | undefined, expectedPath: string): string {
    if (!candidate || !isInternalPath(candidate)) return expectedPath;

    try {
        const url = new URL(candidate, APP_ORIGIN);
        if (url.origin !== APP_ORIGIN || url.pathname !== expectedPath) return expectedPath;
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
