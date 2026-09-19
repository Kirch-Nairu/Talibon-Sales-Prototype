import { ArrowRight } from 'lucide-react';
import type { PublicContent, ServiceItem } from './types';

const groupContent = {
    services: {
        title: 'Services & office guidance',
        description: 'Start with the kind of municipal help or office guidance you need.',
    },
    information: {
        title: 'Public information & records',
        description: 'Find notices, advisories, documents, and transparency information available in this prototype.',
    },
} as const;

function ServiceRow({ service }: { service: ServiceItem }) {
    return <li className="public-service-row">
        <div className="public-service-copy">
            <h4>{service.title}</h4>
            <p>{service.description}</p>
            <span className="public-service-meta">{service.meta}</span>
        </div>
        {service.href && service.action && <a href={service.href} className="public-service-action">
            {service.action}
            <ArrowRight size={15} aria-hidden="true" />
        </a>}
    </li>;
}

export default function PublicServices({ content }: { content: PublicContent }) {
    const grouped = {
        services: content.services.filter(service => service.group === 'services'),
        information: content.services.filter(service => service.group === 'information'),
    };

    return <section id="services" className="public-services" aria-labelledby="public-services-title">
        <div className="public-section-heading">
            <div>
                <h2 id="public-services-title">Municipal Services</h2>
                <p>Find service guidance, public information, and the appropriate next destination. This prototype does not provide online applications.</p>
            </div>
        </div>

        <div className="public-service-directory">
            {(Object.keys(groupContent) as Array<keyof typeof groupContent>).map(group => {
                const items = grouped[group];
                if (!items.length) return null;
                const copy = groupContent[group];

                return <section key={group} className="public-service-group" aria-labelledby={`public-service-group-${group}`}>
                    <header className="public-service-group-header">
                        <h3 id={`public-service-group-${group}`}>{copy.title}</h3>
                        <p>{copy.description}</p>
                    </header>
                    <ul className="public-service-list">
                        {items.map(service => <ServiceRow key={service.title} service={service} />)}
                    </ul>
                </section>;
            })}
        </div>
    </section>;
}
