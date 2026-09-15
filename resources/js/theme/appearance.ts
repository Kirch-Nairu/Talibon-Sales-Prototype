export type AppearancePreference = 'system' | 'light' | 'dark';
export type ResolvedAppearance = 'light' | 'dark';

const STORAGE_KEY = 'talibon.appearance';
const validPreferences: AppearancePreference[] = ['system', 'light', 'dark'];
let memoryPreference: AppearancePreference = 'system';

export function readAppearance(): AppearancePreference {
    if (typeof window === 'undefined') return 'system';

    let stored: string | null = null;
    try { stored = window.localStorage.getItem(STORAGE_KEY); } catch { return memoryPreference; }
    return validPreferences.includes(stored as AppearancePreference)
        ? stored as AppearancePreference
        : 'system';
}

export function resolveAppearance(preference: AppearancePreference): ResolvedAppearance {
    if (preference !== 'system') return preference;
    if (typeof window === 'undefined') return 'light';

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function applyAppearance(preference: AppearancePreference): void {
    if (typeof document === 'undefined') return;

    const resolved = resolveAppearance(preference);
    const root = document.documentElement;
    root.classList.toggle('dark', resolved === 'dark');
    root.dataset.appearance = preference;
    root.style.colorScheme = resolved;
}

export function saveAppearance(preference: AppearancePreference): void {
    memoryPreference = preference;
    if (typeof window !== 'undefined') {
        try { window.localStorage.setItem(STORAGE_KEY, preference); } catch { /* Appearance still applies when storage is unavailable. */ }
    }
    applyAppearance(preference);
    if (typeof window !== 'undefined') window.dispatchEvent(new Event('talibon:appearance'));
}

export function initializeAppearance(): void {
    applyAppearance(readAppearance());
}

export function subscribeToSystemAppearance(callback: () => void): () => void {
    if (typeof window === 'undefined') return () => undefined;

    const media = window.matchMedia('(prefers-color-scheme: dark)');
    media.addEventListener('change', callback);
    return () => media.removeEventListener('change', callback);
}
