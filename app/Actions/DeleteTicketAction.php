<?php

namespace App\Actions;

use App\Models\Ticket;

class DeleteTicketAction
{
    public function execute(Ticket $ticket): Ticket
    {
        $ticket->is_deleted = true;
        $ticket->deleted_at = now();
        $ticket->save();

        return $ticket->refresh();
    }
}
