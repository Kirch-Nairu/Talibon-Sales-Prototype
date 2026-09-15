import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Building2, CheckCircle2, LockKeyhole, ShieldCheck } from 'lucide-react';
import type { FormEvent } from 'react';

export default function Login() {
    const form = useForm({ email: '', password: '', remember: false });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/login');
    }

    return (
        <>
            <Head title="Employee Portal" />
            <div className="min-h-screen bg-slate-100 lg:grid lg:grid-cols-[0.9fr_1.1fr]">
                <section className="hidden bg-[#08264d] p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
                    <Link href="/" className="inline-flex w-fit items-center gap-2 text-sm text-blue-100 transition hover:text-white">
                        <ArrowLeft size={16} /> Back to One Talibon
                    </Link>

                    <div className="max-w-xl">
                        <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm text-blue-100">
                            <ShieldCheck size={17} /> Secure municipal employee access
                        </div>
                        <div className="flex items-center gap-3">
                            <Building2 size={34} />
                            <div>
                                <div className="text-xs font-semibold uppercase tracking-[0.24em] text-blue-200">Municipality of Talibon</div>
                                <div className="text-3xl font-black tracking-tight">ONE TALIBON</div>
                            </div>
                        </div>
                        <h1 className="mt-8 text-4xl font-bold leading-tight xl:text-5xl">Employee Portal</h1>
                        <p className="mt-5 text-base leading-7 text-blue-100 xl:text-lg">Secure access to the municipality's internal coordination, routing, records, and operational workspaces.</p>
                    </div>

                    <div className="text-sm leading-6 text-blue-200">Employee operations remain separate from the public One Talibon information portal.</div>
                </section>

                <section className="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:min-h-0 lg:p-10">
                    <div className="w-full max-w-md">
                        <Link href="/" className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-800 lg:hidden">
                            <ArrowLeft size={16} /> One Talibon
                        </Link>

                        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-900">
                                <LockKeyhole size={22} />
                            </div>
                            <div className="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-blue-700">One Talibon</div>
                            <h2 className="mt-2 text-2xl font-bold text-slate-950">Employee Portal</h2>
                            <p className="mt-2 text-sm leading-6 text-slate-500">Sign in using the credentials privately issued for this prototype environment.</p>

                            <form onSubmit={submit} className="mt-7 space-y-4">
                                <label className="block">
                                    <span className="mb-1.5 block text-sm font-medium text-slate-700">Email</span>
                                    <input
                                        autoComplete="username"
                                        autoFocus
                                        value={form.data.email}
                                        onChange={(event) => form.setData('email', event.target.value)}
                                        type="email"
                                        required
                                        className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-700 focus:ring-4 focus:ring-blue-100"
                                    />
                                    {form.errors.email && <span className="mt-1.5 block text-sm text-red-600">{form.errors.email}</span>}
                                </label>

                                <label className="block">
                                    <span className="mb-1.5 block text-sm font-medium text-slate-700">Password</span>
                                    <input
                                        autoComplete="current-password"
                                        value={form.data.password}
                                        onChange={(event) => form.setData('password', event.target.value)}
                                        type="password"
                                        required
                                        className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-700 focus:ring-4 focus:ring-blue-100"
                                    />
                                    {form.errors.password && <span className="mt-1.5 block text-sm text-red-600">{form.errors.password}</span>}
                                </label>

                                <label className="flex items-center gap-2 text-sm text-slate-600">
                                    <input
                                        type="checkbox"
                                        checked={form.data.remember}
                                        onChange={(event) => form.setData('remember', event.target.checked)}
                                        className="rounded border-slate-300 text-blue-800 focus:ring-blue-700"
                                    />
                                    Remember me on this device
                                </label>

                                <button disabled={form.processing} className="w-full rounded-xl bg-[#0b2852] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#10396f] disabled:opacity-60">
                                    {form.processing ? 'Signing in…' : 'Sign In'}
                                </button>
                            </form>

                            <div className="my-6 flex items-center gap-3 text-xs uppercase tracking-wider text-slate-400">
                                <span className="h-px flex-1 bg-slate-200" /> Future identity integration <span className="h-px flex-1 bg-slate-200" />
                            </div>

                            <button type="button" disabled className="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-500">
                                Continue with Google
                            </button>
                            <p className="mt-2 text-center text-xs leading-5 text-slate-500">Google account integration will be enabled after official LGU identity setup.</p>

                            <div className="mt-6 rounded-2xl bg-blue-50 p-4">
                                <div className="flex items-start gap-3">
                                    <CheckCircle2 className="mt-0.5 shrink-0 text-blue-800" size={18} />
                                    <div>
                                        <div className="text-sm font-semibold text-slate-900">First time accessing the Portal?</div>
                                        <p className="mt-1 text-xs leading-5 text-slate-600">Account activation is not enabled in this prototype.</p>
                                        <Link href="/activate-account" className="mt-2 inline-block text-sm font-semibold text-blue-800 hover:underline">Activate Employee Account</Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}
