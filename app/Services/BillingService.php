<?php
namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\DoctorCommission;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BillingService
{
    public function __construct(private AppointmentService $appointments, private AuditService $audit) {}

    /**
     * End-of-visit atomic unit of work (Rule 10): mark visit complete + create invoice + deduct stock + post commission.
     * $items: [['description'=>..,'quantity'=>1,'unit_price'=>300,'doctor_id'=>1,'stock_item_id'=>null], ...]
     */
    public function completeVisit(Appointment $a, array $items = [], float $discount = 0): Invoice
    {
        return DB::transaction(function () use ($a, $items, $discount) {
            $a->loadMissing('patient', 'doctor');
            $this->appointments->transition($a, AppointmentStatus::Completed);

            $items = $items ?: [[
                'description' => $a->visit_type->label(),
                'quantity' => 1,
                'unit_price' => config('clinic.fees.'.$a->visit_type->value, 0),
                'doctor_id' => $a->doctor_id,
            ]];

            $subtotal = round(collect($items)->sum(fn ($i) => $i['quantity'] * $i['unit_price']), 2);
            $insurance = $a->patient->insurance_company
                ? round($subtotal * config('clinic.insurance_coverage_percent') / 100, 2) : 0.0;
            $total = max(0, round($subtotal - $discount - $insurance, 2));

            $invoice = Invoice::create([
                'number' => 'TMP-'.Str::uuid(),
                'patient_id' => $a->patient_id,
                'appointment_id' => $a->id,
                'branch_id' => $a->branch_id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'insurance_covered' => $insurance,
                'total' => $total,
                'status' => $total == 0 ? 'paid' : 'unpaid',
            ]);
            $invoice->update(['number' => sprintf('INV-%s-%05d', now()->format('Ymd'), $invoice->id)]);

            foreach ($items as $i) {
                $invoice->items()->create([
                    'description' => $i['description'],
                    'quantity' => $i['quantity'],
                    'unit_price' => $i['unit_price'],
                    'line_total' => round($i['quantity'] * $i['unit_price'], 2),
                    'doctor_id' => $i['doctor_id'] ?? null,
                    'stock_item_id' => $i['stock_item_id'] ?? null,
                ]);
                if (! empty($i['stock_item_id'])) {
                    $this->deductStock((int) $i['stock_item_id'], (int) $i['quantity']);
                }
            }

            $this->postCommission($invoice, $a);
            $this->audit->log('invoice.created', $invoice);

            return $invoice;
        });
    }

    public function pay(Invoice $invoice, float $amount, string $method, User $by): Payment
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $by) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $balance = round((float) $invoice->total - (float) $invoice->paid, 2);

            if ($amount <= 0 || $amount > $balance) {
                throw ValidationException::withMessages(['amount' => __('clinic.errors.bad_amount')]);
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id, 'received_by' => $by->id,
                'amount' => $amount, 'method' => $method, 'paid_at' => now(),
            ]);
            $paid = round((float) $invoice->paid + $amount, 2);
            $invoice->update(['paid' => $paid, 'status' => $paid >= (float) $invoice->total ? 'paid' : 'partial']);
            $this->audit->log('invoice.payment', $invoice);

            return $payment;
        });
    }

    private function deductStock(int $id, int $qty): void
    {
        $item = StockItem::query()->lockForUpdate()->findOrFail($id);
        if ($item->quantity < $qty) {
            throw ValidationException::withMessages(['stock' => __('clinic.errors.low_stock', ['item' => $item->name])]);
        }
        $item->decrement('quantity', $qty);
    }

    private function postCommission(Invoice $invoice, Appointment $a): void
    {
        $base = (float) $invoice->items()->where('doctor_id', $a->doctor_id)->sum('line_total');
        $rate = (float) $a->doctor->commission_rate;

        DoctorCommission::firstOrCreate(
            ['doctor_id' => $a->doctor_id, 'invoice_id' => $invoice->id],
            ['base_amount' => $base, 'rate' => $rate, 'amount' => round($base * $rate / 100, 2), 'period' => now()->format('Y-m')],
        );
    }
}

/*
SYSTEM DESIGN:
Unit-of-Work / transaction script. One DB::transaction wraps visit completion, invoice, stock deduction and commission, so a failure at any step rolls everything back. pay() re-reads the invoice with lockForUpdate to stop double-payment races.

DATA STRUCTURE:
Pessimistic locking (SELECT ... FOR UPDATE). Idempotent commission via firstOrCreate on unique(doctor_id, invoice_id). Money math rounded to 2 decimals and persisted as DECIMAL.
*/
