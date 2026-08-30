<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['name', 'is_group', 'avatar', 'created_by'];

    protected $casts = [
        'is_group' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function getUnreadCountAttribute(): int
    {
        $userId = auth()->id();
        $participant = $this->participants()->where('user_id', $userId)->first();
        
        if (!$participant || !$participant->pivot->last_read_at) {
            return $this->messages()->count();
        }

        return $this->messages()
            ->where('user_id', '!=', $userId)
            ->where('created_at', '>', $participant->pivot->last_read_at)
            ->count();
    }

    public function getOtherParticipantAttribute(): ?User
    {
        if ($this->is_group) return null;
        
        return $this->participants
            ->where('id', '!=', auth()->id())
            ->first();
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->name) return $this->name;
        
        $other = $this->otherParticipant;
        return $other ? $other->name : 'Unknown';
    }
}
