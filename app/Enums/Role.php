<?php
namespace App\Enums;

enum Role: string
{
    case Manager = 'manager';
    case Reception = 'reception';
    case Doctor = 'doctor';
    case Nurse = 'nurse';
    case Pharmacist = 'pharmacist';
    case Accountant = 'accountant';
    case Patient = 'patient';

    public function label(): string
    {
        return __('clinic.roles.'.$this->value);
    }

    /** @return array<int,string> staff roles allowed into the dashboard */
    public static function staff(): array
    {
        return array_map(fn (self $r) => $r->value, array_filter(self::cases(), fn (self $r) => $r !== self::Patient));
    }
}

/*
SYSTEM DESIGN:
Backed PHP enum = single source of truth for RBAC roles. Used by EnsureRole middleware, Policies and seeders. Prevents magic strings (Rule: SOLID / clean code).

DATA STRUCTURE:
Enum (closed set of 7 constants) cast on users.role. staff() returns a list (array) derived with array_filter/array_map -> O(n), n=7.
*/
