<?php

namespace App\Actions;

use App\Models\TicketLabel;

class UpdateTicketLabelAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(TicketLabel $ticketLabel, array $data): TicketLabel
    {
        $ticketLabel->fill($data);
        $ticketLabel->save();

        return $ticketLabel->refresh();
    }
}
