import type { AuthUser } from '../../types';

function initialsFor(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

export default function PortalHeaderIdentity({ user }: { user: AuthUser | null }) {
    if (!user) return null;

    const office = user.employee?.department?.short_name || user.employee?.department?.name;
    const position = user.employee?.position;
    const identityTitle = [user.name, position, office].filter(Boolean).join(' · ');

    return (
        <div aria-label={`Signed in as ${identityTitle}`} className="flex min-w-0 items-center gap-2.5 border-l border-slate-200 pl-3 dark:border-slate-700">
            <span
                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-white bg-blue-100 text-sm font-bold text-blue-900 dark:border-slate-700"
                aria-hidden="true"
                title={identityTitle}
            >
                {initialsFor(user.name) || 'OT'}
            </span>
            <div className="hidden min-w-0 max-w-44 xl:block">
                <div className="truncate text-xs font-bold" title={user.name}>{user.name}</div>
                {office && <div className="truncate text-xs text-slate-500 dark:text-slate-400" title={office}>{office}</div>}
                {position && (
                    <div className="truncate text-xs text-slate-500 dark:text-slate-400" title={position}>{position}</div>
                )}
            </div>
        </div>
    );
}
