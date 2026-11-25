<?php
namespace App\Livewire\Pages;

use App\Models\Invoice;
use App\Services\BillingService;
use App\Support\CurrentBranch;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class BillingPage extends Component
{
    use WithPagination;

    public string $status = '';
    public ?int $payId = null;
    public string $amount = '';
    public string $method = 'cash';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function invoices()
    {
        return Invoice::query()->with('patient')->where('branch_id', CurrentBranch::id())
            ->when($this->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->simplePaginate(12);
    }

    public function collect(int $id): void
    {
        $inv = Invoice::query()->where('branch_id', CurrentBranch::id())->findOrFail($id);
        Gate::authorize('pay', $inv);
        $this->payId = $id;
        $this->amount = $inv->balance;
    }

    public function pay(BillingService $billing): void
    {
        $this->validate(['amount' => ['required', 'numeric', 'gt:0'], 'method' => ['required', 'in:cash,card,insurance']]);
        $inv = Invoice::query()->where('branch_id', CurrentBranch::id())->findOrFail($this->payId);
        Gate::authorize('pay', $inv);
        $billing->pay($inv, (float) $this->amount, $this->method, auth()->user());

        $this->reset('payId', 'amount');
        $this->dispatch('toast', message: __('clinic.toast.paid'));
    }

    public function render()
    {
        return view('pages.billing');
    }
}

/*
SYSTEM DESIGN:
Invoice list + partial payments. Authorization (Policy) AND branch scope are both enforced; the amount is validated server-side and again inside BillingService under a row lock.

DATA STRUCTURE:
Paginated ledger; payment is a state change paid += amount, status derived.
*/
