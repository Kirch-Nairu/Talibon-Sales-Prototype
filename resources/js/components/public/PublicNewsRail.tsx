import { CalendarDays, TriangleAlert } from 'lucide-react';
import PublicPanel from './PublicPanel';
import type { NewsItem, PublicContent } from './types';

function Notice({ item }: { item: NewsItem }) {
    return <article className="border-l-2 border-[#e5b63a] pl-3">
        <div className="flex items-center gap-2"><span className="text-xs font-semibold text-[#1769aa] dark:text-blue-300">{item.type}</span><span className="text-xs">{item.date}</span></div>
        <h3 className="mt-2 text-sm font-semibold leading-5">{item.title}</h3><p className="mt-1 text-sm leading-5 opacity-80">{item.summary}</p>
    </article>;
}

export default function PublicNewsRail({ content }: { content: PublicContent }) {
    const advisories = content.news.filter((item) => item.type.toLowerCase() === 'advisory');
    const events = content.news.filter((item) => item.type.toLowerCase() === 'event');
    return <aside className="public-news-rail" aria-label="Public advisories and events">
        <PublicPanel title="Municipal Advisories" icon={TriangleAlert}>

            <div className="space-y-2">{advisories.map((item) => <Notice key={item.title} item={item} />)}{!advisories.length && <p className="text-xs text-slate-500 dark:text-slate-400">No advisory entries available.</p>}</div>
            <a href="#news" className="municipal-link mt-3 inline-block">All news and notices →</a>
        </PublicPanel>
        <PublicPanel title="Upcoming Events" icon={CalendarDays}>

            <div className="space-y-2">{events.map((item) => <Notice key={item.title} item={item} />)}{!events.length && <p className="text-xs text-slate-500 dark:text-slate-400">No event entries available.</p>}</div>
            <a href="#news" className="municipal-link mt-3 inline-block">View news and events →</a>
        </PublicPanel>
    </aside>;
}
