<?php

namespace App\Services;

use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\DocumentLink;
use App\Models\TransactionEvent;
use App\Models\TravelOrder;
use App\Models\TravelOrderEvent;
use App\Models\WorkflowTransaction;
use Illuminate\Database\Eloquent\Model;

final class DocumentEvidenceQuery
{
    /** @return array{record:array<int,array<string,mixed>>,events:array<string,array<int,array<string,mixed>>>} */
    public function forTransaction(WorkflowTransaction $transaction): array
    {
        $eventIds = $transaction->relationLoaded('events')
            ? $transaction->events->pluck('id')->map(fn ($id): int => (int) $id)->all()
            : $transaction->events()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return $this->forParentAndEvents($transaction, (new TransactionEvent())->getMorphClass(), $eventIds);
    }

    /** @return array{record:array<int,array<string,mixed>>,events:array<string,array<int,array<string,mixed>>>} */
    public function forCorrespondence(CorrespondenceRecord $record): array
    {
        $eventIds = $record->events()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return $this->forParentAndEvents($record, (new CorrespondenceEvent())->getMorphClass(), $eventIds);
    }

    /** @return array{record:array<int,array<string,mixed>>,events:array<string,array<int,array<string,mixed>>>} */
    public function forTravelOrder(TravelOrder $travelOrder): array
    {
        $eventIds = $travelOrder->relationLoaded('events')
            ? $travelOrder->events->pluck('id')->map(fn ($id): int => (int) $id)->all()
            : $travelOrder->events()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return $this->forParentAndEvents($travelOrder, (new TravelOrderEvent())->getMorphClass(), $eventIds);
    }

    /**
     * @param array<int, int> $eventIds
     * @return array{record:array<int,array<string,mixed>>,events:array<string,array<int,array<string,mixed>>>}
     */
    private function forParentAndEvents(Model $parent, string $eventType, array $eventIds): array
    {
        $parentType = $parent->getMorphClass();
        $links = DocumentLink::query()
            ->with(['document.uploader:id,name'])
            ->where(function ($query) use ($parent, $parentType, $eventIds, $eventType): void {
                $query->where(function ($direct) use ($parent, $parentType): void {
                    $direct->where('linkable_type', $parentType)
                        ->where('linkable_id', $parent->getKey());
                });

                if ($eventIds !== []) {
                    $query->orWhere(function ($events) use ($eventIds, $eventType): void {
                        $events->where('linkable_type', $eventType)
                            ->whereIn('linkable_id', $eventIds);
                    });
                }
            })
            ->orderBy('id')
            ->get();

        $record = [];
        $events = [];

        foreach ($links as $link) {
            if (! $link->document) {
                continue;
            }

            $item = $this->item($link);
            if ($link->linkable_type === $parentType && (int) $link->linkable_id === (int) $parent->getKey()) {
                $record[] = $item;
                continue;
            }

            if ($link->linkable_type === $eventType) {
                $events[(string) $link->linkable_id][] = $item;
            }
        }

        return ['record' => $record, 'events' => $events];
    }

    /** @return array<string, mixed> */
    private function item(DocumentLink $link): array
    {
        $document = $link->document;

        return [
            'publicId' => $document->public_id,
            'name' => $document->original_name ?: $document->title,
            'mimeType' => $document->mime_type,
            'sizeBytes' => $document->size_bytes,
            'classification' => $document->classification,
            'relationship' => $link->relationship,
            'uploadedAt' => $document->created_at?->toISOString(),
            'uploadedBy' => $document->uploader?->name,
            'downloadUrl' => route('documents.download', $document, false),
        ];
    }
}
