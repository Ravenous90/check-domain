<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckLog extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (CheckLog $log): void {
            $log->created_at ??= now();
        });
    }

    protected $fillable = [
        'domain_check_id',
        'ok',
        'http_status',
        'response_time_ms',
        'error_message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'ok' => 'boolean',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function domainCheck(): BelongsTo
    {
        return $this->belongsTo(DomainCheck::class);
    }
}
