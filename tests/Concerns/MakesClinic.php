<?php
namespace Tests\Concerns;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;

trait MakesClinic
{
    protected function makeDoctor(Branch $branch, string $email = 'd@x.test'): Doctor
    {
        $clinic = Clinic::firstOrCreate(['branch_id' => $branch->id, 'name_ar' => 'باطنة', 'name_en' => 'IM']);
        $user = User::create(['name' => 'Dr '.$email, 'email' => $email, 'password' => 'Secret@12345', 'role' => 'doctor', 'branch_id' => $branch->id, 'is_active' => true]);

        return Doctor::create(['user_id' => $user->id, 'clinic_id' => $clinic->id, 'specialty_ar' => 'باطنة', 'specialty_en' => 'IM', 'commission_rate' => 30]);
    }

    protected function makeStaff(string $role, Branch $branch): User
    {
        return User::create(['name' => $role, 'email' => $role.'@x.test', 'password' => 'Secret@12345', 'role' => $role, 'branch_id' => $branch->id, 'is_active' => true]);
    }

    protected function makePatient(array $o = []): Patient
    {
        static $n = 0;
        $n++;

        return Patient::create($o + ['file_number' => 'T-'.$n, 'name_ar' => 'مريض '.$n, 'phone' => '0100000'.str_pad($n, 4, '0', STR_PAD_LEFT)]);
    }

    protected function makeAppointment(Doctor $d, Patient $p, AppointmentStatus $s = AppointmentStatus::Scheduled, string $time = '10:00:00'): Appointment
    {
        return Appointment::create([
            'patient_id' => $p->id, 'doctor_id' => $d->id, 'clinic_id' => $d->clinic_id, 'branch_id' => $d->clinic->branch_id,
            'appointment_date' => now()->addDay()->toDateString(), 'start_time' => $time, 'end_time' => date('H:i:s', strtotime($time) + 1200),
            'visit_type' => 'consultation', 'status' => $s->value, 'token_number' => 1,
        ]);
    }
}

/*
SYSTEM DESIGN:
Test fixture helpers (no factories needed).

DATA STRUCTURE:

*/
