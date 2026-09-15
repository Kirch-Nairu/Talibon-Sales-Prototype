// Presentation only. Counts, authorization and destination links come from the server.
export function metricPresentation(label: string) {
    if (/overdue|denied|failed/i.test(label)) return 'text-[#c83d4e] dark:text-[#f29ca7]';
    if (/completed|approved|enrolled|configured/i.test(label)) return 'text-[#2f7d45] dark:text-[#8ec9a0]';
    if (/pending|unassigned|action|due|attention|returned/i.test(label)) return 'text-[#996410] dark:text-[#e5b63a]';
    return 'text-[#0b2852] dark:text-[#eef4fa]';
}
