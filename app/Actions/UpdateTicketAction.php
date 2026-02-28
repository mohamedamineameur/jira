<?php

namespace App\Actions;

use App\Models\Ticket;

class UpdateTicketAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Ticket $ticket, array $data): Ticket
    {
        $ticket->fill($data);
        $ticket->save();

        return $ticket->refresh();
    }
}
