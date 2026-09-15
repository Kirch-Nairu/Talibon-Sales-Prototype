import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, LogOut, ShieldCheck } from 'lucide-react';
import type { FormEvent } from 'react';

export default function MfaChallenge() {
    const totp = useForm({ code: '', recovery_code: '' });
    const recovery = useForm({ code: '', recovery_code: '' });

    function submitTotp(event: FormEvent) {
        event.preventDefault();
        totp.post('/security/mfa/challenge');
    }

    function submitRecovery(event: FormEvent) {
        event.preventDefault();
        recovery.post('/security/mfa/challenge');
    }

    return (
        <>
            <Head title="MFA challenge" />
            <main className="min-h-screen bg-[#eef3f9] px-4 py-8 sm:px-6">
                <div className="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-900/5 sm:p-8">
                    <div className="flex items-start gap-3"><div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-800"><ShieldCheck size={21} /></div><div><div className="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Privileged identity assurance</div><h1 className="mt-1 text-2xl font-bold text-slate-950">Verify your second factor</h1><p className="mt-2 text-sm leading-6 text-slate-600">Password authentication is complete. Municipal application access remains blocked until MFA succeeds.</p></div></div>

                    <form onSubmit={submitTotp} className="mt-7 space-y-3">
                        <label className="block"><span className="mb-1.5 block text-sm font-medium text-slate-700">Authenticator code</span><input autoFocus inputMode="numeric" autoComplete="one-time-code" maxLength={6} value={totp.data.code} onChange={(event) => totp.setData('code', event.target.value.replace(/\D/g, '').slice(0, 6))} className="w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-lg tracking-[0.25em] outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100" />{totp.errors.code && <span className="mt-1 block text-sm text-red-600">{totp.errors.code}</span>}</label>
                        <button disabled={totp.processing} className="w-full rounded-xl bg-[#0b2852] px-4 py-3 text-sm font-semibold text-white hover:bg-[#10366c] disabled:opacity-60">{totp.processing ? 'Verifying…' : 'Verify and continue'}</button>
                    </form>

                    <div className="my-6 flex items-center gap-3 text-xs uppercase tracking-widest text-slate-400"><span className="h-px flex-1 bg-slate-200" />Recovery<span className="h-px flex-1 bg-slate-200" /></div>

                    <form onSubmit={submitRecovery} className="space-y-3">
                        <label className="block"><span className="mb-1.5 flex items-center gap-2 text-sm font-medium text-slate-700"><KeyRound size={15} /> One-time recovery code</span><input value={recovery.data.recovery_code} onChange={(event) => recovery.setData('recovery_code', event.target.value)} autoComplete="off" className="w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-sm uppercase outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100" />{recovery.errors.recovery_code && <span className="mt-1 block text-sm text-red-600">{recovery.errors.recovery_code}</span>}{recovery.errors.code && <span className="mt-1 block text-sm text-red-600">{recovery.errors.code}</span>}</label>
                        <button disabled={recovery.processing} className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50 disabled:opacity-60">Use recovery code</button>
                    </form>

                    <button type="button" onClick={() => router.post('/logout')} className="mt-5 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900"><LogOut size={16} /> Sign out</button>
                </div>
            </main>
        </>
    );
}
