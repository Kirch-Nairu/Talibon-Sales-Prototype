import { useEffect, useState } from 'react';

export default function MunicipalContext() {
    const [now, setNow] = useState(() => new Date());
    useEffect(() => {
        const timer = window.setInterval(() => setNow(new Date()), 60000);
        return () => window.clearInterval(timer);
    }, []);
    const date = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }).format(now);
    return <aside className="flex flex-wrap justify-between gap-x-4 gap-y-1 text-xs text-slate-600 dark:text-slate-300" aria-label="Municipal context">
        <span>Talibon, Bohol</span>
        <span>{date} · Philippine time</span>
    </aside>;
}
