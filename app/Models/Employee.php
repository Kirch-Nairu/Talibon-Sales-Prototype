<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_number', 'full_name', 'work_email', 'user_id', 'department_id',
        'supervisor_employee_id', 'position_title', 'employment_status', 'employment_type',
        'appointment_date', 'employment_start_date', 'contract_end_date', 'separation_date',
        'date_of_birth', 'personal_email', 'home_address', 'mobile_number',
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
        'gsis_number', 'philhealth_number', 'pagibig_number', 'tin_number', 'biometric_external_id',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date', 'employment_start_date' => 'date', 'contract_end_date' => 'date',
            'separation_date' => 'date', 'date_of_birth' => 'date', 'personal_email' => 'encrypted',
            'home_address' => 'encrypted', 'emergency_contact_name' => 'encrypted',
            'emergency_contact_phone' => 'encrypted', 'gsis_number' => 'encrypted',
            'philhealth_number' => 'encrypted', 'pagibig_number' => 'encrypted', 'tin_number' => 'encrypted',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function supervisor(): BelongsTo { return $this->belongsTo(self::class, 'supervisor_employee_id'); }
    public function directReports(): HasMany { return $this->hasMany(self::class, 'supervisor_employee_id'); }
    public function leaveCreditAccounts(): HasMany { return $this->hasMany(LeaveCreditAccount::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function attendanceLogs(): HasMany { return $this->hasMany(AttendanceLog::class); }
    public function dtrDailySummaries(): HasMany { return $this->hasMany(DtrDailySummary::class); }
    public function payrollEntries(): HasMany { return $this->hasMany(PayrollEntry::class); }
    public function onboardingCase(): HasOne { return $this->hasOne(OnboardingCase::class); }
    public function offboardingCases(): HasMany { return $this->hasMany(OffboardingCase::class); }
    public function movements(): HasMany { return $this->hasMany(EmployeeMovement::class); }
    public function assetAssignments(): HasMany { return $this->hasMany(AssetAssignment::class); }
    public function accountableAssets(): HasMany { return $this->hasMany(Asset::class, 'accountable_employee_id'); }
    public function performanceRecords(): HasMany { return $this->hasMany(PerformanceRecord::class); }
    public function developmentRecords(): HasMany { return $this->hasMany(EmployeeDevelopmentRecord::class); }
    public function healthRecords(): HasMany { return $this->hasMany(EmployeeHealthRecord::class); }
    public function healthAccessGrants(): HasMany { return $this->hasMany(EmployeeHealthAccessGrant::class); }
}
