import { router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import type { AuthUser } from '../../types';
import AppearanceControl from '../AppearanceControl';
import SidebarIdentity from './SidebarIdentity';

type Props = {
    compact: boolean;
    user: AuthUser | null;
};

export default function SidebarFooter({ compact, user }: Props) {
    const signOut = () => router.post('/logout');

    if (compact) {
        return (
            <div className="flex flex-col items-center gap-3">
                <SidebarIdentity compact user={user} />
                <AppearanceControl compact />
                <button
                    type="button"
                    onClick={signOut}
                    className="flex h-10 w-10 items-center justify-center rounded-lg text-blue-100 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
                    aria-label="Sign out"
                    title="Sign out"
                >
                    <LogOut size={17} aria-hidden="true" />
                </button>
            </div>
        );
    }

    return (
        <div>
            <AppearanceControl />
            <div className="mt-3 border-t border-white/10 pt-3">
                <SidebarIdentity compact={false} user={user} />
                <button
                    type="button"
                    onClick={signOut}
                    className="mt-3 flex min-h-10 w-full items-center gap-2 rounded-lg px-2 text-sm text-blue-100 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
                >
                    <LogOut size={15} aria-hidden="true" />
                    <span>Sign out</span>
                </button>
            </div>
        </div>
    );
}
