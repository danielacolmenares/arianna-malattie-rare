<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Editor = 'editor';
    case Validator = 'validator';
    case Director = 'director';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Amministratore',
            self::Editor => 'Redattore',
            self::Validator => 'Validatore',
            self::Director => 'Direttore UOC',
        };
    }

    public function can(string $permission): bool
    {
        if ($this === self::Administrator) {
            return true;
        }

        return match ($permission) {
            'edit-drafts' => $this === self::Editor,
            'validate-content' => $this === self::Validator,
            'authorize-content' => $this === self::Director,
            'manage-users' => false,
            default => false,
        };
    }
}
