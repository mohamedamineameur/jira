<?php

namespace App\Actions;

use App\Models\TicketLabel;

class DeleteTicketLabelAction
{
    public function execute(TicketLabel $ticketLabel): TicketLabel
    {
        $ticketLabel->is_deleted = true;
        $ticketLabel->deleted_at = now();
        $ticketLabel->save();

        return $ticketLabel->refresh();
    }
}
