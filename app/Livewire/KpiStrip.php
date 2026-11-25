<?php
namespace App\Livewire;

use App\Services\DashboardService;
use App\Support\CurrentBranch;
use Livewire\Attributes\Computed;
use Livewire\Component;

class KpiStrip extends Component
{
    #[Computed]
    public function kpis(): array
    {
        return app(DashboardService::class)->kpis(CurrentBranch::id(), today()->toDateString());
    }

    public function render()
    {
        return view('livewire.kpi-strip');
    }
}

/*
SYSTEM DESIGN:
Presentational Livewire component for the 4-card KPI strip; polls every 30s (wire:poll) while the tab is visible. All numbers come from DashboardService (cached).

DATA STRUCTURE:
Computed property memoised per request; payload = nested associative array.
*/
