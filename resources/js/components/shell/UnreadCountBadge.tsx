type Props = {
    active: boolean;
    compact: boolean;
    count: number;
};

export default function UnreadCountBadge({ active, compact, count }: Props) {
    if (count <= 0) return null;
    const display = count > 99 ? '99+' : String(count);

    if (compact) {
        return (
            <span
                aria-hidden="true"
                className={`absolute right-1 top-1 flex min-h-4 min-w-4 items-center justify-center rounded-full px-1 text-[9px] font-bold leading-none ${
                    active ? 'bg-amber-100 text-amber-900' : 'bg-amber-400 text-slate-950'
                }`}
            >
                {display}
            </span>
        );
    }

    return (
        <span className={`rounded-full px-2 py-0.5 text-xs font-bold ${
            active ? 'bg-amber-100 text-amber-900' : 'bg-amber-400 text-slate-950'
        }`}>
            {display}
        </span>
    );
}
