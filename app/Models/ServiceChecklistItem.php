<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceChecklistItem extends Model
{
    protected $fillable = [
        'checklist_id', 'template_item_id', 'title', 'description',
        'verification_type', 'is_required', 'status',
        'completed_by', 'completed_at', 'verified_by', 'verified_at',
        'approved_by', 'approved_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(ServiceChecklist::class, 'checklist_id');
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplateItem::class, 'template_item_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class, 'checklist_item_id');
    }
}
