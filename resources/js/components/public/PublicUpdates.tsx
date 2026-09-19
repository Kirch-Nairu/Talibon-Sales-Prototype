import type { PublicContent } from './types';

export default function PublicUpdates({ content }: { content: PublicContent }) {
    return <section className="public-official-information" aria-labelledby="public-official-information-title">
        <header className="public-official-header">
            <p className="public-official-kicker">Public information</p>
            <h2 id="public-official-information-title">News, notices & public records</h2>
            <p>Prototype municipal information is presented here for evaluation. Entries are not official publications unless the municipality later confirms them.</p>
        </header>

        <div className="public-editorial-layout">
            <section id="news" className="public-editorial-primary" aria-labelledby="public-news-title">
                <header className="public-editorial-section-header">
                    <h3 id="public-news-title">News &amp; Notices</h3>
                    <p>Sample advisories, events, and municipality updates.</p>
                </header>

                <ol className="public-record-list public-news-list">
                    {content.news.map((item, index) => {
                        const hasDate = item.date && item.date !== 'Prototype';

                        return <li key={item.title}>
                            <article className={index === 0 ? 'public-record public-record-lead' : 'public-record'}>
                                <div className="public-record-meta">
                                    <span>{item.type}</span>
                                    {hasDate && <span>{item.date}</span>}
                                </div>
                                <h4>{item.title}</h4>
                                <p>{item.summary}</p>
                            </article>
                        </li>;
                    })}
                    {!content.news.length && <li className="public-empty-copy">No public updates available.</li>}
                </ol>
            </section>

            <div className="public-editorial-secondary">
                <section id="transparency" className="public-record-section" aria-labelledby="public-documents-title">
                    <header className="public-editorial-section-header">
                        <h3 id="public-documents-title">Public Documents</h3>
                        <p>Prototype document and transparency information. No downloadable files are published here.</p>
                    </header>

                    <ul className="public-record-list">
                        {content.transparency.map(item => <li key={item.label}>
                            <article className="public-record">
                                <div className="public-record-meta"><span>{item.value}</span></div>
                                <h4>{item.label}</h4>
                                <p>{item.note}</p>
                            </article>
                        </li>)}
                        {!content.transparency.length && <li className="public-empty-copy">No public documents available.</li>}
                    </ul>
                </section>

                <section id="projects" className="public-record-section public-project-updates" aria-labelledby="public-projects-title">
                    <header className="public-editorial-section-header">
                        <h3 id="public-projects-title">Projects &amp; Programs</h3>
                        <p>Prototype municipal project and program updates without invented progress or status data.</p>
                    </header>

                    <ul className="public-record-list">
                        {content.projects.map(item => <li key={item.title}>
                            <article className="public-record">
                                <div className="public-record-meta"><span>{item.tag}</span></div>
                                <h4>{item.title}</h4>
                                <p>{item.summary}</p>
                            </article>
                        </li>)}
                        {!content.projects.length && <li className="public-empty-copy">No project updates available.</li>}
                    </ul>
                </section>
            </div>
        </div>
    </section>;
}
