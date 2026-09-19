import { ArrowRight, FileText, Megaphone, Search } from 'lucide-react';

const quickLinks = [
    {
        href: '#services',
        title: 'Find a municipal service',
        description: 'Office and service guidance',
        icon: Search,
    },
    {
        href: '#news',
        title: 'Read news & notices',
        description: 'Public announcements and advisories',
        icon: Megaphone,
    },
    {
        href: '#transparency',
        title: 'Open public documents',
        description: 'Transparency and published information',
        icon: FileText,
    },
] as const;

export default function PublicQuickAccess() {
    return <section className="public-quick-access" aria-labelledby="public-quick-access-title">
        <div className="public-quick-access-intro">
            <p className="public-quick-access-kicker">Quick access</p>
            <h2 id="public-quick-access-title">Common public tasks</h2>
        </div>

        <nav className="public-quick-access-links" aria-label="Quick access">
            {quickLinks.map(({ href, title, description, icon: Icon }) => <a
                key={href}
                href={href}
                className="public-quick-access-link"
            >
                <Icon size={18} aria-hidden="true" />
                <span>
                    <strong>{title}</strong>
                    <small>{description}</small>
                </span>
                <ArrowRight size={16} aria-hidden="true" />
            </a>)}
        </nav>
    </section>;
}
