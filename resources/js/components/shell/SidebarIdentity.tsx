import type { AuthUser } from '../../types';
import type { ShowcasePersona } from '../showcase/types';

function userInitials(name?: string | null): string {
    const parts = name?.trim().split(/\s+/).filter(Boolean) ?? [];
    if (parts.length === 0) return 'OT';
    if (parts.length === 1) return parts[0][0]?.toUpperCase() ?? 'OT';
    return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase();
}

function roleLabel(role?: string | null): string {
    if (!role) return '';
    return role
        .split('_')
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

type Props = {
    compact: boolean;
    user: AuthUser | null;
    persona?: ShowcasePersona | null;
};

export default function SidebarIdentity({ compact, user, persona = null }: Props) {
    const name = persona?.label ?? user?.name;
    const role = persona?.position ?? roleLabel(user?.role);
    const office = persona?.office ?? user?.employee?.department?.short_name ?? user?.employee?.department?.name;
    const position = persona ? null : user?.employee?.position;
    const title = [name, position, role, office].filter(Boolean).join(' · ');
    const context = [role, office].filter(Boolean).join(' · ');

    if (compact) {
        return (
            <div
                role="img"
                aria-label={title ? `Signed in as ${title}` : 'Signed in user'}
                title={title || undefined}
                className="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 bg-white/10 text-[11px] font-bold text-white"
            >
                {userInitials(name)}
            </div>
        );
    }

    return (
        <div className="flex min-w-0 items-center gap-2.5" aria-label={title ? `Signed in as ${title}` : 'Signed in user'} title={title || undefined}>
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/10 text-[11px] font-bold text-white" aria-hidden="true">
                {userInitials(name)}
            </div>
            <div className="min-w-0">
                <div className="truncate text-xs font-semibold leading-4 text-white">
                    {name || 'Signed-in employee'}
                </div>
                {context && (
                    <div className="mt-0.5 truncate text-[10px] leading-4 text-blue-200" title={context}>
                        {context}
                    </div>
                )}
            </div>
        </div>
    );
}
