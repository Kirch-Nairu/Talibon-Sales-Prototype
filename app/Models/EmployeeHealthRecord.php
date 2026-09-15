<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeHealthRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'record_type',
        'title',
        'issued_at',
        'valid_until',
        'status',
        'summary',
        'restriction_notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'valid_until' => 'date',
            'summary' => 'encrypted',
            'restriction_notes' => 'encrypted',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}
