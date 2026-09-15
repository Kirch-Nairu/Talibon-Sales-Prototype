import { Head } from '@inertiajs/react';
import { Info } from 'lucide-react';
import PublicFooter from '../../components/public/PublicFooter';
import PublicGlance from '../../components/public/PublicGlance';
import PublicHeader from '../../components/public/PublicHeader';
import PublicHero from '../../components/public/PublicHero';
import PublicServices from '../../components/public/PublicServices';
import PublicUpdates from '../../components/public/PublicUpdates';
import type { PublicContent } from '../../components/public/types';

type Props = { appName: string; authenticated: boolean; content: PublicContent };

export default function Home({ appName, authenticated, content }: Props) {
    return <>
        <Head title={`One Talibon — ${appName}`} />
        <div className="public-portal min-h-screen bg-[var(--municipal-canvas)] text-slate-900 dark:bg-[#0d1624] dark:text-slate-100">
            <a href="#public-content" className="sr-only z-[80] rounded bg-white p-3 text-blue-900 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
            <PublicHeader authenticated={authenticated} />
            <main id="public-content" tabIndex={-1}>
                <div className="public-content">
                    <div className="public-disclosure">
                        <Info size={14} className="mt-0.5 shrink-0" aria-hidden="true" />
                        <p>Prototype preview — sample public content is shown for evaluation and does not represent official municipal reporting.</p>
                    </div>
                    <div className="public-primary">
                        <PublicHero content={content} authenticated={authenticated} />
                        <PublicServices content={content} />
                        <div className="public-information-zone">
                            <PublicGlance content={content} />
                            <PublicUpdates content={content} />
                        </div>
                    </div>
                </div>
            </main>
            <PublicFooter content={content} authenticated={authenticated} />
        </div>
    </>;
}
