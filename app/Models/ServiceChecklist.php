<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceChecklist extends Model
{
    protected $fillable = [
        'service_id', 'template_id', 'department_id', 'assigned_to',
        'status', 'completed_at', 'verified_at', 'approved_at',
        'verified_by', 'approved_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceChecklistItem::class, 'checklist_id')->orderBy('sort_order');
    }
}
