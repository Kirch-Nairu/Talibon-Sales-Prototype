export type LegislativeRecordItem = {
    id: number;
    record_type: string;
    record_number: string;
    title: string;
    summary?: string;
    approved_at?: string;
    year: number;
    status: string;
    issuing_body: string;
};

export type LegislativeAgendaItem = {
    id: number;
    sequence_no: number;
    title: string;
    status: string;
    transaction?: { reference_no: string; title: string } | null;
    legislative_record?: { record_number: string; title: string } | null;
};

export type LegislativeSession = {
    id: number;
    session_code: string;
    session_type: string;
    title: string;
    scheduled_at: string;
    location?: string | null;
    status: string;
    agenda_items: LegislativeAgendaItem[];
};

export type LegislativeWork = {
    id: number;
    reference_no: string;
    title: string;
    status: string;
    priority: string;
    due_at?: string | null;
    current_department?: { short_name?: string | null; name: string } | null;
};
