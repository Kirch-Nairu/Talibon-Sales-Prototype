export function normalizePortalPath(value: string): string {
    const path = (value.split(/[?#]/, 1)[0] || '/').replace(/\/$/, '');
    return path || '/';
}

export function isPortalPathActive(currentUrl: string, href: string): boolean {
    const path = normalizePortalPath(currentUrl);
    const target = normalizePortalPath(href);

    return path === target || (target !== '/' && path.startsWith(`${target}/`));
}
