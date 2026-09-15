<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegislativeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_type', 'record_number', 'title', 'summary', 'approved_at', 'year',
        'status', 'issuing_body', 'keywords', 'source_file_name', 'source_path',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'date', 'year' => 'integer'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
