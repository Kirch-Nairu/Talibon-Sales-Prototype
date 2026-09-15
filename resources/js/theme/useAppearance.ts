import { useEffect, useState } from 'react';
import { applyAppearance, type AppearancePreference, readAppearance, saveAppearance, subscribeToSystemAppearance } from './appearance';

export function useAppearance() {
    const [appearance, setAppearance] = useState<AppearancePreference>(readAppearance);
    useEffect(() => {
        const sync = () => setAppearance(readAppearance());
        window.addEventListener('talibon:appearance', sync);
        window.addEventListener('storage', sync);
        return () => {
            window.removeEventListener('talibon:appearance', sync);
            window.removeEventListener('storage', sync);
        };
    }, []);
    useEffect(() => {
        applyAppearance(appearance);
        if (appearance === 'system') return subscribeToSystemAppearance(() => applyAppearance('system'));
    }, [appearance]);
    return { appearance, choose: saveAppearance };
}
