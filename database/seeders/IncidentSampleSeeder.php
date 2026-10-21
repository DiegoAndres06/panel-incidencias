<?php

namespace Database\Seeders;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Services\IncidentService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * Incidencias de ejemplo con un historial coherente, creadas a través de
 * `IncidentService`, igual que las crearía la aplicación. Los datos son ficticios.
 *
 * Para que el historial tenga fechas escalonadas se fija la hora "actual" de cada paso
 * y se restaura al terminar.
 */
class IncidentSampleSeeder extends Seeder
{
    public function __construct(private readonly IncidentService $service) {}

    public function run(): void
    {
        $now = Date::now();

        try {
            $this->seedHistories($now);
        } finally {
            Date::setTestNow();
        }
    }

    private function seedHistories(CarbonInterface $now): void
    {
        // Abierta, sin movimiento.
        $this->create($now, 3, 'La VPN corporativa rechaza las conexiones', IncidentPriority::High, 'Marta Ruiz');

        // En revisión, con un comentario.
        $report = $this->create($now, 6, 'El informe mensual de ventas no se exporta', IncidentPriority::Medium, 'Jorge Salas', 'Carlos Peña');
        $this->move($now, 5, $report, IncidentStatus::InReview, 'Carlos Peña');
        $this->comment($now, 4, $report, 'Carlos Peña', 'Reproducido: falla solo con rangos de más de tres meses.');

        // Bloqueada, con el motivo del bloqueo.
        $payments = $this->create($now, 9, 'La pasarela de pagos devuelve errores intermitentes', IncidentPriority::Critical, 'Elena Navarro', 'Laura Gómez');
        $this->move($now, 8, $payments, IncidentStatus::InReview, 'Laura Gómez');
        $this->move($now, 7, $payments, IncidentStatus::Blocked, 'Laura Gómez', 'A la espera de respuesta del proveedor.');
        $this->comment($now, 6, $payments, 'Elena Navarro', 'El proveedor confirma que investigan el incidente.');

        // Resuelta, historial completo.
        $certificate = $this->create($now, 12, 'Renovar el certificado del portal interno', IncidentPriority::Low, 'Pablo Ortega', 'Carlos Peña');
        $this->move($now, 11, $certificate, IncidentStatus::InReview, 'Carlos Peña');
        $this->move($now, 10, $certificate, IncidentStatus::Resolved, 'Carlos Peña');

        // Reabierta tras resolverse: vuelve a estar en revisión.
        $mail = $this->create($now, 14, 'El correo saliente llega con retraso', IncidentPriority::Medium, 'Sofía Marín', 'Laura Gómez');
        $this->move($now, 13, $mail, IncidentStatus::InReview, 'Laura Gómez');
        $this->move($now, 12, $mail, IncidentStatus::Resolved, 'Laura Gómez');
        $this->move($now, 2, $mail, IncidentStatus::InReview, 'Sofía Marín', 'Vuelve a ocurrir tras el último despliegue.');

        // Resuelta tras un bloqueo y un desbloqueo.
        $storage = $this->create($now, 20, 'Migrar el almacenamiento compartido', IncidentPriority::High, 'Andrés Cano', 'Carlos Peña');
        $this->move($now, 19, $storage, IncidentStatus::InReview, 'Carlos Peña');
        $this->move($now, 18, $storage, IncidentStatus::Blocked, 'Carlos Peña', 'Falta espacio en el nuevo servidor.');
        $this->move($now, 15, $storage, IncidentStatus::InReview, 'Carlos Peña', 'Se amplió el espacio.');
        $this->move($now, 13, $storage, IncidentStatus::Resolved, 'Carlos Peña');

        // Abierta y reciente, con dos comentarios.
        $keyboard = $this->create($now, 1, 'Teclado defectuoso en el puesto 14', IncidentPriority::Low, 'Irene Soto');
        $this->comment($now, 1, $keyboard, 'Irene Soto', 'Algunas teclas no responden.');
        $this->comment($now, 0, $keyboard, 'Marta Ruiz', 'Se pedirá uno de repuesto.');
    }

    private function travel(CarbonInterface $now, int $daysAgo): void
    {
        Date::setTestNow($now->subDays($daysAgo)->setTime(9, 0));
    }

    private function create(CarbonInterface $now, int $daysAgo, string $title, IncidentPriority $priority, string $requester, ?string $assignedTo = null): Incident
    {
        $this->travel($now, $daysAgo);

        return $this->service->create([
            'title' => $title,
            'description' => null,
            'priority' => $priority->value,
            'requester_name' => $requester,
            'assigned_to' => $assignedTo,
        ]);
    }

    private function move(CarbonInterface $now, int $daysAgo, Incident $incident, IncidentStatus $to, string $actor, ?string $note = null): void
    {
        $this->travel($now, $daysAgo);

        $this->service->changeStatus($incident, $to, $actor, $note);
    }

    private function comment(CarbonInterface $now, int $daysAgo, Incident $incident, string $author, string $body): void
    {
        $this->travel($now, $daysAgo);

        $this->service->addComment($incident, $author, $body);
    }
}
