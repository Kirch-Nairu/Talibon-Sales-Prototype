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
            <div className="flex flex-col items-center gap-2" aria-busy={signingOut}>
                <SidebarIdentity compact user={user} persona={showcasePersona} />
                <SidebarAppearanceMenu compact />
                {showcaseSession?.active && <WorkspaceSwitcher compact />}
                <button
                    type="button"
                    onClick={signOut}
                    disabled={signingOut}
                    className="flex h-10 w-10 items-center justify-center rounded-lg text-blue-100 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 disabled:cursor-wait disabled:opacity-60"
                    aria-label={signingOut ? 'Signing out' : 'Sign out'}
                    title={signingOut ? 'Signing out' : 'Sign out'}
                >
                    <LogOut size={17} aria-hidden="true" />
                </button>
            </div>
        );
    }

    return (
        <div className="space-y-2" aria-busy={signingOut}>
            <div className="flex min-w-0 items-start gap-2">
                <div className="min-w-0 flex-1">
                    <SidebarIdentity compact={false} user={user} persona={showcasePersona} />
                </div>
                <SidebarAppearanceMenu compact align="end" />
            </div>
            <div className="space-y-1 border-t border-white/10 pt-2">
                {showcaseSession?.active && <WorkspaceSwitcher />}
                <button
                    type="button"
                    onClick={signOut}
                    disabled={signingOut}
                    className="flex min-h-10 w-full items-center gap-2 rounded-lg px-2 text-sm text-blue-100 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 disabled:cursor-wait disabled:opacity-60"
                >
                    <LogOut size={15} aria-hidden="true" />
                    <span>{signingOut ? 'Signing out…' : 'Sign out'}</span>
                </button>
            </div>
        </div>
    );
}
