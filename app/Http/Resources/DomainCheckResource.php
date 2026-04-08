<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DomainCheck */
class DomainCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing('domain');

        return [
            'id' => $this->id,
            'domain_id' => $this->domain_id,
            'path' => $this->path,
            'method' => $this->method,
            'interval_seconds' => $this->interval_seconds,
            'timeout_seconds' => $this->timeout_seconds,
            'is_active' => $this->is_active,
            'next_run_at' => $this->next_run_at?->toIso8601String(),
            'last_ok' => $this->last_ok,
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'url' => $this->buildUrl(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
