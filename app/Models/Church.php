<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Church extends Model
{
    protected $fillable = [
        'name', 'slug', 'address', 'city', 'state', 'country',
        'phone', 'email', 'logo', 'timezone', 'settings',
        'subscription_plan', 'subscription_expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'subscription_expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}
