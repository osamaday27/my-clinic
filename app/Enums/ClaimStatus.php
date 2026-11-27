<?php
namespace App\Enums;

enum ClaimStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case PartiallyApproved = 'partially_approved';
    case Rejected = 'rejected';
    case Paid = 'paid';

    public function label(): string
    {
        return __('clinic.claim.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'info',
            self::Submitted, self::PartiallyApproved => 'warn',
            self::Approved, self::Paid => 'ok',
            self::Rejected => 'bad',
        };
    }

    public function next(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::Approved, self::PartiallyApproved, self::Rejected],
            self::Approved, self::PartiallyApproved => [self::Paid],
            self::Rejected => [self::Submitted],          // appeal
            self::Paid => [],
        };
    }

    public function canMoveTo(self $to): bool
    {
        return in_array($to, $this->next(), true);
    }
}

/*
SYSTEM DESIGN:
FSM for claims: draft -> submitted -> approved | partially_approved | rejected -> paid; rejected can be appealed (back to submitted).

DATA STRUCTURE:
Directed graph as adjacency list.
*/
