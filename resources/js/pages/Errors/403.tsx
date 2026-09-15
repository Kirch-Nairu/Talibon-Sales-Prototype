import { Link } from '@inertiajs/react';
import { ShieldX } from 'lucide-react';
import AppLayout from '../../layouts/AppLayout';

export default function Forbidden({ resource, message }: { resource: string; message: string }) {
    return <AppLayout title="Access Denied"><div className="mx-auto flex min-h-[65vh] max-w-2xl items-center justify-center"><div className="w-full rounded-3xl border border-rose-100 bg-white p-8 text-center shadow-sm md:p-12"><div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-50 text-rose-700"><ShieldX size={30} /></div><div className="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-rose-700">Access denied</div><h1 className="mt-2 text-3xl font-bold text-slate-950">{resource}</h1><p className="mx-auto mt-4 max-w-lg text-sm leading-6 text-slate-600">{message}</p><p className="mt-4 text-xs text-slate-400">This denied access attempt has been recorded in the security audit trail.</p><Link href="/dashboard" className="mt-7 inline-block rounded-xl bg-[#0b2852] px-5 py-3 text-sm font-semibold text-white">Return to dashboard</Link></div></div></AppLayout>;
}
