<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AnnouncementStatus: string implements HasColor, HasLabel
{
    case IN_PROGRESS = 'in_progress';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return __("telegram.announcement_state.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'warning',
            self::SENT => 'success',
            self::FAILED => 'error',
        };
    }
}
