<?php
namespace App\Livewire\Pages;

use App\Models\StockItem;
use App\Support\CurrentBranch;
use Livewire\Attributes\Computed;
use Livewire\Component;

class InventoryPage extends Component
{
    #[Computed]
    public function items()
    {
        return StockItem::query()->where('branch_id', CurrentBranch::id())
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')->get();
    }

    #[Computed]
    public function alerts(): array
    {
        $base = StockItem::query()->where('branch_id', CurrentBranch::id());

        return [
            'expiring' => (clone $base)->expiringSoon()->count(),
            'low' => (clone $base)->low()->count(),
        ];
    }

    public function render()
    {
        return view('pages.inventory');
    }
}

/*
SYSTEM DESIGN:
Medical stock with expiry + reorder alerts. Sorted by nearest expiry first (FEFO - first-expired-first-out).

DATA STRUCTURE:
Priority ordering by expiry_date (indexed); alert counters via COUNT(*) on scopes.
*/
