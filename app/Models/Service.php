<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = [
        'church_id', 'name', 'service_date', 'start_time', 'end_time',
        'service_type', 'speaker', 'worship_leader', 'notes', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date:Y-m-d',
        ];
    }

    // ── Relationships ──

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(ServiceChecklist::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ServiceSection::class);
    }

    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'volunteer_schedules');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
