<?php

namespace App\Enums;

enum CategoryType: int
{
    case INCOME = 1;
    case EXPENSE = 2;

    public function label(): string
    {
        return match ($this) {
            self::INCOME => 'Income',
            self::EXPENSE => 'Expense',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INCOME => 'green',
            self::EXPENSE => 'red',
        };
    }
}
