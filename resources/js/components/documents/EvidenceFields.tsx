import { Paperclip, X } from 'lucide-react';

type Props = {
    files: File[];
    onChange: (files: File[]) => void;
    errors?: Record<string, string | undefined>;
    disabled?: boolean;
    label?: string;
};

export default function EvidenceFields({
    files,
    onChange,
    errors = {},
    disabled = false,
    label = 'Supporting evidence',
}: Props) {
    const error = errors.evidence
        || Object.entries(errors).find(([key]) => key.startsWith('evidence.'))?.[1];

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
            <div className="flex items-center gap-2 text-[11px] font-bold text-slate-800 sm:text-sm">
                <Paperclip size={15} /> {label}
            </div>
            <p className="mt-1 text-[10px] leading-4 text-slate-500 sm:text-xs">
                Optional. PDF, DOCX, JPEG, PNG, or WebP. Files are stored privately and remain subject to the parent record's authorization.
            </p>
            <input
                type="file"
                multiple
                disabled={disabled}
                accept=".pdf,.docx,.jpg,.jpeg,.png,.webp,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,image/webp"
                onChange={(event) => onChange(Array.from(event.target.files ?? []))}
                className="mt-3 block w-full text-[10px] text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-[10px] file:font-semibold file:text-slate-700 hover:file:bg-slate-200 sm:text-xs sm:file:text-xs"
            />
            {files.length > 0 && (
                <div className="mt-3 space-y-1.5">
                    {files.map((file, index) => (
                        <div key={`${file.name}-${file.size}-${index}`} className="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-2.5 py-2 text-[10px] text-slate-600 sm:text-xs">
                            <span className="min-w-0 truncate">{file.name}</span>
                            <button
                                type="button"
                                disabled={disabled}
                                onClick={() => onChange(files.filter((_, itemIndex) => itemIndex !== index))}
                                className="shrink-0 rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-700 disabled:opacity-50"
                                aria-label={`Remove ${file.name}`}
                            >
                                <X size={13} />
                            </button>
                        </div>
                    ))}
                </div>
            )}
            {error && <div className="mt-2 text-[10px] font-semibold text-rose-700 sm:text-xs">{error}</div>}
        </div>
    );
}
