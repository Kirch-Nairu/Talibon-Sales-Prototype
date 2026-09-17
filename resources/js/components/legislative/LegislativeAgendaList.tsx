type Agenda = {
    id: number;
    sequence_no: number;
    title: string;
    status: string;
    transaction?: { reference_no: string; title: string } | null;
    legislative_record?: { record_number: string; title: string } | null;
};

const pretty = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());

export default function LegislativeAgendaList({ items }: { items: Agenda[] }) {
    if (items.length === 0) return <div className="rounded-lg bg-slate-50 px-3 py-4 text-sm text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">No agenda items are recorded for this session.</div>;

    return (
        <ol className="space-y-2">
            {items.map((item) => {
                const reference = item.transaction?.reference_no || item.legislative_record?.record_number || 'Internal agenda item';
                return <li key={item.id} className="rounded-lg bg-slate-50 px-3 py-2.5 text-sm dark:bg-slate-900/40"><div className="font-medium text-slate-900 dark:text-slate-100"><span className="mr-1 font-bold text-indigo-700 dark:text-indigo-300">{item.sequence_no}.</span>{item.title}</div><div className="mt-1 flex flex-wrap gap-x-2 text-xs text-slate-500 dark:text-slate-400"><span>{reference}</span><span>·</span><span>{pretty(item.status)}</span></div></li>;
            })}
        </ol>
    );
}
