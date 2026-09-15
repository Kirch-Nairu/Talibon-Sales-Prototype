import { Building2, FileText, Megaphone } from 'lucide-react';
import type { PublicContent } from './types';

export default function PublicUpdates({ content }: { content: PublicContent }) {
    return <section className="public-updates" aria-label="Public information and updates">
        <div id="news" className="public-update-column public-update-news">
            <header>
                <h2><Megaphone size={18} aria-hidden="true" />News &amp; Notices</h2>
                <p>Public advisories, events, and municipality updates.</p>
            </header>
            <div className="public-update-list">
                {content.news.map((item, index) => <article key={item.title} className={index === 0 ? 'public-update-feature' : ''}>
                    <div className="public-item-meta">{item.type}{item.date && item.date !== 'Prototype' ? ' · ' + item.date : ''}</div>
                    <h3>{item.title}</h3>
                    <p>{item.summary}</p>
                </article>)}
                {!content.news.length && <p className="public-empty-copy">No public updates available.</p>}
            </div>
        </div>

        <div id="transparency" className="public-update-column public-update-documents">
            <header>
                <h2><FileText size={18} aria-hidden="true" />Public Documents</h2>
                <p>Transparency resources prepared for public access.</p>
            </header>
            <div className="public-update-list">
                {content.transparency.map(item => <article key={item.label}>
                    <div className="public-item-meta">{item.value}</div>
                    <h3>{item.label}</h3>
                    <p>{item.note}</p>
                </article>)}
                {!content.transparency.length && <p className="public-empty-copy">No public documents available.</p>}
            </div>
        </div>

        <div id="projects" className="public-update-column public-update-projects">
            <header>
                <h2><Building2 size={18} aria-hidden="true" />Projects &amp; Programs</h2>
                <p>Selected municipal project and program updates.</p>
            </header>
            <div className="public-update-list">
                {content.projects.map(item => <article key={item.title}>
                    <div className="public-item-meta">{item.tag}</div>
                    <h3>{item.title}</h3>
                    <p>{item.summary}</p>
                </article>)}
                {!content.projects.length && <p className="public-empty-copy">No project updates available.</p>}
            </div>
        </div>
    </section>;
}
