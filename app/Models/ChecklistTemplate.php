<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    protected $fillable = [
        'church_id', 'department_id', 'name', 'description',
        'category', 'is_global', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class, 'template_id')->orderBy('sort_order');
    }

    public function serviceChecklists(): HasMany
    {
        return $this->hasMany(ServiceChecklist::class, 'template_id');
    }
}
