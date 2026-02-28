<?php

namespace App\Services;

use App\Actions\CreateTicketAction;
use App\Actions\DeleteTicketAction;
use App\Actions\UpdateTicketAction;
use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketService
{
    public function __construct(
        private readonly CreateTicketAction $createTicketAction,
        private readonly UpdateTicketAction $updateTicketAction,
        private readonly DeleteTicketAction $deleteTicketAction,
    ) {
    }

    public function paginate(Project $project, int $perPage = 15): LengthAwarePaginator
    {
        return Ticket::query()
            ->with(['project', 'assignee', 'reporter'])
            ->where('project_id', $project->id)
            ->where('is_deleted', false)
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Project $project, array $data, string $reporterId): Ticket
    {
        return $this->createTicketAction->execute([
            ...$data,
            'project_id' => $project->id,
            'reporter_id' => $reporterId,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        return $this->updateTicketAction->execute($ticket, $data);
    }

    public function delete(Ticket $ticket): Ticket
    {
        return $this->deleteTicketAction->execute($ticket);
    }
}
