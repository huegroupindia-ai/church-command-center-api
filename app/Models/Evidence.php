<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    protected $table = 'evidence';

    protected $fillable = [
        'checklist_item_id', 'user_id', 'type', 'file_path',
        'file_name', 'file_size', 'mime_type', 'notes',
    ];

    // ── Relationships ──

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ServiceChecklistItem::class, 'checklist_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
