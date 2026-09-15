import { ArrowRight, Building2, FileCheck2, FileText, Megaphone, ShieldCheck, Users } from 'lucide-react';
import type { PublicContent } from './types';

const serviceIcons = [FileCheck2, Users, FileText, Megaphone, Building2, ShieldCheck];

export default function PublicServices({ content }: { content: PublicContent }) {
    return <section id="services" className="public-services" aria-labelledby="public-services-title">
        <div className="public-section-heading">
            <div>
                <h2 id="public-services-title">Municipal Services</h2>
                <p>Find the municipal office or information area related to common public concerns.</p>
            </div>
            <a href="#contact" className="public-section-link">Service enquiries <ArrowRight size={15} aria-hidden="true" /></a>
        </div>

        <div className="public-service-grid">
            {content.services.map((service, index) => {
                const Icon = serviceIcons[index % serviceIcons.length];
                return <article key={service.title} className="public-service-tile">
                    <Icon size={22} aria-hidden="true" />
                    <div className="min-w-0">
                        <h3>{service.title}</h3>
                        <p>{service.description}</p>
                        <span>{service.status}</span>
                    </div>
                </article>;
            })}
        </div>
    </section>;
}
