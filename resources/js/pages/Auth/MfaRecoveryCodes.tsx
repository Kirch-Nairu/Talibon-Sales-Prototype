import { Head, Link, usePage } from '@inertiajs/react';
import { KeyRound, ShieldCheck } from 'lucide-react';

type Props = {
    continueUrl: string;
};

type RecoveryFlash = {
    mfaRecoveryCodes?: string[];
};

export default function MfaRecoveryCodes({ continueUrl }: Props) {
    const flash = usePage().flash as RecoveryFlash | undefined;
    const codes = Array.isArray(flash?.mfaRecoveryCodes) ? flash.mfaRecoveryCodes : [];
    const block = codes.join('\n');

    return (
        <>
            <Head title="MFA recovery codes" />
            <main className="min-h-screen bg-[#eef3f9] px-4 py-8 sm:px-6">
                <div className="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-900/5 sm:p-8">
                    <div className="flex items-start gap-3"><div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><ShieldCheck size={21} /></div><div><div className="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">MFA active</div><h1 className="mt-1 text-2xl font-bold text-slate-950">Store your recovery codes</h1><p className="mt-2 text-sm leading-6 text-slate-600">Each code works once. The portal stores only one-way hashes and will not show this set again.</p></div></div>

                    <pre className="mt-6 whitespace-pre-wrap rounded-xl border border-slate-200 bg-slate-950 p-4 font-mono text-sm leading-7 text-slate-100">{block}</pre>
                    <button type="button" onClick={() => navigator.clipboard?.writeText(block)} className="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><KeyRound size={15} /> Copy codes</button>

                    <div className="mt-6 grid gap-3 sm:grid-cols-2"><Link href={continueUrl} className="rounded-xl bg-[#0b2852] px-4 py-3 text-center text-sm font-semibold text-white hover:bg-[#10366c]">Continue to portal</Link><Link href="/security/mfa" className="rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">MFA settings</Link></div>
                </div>
            </main>
        </>
    );
}
