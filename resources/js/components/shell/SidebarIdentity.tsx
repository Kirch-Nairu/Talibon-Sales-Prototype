import type { ShowcasePersona } from '../showcase/types';
import type { AuthUser } from '../../types';

function userInitials(name?: string | null): string {
    const parts = name?.trim().split(/\s+/).filter(Boolean) ?? [];
    if (parts.length === 0) return 'OT';
    if (parts.length === 1) return parts[0][0]?.toUpperCase() ?? 'OT';
    return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase();
}

type Props = {
    compact: boolean;
    user: AuthUser | null;
    persona?: ShowcasePersona | null;
};

export default function SidebarIdentity({ compact, user, persona = null }: Props) {
    const name = persona?.label ?? user?.name;
    const position = persona?.position ?? user?.employee?.position;
    const department = persona?.office ?? user?.employee?.department?.name;
    const title = [name, position, department].filter(Boolean).join(' · ');
    const context = [position, department].filter(Boolean).join(' · ');

    if (compact) {
        return (
            <div
                role="img"
                aria-label={title ? `Signed in as ${title}` : 'Signed in user'}
                title={title || undefined}
                className="flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 text-xs font-bold text-white"
            >
                {userInitials(name)}
            </div>
        );
    }

    return (
        <div className="min-w-0" aria-label={title ? `Signed in as ${title}` : 'Signed in user'} title={title || undefined}>
            <div className="line-clamp-1 text-sm font-semibold leading-5 text-white">
                {name}
            </div>
            {context && (
                <div className="mt-0.5 line-clamp-2 break-words text-[10px] leading-[14px] text-blue-200">
                    {context}
                </div>
            )}
        </div>
    );
}
