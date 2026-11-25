<?php
namespace App\Services;

use App\Enums\AppointmentStatus as S;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /** KPI strip. Cached 60s per branch/day so 20 open dashboards = 1 query set per minute. */
    public function kpis(?int $branchId, string $date): array
    {
        return Cache::remember("kpi:{$branchId}:{$date}", config('clinic.kpi_cache_seconds'), function () use ($branchId, $date) {
            $counts = $this->counts($branchId, $date);
            $prev = $this->counts($branchId, date('Y-m-d', strtotime("$date -1 day")));

            $revenue = $this->revenue($branchId, $date);
            $prevRevenue = $this->revenue($branchId, date('Y-m-d', strtotime("$date -1 day")));

            $rate = fn ($c) => $c['total'] ? round(($c['cancelled'] + $c['no_show']) / $c['total'] * 100, 1) : 0.0;

            return [
                'appointments' => $counts + ['trend' => $this->trend($counts['total'], $prev['total'])],
                'waiting' => ['count' => $counts['checked_in'], 'trend' => $this->trend($counts['checked_in'], $prev['checked_in'])],
                'revenue' => ['amount' => $revenue, 'trend' => $this->trend($revenue, $prevRevenue)],
                'no_show' => ['rate' => $rate($counts), 'trend' => round($rate($counts) - $rate($prev), 1)],
            ];
        });
    }

    private function counts(?int $branchId, string $date): array
    {
        // ONE aggregate query instead of 6 separate counts.
        $rows = Appointment::query()->forBranch($branchId)->onDate($date)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $get = fn (S $s) => (int) ($rows[$s->value] ?? 0);

        return [
            'total' => (int) $rows->sum(),
            'checked_in' => $get(S::CheckedIn),
            'in_progress' => $get(S::InProgress),
            'completed' => $get(S::Completed),
            'cancelled' => $get(S::Cancelled),
            'no_show' => $get(S::NoShow),
            'pending' => $get(S::Scheduled) + $get(S::Confirmed),
        ];
    }

    private function revenue(?int $branchId, string $date): float
    {
        return (float) Payment::query()
            ->whereDate('paid_at', $date)
            ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('branch_id', $branchId)))
            ->sum('amount');
    }

    private function trend(float|int $now, float|int $before): float
    {
        return $before > 0 ? round((($now - $before) / $before) * 100, 1) : ($now > 0 ? 100.0 : 0.0);
    }
}

/*
SYSTEM DESIGN:
Read-model service for the KPI strip. Uses a single GROUP BY query + short-lived cache; trend = % change vs previous day.

DATA STRUCTURE:
Aggregation: Collection keyed by status (pluck('c','status')). Cache entry per (branch, date) with TTL 60s -> bounded staleness.
*/
