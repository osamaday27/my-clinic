<?php
namespace Database\Seeders;

use App\Enums\AppointmentStatus as S;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\LabRequest;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Vital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $pwd = env('SEED_PASSWORD', 'Clinic@12345');       // demo only - change/disable in production

            $b1 = Branch::create(['name_ar' => 'فرع طنطا', 'name_en' => 'Tanta Branch', 'phone' => '0403330000']);
            $b2 = Branch::create(['name_ar' => 'فرع القاهرة', 'name_en' => 'Cairo Branch', 'phone' => '0222220000']);

            $c1 = Clinic::create(['branch_id' => $b1->id, 'name_ar' => 'الباطنة', 'name_en' => 'Internal Medicine']);
            $c2 = Clinic::create(['branch_id' => $b1->id, 'name_ar' => 'الأطفال', 'name_en' => 'Pediatrics']);
            $c3 = Clinic::create(['branch_id' => $b1->id, 'name_ar' => 'الجلدية', 'name_en' => 'Dermatology']);
            Clinic::create(['branch_id' => $b2->id, 'name_ar' => 'الأسنان', 'name_en' => 'Dentistry']);

            $mk = fn (string $name, string $email, Role $role, ?int $branch = null) => User::create([
                'name' => $name, 'email' => $email, 'password' => $pwd, 'role' => $role->value, 'branch_id' => $branch, 'is_active' => true,
            ]);
            $mk('مدير المركز', 'manager@clinic.test', Role::Manager, $b1->id);
            $mk('سارة - الاستقبال', 'reception@clinic.test', Role::Reception, $b1->id);
            $mk('ممرض أحمد', 'nurse@clinic.test', Role::Nurse, $b1->id);
            $mk('صيدلي محمود', 'pharmacist@clinic.test', Role::Pharmacist, $b1->id);
            $mk('محاسب خالد', 'accountant@clinic.test', Role::Accountant, $b1->id);

            $docs = collect([
                ['د. هاني عبد الله', 'doctor1@clinic.test', $c1, 'باطنة وقلب', 'Internal Medicine & Cardiology', 35],
                ['د. منى السيد', 'doctor2@clinic.test', $c2, 'أطفال وحديثي الولادة', 'Pediatrics & Neonatology', 30],
                ['د. طارق فؤاد', 'doctor3@clinic.test', $c3, 'جلدية وليزر', 'Dermatology & Laser', 40],
            ])->map(function ($d) use ($mk, $b1) {
                $u = $mk($d[0], $d[1], Role::Doctor, $b1->id);

                return Doctor::create(['user_id' => $u->id, 'clinic_id' => $d[2]->id, 'specialty_ar' => $d[3], 'specialty_en' => $d[4], 'commission_rate' => $d[5]]);
            });

            $medicines = [
                ['باراسيتامول', 'Paracetamol', 'paracetamol', 'tablet', '500mg'],
                ['إيبوبروفين', 'Ibuprofen', 'ibuprofen', 'tablet', '400mg'],
                ['أموكسيسيلين', 'Amoxicillin', 'amoxicillin (penicillin class)', 'capsule', '500mg'],
                ['أزيثروميسين', 'Azithromycin', 'azithromycin', 'tablet', '250mg'],
                ['لوراتادين', 'Loratadine', 'loratadine', 'tablet', '10mg'],
                ['أوميبرازول', 'Omeprazole', 'omeprazole', 'capsule', '20mg'],
                ['ميتفورمين', 'Metformin', 'metformin', 'tablet', '850mg'],
                ['كريم هيدروكورتيزون', 'Hydrocortisone Cream', 'hydrocortisone', 'cream', '1%'],
            ];
            foreach ($medicines as [$ar, $en, $gen, $form, $str]) {
                Medicine::create(['name_ar' => $ar, 'name_en' => $en, 'generic_name' => $gen, 'form' => $form, 'strength' => $str]);
            }

            $names = ['محمد علي', 'أحمد حسن', 'فاطمة محمود', 'نور الهدى', 'يوسف إبراهيم', 'مريم عادل', 'عمر خالد', 'هدى سمير', 'كريم مصطفى', 'ليلى حسين', 'إسراء رضا', 'مصطفى كمال',
                'سلمى وائل', 'حسام الدين', 'دعاء عبد الرحمن', 'زياد ناصر', 'آية سعيد', 'بلال فتحي', 'رنا ياسر', 'عبد الله رمضان', 'جميلة صبري', 'إياد منصور', 'ندى أشرف', 'مروان عاطف'];
            $patients = collect($names)->map(fn ($n, $i) => Patient::create([
                'file_number' => 'P-'.str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'name_ar' => $n,
                'phone' => '010'.str_pad(10000000 + $i * 137, 8, '0', STR_PAD_LEFT),
                'birth_date' => now()->subYears(5 + $i * 2)->subDays($i * 11),
                'gender' => $i % 2 ? 'female' : 'male',
                'allergies' => $i % 5 === 0 ? 'Penicillin, Sulfa' : null,
                'chronic_conditions' => $i % 7 === 0 ? 'السكر - ضغط' : null,
                'insurance_company' => $i % 4 === 0 ? 'Misr Insurance' : null,
                'insurance_number' => $i % 4 === 0 ? 'MI-'.(9000 + $i) : null,
            ]));

            foreach ($patients->take(6) as $i => $p) {
                Vital::create(['patient_id' => $p->id, 'bp_systolic' => 118 + $i, 'bp_diastolic' => 76, 'heart_rate' => 72 + $i, 'temperature' => 36.8, 'spo2' => 98, 'weight' => 70 + $i]);
                MedicalRecord::create(['patient_id' => $p->id, 'doctor_id' => $docs[$i % 3]->id, 'icd_code' => 'J06.9', 'diagnosis' => 'التهاب الجهاز التنفسي العلوي', 'notes' => 'راحة وسوائل']);
            }
            LabRequest::create(['patient_id' => $patients[0]->id, 'doctor_id' => $docs[0]->id, 'test_name' => 'CBC', 'status' => 'requested']);

            foreach ([['GLV-001', 'قفازات طبية', 120, 30, 90], ['SYR-005', 'سرنجات 5 مل', 40, 50, 400], ['MSK-010', 'كمامات جراحية', 300, 100, 25],
                ['PAR-500', 'باراسيتامول 500', 15, 20, 20], ['AMX-500', 'أموكسيسيلين 500', 60, 25, 200], ['ALC-100', 'كحول طبي', 18, 10, -5], ['GAZ-010', 'شاش معقم', 200, 40, 600]] as [$sku, $name, $qty, $min, $days]) {
                StockItem::create(['branch_id' => $b1->id, 'sku' => $sku, 'name' => $name, 'quantity' => $qty, 'min_quantity' => $min, 'unit_cost' => 5, 'expiry_date' => now()->addDays($days)]);
            }

            // ---- Today (mixed live queue) + yesterday (completed, for trends) ----
            $today = today();
            $plan = [S::Completed, S::Completed, S::InProgress, S::CheckedIn, S::CheckedIn, S::CheckedIn, S::Confirmed, S::Scheduled, S::Scheduled, S::Cancelled, S::NoShow, S::Confirmed];
            foreach ($plan as $k => $status) {
                $this->appointment($docs[$k % 3], $patients[$k], $today, $k, $status, $k + 1);
            }
            foreach (range(0, 5) as $k) {
                $this->appointment($docs[$k % 3], $patients[10 + $k], $today->copy()->subDay(), $k, S::Completed, $k + 1);
            }
        });
    }

    private function appointment(Doctor $doc, Patient $p, $date, int $slot, S $status, int $token): void
    {
        $start = $date->copy()->setTime(9, 0)->addMinutes(intdiv($slot, 3) * 20);
        $a = Appointment::create([
            'patient_id' => $p->id, 'doctor_id' => $doc->id, 'clinic_id' => $doc->clinic_id, 'branch_id' => 1,
            'appointment_date' => $date->toDateString(), 'start_time' => $start->format('H:i:s'), 'end_time' => $start->copy()->addMinutes(20)->format('H:i:s'),
            'visit_type' => ['consultation', 'follow_up', 'lab_procedure'][$slot % 3], 'status' => $status->value, 'token_number' => $token,
            'chief_complaint' => ['صداع مستمر', 'متابعة تحاليل', 'ألم في المعدة', 'طفح جلدي', 'كحة وحرارة'][$slot % 5],
            'checked_in_at' => in_array($status, [S::CheckedIn, S::InProgress, S::Completed], true) ? now()->subMinutes(5 + $slot * 3) : null,
        ]);

        if ($status === S::Completed) {
            $fee = [300, 150, 200][$slot % 3];
            $inv = Invoice::create(['number' => 'SEED-'.$a->id, 'patient_id' => $p->id, 'appointment_id' => $a->id, 'branch_id' => 1,
                'subtotal' => $fee, 'total' => $fee, 'paid' => $fee, 'status' => 'paid']);
            $inv->items()->create(['description' => $a->visit_type->label(), 'quantity' => 1, 'unit_price' => $fee, 'line_total' => $fee, 'doctor_id' => $doc->id]);
            $inv->payments()->create(['amount' => $fee, 'method' => 'cash', 'paid_at' => $date->copy()->setTime(12, 0)]);
        }
    }
}

/*
SYSTEM DESIGN:
Demo data for every role + realistic live queue (completed / in-progress / waiting / scheduled / cancelled / no-show) and yesterday's completed visits so KPI trends have a baseline. Whole seeding runs in one transaction.

DATA STRUCTURE:
Plan array = ordered list of statuses mapped to doctor round-robin (k % 3) and 3 visits per 20-min slot.
*/
