<?php
namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return __('clinic.status.'.$this->value);
    }

    /** Semantic badge tone used by CSS (.badge--ok / --warn / --bad / --info). */
    public function tone(): string
    {
        return match ($this) {
            self::Confirmed, self::Completed => 'ok',
            self::CheckedIn, self::InProgress => 'warn',
            self::Cancelled, self::NoShow => 'bad',
            self::Scheduled => 'info',
        };
    }

    /** Finite-state-machine edges. Anything not listed is an illegal transition. */
    public function next(): array
    {
        return match ($this) {
            self::Scheduled => [self::Confirmed, self::CheckedIn, self::Cancelled, self::NoShow],
            self::Confirmed => [self::CheckedIn, self::Cancelled, self::NoShow],
            self::CheckedIn => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed],
            default => [],
        };
    }

    public function canMoveTo(self $to): bool
    {
        return in_array($to, $this->next(), true);
    }

    /** Statuses that still occupy the doctor's time slot. */
    public static function blocking(): array
    {
        return [self::Scheduled->value, self::Confirmed->value, self::CheckedIn->value, self::InProgress->value, self::Completed->value];
    }
}

/*
SYSTEM DESIGN:
Finite State Machine (FSM) for the visit lifecycle. The service layer calls canMoveTo() so illegal jumps (e.g. Completed -> Scheduled) are impossible.

DATA STRUCTURE:
Directed graph stored as a match() adjacency list: status -> list of allowed next statuses. Lookup O(1) + in_array over <=4 items.
*/
