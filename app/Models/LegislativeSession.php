<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegislativeSession extends Model
{
    protected $fillable = ['session_code', 'session_type', 'title', 'scheduled_at', 'location', 'status', 'notes', 'created_by_user_id'];
    protected function casts(): array { return ['scheduled_at' => 'datetime']; }
    public function agendaItems(): HasMany { return $this->hasMany(LegislativeAgendaItem::class)->orderBy('sequence_no'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}
