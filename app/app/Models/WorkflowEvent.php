<?php

namespace App\Models;

use App\Enums\DraftStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pathology_id', 'user_id', 'user_role', 'action',
    'previous_status', 'next_status', 'note',
])]
class WorkflowEvent extends Model
{
    public const UPDATED_AT = null;

    public function pathology(): BelongsTo
    {
        return $this->belongsTo(Pathology::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'draft_saved' => 'Bozza salvata',
            'submit' => 'Invio in validazione',
            'validate' => 'Validazione completata',
            'return_validation' => 'Rinvio dal validatore',
            'authorize' => 'Autorizzazione completata',
            'return_authorization' => 'Rinvio dal Direttore UOC',
            'publish' => 'Scheda pubblicata',
            'withdraw' => 'Scheda ritirata',
            default => str_replace('_', ' ', ucfirst($this->action)),
        };
    }

    protected function casts(): array
    {
        return [
            'user_role' => UserRole::class,
            'previous_status' => DraftStatus::class,
            'next_status' => DraftStatus::class,
        ];
    }
}
