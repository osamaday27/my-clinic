<?php
namespace App\Console\Commands;

use App\Models\DoctorCommission;
use Illuminate\Console\Command;

class CommissionReport extends Command
{
    protected $signature = 'clinic:commissions {period? : YYYY-MM}';
    protected $description = 'Monthly doctor commission statement';

    public function handle(): int
    {
        $period = $this->argument('period') ?: now()->subMonth()->format('Y-m');

        $rows = DoctorCommission::query()
            ->join('doctors', 'doctors.id', '=', 'doctor_commissions.doctor_id')
            ->join('users', 'users.id', '=', 'doctors.user_id')
            ->where('period', $period)
            ->selectRaw('users.name, SUM(base_amount) as base, SUM(amount) as due, COUNT(*) as invoices')
            ->groupBy('users.name')->orderBy('users.name')->get();

        $this->table(['Doctor', 'Base', 'Due', 'Invoices'], $rows->map(fn ($r) => [$r->name, $r->base, $r->due, $r->invoices]));

        return self::SUCCESS;
    }
}

/*
SYSTEM DESIGN:
Monthly settlement report (Rule: doctor accounts). Pure SQL aggregation - no row-by-row PHP loops.

DATA STRUCTURE:
GROUP BY doctor over indexed `period`.
*/
