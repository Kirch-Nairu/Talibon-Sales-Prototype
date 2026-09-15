type Props = {
    status: number;
    requestId: string;
};

const content: Record<number, { eyebrow: string; title: string; description: string }> = {
    403: {
        eyebrow: 'Access denied',
        title: 'You cannot open this page',
        description: 'Your account does not have permission to access this resource.',
    },
    404: {
        eyebrow: 'Page not found',
        title: 'We could not find that page',
        description: 'The address may be incorrect, or the page may no longer be available.',
    },
    500: {
        eyebrow: 'Application error',
        title: 'Something went wrong',
        description: 'We could not complete this page. Retry the request or return to the dashboard.',
    },
    503: {
        eyebrow: 'Service unavailable',
        title: 'The portal is temporarily unavailable',
        description: 'The service cannot complete this request right now. Please retry shortly.',
    },
};

export default function Status({ status, requestId }: Props) {
    const copy = content[status] ?? content[500];

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12 text-slate-950 dark:bg-slate-950 dark:text-slate-100">
            <section className="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                    {copy.eyebrow}
                </p>
                <h1 className="mt-2 text-2xl font-bold">{copy.title}</h1>
                <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{copy.description}</p>
                <div className="mt-5 rounded-xl bg-slate-100 px-4 py-3 dark:bg-slate-800">
                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Request ID
                    </div>
                    <code className="mt-1 block break-all text-xs text-slate-700 dark:text-slate-200">{requestId}</code>
                </div>
                <div className="mt-6 flex flex-wrap gap-3">
                    <button
                        type="button"
                        onClick={() => window.location.reload()}
                        className="rounded-xl bg-[#0b2852] px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        Retry
                    </button>
                    <a
                        href="/dashboard"
                        className="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:text-slate-100"
                    >
                        Return to dashboard
                    </a>
                </div>
            </section>
        </main>
    );
}
