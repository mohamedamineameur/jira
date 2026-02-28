<?php

namespace App\Actions;

use App\Models\Ticket;

class CreateTicketAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Ticket
    {
        return Ticket::query()->create($data)->refresh();
    }
}
