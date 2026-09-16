import { router } from '@inertiajs/react';
import {
    ArrowLeft,
    BriefcaseBusiness,
    Building2,
    Landmark,
    Settings2,
    UserRound,
    UsersRound,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import PersonaCard from './PersonaCard';
import type { ShowcasePersona } from './types';

type Props = {
    open: boolean;
    personas: ShowcasePersona[];
    onClose: () => void;
};

export default function WorkspaceDialog({ open, personas, onClose }: Props) {
    const [step, setStep] = useState<'roles' | 'departments'>('roles');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const closeButtonRef = useRef<HTMLButtonElement>(null);

    const byKey = useMemo(
        () => new Map(personas.map((persona) => [persona.key, persona])),
        [personas],
    );

    useEffect(() => {
        if (!open) return;

        setStep('roles');
        setError(null);
        window.setTimeout(() => closeButtonRef.current?.focus(), 0);

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && !processing) onClose();
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [open, onClose, processing]);

    if (!open) return null;

    const enter = (persona: ShowcasePersona | undefined) => {
        if (!persona || processing) return;

        setError(null);
        router.post(
            '/showcase/session',
            { persona: persona.key },
            {
                preserveScroll: false,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => {
                    const message = errors.persona;
                    setError(typeof message === 'string' ? message : 'Workspace access could not be completed.');
                },
            },
        );
    };

    const executive = byKey.get('executive');
    const employee = byKey.get('employee');
    const hr = byKey.get('hr');
    const legislative = byKey.get('legislative');
    const systemAdmin = byKey.get('system_admin');
    const engineering = byKey.get('engineering_head');
    const budget = byKey.get('budget_head');

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/45 p-0 sm:items-center sm:p-5" onMouseDown={(event) => {
            if (event.target === event.currentTarget && !processing) onClose();
        }}>
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="workspace-dialog-title"
                aria-describedby="workspace-dialog-description"
                className="max-h-[92dvh] w-full overflow-y-auto rounded-t-2xl border border-slate-200 bg-slate-50 shadow-2xl sm:max-w-3xl sm:rounded-2xl dark:border-slate-700 dark:bg-slate-950"
            >
                <div className="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-6 dark:border-slate-800 dark:bg-slate-950">
                    <div className="min-w-0">
                        {step === 'departments' && (
                            <button
                                type="button"
                                onClick={() => setStep('roles')}
                                disabled={processing}
                                className="mb-2 inline-flex min-h-9 items-center gap-2 rounded-lg px-2 text-sm font-medium text-slate-600 hover:bg-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-700 disabled:opacity-60 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                <ArrowLeft size={16} aria-hidden="true" /> Back
                            </button>
                        )}
                        <h2 id="workspace-dialog-title" className="text-xl font-bold text-slate-950 dark:text-white">
                            {step === 'roles' ? 'Choose your workspace' : 'Choose office context'}
                        </h2>
                        <p id="workspace-dialog-description" className="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">
                            {step === 'roles'
                                ? 'Select the municipal role you want to enter.'
                                : 'Select the department head office to open.'}
                        </p>
                    </div>
                    <button
                        ref={closeButtonRef}
                        type="button"
                        onClick={onClose}
                        disabled={processing}
                        aria-label="Close workspace selector"
                        className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-700 disabled:opacity-60 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                    >
                        <X size={19} aria-hidden="true" />
                    </button>
                </div>

                <div className="space-y-3 p-5 sm:p-6" aria-busy={processing}>
                    {error && (
                        <div role="alert" className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                            {error}
                        </div>
                    )}

                    {step === 'roles' ? (
                        <>
                            <PersonaCard
                                label="Municipal Executive"
                                description="Municipality-wide oversight and decisions"
                                context={executive?.office}
                                icon={Landmark}
                                onClick={() => enter(executive)}
                                disabled={processing || !executive}
                            />
                            <PersonaCard
                                label="Department Head"
                                description="Department work, programs and office coordination"
                                context="Choose Engineering or Budget"
                                icon={Building2}
                                onClick={() => setStep('departments')}
                                disabled={processing || (!engineering && !budget)}
                            />
                            <PersonaCard
                                label="Employee"
                                description="Assigned work, records and daily office coordination"
                                context={employee?.office}
                                icon={UserRound}
                                onClick={() => enter(employee)}
                                disabled={processing || !employee}
                            />
                            <PersonaCard
                                label="Human Resources"
                                description="Workforce coordination and personnel operations"
                                context={hr?.office}
                                icon={UsersRound}
                                onClick={() => enter(hr)}
                                disabled={processing || !hr}
                            />
                            <PersonaCard
                                label="Legislative Office"
                                description="Legislative records, references and office work"
                                context={legislative?.office}
                                icon={BriefcaseBusiness}
                                onClick={() => enter(legislative)}
                                disabled={processing || !legislative}
                            />
                            <PersonaCard
                                label="System Administration"
                                description="Workspace administration and municipal systems"
                                context={systemAdmin?.office}
                                icon={Settings2}
                                onClick={() => enter(systemAdmin)}
                                disabled={processing || !systemAdmin}
                            />
                        </>
                    ) : (
                        <>
                            {engineering && (
                                <PersonaCard
                                    label={engineering.office}
                                    description={engineering.position}
                                    icon={Building2}
                                    onClick={() => enter(engineering)}
                                    disabled={processing}
                                />
                            )}
                            {budget && (
                                <PersonaCard
                                    label={budget.office}
                                    description={budget.position}
                                    icon={Building2}
                                    onClick={() => enter(budget)}
                                    disabled={processing}
                                />
                            )}
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
