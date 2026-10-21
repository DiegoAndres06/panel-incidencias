<?php

namespace App\Models;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Support\IncidentFilters;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property IncidentStatus $status
 * @property IncidentPriority $priority
 * @property string $requester_name
 * @property string|null $assigned_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'description', 'status', 'priority', 'requester_name', 'assigned_to'])]
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory;

    /**
     * Columnas de texto en las que busca el filtro de texto libre.
     *
     * @var array<int, string>
     */
    private const SEARCHABLE_COLUMNS = ['title', 'description', 'requester_name', 'assigned_to'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
            'priority' => IncidentPriority::class,
        ];
    }

    /**
     * Registros de trazabilidad de la incidencia.
     *
     * @return HasMany<IncidentLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(IncidentLog::class);
    }

    /**
     * Comentarios operativos de seguimiento.
     *
     * @return HasMany<IncidentComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class);
    }

    /**
     * Cuántas incidencias hay en cada estado, con los cuatro estados siempre presentes
     * (los vacíos valen 0). Respeta todos los filtros salvo el de estado: así, al filtrar
     * por un estado se sigue viendo cuántas hay en los demás.
     *
     * @return array<string, int>
     */
    public static function countsByStatus(IncidentFilters $filters): array
    {
        $counts = static::query()
            ->filtered($filters->withoutStatus())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $result = [];

        foreach (IncidentStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    /**
     * Aplica todos los filtros del listado.
     *
     * @param  Builder<Incident>  $query
     */
    public function scopeFiltered(Builder $query, IncidentFilters $filters): void
    {
        $query->ofStatus($filters->status)
            ->ofPriority($filters->priority)
            ->search($filters->search)
            ->ofAssignee($filters->assignee)
            ->ofRequester($filters->requester);
    }

    /**
     * Filtra por estado. Sin estado no filtra.
     *
     * @param  Builder<Incident>  $query
     */
    public function scopeOfStatus(Builder $query, ?IncidentStatus $status): void
    {
        if ($status !== null) {
            $query->where('status', $status->value);
        }
    }

    /**
     * Filtra por prioridad. Sin prioridad no filtra.
     *
     * @param  Builder<Incident>  $query
     */
    public function scopeOfPriority(Builder $query, ?IncidentPriority $priority): void
    {
        if ($priority !== null) {
            $query->where('priority', $priority->value);
        }
    }

    /**
     * Filtra por responsable; con `IncidentFilters::UNASSIGNED`, solo las que no tienen.
     * Sin valor no filtra.
     *
     * @param  Builder<Incident>  $query
     */
    public function scopeOfAssignee(Builder $query, ?string $assignee): void
    {
        if ($assignee === null) {
            return;
        }

        if ($assignee === IncidentFilters::UNASSIGNED) {
            $query->whereNull('assigned_to');

            return;
        }

        $query->where('assigned_to', $assignee);
    }

    /**
     * Filtra por solicitante. Sin valor no filtra.
     *
     * @param  Builder<Incident>  $query
     */
    public function scopeOfRequester(Builder $query, ?string $requester): void
    {
        if ($requester !== null) {
            $query->where('requester_name', $requester);
        }
    }

    /**
     * Búsqueda de texto libre en título, descripción, solicitante y responsable.
     *
     * Los comodines `%` y `_` del texto buscado se escapan para que se traten como
     * caracteres normales. Se usa `!` como carácter de escape porque, a diferencia de
     * la barra invertida, funciona igual en MySQL y en SQLite.
     *
     * @param  Builder<Incident>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || $term === '') {
            return;
        }

        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $query->where(function (Builder $group) use ($pattern) {
            foreach (self::SEARCHABLE_COLUMNS as $column) {
                $group->orWhereRaw("{$column} like ? escape '!'", [$pattern]);
            }
        });
    }
}
