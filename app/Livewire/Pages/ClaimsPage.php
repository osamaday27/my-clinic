<?php
namespace App\Livewire\Pages;

use App\Enums\ClaimStatus as C;
use App\Enums\Role;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Services\ClaimService;
use App\Support\CurrentBranch;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ClaimsPage extends Component
{
    use WithPagination;

    public string $tab = 'claims';
    public string $status = '';
    public string $companyFilter = '';

    public ?int $decideId = null;
    public string $decision = 'approved';
    public string $approvedAmount = '';
    public string $reason = '';

    public ?int $payId = null;
    public string $payAmount = '';

    public ?int $companyId = null;
    public string $c_name_ar = '';
    public string $c_name_en = '';
    public string $c_percent = '80';

    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingCompanyFilter(): void { $this->resetPage(); }

    private function scoped()
    {
        return InsuranceClaim::query()->whereHas('invoice', fn ($q) => $q->where('branch_id', CurrentBranch::id()));
    }

    #[Computed]
    public function claims()
    {
        return $this->scoped()->with(['patient', 'company', 'invoice'])
            ->when($this->status, fn ($q, $s) => $q->where('status', $s))
            ->when($this->companyFilter, fn ($q, $c) => $q->where('insurance_company_id', $c))
            ->latest()->simplePaginate(12);
    }

    #[Computed]
    public function summary(): array
    {
        $rows = $this->scoped()->get(['status', 'amount_claimed', 'amount_approved', 'amount_paid']);

        return [
            'pending' => (float) $rows->where('status', C::Submitted)->sum('amount_claimed'),
            'receivable' => (float) $rows->whereIn('status', [C::Approved, C::PartiallyApproved])->sum(fn ($r) => $r->amount_approved - $r->amount_paid),
            'rejected' => $rows->where('status', C::Rejected)->count(),
        ];
    }

    #[Computed]
    public function companies()
    {
        return InsuranceCompany::query()->orderBy('name_en')->get();
    }

    private function find(int $id): InsuranceClaim
    {
        $c = $this->scoped()->with('invoice')->findOrFail($id);
        Gate::authorize('manage', $c);

        return $c;
    }

    public function submit(int $id, ClaimService $svc): void
    {
        $svc->submit($this->find($id));
        $this->dispatch('toast', message: __('clinic.toast.claim_submitted'));
    }

    public function openDecide(int $id): void
    {
        $c = $this->find($id);
        $this->decideId = $id;
        $this->decision = 'approved';
        $this->approvedAmount = (string) $c->amount_claimed;
        $this->reason = '';
    }

    public function saveDecide(ClaimService $svc): void
    {
        $this->validate(['decision' => ['required', 'in:approved,partially_approved,rejected'], 'approvedAmount' => ['nullable', 'numeric', 'min:0'], 'reason' => ['nullable', 'string', 'max:255']]);
        $svc->decide($this->find($this->decideId), C::from($this->decision), (float) $this->approvedAmount, $this->reason ?: null);
        $this->reset('decideId', 'approvedAmount', 'reason');
        $this->dispatch('toast', message: __('clinic.toast.claim_decided'));
    }

    public function openPay(int $id): void
    {
        $c = $this->find($id);
        $this->payId = $id;
        $this->payAmount = $c->outstanding;
    }

    public function savePay(ClaimService $svc): void
    {
        $this->validate(['payAmount' => ['required', 'numeric', 'gt:0']]);
        $svc->recordPayment($this->find($this->payId), (float) $this->payAmount);
        $this->reset('payId', 'payAmount');
        $this->dispatch('toast', message: __('clinic.toast.claim_paid'));
    }

    public function editCompany(?int $id = null): void
    {
        abort_unless(auth()->user()->role === Role::Manager, 403);
        $this->companyId = $id;
        $co = $id ? InsuranceCompany::findOrFail($id) : null;
        $this->c_name_ar = (string) $co?->name_ar;
        $this->c_name_en = (string) $co?->name_en;
        $this->c_percent = (string) ($co?->coverage_percent ?? 80);
    }

    public function saveCompany(): void
    {
        abort_unless(auth()->user()->role === Role::Manager, 403);
        $d = $this->validate(['c_name_ar' => ['required', 'string', 'max:120'], 'c_name_en' => ['required', 'string', 'max:120'], 'c_percent' => ['required', 'numeric', 'between:0,100']],
            [], ['c_name_ar' => __('clinic.ui.name_ar'), 'c_name_en' => __('clinic.ui.name_en'), 'c_percent' => __('clinic.ui.coverage')]);
        InsuranceCompany::updateOrCreate(['id' => $this->companyId], ['name_ar' => $d['c_name_ar'], 'name_en' => $d['c_name_en'], 'coverage_percent' => $d['c_percent']]);
        $this->reset('companyId', 'c_name_ar', 'c_name_en');
        unset($this->companies);
        $this->dispatch('toast', message: __('clinic.toast.company_saved'));
    }

    public function toggleCompany(int $id): void
    {
        abort_unless(auth()->user()->role === Role::Manager, 403);
        $co = InsuranceCompany::findOrFail($id);
        $co->update(['is_active' => ! $co->is_active]);
        unset($this->companies);
    }

    public function render()
    {
        return view('pages.claims');
    }
}

/*
SYSTEM DESIGN:
Receivables cockpit: summary cards, claims worklist (branch-scoped) with FSM-driven row actions, and insurer master data (manager only). Every action re-resolves the claim server-side and runs Gate::authorize('manage').

DATA STRUCTURE:
Summary computed with a single fetch + in-memory aggregation by status (Collection where/sum).
*/
