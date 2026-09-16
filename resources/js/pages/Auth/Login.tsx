import { Head } from '@inertiajs/react';
import { ArrowRight, Building2 } from 'lucide-react';
import { useState } from 'react';
import AppearanceControl from '../../components/AppearanceControl';
import WorkspaceDialog from '../../components/showcase/WorkspaceDialog';
import type { ShowcaseEntryData } from '../../components/showcase/types';

type Props = {
    showcase: ShowcaseEntryData;
};

export default function Login({ showcase }: Props) {
    const [selectorOpen, setSelectorOpen] = useState(false);

    return (
        <>
            <Head title="Municipal Workspace" />
            <main className="min-h-screen bg-slate-100 text-slate-950 dark:bg-slate-950 dark:text-white">
                <div className="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-5 py-5 sm:px-8 sm:py-7 lg:px-10">
                    <header className="flex items-center justify-between gap-4">
                        <div className="flex min-w-0 items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0b2852] text-white">
                                <Building2 size={20} aria-hidden="true" />
                            </div>
                            <div className="min-w-0">
                                <div className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Municipality of Talibon</div>
                                <div className="text-base font-bold tracking-tight">ONE TALIBON</div>
                            </div>
                        </div>
                        <div className="w-[210px] max-w-[48vw]">
                            <AppearanceControl publicSurface />
                        </div>
                    </header>

                    <section className="flex flex-1 items-center py-12 sm:py-16 lg:py-20">
                        <div className="grid w-full gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(360px,0.72fr)] lg:items-center lg:gap-16">
                            <div className="max-w-2xl">
                                <p className="text-sm font-semibold uppercase tracking-[0.18em] text-blue-800 dark:text-blue-300">One Talibon</p>
                                <h1 className="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">Municipal Workspace</h1>
                                <p className="mt-5 max-w-xl text-base leading-7 text-slate-600 sm:text-lg dark:text-slate-300">
                                    Municipal coordination, planning, records, projects and office operations in one workspace.
                                </p>
                            </div>

                            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900">
                                <div className="text-sm font-semibold text-slate-950 dark:text-white">Open a municipal workspace</div>
                                <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    Choose the role and office context needed for this session.
                                </p>
                                <button
                                    type="button"
                                    onClick={() => setSelectorOpen(true)}
                                    disabled={!showcase.enabled || showcase.personas.length === 0}
                                    className="mt-6 flex min-h-12 w-full items-center justify-between rounded-xl bg-[#0b2852] px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#10396f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-700 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-400 dark:focus-visible:ring-offset-slate-900"
                                >
                                    <span>{showcase.enabled ? 'Enter Workspace' : 'Workspace unavailable'}</span>
                                    {showcase.enabled && <ArrowRight size={18} aria-hidden="true" />}
                                </button>
                            </div>
                        </div>
                    </section>

                    <footer className="border-t border-slate-200 py-4 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                        Municipality of Talibon municipal operations workspace
                    </footer>
                </div>
            </main>

            <WorkspaceDialog
                open={selectorOpen}
                personas={showcase.personas}
                onClose={() => setSelectorOpen(false)}
            />
        </>
    );
}
