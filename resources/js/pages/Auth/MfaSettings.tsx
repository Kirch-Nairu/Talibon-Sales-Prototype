import { Head, Link, router } from '@inertiajs/react';
import { KeyRound, RotateCcw, ShieldCheck, ShieldOff } from 'lucide-react';

type Props = {
    configured: boolean;
    confirmedAt: string | null;
    recoveryGeneratedAt: string | null;
};

export default function MfaSettings({ configured, confirmedAt, recoveryGeneratedAt }: Props) {
    return (
        <>
            <Head title="MFA security" />
            <main className="min-h-screen bg-[#eef3f9] px-4 py-8 sm:px-6">
                <div className="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-900/5 sm:p-8">
                    <div className="flex items-start gap-3"><div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-800"><ShieldCheck size={21} /></div><div><div className="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Identity security</div><h1 className="mt-1 text-2xl font-bold text-slate-950">Multi-factor authentication</h1><p className="mt-2 text-sm leading-6 text-slate-600">Privileged sessions require MFA assurance before authorization and domain actions are reached.</p></div></div>

                    <dl className="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm sm:grid-cols-2"><div><dt className="text-slate-500">Status</dt><dd className="mt-1 font-semibold text-slate-950">{configured ? 'Configured' : 'Not configured'}</dd></div><div><dt className="text-slate-500">Confirmed</dt><dd className="mt-1 font-semibold text-slate-950">{confirmedAt ?? 'Not confirmed'}</dd></div><div className="sm:col-span-2"><dt className="text-slate-500">Recovery codes last generated</dt><dd className="mt-1 font-semibold text-slate-950">{recoveryGeneratedAt ?? 'Not generated'}</dd></div></dl>

                    <div className="mt-6 grid gap-3 sm:grid-cols-2">
                        <button type="button" onClick={() => router.post('/security/mfa/recovery-codes')} className="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"><KeyRound size={16} /> Regenerate recovery codes</button>
                        <button type="button" onClick={() => router.post('/security/mfa/reset')} className="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 hover:bg-amber-100"><RotateCcw size={16} /> Reset MFA enrollment</button>
                        <button type="button" onClick={() => router.delete('/security/mfa')} className="inline-flex items-center justify-center gap-2 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 hover:bg-red-100"><ShieldOff size={16} /> Disable MFA</button>
                        <Link href="/dashboard" className="rounded-xl bg-[#0b2852] px-4 py-3 text-center text-sm font-semibold text-white hover:bg-[#10366c]">Back to dashboard</Link>
                    </div>
                </div>
            </main>
        </>
    );
}
