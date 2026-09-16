import { router } from '@inertiajs/react';
import { Repeat2 } from 'lucide-react';
import { useState } from 'react';

type Props = {
    compact?: boolean;
};

export default function WorkspaceSwitcher({ compact = false }: Props) {
    const [switching, setSwitching] = useState(false);

    const switchWorkspace = () => {
        if (switching) return;
        setSwitching(true);
        router.post('/showcase/switch', {}, { onFinish: () => setSwitching(false) });
    };

    return (
        <button
            type="button"
            onClick={switchWorkspace}
            disabled={switching}
            aria-label={compact ? (switching ? 'Switching workspace' : 'Switch Workspace') : undefined}
            title={compact ? (switching ? 'Switching workspace' : 'Switch Workspace') : undefined}
            className={compact
                ? 'flex h-10 w-10 items-center justify-center rounded-lg text-blue-100 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 disabled:cursor-wait disabled:opacity-60'
                : 'flex min-h-10 w-full items-center gap-2 rounded-lg px-2 text-sm text-blue-100 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 disabled:cursor-wait disabled:opacity-60'}
        >
            <Repeat2 size={compact ? 17 : 15} aria-hidden="true" />
            {!compact && <span>{switching ? 'Switching workspace…' : 'Switch Workspace'}</span>}
        </button>
    );
}
