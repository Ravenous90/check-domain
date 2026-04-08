<?php

namespace App\Jobs;

use App\Models\DomainCheck;
use App\Notifications\CheckStateChangedNotification;
use App\Services\DomainCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunDomainCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(public int $domainCheckId) {}

    public function handle(DomainCheckService $service): void
    {
        $check = DomainCheck::query()->with('domain.user')->find($this->domainCheckId);
        if (! $check || ! $check->is_active) {
            return;
        }

        $previousOk = $check->last_ok;
        $log = $service->run($check);
        $newOk = $log->ok;

        $interval = max(60, min(86400, (int) $check->interval_seconds));
        $check->update([
            'last_ok' => $newOk,
            'last_checked_at' => now(),
            'next_run_at' => now()->addSeconds($interval),
        ]);

        if ($previousOk !== null && $previousOk !== $newOk) {
            $check->domain->user->notify(new CheckStateChangedNotification($check, $previousOk, $newOk, $log));
        }
    }
}
