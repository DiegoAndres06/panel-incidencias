<?php

namespace App\Models;

use App\Enums\IncidentLogAction;
use Database\Factories\IncidentLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Registro de trazabilidad: solo se inserta, nunca se modifica.
 *
 * @property int $id
 * @property int $incident_id
 * @property IncidentLogAction $action
 * @property string|null $old_value
 * @property string|null $new_value
 * @property string|null $user_name
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
#[Fillable(['action', 'old_value', 'new_value', 'user_name', 'metadata'])]
class IncidentLog extends Model
{
    /** @use HasFactory<IncidentLogFactory> */
    use HasFactory;

    // La tabla no tiene updated_at.
    const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => IncidentLogAction::class,
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Incident, $this>
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Solo los registros que dejan la incidencia en un estado (creación, cambio de
     * estado y reapertura). Excluye los comentarios.
     *
     * @param  Builder<IncidentLog>  $query
     */
    public function scopeAffectingStatus(Builder $query): void
    {
        $query->whereIn('action', IncidentLogAction::statusActionValues());
    }

    /**
     * Del más reciente al más antiguo; si dos tienen la misma fecha, gana el de mayor id.
     *
     * @param  Builder<IncidentLog>  $query
     */
    public function scopeNewestFirst(Builder $query): void
    {
        $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
