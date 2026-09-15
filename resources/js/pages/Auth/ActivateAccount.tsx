import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Building2, CheckCircle2, LockKeyhole, ShieldCheck } from 'lucide-react';

const steps = [
    'An authorized administrator registers the employee account internally.',
    'The employee opens Activate Employee Account.',
    'The employee signs in with the authorized LGU Google account.',
    'The employee provides the assigned Employee ID.',
    'The system matches the pre-registered identity.',
    'Security and MFA setup is completed where required.',
    'The Employee Portal becomes active.',
];

export default function ActivateAccount() {
    return (
        <>
            <Head title="Activate Employee Account" />
            <div className="min-h-screen bg-slate-100 px-4 py-8 sm:px-6 lg:py-12">
                <div className="mx-auto max-w-4xl">
                    <div className="flex items-center justify-between gap-4">
                        <Link href="/" className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-900"><ArrowLeft size={16} /> One Talibon</Link>
                        <Link href="/login" className="text-sm font-semibold text-blue-900 hover:underline">Employee Login</Link>
                    </div>

                    <div className="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-900/5">
                        <div className="bg-[#0b2852] px-6 py-8 text-white sm:px-9">
                            <div className="flex items-center gap-3"><Building2 size={28} /><div><div className="text-xs font-bold uppercase tracking-[0.18em] text-blue-200">One Talibon</div><h1 className="mt-1 text-3xl font-black">Activate Employee Account</h1></div></div>
                            <p className="mt-4 max-w-2xl text-sm leading-6 text-blue-100">This page demonstrates the intended production activation flow. It does not perform account lookup, Google authentication, Employee ID validation, or any account mutation.</p>
                        </div>

                        <div className="p-6 sm:p-9">
                            <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">
                                Account activation is not enabled in this prototype.
                            </div>

                            <div className="mt-7 grid gap-3">
                                {steps.map((step, index) => (
                                    <div key={step} className="flex items-start gap-3 rounded-2xl border border-slate-200 p-4">
                                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 text-sm font-bold text-blue-900">{index + 1}</span>
                                        <p className="pt-1 text-sm leading-6 text-slate-700">{step}</p>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-7 grid gap-4 md:grid-cols-2">
                                <div className="rounded-2xl bg-slate-50 p-5">
                                    <LockKeyhole size={20} className="text-blue-900" />
                                    <h2 className="mt-3 font-bold text-slate-950">No public self-registration</h2>
                                    <p className="mt-2 text-sm leading-6 text-slate-600">Employees will claim only identities already registered by authorized municipal administration.</p>
                                </div>
                                <div className="rounded-2xl bg-slate-50 p-5">
                                    <ShieldCheck size={20} className="text-blue-900" />
                                    <h2 className="mt-3 font-bold text-slate-950">Production identity controls</h2>
                                    <p className="mt-2 text-sm leading-6 text-slate-600">Official Google identity integration, matching, activation tokens, and security controls remain future production work.</p>
                                </div>
                            </div>

                            <div className="mt-7 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                                <div className="inline-flex items-center gap-2 text-sm text-slate-600"><CheckCircle2 size={17} className="text-emerald-700" /> Existing Employee Portal authentication remains unchanged.</div>
                                <Link href="/login" className="rounded-xl bg-[#0b2852] px-5 py-3 text-center text-sm font-semibold text-white hover:bg-[#10396f]">Back to Employee Login</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
