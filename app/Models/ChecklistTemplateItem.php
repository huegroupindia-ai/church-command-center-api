<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistTemplateItem extends Model
{
    protected $fillable = [
        'template_id', 'title', 'description', 'verification_type',
        'is_required', 'sort_order', 'estimated_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }
}
