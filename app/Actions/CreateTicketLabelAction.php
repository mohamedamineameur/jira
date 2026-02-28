<?php

namespace App\Actions;

use App\Models\TicketLabel;

class CreateTicketLabelAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): TicketLabel
    {
        return TicketLabel::query()->create($data)->refresh();
    }
}
