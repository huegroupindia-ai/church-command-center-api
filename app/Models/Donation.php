<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'church_id', 'donor_id', 'donor_name', 'donor_email', 'amount',
        'currency', 'type', 'method', 'reference_number', 'notes',
        'is_recurring', 'recurring_frequency', 'is_tax_deductible', 'donated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_recurring' => 'boolean',
        'is_tax_deductible' => 'boolean',
        'donated_at' => 'datetime',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }
}
