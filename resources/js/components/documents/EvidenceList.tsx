import { Download, FileText } from 'lucide-react';

export type EvidenceItem = {
    publicId: string;
    name: string;
    mimeType?: string | null;
    sizeBytes?: number | null;
    classification: string;
    relationship: string;
    uploadedAt?: string | null;
    uploadedBy?: string | null;
    downloadUrl: string;
};

export type EvidencePayload = {
    record: EvidenceItem[];
    events: Record<string, EvidenceItem[]>;
};

type Props = {
    items: EvidenceItem[];
    emptyLabel?: string;
    compact?: boolean;
};

function sizeLabel(bytes?: number | null): string {
    if (!bytes || bytes < 1024) return `${bytes ?? 0} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function EvidenceList({ items, emptyLabel, compact = false }: Props) {
    if (items.length === 0) {
        return emptyLabel ? <div className="text-[10px] text-slate-400 sm:text-xs">{emptyLabel}</div> : null;
    }

    return (
        <div className={compact ? 'mt-2 space-y-1.5' : 'space-y-2'}>
            {items.map((item) => (
                <div key={`${item.publicId}-${item.relationship}`} className="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                    <div className="flex min-w-0 items-start gap-2.5">
                        <FileText size={15} className="mt-0.5 shrink-0 text-blue-700" />
                        <div className="min-w-0">
                            <div className="truncate text-[11px] font-semibold text-slate-800 sm:text-xs">{item.name}</div>
                            <div className="mt-0.5 text-[9px] text-slate-400 sm:text-[10px]">
                                {sizeLabel(item.sizeBytes)} · {item.relationship.replaceAll('_', ' ')} · {item.classification}
                                {item.uploadedBy ? ` · ${item.uploadedBy}` : ''}
                            </div>
                        </div>
                    </div>
                    <a
                        href={item.downloadUrl}
                        className="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-slate-700 hover:bg-slate-100 sm:text-xs"
                    >
                        <Download size={13} /> Download
                    </a>
                </div>
            ))}
        </div>
    );
}
