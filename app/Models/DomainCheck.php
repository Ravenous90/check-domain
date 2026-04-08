<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainCheck extends Model
{
    protected $fillable = [
        'domain_id',
        'path',
        'method',
        'interval_seconds',
        'timeout_seconds',
        'is_active',
        'next_run_at',
        'last_ok',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_ok' => 'boolean',
            'next_run_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CheckLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('next_run_at')
                ->orWhere('next_run_at', '<=', now());
        });
    }

    public function buildUrl(): string
    {
        $host = $this->domain->hostname;
        $path = $this->path ?: '/';
        if ($path[0] !== '/') {
            $path = '/'.$path;
        }

        return 'https://'.$host.$path;
    }
}
