<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMaintenanceLog extends Model
{
    protected $fillable = [
        'equipment_id',
        'performed_by',
        'type',
        'description',
        'cost',
        'performed_at',
        'next_maintenance_at',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'date:Y-m-d',
            'next_maintenance_at' => 'date:Y-m-d',
            'cost' => 'decimal:2',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

