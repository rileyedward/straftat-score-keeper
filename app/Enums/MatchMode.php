<?php

namespace App\Enums;

enum MatchMode: string
{
    case OneVOne = '1v1';
    case TwoVTwo = '2v2';
    case Ffa = 'ffa';

    public function label(): string
    {
        return match ($this) {
            self::OneVOne => '1v1',
            self::TwoVTwo => '2v2',
            self::Ffa => 'FFA',
        };
    }

    public function isTeam(): bool
    {
        return $this === self::TwoVTwo;
    }

    public function isFfa(): bool
    {
        return $this === self::Ffa;
    }
}
