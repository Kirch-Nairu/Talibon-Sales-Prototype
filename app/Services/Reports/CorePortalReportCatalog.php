<?php

namespace App\Services\Reports;

final class CorePortalReportCatalog
{
    public const DEFAULT = 'office-workload';

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return [
            'office-workload' => $this->definition(
                'Office Workload',
                'Authorized active, overdue, completed, assigned, and unassigned work by office.',
                ['date_from', 'date_to', 'office', 'priority', 'transaction_type'],
                ['office' => 'Office', 'active' => 'Active', 'overdue' => 'Overdue', 'completed' => 'Completed', 'assigned' => 'Assigned active', 'unassigned' => 'Unassigned active'],
                'aggregate',
            ),
            'transaction-aging' => $this->definition(
                'Transaction Aging',
                'Current transaction age, due state, accountability, and priority.',
                ['date_from', 'date_to', 'office', 'status', 'priority', 'transaction_type'],
                ['reference' => 'Reference', 'title' => 'Title', 'type' => 'Type', 'originOffice' => 'Origin office', 'currentOffice' => 'Current office', 'assignee' => 'Assignee', 'status' => 'Status', 'priority' => 'Priority', 'receivedAt' => 'Received', 'dueAt' => 'Due', 'age' => 'Age', 'dueState' => 'Due state', 'overdueBy' => 'Overdue by'],
            ),
            'correspondence-status' => $this->definition(
                'Correspondence Status',
                'Authorized incoming-document lifecycle and current accountability.',
                ['date_from', 'date_to', 'office', 'lifecycle', 'classification'],
                ['municipalReference' => 'Municipal reference', 'externalReference' => 'External reference', 'subject' => 'Subject', 'sender' => 'Sender', 'organization' => 'Organization', 'receivedAt' => 'Received', 'lifecycle' => 'Lifecycle', 'classification' => 'Classification', 'receivingOffice' => 'Receiving office', 'accountableOffice' => 'Accountable office', 'assignee' => 'Assignee', 'lastMovementAt' => 'Last movement'],
            ),
            'document-movement' => $this->definition(
                'Document Movement / Routing',
                'Merged correspondence and workflow movement chronology with evidence indicators.',
                ['date_from', 'date_to', 'office', 'lifecycle', 'classification'],
                ['municipalReference' => 'Municipal reference', 'subject' => 'Subject', 'event' => 'Event', 'source' => 'Source', 'fromOffice' => 'From office', 'toOffice' => 'To office', 'actor' => 'Actor', 'remarks' => 'Remarks', 'occurredAt' => 'Timestamp', 'hasEvidence' => 'Evidence', 'accountableOffice' => 'Accountable office', 'assignee' => 'Assignee'],
            ),
            'completed-work' => $this->definition(
                'Completed Work',
                'Factual transaction completions within the selected period.',
                ['date_from', 'date_to', 'office', 'status', 'priority', 'transaction_type'],
                ['reference' => 'Reference', 'title' => 'Title', 'originOffice' => 'Origin office', 'finalOffice' => 'Final office', 'completedAt' => 'Completed', 'processingDuration' => 'Processing duration', 'finalStatus' => 'Final status'],
            ),
            'overdue-action-required' => $this->definition(
                'Overdue / Action Required',
                'Active transactions whose current due date has passed.',
                ['date_from', 'date_to', 'office', 'status', 'priority', 'transaction_type'],
                ['reference' => 'Reference', 'title' => 'Title', 'currentOffice' => 'Current office', 'assignee' => 'Assignee', 'dueAt' => 'Due', 'overdueBy' => 'Overdue by', 'status' => 'Status', 'priority' => 'Priority'],
            ),
        ];
    }

    public function supports(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /** @return array<string, mixed> */
    public function get(string $key): array
    {
        return $this->all()[$key];
    }

    /** @return array<int, array<string, mixed>> */
    public function forClient(): array
    {
        return collect($this->all())
            ->map(fn (array $definition, string $key): array => [
                'key' => $key,
                ...$definition,
                'columns' => collect($definition['columns'])
                    ->map(fn (string $label, string $column): array => ['key' => $column, 'label' => $label])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function definition(
        string $label,
        string $description,
        array $filters,
        array $columns,
        string $kind = 'rows',
    ): array {
        return compact('label', 'description', 'filters', 'columns', 'kind');
    }
}
