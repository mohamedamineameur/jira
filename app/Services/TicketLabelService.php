<?php

namespace App\Services;

use App\Actions\CreateTicketLabelAction;
use App\Actions\DeleteTicketLabelAction;
use App\Actions\UpdateTicketLabelAction;
use App\Models\Label;
use App\Models\Ticket;
use App\Models\TicketLabel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketLabelService
{
    public function __construct(
        private readonly CreateTicketLabelAction $createTicketLabelAction,
        private readonly UpdateTicketLabelAction $updateTicketLabelAction,
        private readonly DeleteTicketLabelAction $deleteTicketLabelAction,
    ) {
    }

    public function paginate(Ticket $ticket, int $perPage = 15): LengthAwarePaginator
    {
        return TicketLabel::query()
            ->with(['ticket', 'label'])
            ->where('ticket_id', $ticket->id)
            ->where('is_deleted', false)
            ->paginate($perPage);
    }

    public function create(Ticket $ticket, Label $label): TicketLabel
    {
        $existing = $this->find($ticket, $label, withDeleted: true);

        if ($existing) {
            return $this->updateTicketLabelAction->execute($existing, [
                'is_deleted' => false,
                'deleted_at' => null,
            ]);
        }

        return $this->createTicketLabelAction->execute([
            'ticket_id' => $ticket->id,
            'label_id' => $label->id,
        ]);
    }

    public function delete(TicketLabel $ticketLabel): TicketLabel
    {
        return $this->deleteTicketLabelAction->execute($ticketLabel);
    }

    public function find(Ticket $ticket, Label $label, bool $withDeleted = false): ?TicketLabel
    {
        $query = TicketLabel::query()
            ->where('ticket_id', $ticket->id)
            ->where('label_id', $label->id);

        if (! $withDeleted) {
            $query->where('is_deleted', false);
        }

        return $query->first();
    }
}
