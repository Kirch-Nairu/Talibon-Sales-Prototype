import type { AuthUser } from '../../types';

function userInitials(name?: string | null): string {
    const parts = name?.trim().split(/\s+/).filter(Boolean) ?? [];
    if (parts.length === 0) return 'OT';
    if (parts.length === 1) return parts[0][0]?.toUpperCase() ?? 'OT';
    return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase();
}

export default function SidebarIdentity({ compact, user }: { compact: boolean; user: AuthUser | null }) {
    const position = user?.employee?.position;
    const department = user?.employee?.department?.name;
    const title = [user?.name, position, department].filter(Boolean).join(' · ');

    if (compact) {
        return (
            <div
                role="img"
                aria-label={title ? `Signed in as ${title}` : 'Signed in user'}
                title={title || undefined}
                className="flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 text-xs font-bold text-white"
            >
                {userInitials(user?.name)}
            </div>
        );
    }

    return (
        <div>
            <div className="text-sm font-semibold leading-snug break-words" title={user?.name || undefined}>
                {user?.name}
            </div>
            {position && (
                <div className="mt-1 text-xs leading-snug text-blue-100 break-words" title={position}>
                    {position}
                </div>
            )}
            {department && (
                <div className="mt-0.5 text-xs leading-snug text-blue-300 break-words" title={department}>
                    {department}
                </div>
            )}
        </div>
    );
}
