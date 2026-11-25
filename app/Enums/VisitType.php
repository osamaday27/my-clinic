<?php
namespace App\Enums;

enum VisitType: string
{
    case Consultation = 'consultation';
    case FollowUp = 'follow_up';
    case Lab = 'lab_procedure';

    public function label(): string
    {
        return __('clinic.visit.'.$this->value);
    }
}

/*
SYSTEM DESIGN:
Enum for the visit type column shown in the schedule table.

DATA STRUCTURE:
Enum with 3 constants cast on appointments.visit_type.
*/
