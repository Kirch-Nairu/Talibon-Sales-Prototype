import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, LogOut, ShieldCheck } from 'lucide-react';
import type { FormEvent } from 'react';

type Props = {
    secret: string;
    provisioningUri: string;
    issuer: string;
};

export default function MfaEnrollment({ secret, provisioningUri, issuer }: Props) {
    const form = useForm({ code: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/security/mfa/enroll');
    }

    return (
        <>
            <Head title="Set up MFA" />
            <main className="min-h-screen bg-[#eef3f9] px-4 py-8 sm:px-6">
                <div className="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-900/5 sm:p-8">
                    <div className="flex items-start gap-3">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-800"><ShieldCheck size={21} /></div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Privileged identity assurance</div>
                            <h1 className="mt-1 text-2xl font-bold text-slate-950">Set up multi-factor authentication</h1>
                            <p className="mt-2 text-sm leading-6 text-slate-600">Municipal application access remains restricted until this enrollment is confirmed.</p>
                        </div>
                    </div>

                    <section className="mt-7 space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                        <div><div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Authenticator issuer</div><div className="mt-1 text-sm font-semibold text-slate-900">{issuer}</div></div>
                        <div><div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Setup secret</div><code className="mt-1 block break-all rounded-lg bg-white px-3 py-2 font-mono text-sm font-semibold text-slate-900">{secret}</code></div>
                        <a href={provisioningUri} className="inline-flex items-center gap-2 text-sm font-semibold text-blue-800 hover:underline"><KeyRound size={16} /> Open authenticator setup URI</a>
                        <p className="text-xs leading-5 text-slate-500">Add the account to a TOTP-compatible authenticator, then enter the current six-digit code below. The setup secret is shown only in this enrollment flow.</p>
                    </section>

                    <form onSubmit={submit} className="mt-6 space-y-3">
                        <label className="block"><span className="mb-1.5 block text-sm font-medium text-slate-700">Six-digit verification code</span><input autoFocus inputMode="numeric" autoComplete="one-time-code" maxLength={6} value={form.data.code} onChange={(event) => form.setData('code', event.target.value.replace(/\D/g, '').slice(0, 6))} className="w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-lg tracking-[0.25em] outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100" />{form.errors.code && <span className="mt-1 block text-sm text-red-600">{form.errors.code}</span>}</label>
                        <button disabled={form.processing} className="w-full rounded-xl bg-[#0b2852] px-4 py-3 text-sm font-semibold text-white hover:bg-[#10366c] disabled:opacity-60">{form.processing ? 'Confirming…' : 'Confirm MFA enrollment'}</button>
                    </form>

                    <button type="button" onClick={() => router.post('/logout')} className="mt-4 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900"><LogOut size={16} /> Sign out</button>
                </div>
            </main>
        </>
    );
}
