<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SeedIfEmpty extends Command
{
    protected $signature = 'clinic:seed-if-empty';
    protected $description = 'Load demo data only when there are no users yet (safe to run on every update)';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->info('Users already exist - demo data skipped.');

            return self::SUCCESS;
        }

        return $this->call('db:seed', ['--force' => true]);
    }
}

/*
SYSTEM DESIGN:
Idempotent bootstrap used by setup.ps1: first install gets demo data, later updates never duplicate or overwrite real data.

DATA STRUCTURE:
Existence check (EXISTS query) as a guard.
*/
