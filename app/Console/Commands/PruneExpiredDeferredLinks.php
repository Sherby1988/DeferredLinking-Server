<?php

namespace App\Console\Commands;

use App\Models\DeferredLink;
use Illuminate\Console\Command;

class PruneExpiredDeferredLinks extends Command
{
    protected $signature = 'deferred-linking:prune
                            {--dry-run : Show count without deleting}';

    protected $description = 'Prune expired and already-resolved deferred links';

    public function handle(): int
    {
        $query = DeferredLink::where(function ($q) {
            $q->where('expires_at', '<', now())
              ->orWhere('resolved', true);
        });

        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info("Would delete {$count} deferred link record(s).");
            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Pruned {$deleted} deferred link record(s).");

        return self::SUCCESS;
    }
}
