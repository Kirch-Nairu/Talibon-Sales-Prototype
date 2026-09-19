// Presentation only. Counts, authorization and destination links come from the server.
export function metricPresentation(label: string) {
    if (/overdue|denied|failed/i.test(label)) return 'employee-tone-danger';
    if (/completed|approved|enrolled|configured/i.test(label)) return 'employee-tone-success';
    if (/pending|unassigned|action|due|attention|returned/i.test(label)) return 'employee-tone-warning';
    return 'employee-tone-neutral';
}
