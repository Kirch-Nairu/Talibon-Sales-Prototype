import { Component, type ReactNode } from 'react';

type Props = {
    children: ReactNode;
};

type State = {
    failed: boolean;
};

export default class AppErrorBoundary extends Component<Props, State> {
    state: State = { failed: false };

    static getDerivedStateFromError(): State {
        return { failed: true };
    }

    render() {
        if (!this.state.failed) {
            return this.props.children;
        }

        return (
            <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12 text-slate-950 dark:bg-slate-950 dark:text-slate-100">
                <section
                    role="alert"
                    aria-live="assertive"
                    className="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8"
                >
                    <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                        Application recovery
                    </p>
                    <h1 className="mt-2 text-2xl font-bold">Something went wrong</h1>
                    <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        We could not complete this page. You can retry the current page or return to the dashboard.
                    </p>
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
}
