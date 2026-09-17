import { router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { useState } from 'react';
import type { AuthUser, SharedProps } from '../../types';
import WorkspaceSwitcher from '../showcase/WorkspaceSwitcher';
import SidebarAppearanceMenu from './SidebarAppearanceMenu';
import SidebarIdentity from './SidebarIdentity';

type Props = {
    compact: boolean;
    user: AuthUser | null;
};

export default function SidebarFooter({ compact, user }: Props) {
    const [signingOut, setSigningOut] = useState(false);
    const { showcaseSession } = usePage<SharedProps>().props;
    const showcasePersona = showcaseSession?.active ? showcaseSession.persona : null;

    const signOut = () => {
        if (signingOut) return;
        setSigningOut(true);
        router.post('/logout', {}, { onFinish: () => setSigningOut(false) });
    };

    if (compact) {
        return (
            <div className="flex flex-col items-center gap-1" aria-busy={signingOut}>
                <SidebarIdentity compact user={user} persona={showcasePersona} />
                <div className="mt-0.5 flex flex-col gap-0.5 border-t border-white/10 pt-1.5">
                    {showcaseSession?.active && <WorkspaceSwitcher compact />}
                    <SidebarAppearanceMenu compact />
                    <button
                        type="button"
                        onClick={signOut}
                        disabled={signingOut}
                        className="flex h-9 w-9 items-center justify-center rounded-md text-blue-200 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 disabled:cursor-wait disabled:opacity-60"
                        aria-label={signingOut ? 'Signing out' : 'Sign out'}
                        title={signingOut ? 'Signing out' : 'Sign out'}
                    >
                        <LogOut size={16} aria-hidden="true" />
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex min-w-0 items-center gap-2" aria-busy={signingOut}>
            <div className="min-w-0 flex-1">
                <SidebarIdentity compact={false} user={user} persona={showcasePersona} />
            </div>
            <div className="flex shrink-0 items-center gap-0.5 border-l border-white/10 pl-1.5">
                {showcaseSession?.active && <WorkspaceSwitcher compact />}
                <SidebarAppearanceMenu compact align="end" />
                <button
                    type="button"
                    onClick={signOut}
                    disabled={signingOut}
                    className="flex h-9 w-9 items-center justify-center rounded-md text-blue-200 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 disabled:cursor-wait disabled:opacity-60"
                    aria-label={signingOut ? 'Signing out' : 'Sign out'}
                    title={signingOut ? 'Signing out' : 'Sign out'}
                >
                    <LogOut size={16} aria-hidden="true" />
                </button>
            </div>
        </div>
    );
}
