<?php

namespace App\Console\Commands;

use App\Jobs\RunDomainCheckJob;
use App\Models\DomainCheck;
use Illuminate\Console\Command;

class DispatchDueDomainChecksCommand extends Command
{
    protected $signature = 'domain-checks:dispatch-due';

    protected $description = 'Queue domain checks that are due according to schedule';

    public function handle(): int
    {
        DomainCheck::query()
            ->active()
            ->due()
            ->orderBy('id')
            ->chunkById(100, function ($checks) {
                foreach ($checks as $check) {
                    RunDomainCheckJob::dispatch($check->id);
                }
            });

        return self::SUCCESS;
    }
}
