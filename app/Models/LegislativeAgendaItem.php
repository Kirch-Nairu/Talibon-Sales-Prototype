<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegislativeAgendaItem extends Model
{
    protected $fillable = ['legislative_session_id', 'sequence_no', 'title', 'description', 'transaction_id', 'legislative_record_id', 'status', 'disposition'];
    public function session(): BelongsTo { return $this->belongsTo(LegislativeSession::class, 'legislative_session_id'); }
    public function transaction(): BelongsTo { return $this->belongsTo(WorkflowTransaction::class, 'transaction_id'); }
    public function legislativeRecord(): BelongsTo { return $this->belongsTo(LegislativeRecord::class); }
}
