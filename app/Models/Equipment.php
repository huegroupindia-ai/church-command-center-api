<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    protected $fillable = [
        'church_id', 'category_id', 'department_id', 'name', 'asset_id',
        'description', 'brand', 'model', 'serial_number', 'purchase_date',
        'warranty_expires_at', 'purchase_price', 'status', 'qr_code',
        'qr_code_image_path', 'location', 'last_maintenance_at',
        'next_maintenance_at', 'image_path', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date:Y-m-d',
            'warranty_expires_at' => 'date:Y-m-d',
            'last_maintenance_at' => 'date:Y-m-d',
            'next_maintenance_at' => 'date:Y-m-d',
            'purchase_price' => 'decimal:2',
        ];
    }

    // ── Relationships ──

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class, 'category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(EquipmentMaintenanceLog::class, 'equipment_id');
    }

    public function faultReports(): HasMany
    {
        return $this->hasMany(EquipmentFaultReport::class, 'equipment_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
