<?php

return [
    'app' => 'Clinic & Medical Center ERP',
    'roles' => ['manager' => 'Center manager', 'reception' => 'Reception', 'doctor' => 'Doctor', 'nurse' => 'Nurse', 'pharmacist' => 'Pharmacist', 'accountant' => 'Accountant', 'patient' => 'Patient'],
    'status' => ['scheduled' => 'Scheduled', 'confirmed' => 'Confirmed', 'checked_in' => 'Waiting', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'],
    'visit' => ['consultation' => 'Consultation', 'follow_up' => 'Follow-up', 'lab_procedure' => 'Lab / Procedure'],
    'nav' => ['dashboard' => 'Dashboard', 'appointments' => 'Appointments', 'patients' => 'Patients', 'doctors' => 'Doctors & Staff', 'billing' => 'Billing & Insurance', 'inventory' => 'Inventory / Pharmacy', 'settings' => 'Settings', 'lab' => 'Lab & Radiology'],
    'ui' => [
        'search' => 'Search by name, phone or file ID', 'new_appointment' => 'New appointment', 'logout' => 'Sign out',
        'branch' => 'Branch', 'language' => 'العربية', 'today' => 'Today', 'all' => 'All', 'doctor' => 'Doctor', 'status' => 'Status',
        'patient' => 'Patient', 'token' => 'Token', 'time' => 'Time', 'department' => 'Department', 'type' => 'Visit type', 'actions' => 'Actions',
        'check_in' => 'Check-in', 'start_visit' => 'Start visit', 'complete' => 'Finish & invoice', 'reschedule' => 'Reschedule', 'cancel' => 'Cancel',
        'save' => 'Save', 'close' => 'Close', 'back' => 'Back', 'next' => 'Next', 'prev' => 'Previous', 'empty' => 'Nothing here yet',
        'next_patient' => 'Current / next patient', 'waiting_queue' => 'Waiting queue', 'no_active' => 'No patient is waiting',
        'age' => 'Age', 'years' => 'yrs', 'last_visit' => 'Last visit', 'complaint' => 'Chief complaint', 'allergy' => 'Allergy', 'chronic' => 'Chronic conditions',
        'prescription' => 'Prescription', 'phone' => 'Phone', 'file' => 'File no.', 'insurance' => 'Insurance', 'schedule' => "Today's schedule",
        'login' => 'Sign in', 'email' => 'Email', 'password' => 'Password', 'remember' => 'Remember me',
        'collect' => 'Collect', 'amount' => 'Amount', 'method' => 'Method', 'cash' => 'Cash', 'card' => 'Card', 'insurance_m' => 'Insurance',
        'invoice' => 'Invoice', 'total' => 'Total', 'paid' => 'Paid', 'balance' => 'Balance', 'active' => 'Active', 'inactive' => 'Disabled',
        'commission' => 'Commission', 'today_visits' => "Today's visits", 'qty' => 'Qty', 'expiry' => 'Expiry', 'expired' => 'Expired', 'expiring' => 'Expiring soon', 'low' => 'Low stock', 'ok' => 'OK',
        'branches' => 'Branches', 'staff' => 'Staff', 'role' => 'Role', 'name' => 'Name', 'notifications' => 'Notifications', 'skip' => 'Skip to content',
        'live_preview' => 'Live preview', 'pick_slot' => 'Pick an available time', 'no_slots' => 'Choose a doctor and date to see slots', 'search_patient' => 'Find patient',
        'history' => 'History & prescriptions', 'overview' => 'Overview & vitals', 'invoices' => 'Invoices', 'diagnosis' => 'Diagnosis',
        'no_vitals' => 'No vitals recorded', 'vitals' => 'Vitals', 'bp' => 'BP', 'hr' => 'HR', 'temp' => 'Temp', 'weight' => 'Weight',
        'print' => 'Print', 'issue' => 'Issue prescription', 'add_line' => 'Add medicine', 'notes' => 'Notes', 'ack' => 'I reviewed the allergy alert and want to continue',
        'confirm_title' => 'Are you sure?', 'yes' => 'Yes, continue', 'no' => 'Go back', 'hint_date' => 'Past dates cannot be booked', 'hint_slot' => 'Greyed slots are taken or already passed',
        'wait_trend' => 'live', 'vs_yesterday' => 'vs. yesterday',
        'chart' => 'Visit chart', 'tab_vitals' => 'Vitals', 'tab_diagnosis' => 'Diagnosis', 'tab_lab' => 'Lab & radiology', 'systolic' => 'Systolic', 'diastolic' => 'Diastolic', 'bpm' => 'Heart rate (bpm)', 'temp_c' => 'Temp (°C)', 'weight_kg' => 'Weight (kg)', 'icd' => 'ICD-10 code', 'clin_notes' => 'Clinical notes', 'lab' => 'Lab', 'radiology' => 'Radiology', 'test_name' => 'Test name', 'request_lab' => 'Request test', 'result' => 'Result', 'attachment' => 'Attachment (PDF / image)', 'download' => 'Download', 'record_result' => 'Record result', 'requested' => 'Requested', 'resulted' => 'Resulted', 'recent' => 'Recent readings', 'saved_records' => 'This visit diagnoses', 'visit_labs' => 'This visit tests',
    ],
    'kpi' => ['appointments' => "Today's appointments", 'waiting' => 'In waiting room', 'revenue' => 'Collected today', 'no_show' => 'No-show / cancellation', 'checked' => 'checked in', 'pending' => 'pending', 'currency' => 'EGP'],
    'rx' => ['medicine' => 'Medicine', 'dose' => 'Dose', 'frequency' => 'Frequency', 'days' => 'Days', 'instructions' => 'Instructions', 'title' => 'Medical prescription', 'signature' => 'Doctor signature', 'alert' => 'Drug allergy alert: :names'],
    'confirm' => ['cancel' => 'This appointment will be cancelled and the slot released.', 'complete' => 'The visit will be closed, the invoice issued and supplies deducted.', 'toggle' => 'Change activation status?'],
    'toast' => ['checked_in' => 'Patient checked in', 'completed' => 'Visit completed - invoice :no issued', 'cancelled' => 'Appointment cancelled', 'rescheduled' => 'Appointment rescheduled', 'booked' => 'Appointment booked', 'rx_issued' => 'Prescription issued', 'paid' => 'Payment recorded', 'saved' => 'Saved', 'vitals_saved' => 'Vitals saved', 'diagnosis_saved' => 'Diagnosis saved', 'lab_requested' => 'Test requested', 'lab_resulted' => 'Result recorded'],
    'errors' => [
        'slot_taken' => 'The doctor is already booked at this time',
        'doctor_inactive' => 'This doctor is not available for booking',
        'bad_transition' => 'This action is not allowed for the current appointment status',
        'bad_amount' => 'Amount exceeds the balance or is invalid',
        'low_stock' => 'Not enough stock: :item',
        'allergy' => 'Warning: conflicts with the patient\'s allergies (:names). Acknowledge to continue.',
        'login' => 'Invalid credentials or the account is disabled',
        'visit_closed' => 'This visit is closed and cannot be edited',
        'vitals_empty' => 'Enter at least one reading',
        'result_empty' => 'Write the result or attach a file',
    ],
    'reminder' => 'Reminder: your appointment with Dr. :doctor on :date at :time. See you - :name',
    'attributes' => ['patient_id' => 'patient', 'doctor_id' => 'doctor', 'appointment_date' => 'appointment date', 'start_time' => 'time', 'visit_type' => 'visit type', 'chief_complaint' => 'complaint'],
    'validation' => [
        'patient_id.required' => 'Please choose a patient',
        'doctor_id.required' => 'Please choose a doctor',
        'appointment_date.required' => 'Pick a date',
        'appointment_date.after_or_equal' => 'Past dates cannot be booked',
        'start_time.required' => 'Pick an available time',
        'visit_type.required' => 'Choose the visit type',
    ],
];

/*
SYSTEM DESIGN:
English mirror of the Arabic file.

DATA STRUCTURE:
Same key tree (parity checked by tests/Feature/LangParityTest.php).
*/
