<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CheckLog */
class CheckLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain_check_id' => $this->domain_check_id,
            'ok' => $this->ok,
            'http_status' => $this->http_status,
            'response_time_ms' => $this->response_time_ms,
            'error_message' => $this->error_message,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
