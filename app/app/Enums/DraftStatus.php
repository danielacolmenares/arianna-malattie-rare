<?php

namespace App\Enums;

enum DraftStatus: string
{
    case Draft = 'draft';
    case InValidation = 'in_validation';
    case AwaitingAuthorization = 'awaiting_authorization';
    case Authorized = 'authorized';
    case Published = 'published';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bozza',
            self::InValidation => 'In validazione',
            self::AwaitingAuthorization => 'Da autorizzare',
            self::Authorized => 'Autorizzata',
            self::Published => 'Pubblicata',
            self::Withdrawn => 'Ritirata',
        };
    }
}
