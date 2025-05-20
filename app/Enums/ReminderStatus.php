<?php

namespace App\Enums;

enum ReminderStatus: int
{
    case COMPLETED = 1;
    case PENDING = 2;
    case CANCELLED = 3;

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Completed',
            self::PENDING => 'Pending',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::COMPLETED => 'success',
            self::PENDING => 'warning',
            self::CANCELLED => 'danger',
        };
    }
}
